<?php

namespace App\Core\Http;

/**
 * HTTP响应处理类
 * 
 * 该类负责处理所有的HTTP响应，提供以下功能：
 * 1. JSON响应的生成和发送
 * 2. 成功/失败响应的标准化处理
 * 3. HTTP响应头的安全性配置
 * 4. 响应压缩和缓存控制
 * 5. 错误追踪和日志记录
 */
class Response {
    // HTTP状态码常量定义
    /** 请求成功 */
    public const HTTP_OK = 200;
    /** 创建成功 */
    public const HTTP_CREATED = 201;
    /** 请求已接受 */
    public const HTTP_ACCEPTED = 202;
    /** 无内容返回 */
    public const HTTP_NO_CONTENT = 204;
    /** 请求参数错误 */
    public const HTTP_BAD_REQUEST = 400;
    /** 未授权访问 */
    public const HTTP_UNAUTHORIZED = 401;
    /** 禁止访问 */
    public const HTTP_FORBIDDEN = 403;
    /** 资源未找到 */
    public const HTTP_NOT_FOUND = 404;
    /** 请求方法不允许 */
    public const HTTP_METHOD_NOT_ALLOWED = 405;
    /** 资源冲突 */
    public const HTTP_CONFLICT = 409;
    /** 资源已永久删除 */
    public const HTTP_GONE = 410;
    /** 需要内容长度 */
    public const HTTP_LENGTH_REQUIRED = 411;
    /** 前置条件失败 */
    public const HTTP_PRECONDITION_FAILED = 412;
    /** 请求内容过大 */
    public const HTTP_PAYLOAD_TOO_LARGE = 413;
    /** 不支持的媒体类型 */
    public const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
    /** 请求数据验证失败 */
    public const HTTP_UNPROCESSABLE_ENTITY = 422;
    /** 请求频率超限 */
    public const HTTP_TOO_MANY_REQUESTS = 429;
    /** 服务器内部错误 */
    public const HTTP_INTERNAL_SERVER_ERROR = 500;
    /** 服务暂时不可用 */
    public const HTTP_SERVICE_UNAVAILABLE = 503;
    
    // 缓存控制常量
    private const CACHE_CONTROL_NO_CACHE = 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0';
    private const CACHE_CONTROL_PUBLIC = 'public, max-age=31536000'; // 1年
    private const CACHE_CONTROL_PRIVATE = 'private, max-age=3600'; // 1小时
    /**
     * 发送JSON响应
     *
    * @param mixed $data 响应数据
    * @param int $statusCode HTTP状态码
    * @param bool $cache 是否允许缓存
    * @param bool $private 是否为私有缓存
     */
    public static function json($data, int $statusCode = 200, bool $cache = false, bool $private = true): void {
        // 设置状态码
        http_response_code($statusCode);
        
        // 设置基本头部
        header('Content-Type: application/json; charset=utf-8');
        
        // 设置安全头部
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
        
        // 设置缓存控制
        if ($cache) {
            header('Cache-Control: ' . ($private ? self::CACHE_CONTROL_PRIVATE : self::CACHE_CONTROL_PUBLIC));
            header('ETag: "' . hash('xxh3', json_encode($data)) . '"');
        } else {
            header('Cache-Control: ' . self::CACHE_CONTROL_NO_CACHE);
            header('Pragma: no-cache');
            header('Expires: 0');
        }
        
        // 准备响应数据
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($jsonData === false) {
            self::error('JSON编码失败', self::HTTP_INTERNAL_SERVER_ERROR);
            return;
        }
        
        // 检查并启用压缩
        if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
            header('Content-Encoding: gzip');
            echo gzencode($jsonData, 9);
        } else {
            echo $jsonData;
        }
        
        exit;
    }
    
    /**
     * 发送成功响应（标准化格式）
     *
     * @param mixed $data 返回的数据（可为 null）
     * @param string $message 成功提示消息
     * @param int $statusCode HTTP 状态码（默认 200）
     * @param bool $cache 是否允许缓存
     * @param bool $private 是否为私有缓存
     */
    public static function success($data = null, string $message = '操作成功', int $statusCode = self::HTTP_OK, bool $cache = false, bool $private = true): void {
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        self::json($response, $statusCode, $cache, $private);
    }
    
    /**
     * 发送错误响应（标准化格式）
     *
     * @param string $message 错误消息
     * @param int $statusCode HTTP 状态码
     * @param array $errors 详情错误数组（可选）
     * @param string|null $code 自定义错误码（可选）
     */
    public static function error(string $message, int $statusCode = self::HTTP_BAD_REQUEST, array $errors = [], ?string $code = null): void {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        if ($code !== null) {
            $response['code'] = $code;
        }
        
        // 对于5xx错误，添加请求ID以便追踪
        if ($statusCode >= 500) {
            $response['request_id'] = uniqid('req_', true);
        }
        
        self::json($response, $statusCode, false, true);
    }
    
    /**
     * 发送验证失败响应，使用 422 状态码并标注错误类型
     *
     * @param array $errors 验证错误详情
     * @param string $message 错误消息（默认：数据验证失败）
     */
    public static function validationError(array $errors, string $message = '数据验证失败'): void {
        self::error($message, self::HTTP_UNPROCESSABLE_ENTITY, $errors, 'VALIDATION_ERROR');
    }
}
