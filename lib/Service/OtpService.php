<?php

namespace Otp\Service;

use Bitrix\Main\UserTable;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Context;
use Bitrix\Main\Config\Option;
use Otp\Model\OtpTable;
use Otp\Contracts\SmsSenderInterface;
use Otp\Sms\LogSmsSender;
use Otp\Helper\Logger;
use CUser;

class OtpService
{
    private static $max_attempts;    
    private static $code_ttl_minutes;    
    private static $cooldown_seconds;    
    private static $add_group = '';   
    private static SmsSenderInterface $smsSender; 

    public function __construct(?SmsSenderInterface $smsSender = null)
    {
        self::$smsSender = $smsSender ?: new LogSmsSender();      

        self::$max_attempts = (int) Option::get('mg15.otpauth', 'max_attempts', 3);
        self::$code_ttl_minutes = (int) Option::get('mg15.otpauth', 'code_ttl_minutes', 5);
        self::$cooldown_seconds = (int) Option::get('mg15.otpauth', 'timeout', 30);
    }

    // отправка проверочного кода
    public static function send(string $login, array $config = []): array
    {
        $login = self::normalizeLogin($login);

        $type  = self::detectType($login);

        if (!$type) {
            return self::error('Введите корректный телефон или email');
        }
    
        self::applyConfig($config);

        if (self::isCooldown($login)) {
            return self::error('Повторная отправка через ' . self::$cooldown_seconds . ' секунд');
        }

        $code = self::generateCode();        

        $send_result = self::sendCode($login, $code, $type);

        if(!$send_result && $type === 'phone') {
            return self::error('Ошибка отправки SMS. Попробуйте позже.');
        }

        OtpTable::add([
            'LOGIN' => $login,
            'CODE' => $code,
            'TYPE' => $type,
            'CREATED_AT' => new DateTime(),
            'EXPIRE_AT' => (new DateTime())->add(self::$code_ttl_minutes . ' minutes'),
            'ATTEMPTS' => 0,
            'IS_USED' => 'N',
            'IP' => Context::getCurrent()->getRequest()->getRemoteAddress()
        ]);

        if($type === 'email') {
            return self::success('Код отправлен на Email');
        }            

        if((new \ReflectionClass(self::$smsSender))->getShortName() === 'LogSmsSender') {
            return self::success('Тестовый режим, код записан в лог');
        }

        return self::success('Код отправлен по СМС');
    }

    // проверка кода
    public static function check(string $login, string $code, array $config = []): array
    {

        self::applyConfig($config);

        $login = self::normalizeLogin($login);

        if (!$code) {
            return self::error('Введите код');
        }

        $row = OtpTable::getList([
            'filter' => [
                '=LOGIN' => $login,
                '=TYPE'  => self::detectType($login),
                '=IS_USED' => 'N'
            ],
            'order' => ['ID' => 'DESC'],
            'limit' => 1
        ])->fetch();

        if (!$row) {
            return self::error('Код не найден');
        }

        if ($row['EXPIRE_AT']->getTimestamp() < time()) {
            self::expire($row['ID']);
            return self::error('Срок кода истёк');
        }

        if ($row['ATTEMPTS'] >= self::$max_attempts) {        
            self::expire($row['ID']);
            return self::error('Превышен лимит попыток');
        }

        if ($row['CODE'] !== $code) {
            self::increaseAttempts($row['ID'], $row['ATTEMPTS']);            
            $left = self::$max_attempts - ($row['ATTEMPTS'] + 1);

            return self::error("Неверный код. Осталось попыток: {$left}");
        }

        self::markUsed($row['ID']);

        $userId = self::getOrCreateUser($login);

        global $USER;
        $USER->Authorize($userId);

        return self::success('Успешный вход', ['reload' => true]);
    }


    // применяем конфиг из настроек компонента
    private static function applyConfig(array $config = []): void
    {               
        if (!empty($config['add_group'])) {
            self::$add_group = (string)$config['add_group'];
        }        
    }

    // авторизация/регистрация пользователя
    private static function getOrCreateUser(string $login): int
    {
        $type = self::detectType($login);

        if ($type === 'email') {
            $user = CUser::GetList($by='id', $order='asc', ['EMAIL'=>$login])->Fetch();
        } else {
            $user = CUser::GetList($by='id', $order='asc', ['PERSONAL_PHONE'=>$login])->Fetch();
        }

        if ($user) {
            return (int)$user['ID'];
        }

        $user = new CUser();

        $password = bin2hex(random_bytes(8));

        $fields = [
            "ACTIVE" => "Y",
            "LOGIN" => $login,
            "PASSWORD" => $password,
            "CONFIRM_PASSWORD" => $password,
        ];

        if ($type === 'email') {
            $fields['EMAIL'] = $login;
        } else {
            $fields['PERSONAL_PHONE'] = $login;        

            // если email обязателен при регистрации - добавляем фейковый
            if (Option::get('main', 'new_user_email_required', 'N') === 'Y') {
                $fields['EMAIL'] = $login . '@local.ru';
            }    
        }

        $userId = (int)$user->Add($fields);

        if(self::$add_group) {
            $groupArr = explode(',', self::$add_group);
        
            $arGroups = CUser::GetUserGroup($userId); 
            
            foreach($groupArr as $groupItem) {
                $arGroups[] = $groupItem;
            }            
            CUser::SetUserGroup($userId, $arGroups); 
        }

        return $userId;         
    }

    // пауза перед повторной отправкой кода
    private static function isCooldown(string $login): bool
    {
        $row = OtpTable::getList([
            'filter' => [
                '=LOGIN' => $login,
                '>=CREATED_AT' => (new DateTime())->add('-' . self::$cooldown_seconds . ' seconds')
            ],
            'order' => ['ID' => 'DESC'],
            'limit' => 1
        ])->fetch();

        return (bool)$row;
    }

    // генерация кода
    private static function generateCode(): string
    {
        return (string)random_int(100000, 999999);
    }


    // отправка кода
    private static function sendCode(string $login, string $code, string $type): bool
    {
        if ($type === 'email') {
            \CEvent::Send("OTP_CODE", SITE_ID, [
                "EMAIL" => $login,
                "CODE" => $code
            ]);
            return true;
        }        

        try {

            $ok = self::$smsSender->send(
                $login,
                'Ваш код: ' . $code
            );

            if (!$ok) {
                throw new \RuntimeException(
                    'SMS provider returned error'
                );
            }

            return true;

        } catch (\Throwable $e) {

            Logger::write(
                'Телефон: ' . $login . ', ошибка: ' . $e->getMessage(),
                'Ошибка отправки SMS', 
                '/local/modules/mg15.otpauth/log/OtpService.log'
            );

            return false;
        }
    }

    private static function increaseAttempts(int $id, int $current): void
    {
        OtpTable::update($id, [
            'ATTEMPTS' => $current + 1
        ]);
    }

    private static function markUsed(int $id): void
    {
        OtpTable::update($id, [
            'IS_USED' => 'Y'
        ]);
    }

    private static function expire(int $id): void
    {
        self::markUsed($id);
    }

    // helpers
    private static function detectType(string $value): string | null
    {
        $value = trim($value);

        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return 'email';
        }

        $digits = preg_replace('/\D+/', '', $value);

        if (strlen($digits) >= 10) {
            return 'phone';
        }

        return null;
    }

    private static function normalizeLogin(string $login): string
    {
        $login = trim($login);

        if (!$login) {
            return self::error('Введите телефон или email');
        }

        if (self::detectType($login) === 'phone') {
            return preg_replace('/\D+/', '', $login);
        }

        return $login;
    }

    private static function success(string $message, array $extra = []): array
    {
        return array_merge([
            'success' => true,
            'message' => $message
        ], $extra);
    }

    private static function error(string $message): array
    {
        return [
            'success' => false,
            'message' => $message
        ];
    }
}