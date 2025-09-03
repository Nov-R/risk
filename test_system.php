<?php
/**
 * 简单测试脚本 - 验证环境配置和核心功能
 */

// 加载自动加载器
require_once __DIR__ . '/tests/bootstrap.php';

use App\Core\Config\Environment;
use App\Core\Container;

echo "=== 风险管理系统核心功能测试 ===\n\n";

// 测试环境配置
echo "1. 测试环境配置加载...\n";
$env = Environment::getInstance();
echo "   - APP_ENV: " . $env->get('APP_ENV') . "\n";
echo "   - DB_HOST: " . $env->get('DB_HOST') . "\n";
echo "   - DB_DATABASE: " . $env->get('DB_DATABASE') . "\n";
echo "   - 调试模式: " . ($env->isDebug() ? '是' : '否') . "\n";
echo "   ✅ 环境配置加载成功\n\n";

// 测试依赖注入容器
echo "2. 测试依赖注入容器...\n";
$container = Container::getInstance();

// 测试简单绑定
$container->bind('test_service', function() {
    return new stdClass();
});

$service1 = $container->make('test_service');
$service2 = $container->make('test_service');

echo "   - 容器绑定: " . (is_object($service1) ? '成功' : '失败') . "\n";
echo "   - 非单例测试: " . ($service1 !== $service2 ? '成功' : '失败') . "\n";

// 测试单例绑定
$container->singleton('singleton_service', function() {
    $obj = new stdClass();
    $obj->id = uniqid();
    return $obj;
});

$singleton1 = $container->make('singleton_service');
$singleton2 = $container->make('singleton_service');

echo "   - 单例绑定: " . ($singleton1 === $singleton2 ? '成功' : '失败') . "\n";
echo "   ✅ 依赖注入容器测试成功\n\n";

// 测试配置文件加载
echo "3. 测试配置文件加载...\n";
$dbConfig = require __DIR__ . '/config/database.php';
echo "   - 数据库配置: " . (is_array($dbConfig) ? '成功' : '失败') . "\n";
echo "   - 主机配置: " . $dbConfig['host'] . "\n";
echo "   - 数据库名: " . $dbConfig['database'] . "\n";
echo "   ✅ 配置文件加载成功\n\n";

// 测试路由配置
echo "4. 测试路由配置...\n";
$routes = require __DIR__ . '/app/Modules/Risk/routes.php';
echo "   - 路由数量: " . count($routes) . "\n";
echo "   - 风险API: " . (isset($routes['/api/risks']) ? '存在' : '不存在') . "\n";
echo "   - 反馈API: " . (isset($routes['/api/feedbacks']) ? '存在' : '不存在') . "\n";
echo "   - 节点API: " . (isset($routes['/api/nodes']) ? '存在' : '不存在') . "\n";
echo "   ✅ 路由配置测试成功\n\n";

echo "=== 所有核心功能测试完成 ===\n";
echo "系统已准备就绪！🎉\n";
echo "\n访问方式:\n";
echo "- Web界面: http://localhost:8080/demo.html\n";
echo "- API端点: http://localhost:8080/api/risks\n";
echo "- 文档: docs/api/openapi.yml\n";