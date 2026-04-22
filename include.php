<?php

spl_autoload_register(function ($class) {
    if (!str_starts_with($class, "Otp")) {
        return;
    }

    $path = str_replace("Otp", "", $class);
    $path = str_replace("\\", "/", $path);

    $file = __DIR__ . "/lib/" . $path . ".php";

    if (file_exists($file)) {
        require_once $file;
    }
});