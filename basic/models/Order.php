<?php

namespace app\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%order}}".
 *
 * @property integer $id
 * @property integer $organization_id
 * @property integer $tender_id
 * @property integer $current_status_id
 * @property integer $period_execution
 * @property integer $cn_workers
 * @property integer $mobilization
 * @property float $warranty_time_material
 * @property float $warranty_time_work
 * @property string $guarantee_deduction_percent
 * @property string $payment_term
 * @property bool $is_contract
 * @property bool $charity_fund
 * @property bool $agree_advance_return_conditions
 * @property bool $estimate_includes_all_costs
 * @property bool $estimate_includes_certify
 * @property bool $estimate_includes_lifting
 * @property bool $estimate_includes_supervision
 * @property bool $executor_full_responsibility
 * @property bool $tech_doc_required
 * @property bool $construction_confirm
 *
 * @property Organization $organization
 * @property OrderItem[] $orderItems
 * @property OrderItem[] $orderItemsWithParentsTender
 * @property Status $currentStatus
 */
class Order extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%order}}';
    }

    public function getOrganization(): ActiveQuery
    {
        return $this->hasOne(Organization::class, ['id' => 'organization_id']);
    }

    public function getOrderLots(): ActiveQuery
    {
        return $this->hasMany(OrderLot::class, ['order_id' => 'id']);
    }

    public function getOrderItems(): ActiveQuery
    {
        return $this->hasMany(OrderItem::class, ['order_id' => 'id'])
            ->innerJoinWith(['tenderItem'])
            ->andWhere([TenderItem::tableName() . '.is_position' => 1]);
    }

    public function getOrderItemsWithParentsTender(): ActiveQuery
    {
        return $this->hasMany(OrderItem::class, ['order_id' => 'id'])
            ->innerJoinWith(['tenderItem'])
            ->indexBy('tender_item_id')
            ->orderBy('tender_item.lft');
    }

    public function getCurrentStatus(): ActiveQuery
    {
        return $this->hasOne(Status::class, ['id' => 'current_status_id']);
    }
}
