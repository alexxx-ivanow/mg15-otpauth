<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

$moduleId = 'mg15.otpauth';

Loc::loadMessages(__FILE__);

if (!$USER->IsAdmin()) {
    return;
}

/**
 * Получить список SMS провайдеров
 */
function getSmsProviders(): array
{
    $result = [];

    $dir = $_SERVER['DOCUMENT_ROOT'] .
        '/local/modules/mg15.otpauth/lib/Sms/';

    foreach (glob($dir . '*Sender.php') as $file) {

        $name = basename($file, '.php');

        $class = '\\Otp\\Sms\\' . $name;

        $result[$class] = $name;
    }

    return $result;
}

/**
 * Сохранение
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && check_bitrix_sessid()
) {
    Option::set(
        $moduleId,
        'sms_provider_class',
        $_POST['sms_provider_class'] ?? ''
    );

    Option::set(
        $moduleId,
        'api_key',
        $_POST['api_key'] ?? ''
    );

    Option::set(
        $moduleId,
        'api_login',
        $_POST['api_login'] ?? ''
    );

    Option::set(
        $moduleId,
        'api_pass',
        $_POST['api_pass'] ?? ''
    );

    Option::set(
        $moduleId,
        'timeout',
        $_POST['timeout'] ?? ''
    );

    Option::set(
        $moduleId,
        'max_attempts',
        $_POST['max_attempts'] ?? ''
    );

    Option::set(
        $moduleId,
        'code_ttl_minutes',
        $_POST['code_ttl_minutes'] ?? ''
    );

    Option::set($moduleId, 'email_sender',   $_POST['email_sender']   ?? 'cevent');
    Option::set($moduleId, 'smtp_host',      $_POST['smtp_host']      ?? '');
    Option::set($moduleId, 'smtp_port',      $_POST['smtp_port']      ?? '587');
    Option::set($moduleId, 'smtp_user',      $_POST['smtp_user']      ?? '');
    Option::set($moduleId, 'smtp_pass',      $_POST['smtp_pass']      ?? '');
    Option::set($moduleId, 'smtp_from',      $_POST['smtp_from']      ?? '');
    Option::set($moduleId, 'smtp_from_name', $_POST['smtp_from_name'] ?? '');
    Option::set($moduleId, 'smtp_secure',    $_POST['smtp_secure']    ?? 'tls');

}

$providers = getSmsProviders();

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

$timeout = Option::get(
    $moduleId,
    'timeout',
    ''
);

$max_attempts = Option::get(
    $moduleId,
    'max_attempts',
    ''
);

$code_ttl_minutes = Option::get(
    $moduleId,
    'code_ttl_minutes',
    ''
);

$smtpHost     = Option::get($moduleId, 'smtp_host',      '');
$smtpPort     = Option::get($moduleId, 'smtp_port',      '587');
$smtpUser     = Option::get($moduleId, 'smtp_user',      '');
$smtpPass     = Option::get($moduleId, 'smtp_pass',      '');
$smtpFrom     = Option::get($moduleId, 'smtp_from',      '');
$smtpFromName = Option::get($moduleId, 'smtp_from_name', '');
$smtpSecure   = Option::get($moduleId, 'smtp_secure',    'tls');
$emailSender  = Option::get($moduleId, 'email_sender',   'cevent');


$aTabs = [
    [
        'DIV'   => 'edit1',
        'TAB'   => 'Настройки',
        'TITLE' => 'Настройки модуля OTP-авторизации'
    ]
];

$tabControl = new CAdminTabControl(
    'tabControl',
    $aTabs
);

$tabControl->Begin();
?>

<form method="post" action="<?= $APPLICATION->GetCurPage()?>?mid=<?=$moduleId?>&lang=<?=LANG?>">
<?php
$tabControl->BeginNextTab();
?>

<tr class="heading">
    <td colspan="2">SMS шлюз</td>
</tr>

<tr>
    <td width="40%">
        SMS-провайдер:
    </td>
    <td width="60%">
        <select name="sms_provider_class" style="width:250px;">

            <?php foreach ($providers as $class => $title): ?>

                <option
                    value="<?=htmlspecialcharsbx($class)?>"
                    <?= $class === $currentProvider ? 'selected' : '' ?>
                >
                    <?=htmlspecialcharsbx($title)?>
                </option>

            <?php endforeach; ?>

        </select>
    </td>
</tr>

<tr>
    <td>
        Токен:
    </td>
    <td>
        <input
            type="text"
            name="api_key"
            value="<?=htmlspecialcharsbx($apiKey)?>"
            size="30"
        >
    </td>
</tr>

<tr>
    <td>
        Логин:
    </td>
    <td>
        <input
            type="text"
            name="api_login"
            value="<?=htmlspecialcharsbx($apiLogin)?>"
            size="30"
        >
    </td>
</tr>

<tr>
    <td>
        Пароль:
    </td>
    <td>
        <input
            type="text"
            name="api_pass"
            value="<?=htmlspecialcharsbx($apiPass)?>"
            size="30"
        >
    </td>
</tr>

<tr class="heading">
    <td colspan="2">Защита</td>
</tr>

<tr>
    <td>
        Задержка отправки следующего проверочного кода (в сек), по умолчанию - 30:
    </td>
    <td>
        <input
            type="text"
            name="timeout"
            value="<?=htmlspecialcharsbx($timeout)?>"
            size="30"
        >
    </td>
</tr>

<tr>
    <td>
        Максимальное количество попыток ввода проверочного кода, по умолчанию - 3:
    </td>
    <td>
        <input
            type="text"
            name="max_attempts"
            value="<?=htmlspecialcharsbx($max_attempts)?>"
            size="30"
        >
    </td>
</tr>

<tr>
    <td>
        Время жизни проверочного кода (в мин), по умолчанию - 5:
    </td>
    <td>
        <input
            type="text"
            name="code_ttl_minutes"
            value="<?=htmlspecialcharsbx($code_ttl_minutes)?>"
            size="30"
        >
    </td>
</tr>



<tr class="heading">
    <td colspan="2">Email</td>
</tr>

<tr>
    <td width="40%">Метод отправки Email:</td>
    <td width="60%">
        <label style="margin-right:20px;">
            <input type="radio" name="email_sender" value="cevent"
                <?= $emailSender === 'cevent' ? 'checked' : '' ?>>
            Через очередь \CEvent::Send (стандартный)
        </label>
        <label>
            <input type="radio" name="email_sender" value="phpmailer"
                <?= $emailSender === 'phpmailer' ? 'checked' : '' ?>>
            Мгновенно через PHPMailer (SMTP)
        </label>
    </td>
</tr>

<tr id="smtp-settings" style="<?= $emailSender === 'phpmailer' ? '' : 'display:none' ?>">
    <td colspan="2">
        <table width="100%" id="smtp-inner">
            <tr>
                <td width="40%">SMTP-сервер:</td>
                <td><input type="text" name="smtp_host" value="<?=htmlspecialcharsbx($smtpHost)?>" size="30"></td>
            </tr>
            <tr>
                <td>SMTP-порт (по умолч. 587):</td>
                <td><input type="text" name="smtp_port" value="<?=htmlspecialcharsbx($smtpPort)?>" size="10"></td>
            </tr>
            <tr>
                <td>Шифрование:</td>
                <td>
                    <select name="smtp_secure">
                        <option value="tls"  <?= $smtpSecure === 'tls'  ? 'selected' : '' ?>>TLS (STARTTLS, порт 587)</option>
                        <option value="ssl"  <?= $smtpSecure === 'ssl'  ? 'selected' : '' ?>>SSL (порт 465)</option>
                        <option value=""     <?= $smtpSecure === ''     ? 'selected' : '' ?>>Без шифрования</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>Логин SMTP:</td>
                <td><input type="text" name="smtp_user" value="<?=htmlspecialcharsbx($smtpUser)?>" size="30"></td>
            </tr>
            <tr>
                <td>Пароль SMTP:</td>
                <td><input type="password" name="smtp_pass" value="<?=htmlspecialcharsbx($smtpPass)?>" size="30"></td>
            </tr>
            <tr>
                <td>Email отправителя (From):</td>
                <td><input type="text" name="smtp_from" value="<?=htmlspecialcharsbx($smtpFrom)?>" size="30"></td>
            </tr>
            <tr>
                <td>Имя отправителя:</td>
                <td><input type="text" name="smtp_from_name" value="<?=htmlspecialcharsbx($smtpFromName)?>" size="30"></td>
            </tr>
        </table>
    </td>
</tr>

<script>
(function () {
    var radios = document.querySelectorAll('input[name="email_sender"]');
    var block  = document.getElementById('smtp-settings');
    radios.forEach(function (r) {
        r.addEventListener('change', function () {
            block.style.display = (this.value === 'phpmailer') ? '' : 'none';
        });
    });
}());
</script>

<style>
    #smtp-inner tr td:first-child {
        text-align: right;
    }
</style>

<?php
$tabControl->Buttons();
?>

<input
    type="submit"
    name="save"
    value="Сохранить"
    class="adm-btn-save"
>

<?=bitrix_sessid_post();?>

<?php
$tabControl->End();
?>

</form>