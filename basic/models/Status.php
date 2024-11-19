<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%status}}".
 *
 * @property integer $id
 * @property string $name Название
 * @property string $description
 */
class Status extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%status}}';
    }
}
