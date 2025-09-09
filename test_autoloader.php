<?php

/**
 * 自动加载器测试脚本
 * 
 * 用于测试自动加载器的功能是否正常
 */

// 加载自动加载器
require_once __DIR__ . '/app/Core/Autoloader.php';

// 初始化自动加载器
\App\Core\Autoloader::initApp();

echo "=== 自动加载器测试 ===\n\n";

// 测试基本类加载
try {
    echo "1. 测试Container类加载: ";
    $container = \App\Core\Container::getInstance();
    echo "✓ 成功\n";
} catch (\Throwable $e) {
    echo "✗ 失败: " . $e->getMessage() . "\n";
}

// 测试Request类加载
try {
    echo "2. 测试Request类加载: ";
    $request = new \App\Core\Http\Request();
    echo "✓ 成功\n";
} catch (\Throwable $e) {
    echo "✗ 失败: " . $e->getMessage() . "\n";
}

// 测试控制器类加载
try {
    echo "3. 测试RiskController类加载: ";
    $controllerClass = \App\Modules\Risk\Controllers\RiskController::class;
    if (class_exists($controllerClass)) {
        echo "✓ 成功\n";
    } else {
        echo "✗ 失败: 类不存在\n";
    }
} catch (\Throwable $e) {
    echo "✗ 失败: " . $e->getMessage() . "\n";
}

// 测试服务类加载
try {
    echo "4. 测试RiskService类加载: ";
    $serviceClass = \App\Modules\Risk\Services\RiskService::class;
    if (class_exists($serviceClass)) {
        echo "✓ 成功\n";
    } else {
        echo "✗ 失败: 类不存在\n";
    }
} catch (\Throwable $e) {
    echo "✗ 失败: " . $e->getMessage() . "\n";
}

// 显示已注册的命名空间
echo "\n5. 已注册的命名空间前缀:\n";
$prefixes = \App\Core\Autoloader::getPrefixes();
foreach ($prefixes as $namespace => $paths) {
    echo "   {$namespace} => " . implode(', ', $paths) . "\n";
}

echo "\n=== 测试完成 ===\n";
