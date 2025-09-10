<?php
/**
 * 应用程序入口文件
 * 
 * 这是整个应用程序的入口点，负责：
 * - 加载核心类文件（自动加载器和引导程序）
 * - 初始化应用程序
 * - 启动Web应用程序
 * - 处理致命错误和异常
 * 
 * @author 风险管理系统开发组
 * @version 1.0
 * @since 2025-09-10
 */

// 设置错误报告级别（生产环境应设置为0）
error_reporting(E_ALL);
ini_set('display_errors', '1');

// 设置默认时区
date_default_timezone_set('Asia/Shanghai');

try {
    // 定义应用程序根目录常量
    define('APP_ROOT', __DIR__ . '/..');
    define('APP_DIR', APP_ROOT . '/app');
    
    // 首先手动加载自动加载器和引导类
    $autoloaderPath = APP_DIR . '/Core/Autoloader.php';
    $bootstrapPath = APP_DIR . '/Core/Bootstrap.php';
    
    if (!file_exists($autoloaderPath)) {
        throw new RuntimeException('自动加载器文件不存在：' . $autoloaderPath);
    }
    
    if (!file_exists($bootstrapPath)) {
        throw new RuntimeException('引导程序文件不存在：' . $bootstrapPath);
    }
    
    require_once $autoloaderPath;
    require_once $bootstrapPath;

    // 初始化并运行应用程序
    $application = new \App\Core\Bootstrap(APP_DIR);
    $application->init()->run();
    
} catch (Throwable $exception) {
    // 处理应用程序启动过程中的致命错误
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    
    $errorResponse = [
        'success' => false,
        'error' => '应用程序启动失败',
        'message' => '系统遇到了一个无法恢复的错误，请稍后再试或联系系统管理员'
    ];
    
    // 在开发环境显示详细错误信息
    if (ini_get('display_errors')) {
        $errorResponse['debug'] = [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];
    }
    
    // 记录错误到系统日志
    error_log(sprintf(
        '[%s] 应用启动失败: %s in %s:%d',
        date('Y-m-d H:i:s'),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine()
    ));
    
    echo json_encode($errorResponse, JSON_UNESCAPED_UNICODE);
    exit(1);
}

