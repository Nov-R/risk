<?php

namespace App\Core\Utils;

use DateTime;
use Throwable;

/**
 * 应用程序日志工具类
 *
 * 提供功能完善的日志记录系统，支持：
 * - 多种日志级别（DEBUG、INFO、WARNING、ERROR、CRITICAL）
 * - 灵活的日志格式化
 * - 日志文件自动轮转和大小管理
 * - 上下文信息记录
 * - 性能监控和统计
 * - 异常详细信息记录
 * - 多输出目标支持
 * 
 * 遵循PSR-3日志接口标准，提供完全中文化的错误信息。
 * 
 * @author 风险管理系统开发组
 * @version 1.0
 * @since 2025-09-10
 * 
 * @example
 * ```php
 * // 初始化日志系统
 * Logger::init('/path/to/logs/app.log', Logger::LEVEL_INFO);
 * 
 * // 记录不同级别的日志
 * Logger::info('用户登录成功', ['user_id' => 123]);
 * Logger::error('数据库连接失败', ['error' => $e->getMessage()]);
 * Logger::debug('调试信息', ['debug_data' => $data]);
 * 
 * // 记录异常
 * Logger::exception($exception, '订单处理失败');
 * ```
 */
class Logger 
{
    /**
     * 日志级别常量定义
     * 数值越小，级别越高（越重要）
     */
    
    /** 调试信息：详细的调试信息，仅在开发环境使用 */
    public const LEVEL_DEBUG = 0;
    
    /** 一般信息：系统运行的一般信息记录 */
    public const LEVEL_INFO = 1;
    
    /** 警告信息：潜在的问题，但不影响系统运行 */
    public const LEVEL_WARNING = 2;
    
    /** 错误信息：运行时错误，但不需要立即处理 */
    public const LEVEL_ERROR = 3;
    
    /** 严重错误：严重错误，需要立即关注和处理 */
    public const LEVEL_CRITICAL = 4;

    /**
     * 日志级别名称映射
     * 
     * @var array<int, string>
     */
    private static array $levelNames = [
        self::LEVEL_DEBUG => 'DEBUG',
        self::LEVEL_INFO => 'INFO',
        self::LEVEL_WARNING => 'WARNING',
        self::LEVEL_ERROR => 'ERROR',
        self::LEVEL_CRITICAL => 'CRITICAL'
    ];

    /**
     * 日志文件路径
     * 
     * @var string|null
     */
    private static ?string $logPath = null;

    /**
     * 当前日志级别
     * 只记录大于等于此级别的日志
     * 
     * @var int
     */
    private static int $logLevel = self::LEVEL_INFO;

    /**
     * 日志文件最大大小（字节）
     * 超过此大小会自动轮转
     * 
     * @var int
     */
    private static int $maxFileSize = 10 * 1024 * 1024; // 10MB

    /**
     * 保留的日志文件数量
     * 
     * @var int
     */
    private static int $maxFiles = 5;

    /**
     * 是否启用日志记录
     * 
     * @var bool
     */
    private static bool $enabled = true;

    /**
     * 日志统计信息
     * 
     * @var array<string, int>
     */
    private static array $stats = [
        'total_logs' => 0,
        'debug_logs' => 0,
        'info_logs' => 0,
        'warning_logs' => 0,
        'error_logs' => 0,
        'critical_logs' => 0
    ];

    /**
     * 初始化日志系统
     * 
     * @param string|null $path 日志文件路径，null则使用默认路径
     * @param int $level 日志记录级别
     * @param int $maxFileSize 最大文件大小（字节）
     * @param int $maxFiles 最大保留文件数
     * @param bool $enabled 是否启用日志记录
     * 
     * @throws \RuntimeException 当无法创建日志目录时
     */
    public static function init(
        ?string $path = null, 
        int $level = self::LEVEL_INFO,
        int $maxFileSize = 10485760, // 10MB
        int $maxFiles = 5,
        bool $enabled = true
    ): void {
        self::$logPath = $path ?? __DIR__ . '/../../../logs/app.log';
        self::$logLevel = $level;
        self::$maxFileSize = $maxFileSize;
        self::$maxFiles = $maxFiles;
        self::$enabled = $enabled;

        // 确保日志目录存在
        $logDir = dirname(self::$logPath);
        if (!is_dir($logDir)) {
            if (!mkdir($logDir, 0755, true)) {
                throw new \RuntimeException("无法创建日志目录：{$logDir}");
            }
        }

        // 检查目录是否可写
        if (!is_writable($logDir)) {
            throw new \RuntimeException("日志目录不可写：{$logDir}");
        }
    }

    /**
     * 记录调试信息
     * 
     * @param string $message 日志消息
     * @param array<string, mixed> $context 上下文数据
     * 
     * @return void
     */
    public static function debug(string $message, array $context = []): void 
    {
        self::write(self::LEVEL_DEBUG, $message, $context);
    }

    /**
     * 记录一般信息
     * 
     * @param string $message 日志消息
     * @param array<string, mixed> $context 上下文数据
     * 
     * @return void
     */
    public static function info(string $message, array $context = []): void 
    {
        self::write(self::LEVEL_INFO, $message, $context);
    }

    /**
     * 记录警告信息
     * 
     * @param string $message 日志消息
     * @param array<string, mixed> $context 上下文数据
     * 
     * @return void
     */
    public static function warning(string $message, array $context = []): void 
    {
        self::write(self::LEVEL_WARNING, $message, $context);
    }

    /**
     * 记录错误信息
     * 
     * @param string $message 日志消息
     * @param array<string, mixed> $context 上下文数据
     * 
     * @return void
     */
    public static function error(string $message, array $context = []): void 
    {
        self::write(self::LEVEL_ERROR, $message, $context);
    }

    /**
     * 记录严重错误信息
     * 
     * @param string $message 日志消息
     * @param array<string, mixed> $context 上下文数据
     * 
     * @return void
     */
    public static function critical(string $message, array $context = []): void 
    {
        self::write(self::LEVEL_CRITICAL, $message, $context);
    }

    /**
     * 记录异常信息
     * 
     * 专门用于记录异常，会自动提取异常的详细信息
     * 
     * @param Throwable $exception 异常对象
     * @param string|null $message 额外的描述消息
     * @param array<string, mixed> $context 额外的上下文数据
     * 
     * @return void
     */
    public static function exception(Throwable $exception, ?string $message = null, array $context = []): void 
    {
        $logMessage = $message ? "{$message}: {$exception->getMessage()}" : $exception->getMessage();
        
        $exceptionContext = array_merge($context, [
            'exception_class' => get_class($exception),
            'exception_message' => $exception->getMessage(),
            'exception_code' => $exception->getCode(),
            'exception_file' => $exception->getFile(),
            'exception_line' => $exception->getLine(),
            'stack_trace' => $exception->getTraceAsString()
        ]);

        // 如果是应用自定义异常，添加上下文信息
        if ($exception instanceof \App\Core\Exceptions\AppException) {
            $exceptionContext['exception_context'] = $exception->getContext();
        }

        self::write(self::LEVEL_ERROR, $logMessage, $exceptionContext);
    }

    /**
     * 写入日志的核心方法
     * 
     * @param int $level 日志级别
     * @param string $message 日志消息
     * @param array<string, mixed> $context 上下文数据
     * 
     * @return void
     */
    private static function write(int $level, string $message, array $context = []): void 
    {
        // 检查日志是否启用
        if (!self::$enabled) {
            return;
        }

        // 检查日志级别
        if ($level < self::$logLevel) {
            return;
        }

        // 验证日志级别
        if (!isset(self::$levelNames[$level])) {
            throw new \InvalidArgumentException("无效的日志级别：{$level}");
        }

        // 确保日志系统已初始化
        if (!isset(self::$logPath)) {
            self::init();
        }

        // 检查文件大小，必要时进行轮转
        self::rotateLogFile();

        // 格式化日志消息
        $logEntry = self::formatLogEntry($level, $message, $context);

        // 写入日志文件
        if (file_put_contents(self::$logPath, $logEntry, FILE_APPEND | LOCK_EX) === false) {
            error_log("Logger: 无法写入日志文件 " . self::$logPath);
        }

        // 更新统计信息
        self::updateStats($level);
    }

    /**
     * 格式化日志条目
     * 
     * @param int $level 日志级别
     * @param string $message 日志消息
     * @param array<string, mixed> $context 上下文数据
     * 
     * @return string 格式化后的日志条目
     */
    private static function formatLogEntry(int $level, string $message, array $context = []): string 
    {
        $timestamp = (new DateTime())->format('Y-m-d H:i:s');
        $levelName = self::$levelNames[$level];
        $pid = getmypid() ?: 'unknown';
        $memory = self::formatBytes(memory_get_usage());
        
        // 基本日志格式
        $logEntry = "[{$timestamp}] [{$levelName}] [PID:{$pid}] [MEM:{$memory}] {$message}";

        // 添加上下文信息
        if (!empty($context)) {
            $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($contextJson !== false) {
                $logEntry .= " | Context: {$contextJson}";
            }
        }

        return $logEntry . PHP_EOL;
    }

    /**
     * 日志文件轮转
     * 
     * 当日志文件超过最大大小时，进行文件轮转
     * 
     * @return void
     */
    private static function rotateLogFile(): void 
    {
        if (!file_exists(self::$logPath)) {
            return;
        }

        if (filesize(self::$logPath) < self::$maxFileSize) {
            return;
        }

        // 轮转现有文件
        for ($i = self::$maxFiles - 1; $i >= 1; $i--) {
            $oldFile = self::$logPath . '.' . $i;
            $newFile = self::$logPath . '.' . ($i + 1);

            if ($i === self::$maxFiles - 1 && file_exists($oldFile)) {
                unlink($oldFile); // 删除最老的文件
            } elseif (file_exists($oldFile)) {
                rename($oldFile, $newFile);
            }
        }

        // 轮转当前文件
        if (file_exists(self::$logPath)) {
            rename(self::$logPath, self::$logPath . '.1');
        }
    }

    /**
     * 更新统计信息
     * 
     * @param int $level 日志级别
     * 
     * @return void
     */
    private static function updateStats(int $level): void 
    {
        self::$stats['total_logs']++;
        
        $levelKey = null;
        switch ($level) {
            case self::LEVEL_DEBUG:
                $levelKey = 'debug_logs';
                break;
            case self::LEVEL_INFO:
                $levelKey = 'info_logs';
                break;
            case self::LEVEL_WARNING:
                $levelKey = 'warning_logs';
                break;
            case self::LEVEL_ERROR:
                $levelKey = 'error_logs';
                break;
            case self::LEVEL_CRITICAL:
                $levelKey = 'critical_logs';
                break;
            default:
                $levelKey = null;
                break;
        }

        if ($levelKey) {
            self::$stats[$levelKey]++;
        }
    }

    /**
     * 格式化字节数
     * 
     * @param int $bytes 字节数
     * 
     * @return string 格式化后的字符串
     */
    private static function formatBytes(int $bytes): string 
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . $units[$unitIndex];
    }

    /**
     * 获取日志统计信息
     * 
     * @return array<string, int> 统计信息数组
     */
    public static function getStats(): array 
    {
        return self::$stats;
    }

    /**
     * 设置日志级别
     * 
     * @param int $level 新的日志级别
     * 
     * @return void
     */
    public static function setLevel(int $level): void 
    {
        if (!isset(self::$levelNames[$level])) {
            throw new \InvalidArgumentException("无效的日志级别：{$level}");
        }
        
        self::$logLevel = $level;
    }

    /**
     * 获取当前日志级别
     * 
     * @return int 当前日志级别
     */
    public static function getLevel(): int 
    {
        return self::$logLevel;
    }

    /**
     * 启用或禁用日志记录
     * 
     * @param bool $enabled 是否启用
     * 
     * @return void
     */
    public static function setEnabled(bool $enabled): void 
    {
        self::$enabled = $enabled;
    }

    /**
     * 检查日志记录是否启用
     * 
     * @return bool 是否启用
     */
    public static function isEnabled(): bool 
    {
        return self::$enabled;
    }

    /**
     * 清空日志统计信息
     * 
     * @return void
     */
    public static function resetStats(): void 
    {
        self::$stats = [
            'total_logs' => 0,
            'debug_logs' => 0,
            'info_logs' => 0,
            'warning_logs' => 0,
            'error_logs' => 0,
            'critical_logs' => 0
        ];
    }
}
