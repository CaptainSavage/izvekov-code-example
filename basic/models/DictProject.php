<?php

namespace app\models;

use yii\db\ActiveRecord;

class DictProject extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%dict_project}}';
    }
}