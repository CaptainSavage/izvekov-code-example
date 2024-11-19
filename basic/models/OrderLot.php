<?php

namespace app\models;

/**
 * This is the model class for table "{{%order_lot}}".
 *
 * @property integer $id ID
 * @property integer $order_id ID заявки
 * @property integer $lot_id ID лота
 * @property bool $is_verified Признак подтверждения заказчиком
 */
class OrderLot extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%order_lot}}';
    }
}