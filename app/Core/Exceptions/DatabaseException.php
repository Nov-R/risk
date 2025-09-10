<?php

namespace App\Core\Exceptions;

use Throwable;
use PDOException;

/**
 * 数据库操作异常类
 * 
 * 专门用于处理数据库相关的异常情况，包括：
 * - 数据库连接失败
 * - SQL语句执行错误
 * - 数据约束违反
 * - 事务处理失败
 * - 数据库配置错误
 * 
 * 提供数据库错误的统一处理和中文化错误信息，
 * 同时保留原始错误信息用于调试和日志记录。
 * 
 * @author 风险管理系统开发组
 * @version 1.0
 * @since 2025-09-10
 * 
 * @example
 * ```php
 * try {
 *     $pdo->exec($sql);
 * } catch (PDOException $e) {
 *     throw DatabaseException::fromPDOException($e, '用户创建失败', [
 *         'sql' => $sql,
 *         'user_id' => $userId
 *     ]);
 * }
 * ```
 */
class DatabaseException extends AppException 
{
    /**
     * 数据库错误类型常量定义
     */
    
    /** 数据库连接失败 */
    public const CONNECTION_FAILED = 'CONNECTION_FAILED';
    
    /** SQL语法错误 */
    public const SYNTAX_ERROR = 'SYNTAX_ERROR';
    
    /** 数据约束违反（如唯一约束、外键约束等） */
    public const CONSTRAINT_VIOLATION = 'CONSTRAINT_VIOLATION';
    
    /** 数据不存在 */
    public const DATA_NOT_FOUND = 'DATA_NOT_FOUND';
    
    /** 事务处理失败 */
    public const TRANSACTION_FAILED = 'TRANSACTION_FAILED';
    
    /** 权限不足 */
    public const PERMISSION_DENIED = 'PERMISSION_DENIED';
    
    /** 数据库配置错误 */
    public const CONFIGURATION_ERROR = 'CONFIGURATION_ERROR';
    
    /** 未知数据库错误 */
    public const UNKNOWN_ERROR = 'UNKNOWN_ERROR';

    /**
     * 数据库错误类型
     * 
     * @var string
     */
    private string $errorType;

    /**
     * 原始SQL语句（如果适用）
     * 
     * @var string|null
     */
    private ?string $sql;

    /**
     * 构造数据库异常实例
     * 
     * @param string $message 异常消息（中文描述）
     * @param int $code 异常代码（建议使用HTTP状态码）
     * @param Throwable|null $previous 上一个异常（通常是PDOException）
     * @param array<string, mixed> $context 异常上下文数据
     * @param string $errorType 数据库错误类型
     * @param string|null $sql 相关SQL语句
     */
    public function __construct(
        string $message = "数据库操作失败", 
        int $code = 500, 
        ?Throwable $previous = null,
        array $context = [],
        string $errorType = self::UNKNOWN_ERROR,
        ?string $sql = null
    ) {
        parent::__construct($message, $code, $previous, $context);
        $this->errorType = $errorType;
        $this->sql = $sql;
    }

    /**
     * 从PDOException创建DatabaseException
     * 
     * 自动分析PDO异常并转换为更友好的中文异常信息
     * 
     * @param PDOException $pdoException 原始PDO异常
     * @param string|null $userMessage 用户友好的错误消息
     * @param array<string, mixed> $context 额外的上下文信息
     * @param string|null $sql 相关的SQL语句
     * 
     * @return static 新的DatabaseException实例
     */
    public static function fromPDOException(
        PDOException $pdoException, 
        ?string $userMessage = null,
        array $context = [],
        ?string $sql = null
    ): self {
        $errorCode = $pdoException->getCode();
        $errorMessage = $pdoException->getMessage();
        
        // 根据PDO错误代码确定错误类型和中文消息
        [$errorType, $chineseMessage, $httpCode] = self::analyzePDOError($errorCode, $errorMessage);
        
        // 使用提供的消息或自动生成的中文消息
        $finalMessage = $userMessage ?? $chineseMessage;
        
        // 添加原始错误信息到上下文
        $context = array_merge($context, [
            'original_message' => $errorMessage,
            'pdo_error_code' => $errorCode,
            'sql_state' => $pdoException->errorInfo[0] ?? null,
        ]);

        return new static(
            $finalMessage,
            $httpCode,
            $pdoException,
            $context,
            $errorType,
            $sql
        );
    }

    /**
     * 分析PDO错误并返回相应的错误信息
     * 
     * @param mixed $errorCode PDO错误代码
     * @param string $errorMessage 原始错误消息
     * 
     * @return array{0: string, 1: string, 2: int} [错误类型, 中文消息, HTTP状态码]
     */
    private static function analyzePDOError($errorCode, string $errorMessage): array 
    {
        // 检查常见的数据库错误模式
        if (str_contains($errorMessage, 'Connection refused') || str_contains($errorMessage, 'Connection failed')) {
            return [self::CONNECTION_FAILED, '数据库连接失败，请检查数据库服务是否正常', 503];
        }
        
        if (str_contains($errorMessage, 'Duplicate entry') || str_contains($errorMessage, 'UNIQUE constraint')) {
            return [self::CONSTRAINT_VIOLATION, '数据已存在，违反唯一性约束', 409];
        }
        
        if (str_contains($errorMessage, 'foreign key constraint') || str_contains($errorMessage, 'FOREIGN KEY')) {
            return [self::CONSTRAINT_VIOLATION, '数据关联性错误，违反外键约束', 400];
        }
        
        if (str_contains($errorMessage, 'syntax error') || str_contains($errorMessage, 'SQL syntax')) {
            return [self::SYNTAX_ERROR, 'SQL语法错误', 500];
        }
        
        if (str_contains($errorMessage, 'Access denied') || str_contains($errorMessage, 'permission')) {
            return [self::PERMISSION_DENIED, '数据库访问权限不足', 403];
        }
        
        if (str_contains($errorMessage, 'Unknown database') || str_contains($errorMessage, 'Unknown table')) {
            return [self::CONFIGURATION_ERROR, '数据库配置错误，表或数据库不存在', 500];
        }

        return [self::UNKNOWN_ERROR, '数据库操作失败', 500];
    }

    /**
     * 获取数据库错误类型
     * 
     * @return string 错误类型常量
     */
    public function getErrorType(): string 
    {
        return $this->errorType;
    }

    /**
     * 获取相关的SQL语句
     * 
     * @return string|null SQL语句，如果不适用则返回null
     */
    public function getSql(): ?string 
    {
        return $this->sql;
    }

    /**
     * 判断是否为连接相关的错误
     * 
     * @return bool 如果是连接错误返回true
     */
    public function isConnectionError(): bool 
    {
        return $this->errorType === self::CONNECTION_FAILED;
    }

    /**
     * 判断是否为约束违反错误
     * 
     * @return bool 如果是约束违反错误返回true
     */
    public function isConstraintViolation(): bool 
    {
        return $this->errorType === self::CONSTRAINT_VIOLATION;
    }

    /**
     * 判断是否为权限错误
     * 
     * @return bool 如果是权限错误返回true
     */
    public function isPermissionError(): bool 
    {
        return $this->errorType === self::PERMISSION_DENIED;
    }

    /**
     * 获取用户友好的错误提示
     * 
     * 根据错误类型返回适合向最终用户显示的错误信息
     * 
     * @return string 用户友好的错误消息
     */
    public function getUserFriendlyMessage(): string 
    {
        switch ($this->errorType) {
            case self::CONNECTION_FAILED:
                return '系统暂时无法连接到数据库，请稍后再试';
            case self::CONSTRAINT_VIOLATION:
                return '操作失败，数据冲突或不符合业务规则';
            case self::DATA_NOT_FOUND:
                return '请求的数据不存在';
            case self::PERMISSION_DENIED:
                return '权限不足，无法执行此操作';
            case self::TRANSACTION_FAILED:
                return '操作失败，请重试';
            default:
                return '系统繁忙，请稍后再试';
        }
    }
}
