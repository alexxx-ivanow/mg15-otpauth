<?php

use Bitrix\Main\Loader;
use Otp\OtpService;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

header('Content-Type: application/json; charset=UTF-8');

Loader::includeModule('mg15.otpauth');

$action = trim($_POST['action'] ?? '');
$login  = trim($_POST['login'] ?? '');
$code   = trim($_POST['code'] ?? '');
$config   = $_POST['config'] ?? [];

if (!$action) {
    jsonResponse([
        'success' => false,
        'message' => 'No action'
    ]);
}

/**
 * =========================
 * SEND CODE
 * =========================
 */
if ($action === 'send') {

    $result = OtpService::send($login, $config);

    jsonResponse($result);
}

/**
 * =========================
 * CHECK CODE
 * =========================
 */
if ($action === 'check') {

    $result = Otp\OtpService::check($login, $code, $config);

    jsonResponse($result);
}

/**
 * =========================
 * UNKNOWN ACTION
 * =========================
 */
jsonResponse([
    'success' => false,
    'message' => 'Unknown action'
]);

function jsonResponse(array $data): void
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    die();
}