<?php

namespace Otp\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;

class OtpTable extends DataManager
{
    public static function getTableName()
    {
        return "b_otp_codes";
    }

    public static function getMap()
    {
        return [
            new Fields\IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true
            ]),
            new Fields\StringField('LOGIN'),
            new Fields\StringField('CODE'),
            new Fields\StringField('TYPE'),
            new Fields\DatetimeField('CREATED_AT'),
            new Fields\DatetimeField('EXPIRE_AT'),
            new Fields\IntegerField('ATTEMPTS'),
            new Fields\StringField('IS_USED'),
            new Fields\StringField('IP'),
        ];
    }
}