<?

namespace Otp;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SiteTable;
use Bitrix\Main\Mail\Internal\EventTypeTable;
use Bitrix\Main\Mail\Internal\EventMessageTable;

Loc::loadMessages(__FILE__);

class Events
{
    public static function InstallEvents()
    {
        $eventTypes = [
            [
                'EVENT_NAME' => 'OTP_CODE',
                'NAME' => 'Отправка проверочного кода при авторизации',
                'DESCRIPTION' => '',
                'LID' => SITE_ID,
                'SORT' => 100,
            ]
        ];

        foreach ($eventTypes as $eventType) {
            self::createEventType($eventType);
        }

        return true;
    }

    private static function createEventType(array $fields)
    {
        try {
            $result = EventTypeTable::add($fields);
            return $result->isSuccess();
        } catch (\Exception $e) {
            // Логирование ошибки
            return false;
        }
    }

    public static function InstallTemplates()
    {
        // получаем ID сайтов
        $sites = SiteTable::getList([
            'select' => ['LID'],
            'filter' => ['ACTIVE' => 'Y']
        ])->fetchAll();

        $lids = [];
        foreach ($sites as $site) {
            $lids[] = $site['LID'];
        }

        $templates = [
            [
                "ACTIVE" => "Y",
                "EVENT_NAME" => "OTP_CODE",
                "LID" => $lids,
                "EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
                "EMAIL_TO" => '#EMAIL#',
                "SUBJECT" => "Вы запросили авторизацию на сайте #SITE_NAME#",
                "BODY_TYPE" => "html",
                "MESSAGE" => "<p>Проверочный код: <b>#CODE#</b></p>",
            ]
        ];

        foreach ($templates as $template) {
            self::createEventTemplate($template);
        }

        return true;
    }

    private static function createEventTemplate(array $fields)
    {
        try {
            $eventMessage = new \CEventMessage;
            $id = $eventMessage->Add($fields);
            return $id;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function UnInstallEvents()
    {
        $eventNames = [
            "OTP_CODE"
        ];

        foreach ($eventNames as $eventName) {
            self::deleteEventType($eventName);
        }

        return true;
    }

    private static function deleteEventType(string $eventName)
    {
        try {
            $eventType = EventTypeTable::getList([
                'filter' => ['EVENT_NAME' => $eventName]
            ])->fetch();

            if ($eventType) {
                EventTypeTable::delete($eventType['ID']);
            }

            // Удаляем шаблоны
            $templates = EventMessageTable::getList([
                'filter' => ['EVENT_NAME' => $eventName]
            ])->fetchAll();

            foreach ($templates as $template) {
                EventMessageTable::delete($template['ID']);
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
?>