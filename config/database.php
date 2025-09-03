<?php
/**
 * 数据库配置文件
 * 
 * 此配置文件定义了数据库连接所需的所有参数：
 * - driver: 数据库类型（mysql, pgsql, sqlite, oci 等）
 * - host: 数据库服务器地址
 * - database: 数据库名称
 * - username: 数据库用户名
 * - password: 数据库密码
 * - charset: 字符集（针对MySQL）
 * - options: PDO连接选项
 */

use App\Core\Config\Environment;

$env = Environment::getInstance();

return [
    'host' => $env->get('DB_HOST', 'localhost'),
    'database' => $env->get('DB_DATABASE', 'risk_management'),
    'username' => $env->get('DB_USERNAME', 'root'),
    'password' => $env->get('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ],
];
