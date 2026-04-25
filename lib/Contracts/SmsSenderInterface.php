<?php

namespace Otp\Contracts;

interface SmsSenderInterface
{
    /**
     * Отправить SMS
     */
    public function send(string $phone, string $message): bool;
}