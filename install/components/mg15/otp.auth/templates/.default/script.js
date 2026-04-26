document.addEventListener('DOMContentLoaded', function(){

    const loginWrap  = document.getElementById('js-otp-auth');
    const actionPath = loginWrap.getAttribute('data-action');

    const loginInput  = document.getElementById('js-auth-login');
    const codeInput   = document.getElementById('js-auth-code');

    const sendBtn     = document.getElementById('js-send-code-btn');
    const checkBtn    = document.getElementById('js-check-code-btn');
    const backBtn     = document.getElementById('js-back-btn');
    const nextBtn     = document.getElementById('js-next-btn');

    const hint        = document.getElementById('js-auth-hint');
    const msg         = document.getElementById('js-otp-message');

    const stepLogin   = document.getElementById('js-otp-step-login');
    const stepCode    = document.getElementById('js-otp-step-code');

    const destination = document.getElementById('js-otp-destination');
    const timeout = otpConfig.timeout || 30;


    nextBtn.style.display = 'none';

    const saved = localStorage.getItem('otp_login');

    if (saved) {
        loginInput.value = saved;
    }

    if (localStorage.getItem('otp_step') === 'code') {
        stepCode.style.display = 'block';
        stepLogin.style.display = 'none';

        if(loginInput.value !== '') {
            nextBtn.style.display = 'block';
            destination.innerHTML = 'Код отправлен на: ' + loginInput.value;
        }
    }        


    function showMessage(text, type='error')
    {
        msg.className = 'otp-message ' + (type === 'success' ? 'otp-success':'otp-error');
        msg.innerHTML = text;
    }

    function clearMessage()
    {
        msg.innerHTML = '';
        msg.className = 'otp-message';
    }

    function detectType(value)
    {
        value = value.trim();

        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (emailPattern.test(value)) {
            return 'email';
        }

        const digits = value.replace(/\D/g,'');

        if (digits.length >= 6) {
            return 'phone';
        }

        return 'unknown';
    }

    function formatPhone(value)
    {
        let digits = value.replace(/\D/g,'');

        if (!digits.length) return '';

        if (digits[0] === '8') digits = '7' + digits.slice(1);
        if (digits[0] !== '7') digits = '7' + digits;

        let result = '+7';

        if (digits.length > 1) result += ' ' + digits.substring(1,4);
        if (digits.length >= 5) result += ' ' + digits.substring(4,7);
        if (digits.length >= 8) result += '-' + digits.substring(7,9);
        if (digits.length >= 10) result += '-' + digits.substring(9,11);

        return result;
    }

    loginInput.addEventListener('input', function(){

        let value = this.value;
        let type = detectType(value);

        if (type === 'phone') {
            this.value = formatPhone(value);
            hint.innerHTML = 'Код придёт по SMS';
        } else if (type === 'email') {
            hint.innerHTML = 'Код придёт на email';
        } else {
            hint.innerHTML = '';
        }

        if(value == '') {
            nextBtn.style.display = 'none';    
        } else if(localStorage.getItem('otp_step') === 'code') {
            nextBtn.style.display = 'block';
        }
        
        
        localStorage.setItem('otp_login', this.value);
    });


    sendBtn.addEventListener('click', function(){

        clearMessage();

        const login = loginInput.value.trim();

        if (!login) {
            showMessage('Введите телефон или email');
            return;
        }

        BX.ajax({
            url: actionPath + '/ajax.php',
            method: 'POST',
            dataType: 'json',
            data: {
                sessid: BX.bitrix_sessid(),
                action: 'send',
                login: login,
                config: otpConfig
            },
            onsuccess: function(response){

                if (!response.success) {
                    showMessage(response.message);
                    return;
                }

                stepLogin.style.display = 'none';
                stepCode.style.display = 'block';

                destination.innerHTML = 'Код отправлен на: ' + login;
                codeInput.focus();

                showMessage(response.message, 'success');

                localStorage.setItem('otp_step', 'code');

                startResendTimer(timeout, sendBtn);

                const end = Date.now() + timeout * 1000;
                localStorage.setItem('otp_timer_end', end);
            }
        });

    });


    checkBtn.addEventListener('click', function(){

        clearMessage();

        BX.ajax({
            url: actionPath + '/ajax.php',
            method: 'POST',
            dataType: 'json',
            data: {
                sessid: BX.bitrix_sessid(),
                action: 'check',
                login: loginInput.value,
                code: codeInput.value,
                config: otpConfig
            },
            onsuccess: function(response){

                if (!response.success) {
                    showMessage(response.message);
                    return;
                }

                localStorage.removeItem('otp_login');
                localStorage.removeItem('otp_step');
                localStorage.removeItem('otp_timer_end');

                showMessage(response.message, 'success');

                if (response.reload) {
                    location.reload();
                }
            }
        });

    });

    // клик по кнопке Назад
    backBtn.addEventListener('click', function(){

        stepCode.style.display = 'none';
        stepLogin.style.display = 'block';

        codeInput.value = '';
        clearMessage();
    });

    // клик по кнопке Ввести код
    nextBtn.addEventListener('click', function(){

        if(loginInput.value != '') {
            stepCode.style.display = 'block';
            stepLogin.style.display = 'none';

            codeInput.value = '';
            clearMessage();
        }
        
    });   

    // проверка задержки таймера
    if (sendBtn) {       

        let seconds = 0;
        const timestamp = Number(localStorage.getItem('otp_timer_end')) || 0;

        if(timestamp > 0) {
            seconds = Math.max(0, parseInt((localStorage.getItem('otp_timer_end') - Date.now()) / 1000));                
        }

        if (seconds > 0) {
            startResendTimer(seconds, sendBtn);
        }

    } 

});

/**
 * Запуск таймера обратного отсчёта
 *
 * @param {Number} seconds       Количество секунд
 * @param {String|HTMLElement} el  Элемент таймера
 * @param {String|HTMLElement} btn Кнопка отправки
 */
function startResendTimer(seconds, btn) {
    const button  = typeof btn === 'string' ? document.querySelector(btn) : btn;

    if (!button) return;

    let timeLeft = parseInt(seconds, 10);

    if (timeLeft <= 0) {        
        button.disabled = false;
        return;
    }

    button.disabled = true;
    button.dataset.seconds = timeLeft;

    render();

    const interval = setInterval(function () {
        timeLeft--;
        button.dataset.seconds = timeLeft;

        render();

        if (timeLeft <= 0) {
            clearInterval(interval);
            btn.innerHTML = 'Получить код';
            button.disabled = false;
            button.dataset.seconds = 0;
        }
    }, 1000);

    function render() {
        btn.innerHTML = 'Повторный код через ' + timeLeft + ' сек.';
    }
}