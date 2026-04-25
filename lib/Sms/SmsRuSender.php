<?php

namespace Otp\Sms;

use Otp\Contracts\SmsSenderInterface;
use Bitrix\Main\Diag\Debug;
use Otp\Helper\Logger;

class SmsRuSender implements SmsSenderInterface
{
    private string $api_key;

    public function __construct(array $config = [])
    {

        if (empty($config['api_key'])) {
            throw new \InvalidArgumentException(
                'api_key is required'
            );
        }
        
        $this->api_key = $config['api_key'];
    }

    public function send(string $phone, string $message): bool
    {
        $ch = curl_init("https://sms.ru/sms/send");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(
            [
                "api_id" => $this->api_key,
                "to" => $phone,
                "msg" => $message,
                //"msg" => iconv("windows-1251", "utf-8", "Привет!"),            
                "json" => 1
            ]
        ));
        $body = curl_exec($ch);
        curl_close($ch);      

        /*Logger::write(
            json_decode($body, true),
            "Ответ при отправке СМС ", 
            "/local/modules/mg15.otpauth/log/SmsRuAnswer.log"
        );*/ 

        $json = json_decode($body);
        if ($json) {
            if ($json->status == "OK") {

                foreach ($json->sms as $ph => $data) { // Перебираем массив СМС сообщений
                    if ($data->status == "OK") { // Сообщение отправлено
                        return true;
                    } else { // Ошибка в отправке

                        if($phone === $ph) {
                            Logger::write(
                                "Код ошибки: $data->status_code. " . " Текст ошибки: $data->status_text. ",
                                "Ошибка в сервисе СМС, телефон " . $ph, 
                                "/local/modules/mg15.otpauth/log/ErrorSmsRuProvider.log"
                            );
                        }
                        return false;
                    }
                }
                
            }

            Logger::write(
                "Код ошибки: $json->status_code. " . " Текст ошибки: $json->status_text. ",
                "Ошибка при отправке СМС ", 
                "/local/modules/mg15.otpauth/log/ErrorSmsRuProvider.log"
            );

            return false;
        }

        Logger::write(
            "Непредвиденная ошибка при отправке СМС",
            "ERROR", 
            "/local/modules/mg15.otpauth/log/ErrorSmsRuProvider.log"
        );

        return false;
    }
}