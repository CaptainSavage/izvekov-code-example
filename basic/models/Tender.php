<?php

namespace app\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%tender}}".
 *
 * @property integer $id
 * @property string $name
 *
 * @property DictProject $project
 */

class Tender extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%tender}}';
    }

    public function getProject()
    {
        return $this->hasOne(DictProject::class, ['id' => 'project_id']);
    }

    public function getTenderItems(): ActiveQuery
    {
        return $this->hasMany(TenderItem::class, ['tender_id' => 'id']);
    }

    public function getTenderLots(): ActiveQuery
    {
        return $this->hasMany(TenderLot::class, ['tender_id' => 'id']);
    }
}

