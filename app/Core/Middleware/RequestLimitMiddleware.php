<?php

namespace App\Core\Middleware;

use App\Core\Http\Response;
use App\Core\Utils\Logger;

/**
 * 请求限制中间件
 *
 * - 检查请求体大小、上传文件大小和单位时间内的请求次数
 * - 当检测到超出限制时，返回相应的 HTTP 错误响应
 */
class RequestLimitMiddleware
{
    private const MAX_BODY_SIZE = 10485760; // 10MB in bytes
    private const MAX_FILE_SIZE = 5242880;  // 5MB in bytes
    
    private const RATE_LIMITS = [
        'per_minute' => 100,
        'per_hour' => 1000
    ];
    
    private static array $requestCounts = [];
    
    public function handle(): bool
    {
    /**
     * 中间件入口：检查请求是否满足限制条件
     *
     * - 校验请求体大小（MAX_BODY_SIZE）
     * - 校验上传文件大小（MAX_FILE_SIZE）
     * - 校验单位时间内的请求次数（RATE_LIMITS）
     *
     * 返回：true 表示允许继续处理请求，false 表示已生成响应并应中止后续处理。
     * 错误处理：若发生未预期的异常，会记录日志并返回 500 响应。
     *
     * @return bool 是否允许继续处理（true = 继续，false = 已拦截）
     */
        try {
            if (!$this->checkRequestSize()) {
                return false;
            }
            
            if (!$this->checkFileSize()) {
                return false;
            }
            
            if (!$this->checkRateLimit()) {
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            Logger::error('请求限制中间件错误', ['error' => $e->getMessage()]);
            Response::error('服务器内部错误', 500);
            return false;
        }
    }
    
    private function checkRequestSize(): bool
    {
        /**
         * 检查请求体大小是否超过最大限制
         *
         * @return bool 返回 true 表示大小合法，false 表示已经通过 Response 返回 413 并拦截请求
         */
        $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($contentLength > self::MAX_BODY_SIZE) {
            Response::error('请求体过大', 413);
            return false;
        }
        return true;
    }
    
    private function checkFileSize(): bool
    {
        /**
         * 检查上传文件大小是否超过最大限制
         *
         * 遍历 PHP 全局数组 $_FILES，当任一文件大小超过 MAX_FILE_SIZE 时，通过 Response 返回 413 并拦截请求。
         *
         * @return bool 返回 true 表示所有文件大小合法，false 表示已拦截
         */
        if (!empty($_FILES)) {
            foreach ($_FILES as $file) {
                if ($file['size'] > self::MAX_FILE_SIZE) {
                    Response::error('文件大小超过限制', 413);
                    return false;
                }
            }
        }
        return true;
    }
    
    private function checkRateLimit(): bool
    {
        /**
         * 简单的基于内存的速率限制实现
         *
         * - 基于客户端 IP（$_SERVER['REMOTE_ADDR']）进行计数
         * - 维护两级计数：每分钟与每小时
         * - 超出 RATE_LIMITS 的阈值会通过 Response 返回 429 并拦截请求
         *
         * 注意：该实现会随着 PHP 进程退出而丢失计数，不适用于多进程/分布式部署；生产环境建议使用 Redis 等外部存储实现。
         *
         * @return bool 返回 true 表示未超过限制，false 表示已拦截
         */
        $ip = $_SERVER['REMOTE_ADDR'];
        $now = time();

        // Initialize or clean up old records
        if (!isset(self::$requestCounts[$ip])) {
            self::$requestCounts[$ip] = [
                'minute' => ['count' => 0, 'timestamp' => $now],
                'hour' => ['count' => 0, 'timestamp' => $now]
            ];
        }

        // Check and update minute count
        if ($now - self::$requestCounts[$ip]['minute']['timestamp'] > 60) {
            self::$requestCounts[$ip]['minute'] = ['count' => 0, 'timestamp' => $now];
        }

        // Check and update hour count
        if ($now - self::$requestCounts[$ip]['hour']['timestamp'] > 3600) {
            self::$requestCounts[$ip]['hour'] = ['count' => 0, 'timestamp' => $now];
        }

        // Increment counters
        self::$requestCounts[$ip]['minute']['count']++;
        self::$requestCounts[$ip]['hour']['count']++;

        // Check limits
        if (self::$requestCounts[$ip]['minute']['count'] > self::RATE_LIMITS['per_minute']) {
            Response::error('每分钟请求过多', 429);
            return false;
        }

        if (self::$requestCounts[$ip]['hour']['count'] > self::RATE_LIMITS['per_hour']) {
            Response::error('每小时请求过多', 429);
            return false;
        }

        return true;
    }
}
