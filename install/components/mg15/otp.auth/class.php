<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;

class OtpAuthComponent extends CBitrixComponent
{

    public function executeComponent()
    {

        global $USER;

        // если пользователь уже авторизован — редирект
        if ($USER->IsAuthorized())
        {
            $redirect = $this->arParams['REDIRECT_AUTH'] ?? '/';

            LocalRedirect($redirect);
            return;
        }
        
        $this->includeComponentTemplate();
    }
}