<?php

namespace app\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%organization}}".
 *
 * @property integer $id
 * @property string $name_full
 * @property string $name_short
 * @property bool $is_sro
 *
 * @property TicketOrg[] $tickets
 * @property TicketOrg $lastTicket
 */
class Organization extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%organization}}';
    }

    /**
     * Связанные запросы на изменение
     */
    public function getTickets(): ActiveQuery
    {
        return $this->hasMany(TicketOrg::class, ['org_id' => 'id']);
    }

    public function getLastTicket(): ActiveRecord
    {
        return $this->getTickets()
            ->orderBy(['id' => SORT_DESC])
            ->one();
    }
}
