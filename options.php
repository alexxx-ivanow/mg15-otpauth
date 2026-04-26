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