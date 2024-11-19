<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%dict_project}}".
 *
 * @property integer $id
 * @property string $name_full
 */
class DictProject extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%dict_project}}';
    }
}