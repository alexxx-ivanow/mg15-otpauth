<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;

class OtpAuthComponent extends CBitrixComponent
{

    public function executeComponent()
    {

        global $USER;

        // если пользователь авторизован — редирект
        if ($USER->IsAuthorized())
        {
            $redirect = $this->arParams['REDIRECT_AUTH'];

            if($redirect) {
                LocalRedirect($redirect);
            } else {
                $app = Application::getInstance();
                $context = $app->getContext();
                $request = $context->getRequest();
                LocalRedirect($request->getRequestUri());
            }

            return;
        }

        $this->arResult['COOLDOWN_SECONDS'] = (int) Option::get(
            'mg15.otpauth',
            'timeout',
            30
        );
        
        $this->includeComponentTemplate();
    }
}