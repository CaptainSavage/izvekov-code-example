<?php

namespace app\models\printService\handlers;

use app\models\Order;
use app\models\OrderStatus;
use app\models\printService\Task;
use app\models\Status;
use app\models\Tender;
use app\models\TenderItem;
use app\models\TenderLot;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yii;
use yii\web\NotFoundHttpException;

class CompareOrderShortHandler
{
    /**
     * @throws Exception
     * @throws NotFoundHttpException
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function run(Task $task): string
    {
        $taskProperty = $task->getProperty();

        $data = self::prepareData($taskProperty);
        if ($data['hasLots']) {
            $excelPath = $this->makeExcelWithLost($data);
        } else {
            $excelPath = $this->makeExcel($data);
        }

        return $excelPath;
    }

    /**
     * @throws NotFoundHttpException
     */
    private static function prepareData($taskProperty): array
    {
        $tenderId = $taskProperty['tender_id'];
        $tender = Tender::findOne($tenderId);
        if (is_null($tender)) {
            throw new NotFoundHttpException("Тендер id: $tenderId не найден!");
        }

        $orderIds = $taskProperty['orders'];

        /** @var TenderLot[] $lots */
        $lots = $tender->getTenderLots()->where(['is_active' => 1])->indexBy('id')->all();
        $hasLots = (bool)$lots;

        $orders = [];
        $orderQuery = Order::find()
            ->joinWith(['currentStatus', 'orderItems', 'organization'])
            ->where([
                Order::tableName() . '.id' => $orderIds,
                Order::tableName() . '.tender_id' => $tender->id,
            ])
            ->andWhere([
                'NOT IN',
                Status::tableName() . '.action_status_id',
                [OrderStatus::ACTION_STATUS_DRAFT, OrderStatus::ACTION_STATUS_REJECTED]
            ])
            ->orderBy('price_nds ASC');

        $tenderItems = [];
        $tenderItemsQuery = $tender->getTenderItems()
            // Сортировка позиций такая же как на веб-позициях тендера
            ->orderBy(['tree' => SORT_ASC, 'lft' => SORT_ASC])
            ->indexBy('id');

        if ($hasLots) {
            foreach ($lots as $lotId => $lot) {
                $tenderLotItemsQuery = clone $tenderItemsQuery;
                $tenderItems[$lotId] = $tenderLotItemsQuery
                    ->andWhere(['tender_lot_id' => $lotId])
                    ->all();

                $orderLotQuery = clone $orderQuery;
                $orders[$lotId] = $orderLotQuery->joinWith('orderLots')
                    ->andWhere(['lot_id' => $lotId])
                    ->all();
            }
        } else {
            $orders = $orderQuery->all();
            $tenderItems = $tenderItemsQuery->all();
        }

        return [
            'tender' => $tender,
            'tenderItems' => $tenderItems,
            'orders' => $orders,
            'lots' => $lots,
            'hasLots' => $hasLots,
        ];
    }

    /**
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws NotFoundHttpException
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    private function makeExcelWithLost(array $data): string
    {
        $templateName = 'compare-order-short';
        $filePath = Yii::getAlias('@app/templates/') . $templateName . '.xlsx';

        $reader = IOFactory::createReader("Xlsx");
        $templateSpreadsheet = $reader->load($filePath);
        $templateSheet = $templateSpreadsheet->getActiveSheet();

        $spreadsheet = new Spreadsheet();

        // Копируем стили с шаблона на листы по каждому лоту
        /** @var TenderLot[] $lots */
        $lots = $data['lots'];
        foreach ($lots as $lot) {
            $clonedWorksheet = clone $templateSheet;
            $clonedWorksheet->setTitle(mb_substr($lot->name, 0, 23));
            $templateSpreadsheet->addSheet($clonedWorksheet);

            $spreadsheet->addExternalSheet($clonedWorksheet);
        }

        // Убираем первый лист, который создается автоматом при создании Spreadsheet, т.к. он без стилей из шаблона
        $spreadsheet->removeSheetByIndex(0);

        // Заполняем листы информацией по заявкам
        /** @var Tender $tender */
        $tender = $data['tender'];
        $sheetIndex = 0;
        foreach ($lots as $lotId => $lot) {
            /** @var TenderItem[] $tenderItems */
            /** @var Order[] $orders */
            $tenderItems = $data['tenderItems'][$lotId];
            $orders = $data['orders'][$lotId];

            $sheet = $spreadsheet->getSheet($sheetIndex);
            $this->fillSheet($sheet, $tender, $tenderItems, $orders);

            $sheetIndex++;
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $this->saveExcel($tender->id, $spreadsheet);
    }

    /**
     * @throws Exception
     * @throws NotFoundHttpException
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    private function makeExcel($data): string
    {
        $templateName = 'compare-order-short';
        $filePath = Yii::getAlias('@app/pf-templates/') . $templateName . '.xlsx';

        $reader = IOFactory::createReader("Xlsx");
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        /** @var Tender $tender */
        /** @var TenderItem[] $tenderItems */
        /** @var Order[] $orders */
        $tender = $data['tender'];
        $tenderItems = $data['tenderItems'];
        $orders = $data['orders'];
        $this->fillSheet($sheet, $tender, $tenderItems, $orders);

        return $this->saveExcel($tender->id, $spreadsheet);
    }

    /**
     * Заполнение листа информацией по заявкам
     *
     * @param Worksheet $sheet
     * @param Tender $tender
     * @param TenderItem[] $tenderItems
     * @param Order[] $orders
     * @return void
     * @throws Exception
     */
    private function fillSheet(Worksheet $sheet, Tender $tender, array $tenderItems, array $orders): void
    {
        $cnOrders = count($orders);
        // для первой заявки стили заполнены в шаблоне, дублируем их на последующие заявки, если они есть
        if ($cnOrders > 1) {
            $this->duplicateStyles($sheet, $cnOrders);
        }

        // Заполнение верхней части
        $sheet->getCell('A2')->setValue($tender->project->name_full);
        $sheet->getCell('A3')->setValue($tender->name);

        // Вставляем пустые строки под категории и их названия в таблицу стоимостей
        if ($tenderItems) {
            $row = 8;
            foreach ($tenderItems as $tenderItem) {
                if (!$tenderItem->is_position) {
                    $sheet->insertNewRowBefore($row + 1);
                    $sheet->getCell('A' . $row)->setValue($tenderItem->item_name);
                    $row++;
                }
            }

            // Удаляем пустую строку, в которой хранили стили в шаблоне
            $sheet->removeRow($row);
        }

        // Заполнение данных по заявкам
        $firstColumn = 'B';
        $middleColumn = 'C';
        $lastColumn = 'D';
        foreach ($orders as $order) {
            $sheet->getCell($firstColumn . 5)->setValue($order->organization->name_short);

            // Таблица стоимостей по категориям
            $totalSum = 0;
            $workSum = 0;
            $materialSum = 0;

            $orderItems = $order->orderItemsWithParentsTender;

            $row = 8;
            foreach ($tenderItems as $tenderItem) {
                if (!$tenderItem->is_position) {
                    /** @var TenderItem[] $categoryDescendants */
                    $categoryDescendants = $tenderItem->getDescendants()
                        ->andWhere(['is_position' => 1])
                        ->all();
                    $categorySum = TenderItem::getSumByPositions($categoryDescendants, $orderItems);
                    $sheet->getCell($lastColumn . $row)->setValue($categorySum);

                    $row++;
                }
            }
            $workSum += TenderItem::getWorkSumByPositions($tenderItems, $orderItems);
            $materialSum += TenderItem::getMaterialSumByPositions($tenderItems, $orderItems);
            $totalSum += TenderItem::getSumByPositions($tenderItems, $orderItems);

            $sheet->getCell($firstColumn . 7)->setValue($materialSum);
            $sheet->getCell($middleColumn . 7)->setValue($workSum);
            $sheet->getCell($lastColumn . 7)->setValue($totalSum);

            // Прочие условия
            $row += 3;
            $sheet->getCell($firstColumn . $row++)->setValue($order->payment_term);
            $sheet->getCell($firstColumn . $row++)->setValue($order->agree_advance_return_conditions ? 'Да' : 'Нет');
            $sheet->getCell($firstColumn . $row++)->setValue($order->guarantee_deduction_percent ? 'Учтено' : '');
            $sheet->getCell($firstColumn . $row++)->setValue($order->period_execution);
            $sheet->getCell($firstColumn . $row++)->setValue($order->construction_confirm ? 'Да' : 'Нет');
            $sheet->getCell($firstColumn . $row++)->setValue($order->mobilization);
            $sheet->getCell($firstColumn . $row++)->setValue($order->cn_workers);
            $sheet->getCell($firstColumn . $row++)->setValue($order->warranty_time_work);
            $sheet->getCell($firstColumn . $row++)->setValue($order->warranty_time_material);
            $sheet->getCell($firstColumn . $row++)->setValue($order->is_contract ? 'Да' : 'Нет');
            $sheet->getCell($firstColumn . $row++)->setValue($order->charity_fund ? 'Да' : 'Нет');
            $sheet->getCell($firstColumn . $row++)->setValue($order->organization->is_sro);
            $sheet->getCell($firstColumn . $row++)->setValue($order->estimate_includes_all_costs ? 'Да' : 'Нет');
            $sheet->getCell($firstColumn . $row++)->setValue($order->estimate_includes_lifting ? 'Да' : 'Нет');
            $sheet->getCell($firstColumn . $row++)->setValue($order->executor_full_responsibility ? 'Да' : 'Нет');
            $sheet->getCell($firstColumn . $row++)->setValue($order->tech_doc_required ? 'Да' : 'Нет');
            $sheet->getCell($firstColumn . $row++)->setValue($order->estimate_includes_certify ? 'Да' : 'Нет');

            $lastTicket = $order->organization->lastTicket;
            if ($lastTicket) {
                $sheet->getCell($firstColumn . $row++)->setValue($lastTicket->fio_contact_person);
                $sheet->getCell($firstColumn . $row++)->setValue($lastTicket->phone_contact_person);
                $sheet->getCell($firstColumn . $row)->setValue($lastTicket->email_contact_person);
            }

            for ($i = 0; $i < 4; $i++) {
                $firstColumn++;
                $middleColumn++;
                $lastColumn++;
            }
        }
    }

    /**
     * Дублирование стилей с первой заявки на последующие
     *
     * @param Worksheet $sheet
     * @param int $cnOrders
     * @return void
     * @throws Exception
     */
    private function duplicateStyles(Worksheet $sheet, int $cnOrders): void
    {
        $referenceColumn = 'B';

        $firstColumn = [
            'index' => 'F',
            'headValue' => $sheet->getCell('B6')->getValue(),
            'width' => $sheet->getColumnDimension('B')->getWidth(),
        ];
        $middleColumn = [
            'index' => 'G',
            'headValue' => $sheet->getCell('C6')->getValue(),
            'width' => $sheet->getColumnDimension('C')->getWidth(),
        ];
        $lastColumn = [
            'index' => 'H',
            'headValue' => $sheet->getCell('D6')->getValue(),
            'width' => $sheet->getColumnDimension('D')->getWidth(),
        ];
        $dividingColumn = [
            'index' => 'I',
            'width' => $sheet->getColumnDimension('E')->getWidth(),
        ];

        for ($i = 1; $i < $cnOrders; $i++) {
            // Ширина столбцов
            $sheet->getColumnDimension($firstColumn['index'])->setWidth($firstColumn['width']);
            $sheet->getColumnDimension($middleColumn['index'])->setWidth($middleColumn['width']);
            $sheet->getColumnDimension($lastColumn['index'])->setWidth($lastColumn['width']);
            $sheet->getColumnDimension($dividingColumn['index'])->setWidth($dividingColumn['width']);

            // Заголовки на 6-й строке
            $sheet->getCell($firstColumn['index'] . 6)->setValue($firstColumn['headValue']);
            $sheet->getCell($middleColumn['index'] . 6)->setValue($middleColumn['headValue']);
            $sheet->getCell($lastColumn['index'] . 6)->setValue($lastColumn['headValue']);

            // Стили по всем строкам (шрифт, цвет текста, заливка)
            for ($row = 5; $row <= 31; $row++) {
                $style = $sheet->getStyle($referenceColumn . $row);
                $range = $firstColumn['index'] . $row . ':' . $lastColumn['index'] . $row;
                $sheet->duplicateStyle($style, $range);
            }

            // Объединение ячеек
            $sheet->mergeCells($firstColumn['index'] . 5 . ':' . $lastColumn['index'] . 5);
            for ($row = 10; $row <= 31; $row++) {
                $sheet->mergeCells($firstColumn['index'] . $row . ':' . $lastColumn['index'] . $row);
            }

            for ($j = 0; $j <= 3; $j++) {
                $firstColumn['index']++;
                $middleColumn['index']++;
                $lastColumn['index']++;
                $dividingColumn['index']++;
            }
        }
    }

    /**
     * Сохранение файла
     *
     * @param integer $tenderId
     * @param Spreadsheet $spreadsheet
     * @return string
     * @throws NotFoundHttpException
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    private function saveExcel(int $tenderId, Spreadsheet $spreadsheet): string
    {
        $fileName = 'compare-order-short-' . $tenderId . '.xlsx';
        $filePath = Yii::getAlias('@runtime') . '/pf/' . $fileName;

        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Cache-Control: max-age=0');

        $writer->save($filePath);

        if (file_exists($filePath)) {
            return $filePath;
        }

        throw new NotFoundHttpException('File not found!');
    }
}