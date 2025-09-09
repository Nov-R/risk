<?php
/**
 * 应用程序入口文件
 * 
 * 简化的入口点，使用Bootstrap类处理所有初始化逻辑
 */

// 首先手动加载自动加载器和引导类
require_once __DIR__ . '/../app/Core/Autoloader.php';
require_once __DIR__ . '/../app/Core/Bootstrap.php';

// 初始化并运行应用程序
$app = new \App\Core\Bootstrap(__DIR__ . '/../app');
$app->init()->run();

