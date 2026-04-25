<?php

namespace Otp\Factory;

use Otp\Contracts\SmsSenderInterface;
use Otp\Sms\LogSmsSender;
use Otp\Helper\Logger;

class SmsSenderFactory
{
    public static function make(string $provider, array $config = []): SmsSenderInterface
    {
        //$provider = str_replace(' ', '', ucwords(
        $class = str_replace(' ', '', ucwords(
            str_replace(['_', '-'], ' ', $provider)
        ));

        //$class = '\\Otp\\Sms\\' . $provider . 'Sender';

        if (
            class_exists($class)
            && is_subclass_of($class, SmsSenderInterface::class)
        ) {

            try {
                return new $class($config);
            } catch (\InvalidArgumentException $e) {        

                Logger::write(
                    $e->getMessage(),
                    'Ошибка инициализации класса ' . $class, 
                    '/local/modules/mg15.otpauth/log/SmsSenderFactory.log'
                );        

                return new LogSmsSender();
            }

        }

        return new LogSmsSender();
    }
}