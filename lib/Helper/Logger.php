<?php

namespace Otp\Helper;

/**
 * Logger::write('Текст');
 * Logger::write(['id' => 15], 'OTP');
 * Logger::write($e, 'ERROR', $_SERVER['DOCUMENT_ROOT'].'/upload/logs/error.log');
 */

class Logger
{

    /**
     * Запись в лог
     *
     * @param mixed       $message Любой тип данных
     * @param string      $title   Заголовок
     * @param string|null $file    Полный путь или путь от DOCUMENT_ROOT
     */
    public static function write(
        $message,
        string $title = 'LOG',
        ?string $file = null
    ): void {
        $file = self::resolvePath($file);

        self::ensureDirectory(dirname($file));

        $date = date('Y-m-d H:i:s');

        $body = self::normalize($message);

        $content =
            '[' . $date . '] ' .
            '[' . $title . ']' . PHP_EOL .
            $body . PHP_EOL .
            str_repeat('-', 80) .
            PHP_EOL;

        file_put_contents(
            $file,
            $content,
            FILE_APPEND | LOCK_EX
        );
    }

    /**
     * Преобразование любого типа в строку
     */
    private static function normalize($message): string
    {
        if ($message instanceof \Throwable) {
            return
                'Type: ' . get_class($message) . PHP_EOL .
                'Message: ' . $message->getMessage() . PHP_EOL .
                'File: ' . $message->getFile() . ':' . $message->getLine() . PHP_EOL .
                'Trace:' . PHP_EOL .
                $message->getTraceAsString();
        }

        if (is_bool($message)) {
            return $message ? 'true' : 'false';
        }

        if (is_scalar($message) || $message === null) {
            return (string)$message;
        }

        return print_r($message, true);
    }

    /**
     * Подготовка пути
     */
    private static function resolvePath(?string $file): string
    {
        if (!$file) {
            $file = '/upload/logs/otp.log';
        }

        if (mb_strpos($file, $_SERVER['DOCUMENT_ROOT']) === 0) {
            return $file;
        }

        return rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $file;        
    }

    /**
     * Создать директорию, если нет
     */
    private static function ensureDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }
}