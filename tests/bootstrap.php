<?php

/**
 * PHPUnit 测试引导文件
 * 
 * 在运行测试之前初始化必要的环境和配置
 */

// 配置自动加载器
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// 为测试添加 Tests 命名空间的自动加载
spl_autoload_register(function ($class) {
    $prefix = 'Tests\\';
    $baseDir = __DIR__ . '/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// 设置测试环境变量
putenv('APP_ENV=testing');
putenv('APP_DEBUG=true');
putenv('DB_DATABASE=risk_management_test');

// 初始化环境配置
\App\Core\Config\Environment::getInstance();

// 配置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);