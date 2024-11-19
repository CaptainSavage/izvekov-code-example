<?php

namespace app\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%order_item}}".
 *
 * @property integer $id
 * @property integer $order_id
 * @property integer $tender_item_id
 * @property float $price_per_item
 * @property float $price_per_item_work
 *
 * @property Order $order
 * @property TenderItem $tenderItem
 */
class OrderItem extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%order_item}}';
    }

    public function getOrder(): ActiveQuery
    {
        return $this->hasOne(Order::class, ['id' => 'order_id']);
    }

    public function getTenderItem(): ActiveQuery
    {
        return $this->hasOne(TenderItem::class, ['id' => 'tender_item_id']);
    }
}
