<?php

namespace app\models\printService\requests;

use yii\base\Model;

class CompareOrderShortRequest extends Model
{
    public $tender_id;
    public $orders;

    public function rules()
    {
        return array_merge(parent::rules(), [
            [['tender_id',], 'required'],
            [['tender_id'], 'integer'],
            [['orders'], 'each', 'rule' => ['integer']],
        ]);
    }
}