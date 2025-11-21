<?php

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$database = getenv('DB_NAME') ?: 'carrevolutis';
$username = getenv('DB_USER') ?: 'app';
$password = getenv('DB_PASSWORD') ?: 'app';

$config = new Configuration();
$connectionParams = [
    'dbname' => $database,
    'user' => $username,
    'password' => $password,
    'host' => $host,
    'port' => (int) $port,
    'driver' => 'pdo_mysql',
    'charset' => 'utf8mb4',
];

try {
    $db = DriverManager::getConnection($connectionParams, $config);
} catch (Throwable $e) {
    error_log(sprintf('{"level":"error","message":"DB connection failed","error":"%s"}', $e->getMessage()));
    throw $e;
}
