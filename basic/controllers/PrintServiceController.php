<?php

namespace app\controllers;

use app\models\printService\handlers\CompareOrderShortHandler;
use app\models\printService\requests\CompareOrderShortRequest;
use app\models\printService\Task;
use PhpOffice\PhpSpreadsheet\Exception;
use Yii;
use yii\filters\ContentNegotiator;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class PrintServiceController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors(): array
    {
        return [
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
        ];
    }

    /**
     * Сравнительная таблица заявок КА (краткая)
     *
     * @throws NotFoundHttpException
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionCompareOrderShort()
    {
        $post = Yii::$app->request->post();
        if (empty($post)) {
            throw new \Exception("Ошибка при получении данных json");
        }

        $model = new CompareOrderShortRequest();
        $model->load($post, '');
        if (!$model->validate()) {
            $this->response->statusCode = 400;
            $this->response->data = [
                'status' => 'error',
                'message' => $model->firstErrors
            ];

            return;
        }

        $task = new Task();
        $task->setProperty(['tender_id' => $model->tender_id, 'orders' => $model->orders]);
        $task->setHandlerClassModel(CompareOrderShortHandler::class);
        $task->setAlias("CompareOrderShortExcel");
        $task->setDescription(sprintf("CompareOrderShortExcel; tenderId: %d", $model->tender_id));

        $handler = new CompareOrderShortHandler();
        $filePath = $handler->run($task);

        $this->response
            ->sendFile($filePath)
            ->on(
                Response::EVENT_AFTER_SEND,
                function ($event) {
                    unlink($event->data);
                },
                $filePath
            );
    }
}