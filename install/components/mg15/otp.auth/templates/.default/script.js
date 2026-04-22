document.addEventListener('DOMContentLoaded', function(){

    const loginWrap  = document.getElementById('otp-auth');
    const actionPath = loginWrap.getAttribute('data-action');

    const loginInput  = document.getElementById('auth-login');
    const codeInput   = document.getElementById('auth-code');

    const sendBtn     = document.getElementById('send-code-btn');
    const checkBtn    = document.getElementById('check-code-btn');
    const backBtn     = document.getElementById('back-btn');

    const hint        = document.getElementById('auth-hint');
    const msg         = document.getElementById('otp-message');

    const stepLogin   = document.getElementById('otp-step-login');
    const stepCode    = document.getElementById('otp-step-code');

    const destination = document.getElementById('otp-destination');


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

});