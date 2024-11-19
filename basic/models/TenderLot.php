<?php

namespace app\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%tbl_tender_lot}}".
 *
 * @property integer $id
 * @property integer $tender_id Тендер
 * @property string $name Название
 * @property bool $is_active
 *
 * @property Tender $tender
 */
class TenderLot extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%tbl_tender_lot}}';
    }

    public function getTender(): ActiveQuery
    {
        return $this->hasOne(Tender::class, ['id' => 'tender_id']);
    }
}
