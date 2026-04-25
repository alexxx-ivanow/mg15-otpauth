<?php

namespace Otp\Sms;

use Otp\Contracts\SmsSenderInterface;
use Otp\Helper\Logger;

class LogSmsSender implements SmsSenderInterface
{
    public function send(string $phone, string $message): bool
    {        

        Logger::write(
            'Тестовая отправка СМС на номер ' . $phone . ', сообщение: ' . $message,
            'Тестовая отправка СМС', 
            '/local/modules/mg15.otpauth/log/TestSmsService.log'
        );

        return true;
    }
}