<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); 

$this->setFrameMode(false);

CJSCore::Init(['ajax']);
?>

<div class="otp-auth" id="js-otp-auth" data-action="<?=$componentPath?>">

    <!-- Шаг 1 -->
    <div class="otp-step otp-step-login" id="js-otp-step-login">

        <div class="otp-title">
            Вход или регистрация
        </div>

        <div class="otp-row">
            <input
                type="text"
                id="js-auth-login"
                name="login"
                class="otp-input"
                placeholder="Телефон или Email"
                autocomplete="off"
            >
        </div>

        <div class="otp-hint" id="js-auth-hint"></div>

        <div class="otp-row">
            
            <button type="button" class="otp-btn" id="js-send-code-btn" data-seconds="0">
                Получить код
            </button>
        </div>

        <div class="otp-row">
            <button type="button" class="otp-btn-light" id="js-next-btn">
                Ввести код
            </button>
        </div>

    </div>


    <!-- Шаг 2 -->
    <div class="otp-step otp-step-code" id="js-otp-step-code" style="display:none;">

        <div class="otp-title">
            Введите код
        </div>

        <div class="otp-subtitle" id="js-otp-destination"></div>

        <div class="otp-row">
            <input
                type="number"
                id="js-auth-code"
                class="otp-input"
                placeholder="6 цифр"
                oninput="this.value = this.value.slice(0, 6)"
                autocomplete="one-time-code"
            >
        </div>

        <div class="otp-row">
            <button type="button" class="otp-btn" id="js-check-code-btn">
                Войти
            </button>
        </div>

        <div class="otp-row">
            <button type="button" class="otp-btn-light" id="js-back-btn">
                Назад
            </button>
        </div>

    </div>

    <div class="otp-message" id="js-otp-message"></div>

</div>


<script>
const otpConfig = {    
    add_group: '<?=CUtil::JSEscape(implode(',', $arParams["ADD_GROUP"]))?>',
    timeout: <?=(int)$arResult['COOLDOWN_SECONDS'] ?>
};
</script>