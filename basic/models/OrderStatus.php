<?php

namespace app\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%order_status}}".
 *
 * @property integer $id
 * @property integer $order_id
 * @property integer $status_id
 *
 * @property Order $order
 * @property Status $status
 */
class OrderStatus extends ActiveRecord
{
    const ACTION_STATUS_DRAFT = 1;
    const ACTION_STATUS_NEW = 2;
    const ACTION_STATUS_CONTEST = 3;
    const ACTION_STATUS_ACCEPTED = 4;
    const ACTION_STATUS_REJECTED = 5;
    const ACTION_STATUS_WINNER = 6;
    const ACTION_STATUS_RE_BARGAIN = 7;

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%order_status}}';
    }

    public function getStatus(): ActiveQuery
    {
        return $this->hasOne(Status::class, ['id' => 'status_id']);
    }

    public function getOrder(): ActiveQuery
    {
        return $this->hasOne(Order::class, ['id' => 'order_id']);
    }
}
