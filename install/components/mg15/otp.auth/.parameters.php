<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;

function getUserGroups(): array
{
    Loader::includeModule('main');

    $groups = [];

    $res = \CGroup::GetList($by = "c_sort", $order = "asc", ["ACTIVE" => "Y"]);

    while ($group = $res->Fetch()) {
        $groups[$group["ID"]] = $group["NAME"];
    }

    return $groups;
}

$arComponentParameters = [
    "PARAMETERS" => [
        "REDIRECT_AUTH" => [
            "PARENT" => "BASE",
            "NAME" => "Редирект после авторизации",
            "TYPE" => "STRING",
            "DEFAULT" => "/personal/",
        ],

        "CODE_TTL_MINUTES" => [
            "PARENT" => "BASE",
            "NAME" => "Время жизни кода (в минутах)",
            "TYPE" => "STRING",
            "DEFAULT" => "5",
        ],
       
        "COOLDOWN_SECONDS" => [
            "PARENT" => "BASE",
            "NAME" => "Интервал между повторной отправкой кода (в секундах)",
            "TYPE" => "STRING",
            "DEFAULT" => "60",
        ],
       
        "MAX_ATTEMPTS" => [
            "PARENT" => "BASE",
            "NAME" => "Количества попыток ввода проверочного кода",
            "TYPE" => "STRING",
            "DEFAULT" => "3",
        ],

        "ADD_GROUP" => [
            "PARENT" => "BASE",
            "NAME" => "Группы пользователя при регистрации",
            "TYPE" => "LIST",
            "SIZE" => "5",
            "MULTIPLE" => "Y",
            "VALUES" => getUserGroups(),
            "DEFAULT" => []
        ],        
    ]
];