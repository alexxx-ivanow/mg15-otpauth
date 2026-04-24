<?php

use Bitrix\Main\ModuleManager;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Entity\Base;
use Otp\Events;

class mg15_otpauth extends CModule
{
    public $MODULE_ID = "mg15.otpauth";
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;

    function __construct()
    {
        // создаем пустой массив для файла version.php
        $arModuleVersion = [];
        include(__DIR__ . '/version.php');

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = "OTP-авторизация";
        $this->MODULE_DESCRIPTION = "Авторизация по коду email / phone из одного поля";
        $this->PARTNER_NAME = "Mg15";
    }

    function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);

        $this->installDB();
        $this->InstallEmailEvents();
        $this->InstallUserEvents();
        $this->installFiles();
    }

    function DoUninstall()
    {
        $this->uninstallFiles();
        $this->UnInstallEmailEvents();        
        $this->UnInstallUserEvents();        
        $this->uninstallDB();

        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    // метод для установки почтовых событий и шаблонов
    function InstallEmailEvents()
    {
        Loader::includeModule($this->MODULE_ID);
        Events::InstallEvents();
        Events::InstallTemplates();
        return true;
    }

    // метод для установки события обновления пользователя
    function InstallUserEvents()
    {

        RegisterModuleDependences(
            "main",
            "OnBeforeUserUpdate",
            $this->MODULE_ID,
            Events::class,
            "UpdateUserEventsHandler"
        );     

        return true;
    }

    // метод для удаления почтовых событий и шаблонов
    function UnInstallEmailEvents()
    {
        Loader::includeModule($this->MODULE_ID);
        Events::UnInstallEvents();
        return true;
    }

    // метод для удаления события обновления пользователя
    function UnInstallUserEvents()
    {
        
        UnRegisterModuleDependences(
            "main",
            "OnBeforeUserUpdate",
            $this->MODULE_ID,
            Events::class,
            "UpdateUserEventsHandler"
        );

        return true;
    }

    function installDB()
    {
        global $DB;

        $DB->Query("
            CREATE TABLE IF NOT EXISTS b_otp_codes (
                ID INT AUTO_INCREMENT PRIMARY KEY,
                LOGIN VARCHAR(255),
                CODE VARCHAR(10),
                TYPE VARCHAR(10),
                CREATED_AT DATETIME,
                EXPIRE_AT DATETIME,
                ATTEMPTS INT DEFAULT 0,
                IS_USED CHAR(1) DEFAULT 'N',
                IP VARCHAR(45)
            )
        ");
    }

    function uninstallDB()
    {
        global $DB;

        $DB->Query("DROP TABLE IF EXISTS b_otp_codes");
    }

    function installFiles()
    {
        CopyDirFiles(
            __DIR__ . "/components/mg15/otp.auth",
            $_SERVER["DOCUMENT_ROOT"] . "/local/components/mg15/otp.auth",
            true,
            true
        );
    }
    
    function uninstallFiles()
    {
        DeleteDirFilesEx("/local/components/mg15/otp.auth");
    }
}