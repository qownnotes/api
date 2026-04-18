<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    // Symfony's bootEnv reads $_SERVER before $_ENV, but PHPUnit's <env> config only sets $_ENV.
    // Sync $_ENV values into $_SERVER so Symfony picks up PHPUnit's environment overrides.
    foreach (array_keys($_ENV) as $key) {
        $_SERVER[$key] = $_ENV[$key];
    }
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}
