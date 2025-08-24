<?php

namespace App\Core\Utils;

/**
 * 日志工具类
 *
 * 提供应用内简单的日志记录方法（INFO/WARNING/ERROR/DEBUG）。
 * 所有对外抛出的参数异常消息均已中文化。
 */
class Logger {
    private static string $logPath;
    private static array $levels = ['INFO', 'WARNING', 'ERROR', 'DEBUG'];
    
    /**
     * 初始化日志路径
     */
    public static function init(string $path = null): void {
        self::$logPath = $path ?? __DIR__ . '/../../../logs/app.log';
    }
    
    /**
     * 写入日志
     */
    private static function write(string $level, string $message, array $context = []): void {
        if (!in_array($level, self::$levels)) {
            throw new \InvalidArgumentException('无效的日志级别');
        }
        
        if (!isset(self::$logPath)) {
            self::init();
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        $logMessage = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;
        
        error_log($logMessage, 3, self::$logPath);
    }
    
    /**
     * 记录信息日志
     */
    public static function info(string $message, array $context = []): void {
        self::write('INFO', $message, $context);
    }
    
    /**
     * 记录警告日志
     */
    public static function warning(string $message, array $context = []): void {
        self::write('WARNING', $message, $context);
    }
    
    /**
     * 记录错误日志
     */
    public static function error(string $message, array $context = []): void {
        self::write('ERROR', $message, $context);
    }
    
    /**
     * 记录调试日志
     */
    public static function debug(string $message, array $context = []): void {
        self::write('DEBUG', $message, $context);
    }
}
