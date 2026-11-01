<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

// the container's env may allow any origin, which would hide what CORS scenarios check
$_SERVER['CORS_ALLOW_ORIGIN'] = $_ENV['CORS_ALLOW_ORIGIN'] = '^https://frontend\.example$';
// tests never reach ClickHouse, even when the container has it configured
$_SERVER['COJEDZIE_CLICKHOUSE_URL'] = $_ENV['COJEDZIE_CLICKHOUSE_URL'] = '';

if (file_exists(dirname(__DIR__) . '/config/bootstrap.php')) {
    require dirname(__DIR__) . '/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    new Dotenv()->bootEnv(dirname(__DIR__) . '/.env');
}
