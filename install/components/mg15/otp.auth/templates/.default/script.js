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

                /*const end = Date.now() + 60000;
                localStorage.setItem('otp_timer_end', end);*/
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


    backBtn.addEventListener('click', function(){

        stepCode.style.display = 'none';
        stepLogin.style.display = 'block';

        codeInput.value = '';
        clearMessage();
    });

    nextBtn.addEventListener('click', function(){

        console.log(loginInput.value);

        if(loginInput.value != '') {
            stepCode.style.display = 'block';
            stepLogin.style.display = 'none';

            codeInput.value = '';
            clearMessage();
        }
        
    });

});