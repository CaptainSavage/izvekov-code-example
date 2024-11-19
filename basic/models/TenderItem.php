<?php

namespace app\models;

use paulzi\nestedsets\NestedSetsBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%tender_item}}".
 *
 * @property integer $id
 * @property integer $parent_id ID родителя
 * @property integer $tender_id Тендер
 * @property integer $position_type_id Тип позиции
 * @property integer $tender_lot_id Лот
 * @property float $count Количество
 * @property string $item_name Наименование
 * @property string $item_description Описание
 * @property bool $is_position Признак позиции
 *
 * @property Tender $tender
 * @property TenderItem $parent
 *
 * @mixin NestedSetsBehavior
 */
class TenderItem extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%tender_item}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        return [
            [
                'class' => NestedSetsBehavior::class,
                'treeAttribute' => 'tree',
            ],
        ];
    }

    public function getTender(): ActiveQuery
    {
        return $this->hasOne(Tender::class, ['id' => 'tender_id']);
    }

    private function isPositionTypeNominatedOrUnNominated(): bool
    {
        if (!$this->position_type_id) {
            return false;
        }

        return in_array($this->position_type_id, [3, 4]);
    }

    /**
     *  Расчёт суммы по позициям тендера и заявки
     *
     * @param TenderItem[] $tenderItems
     * @param OrderItem[] $positions
     *
     * @return float|int
     */
    public static function getSumByPositions(array $tenderItems, array $positions)
    {
        $result = 0;

        foreach ($tenderItems as $item) {
            if ($item->isPositionTypeNominatedOrUnNominated()) {
                continue;
            }

            if (!array_key_exists($item->id, $positions)) {
                continue;
            }

            $materialRate = $positions[$item->id]->price_per_item;
            $workRate = $positions[$item->id]->price_per_item_work;
            $cn = $item->count;

            $result += round($cn * ($materialRate + $workRate), 2);
        }

        return $result;
    }

    /**
     *  Расчёт суммы по материалам по позициям тендера и заявки
     *
     * @param TenderItem[] $tenderItems
     * @param OrderItem[] $positions
     *
     * @return float|int
     */
    public static function getMaterialSumByPositions(array $tenderItems, array $positions)
    {
        $result = 0;

        foreach ($tenderItems as $item) {
            if ($item->isPositionTypeNominatedOrUnNominated()) {
                continue;
            }

            if (!array_key_exists($item->id, $positions)) {
                continue;
            }

            $materialRate = $positions[$item->id]->price_per_item;
            $cn = $item->count;

            $result += round($cn * $materialRate, 2);
        }

        return $result;
    }

    /**
     *  Расчёт суммы по работам по позициям тендера и заявки
     *
     * @param TenderItem[] $tenderItems
     * @param OrderItem[] $positions
     *
     * @return float|int
     */
    public static function getWorkSumByPositions(array $tenderItems, array $positions)
    {
        $result = 0;

        foreach ($tenderItems as $item) {
            if ($item->isPositionTypeNominatedOrUnNominated()) {
                continue;
            }

            if (!array_key_exists($item->id, $positions)) {
                continue;
            }

            $workRate = $positions[$item->id]->price_per_item_work;
            $cn = $item->count;

            $result += round($cn * $workRate, 2);
        }

        return $result;
    }
}
