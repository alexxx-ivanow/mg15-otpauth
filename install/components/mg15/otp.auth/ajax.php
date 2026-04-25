<?php

use Bitrix\Main\Loader;
use Otp\Factory\SmsSenderFactory;
use Otp\OtpService;
use Bitrix\Main\Config\Option;

use Otp\Helper\Logger;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

header('Content-Type: application/json; charset=UTF-8');

Loader::includeModule('mg15.otpauth');

// Только POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([
        'success' => false,
        'message' => 'Подмена запроса'
    ]);
}

// Проверка сессии Bitrix (CSRF)
if (!check_bitrix_sessid()) {
    jsonResponse([
        'success' => false,
        'message' => 'Сессия истекла. Обновите страницу'
    ]);
}

$action = trim($_POST['action'] ?? '');
$login  = trim($_POST['login'] ?? '');
$code   = trim($_POST['code'] ?? '');
$config   = $_POST['config'] ?? [];

// если action не передан
if (!$action) {
    jsonResponse([
        'success' => false,
        'message' => 'No action'
    ]);
}

// отправка кода
if ($action === 'send') {

    $moduleId = 'mg15.otpauth';

    $currentProvider = Option::get(
        $moduleId,
        'sms_provider_class',
        '\\Otp\\Sms\\LogSmsSender'
    );

    $apiKey = Option::get(
        $moduleId,
        'api_key',
        ''
    );

    $apiLogin = Option::get(
        $moduleId,
        'api_login',
        ''
    );

    $apiPass = Option::get(
        $moduleId,
        'api_pass',
        ''
    );

    /*$provider = 'sms_ru'; // из настроек
    $provider_config = [
        'api_key' => '1234567'
    ];*/

    $provider = $currentProvider;

    $provider_config = [
        'api_key' => $apiKey,
        'api_login' => $apiLogin,
        'api_pass' => $apiPass,
    ];

    /*Logger::write(
        $provider_config,
        'Класс провайдера: ' . $provider, 
        '/local/modules/mg15.otpauth/log/ajax.log'
    );  */
    
    $sender = SmsSenderFactory::make($provider, $provider_config);

    $service = new OtpService($sender);

    $result = $service::send($login, $config);

    jsonResponse($result);
}

// проверка кода
if ($action === 'check') {

    $result = OtpService::check($login, $code, $config);

    jsonResponse($result);
}


// неизвестный action
jsonResponse([
    'success' => false,
    'message' => 'Попытка взлома'
]);

function jsonResponse(array $data): void
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    die();
}