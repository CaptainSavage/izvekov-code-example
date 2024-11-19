<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "ticket_org".
 *
 * @property integer $id
 * @property string $fio_contact_person
 * @property string $phone_contact_person
 * @property string $email_contact_person
 */
class TicketOrg extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return 'ticket_org';
    }
}