<?php

use Bitrix\Main\Loader;
use Otp\OtpService;

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

    $result = OtpService::send($login, $config);

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