<?php

namespace Otp\Mail;

use Bitrix\Main\Config\Option;
use Otp\Helper\Logger;

class MailSender
{
    /**
     * Загружает файлы PHPMailer из lib/Email
     */
    private static function loadPhpMailer(): void
    {
        static $loaded = false;

        if ($loaded) {
            return;
        }

        $base = dirname(__DIR__) . '/Email/';

        require_once $base . 'Exception.php';
        require_once $base . 'SMTP.php';
        require_once $base . 'PHPMailer.php';

        $loaded = true;
    }

    /**
     * Отправка письма с OTP-кодом напрямую через PHPMailer, минуя очередь b_event.
     *
     * @param string $to    Email получателя
     * @param string $code  Одноразовый код
     * @return bool         true — письмо отправлено, false — ошибка
     */
    public static function sendOtpCode(string $to, string $code): bool
    {
        self::loadPhpMailer();

        $moduleId = 'mg15.otpauth';

        $host     = Option::get($moduleId, 'smtp_host',     '');
        $port     = (int) Option::get($moduleId, 'smtp_port',     587);
        $user     = Option::get($moduleId, 'smtp_user',     '');
        $pass     = Option::get($moduleId, 'smtp_pass',     '');
        $from     = Option::get($moduleId, 'smtp_from',     $user);
        $fromName = Option::get($moduleId, 'smtp_from_name', 'Проверочный код');
        $secure   = Option::get($moduleId, 'smtp_secure',   'tls');

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            $mail->isSMTP();
            $mail->Host       = $host;
            $mail->Port       = $port;
            $mail->SMTPAuth   = ($user !== '');
            $mail->Username   = $user;
            $mail->Password   = $pass;
            $mail->SMTPSecure = $secure;

            $mail->CharSet = 'UTF-8';
            $mail->setFrom($from ?: $user, $fromName);
            $mail->addAddress($to);

            $mail->Subject = 'Ваш код подтверждения';
            $mail->isHTML(true);
            $mail->Body    = self::buildHtml($code);
            $mail->AltBody = 'Ваш код подтверждения: ' . $code;
        
            $mail->send();

            return true;

        } catch (\Exception $e) {
            Logger::write(
                'Email: ' . $to . ', ошибка: ' . $e->getMessage(),
                'Ошибка отправки Email (PHPMailer)',
                '/local/modules/mg15.otpauth/log/OtpService.log'
            );
            return false;
        }
    }

    /**
     * HTML-шаблон письма.
     */
    private static function buildHtml(string $code): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:30px;">
  <div style="max-width:420px;margin:0 auto;background:#fff;border-radius:8px;padding:32px;box-shadow:0 2px 8px rgba(0,0,0,.08);">
    <p style="font-size:16px;color:#333;margin-top:0;">Ваш код подтверждения:</p>
    <div style="font-size:36px;font-weight:bold;letter-spacing:8px;color:#1a1a1a;margin:16px 0;">{$code}</div>
    <p style="font-size:13px;color:#888;margin-bottom:0;">Код действителен несколько минут. Никому его не сообщайте.</p>
  </div>
</body>
</html>
HTML;
    }
}
