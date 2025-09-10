<?php

namespace App\Core\Exceptions;

use Throwable;

/**
 * 数据验证异常类
 * 
 * 专门用于处理数据验证失败的异常情况，包括：
 * - 表单数据验证失败
 * - API参数验证失败
 * - 业务规则验证失败
 * - 数据格式验证失败
 * 
 * 提供详细的验证错误信息收集和管理功能，
 * 支持字段级别的错误信息和批量错误处理。
 * 
 * @author 风险管理系统开发组
 * @version 1.0
 * @since 2025-09-10
 * 
 * @example
 * ```php
 * $errors = [
 *     'name' => '用户名不能为空',
 *     'email' => '邮箱格式不正确',
 *     'age' => '年龄必须在18-100之间'
 * ];
 * 
 * throw new ValidationException($errors, '用户注册数据验证失败');
 * ```
 */
class ValidationException extends AppException 
{
    /**
     * 验证错误集合
     * 
     * 存储字段级别的验证错误信息，格式为：
     * [
     *     '字段名' => '错误消息',
     *     '字段名' => ['错误消息1', '错误消息2'], // 支持多个错误
     * ]
     * 
     * @var array<string, string|array<string>>
     */
    private array $errors;

    /**
     * 验证失败的字段数量
     * 
     * @var int
     */
    private int $failedFieldsCount;

    /**
     * 构造验证异常实例
     * 
     * @param array<string, string|array<string>> $errors 验证错误数组
     * @param string $message 异常主消息
     * @param int $code 异常代码（默认400表示客户端请求错误）
     * @param Throwable|null $previous 上一个异常
     * @param array<string, mixed> $context 额外的上下文信息
     * 
     * @example
     * ```php
     * // 基本用法
     * $errors = ['name' => '姓名不能为空'];
     * throw new ValidationException($errors);
     * 
     * // 带自定义消息
     * throw new ValidationException($errors, '用户信息验证失败');
     * 
     * // 多个字段错误
     * $errors = [
     *     'name' => '姓名不能为空',
     *     'email' => ['邮箱格式不正确', '邮箱已被使用']
     * ];
     * throw new ValidationException($errors);
     * ```
     */
    public function __construct(
        array $errors, 
        string $message = "数据验证失败", 
        int $code = 400,
        ?Throwable $previous = null,
        array $context = []
    ) {
        // 验证错误数组不能为空
        if (empty($errors)) {
            throw new \InvalidArgumentException('验证错误数组不能为空');
        }

        $this->errors = $errors;
        $this->failedFieldsCount = count($errors);
        
        // 将错误信息添加到上下文
        $context = array_merge($context, [
            'errors' => $errors,
            'failed_fields_count' => $this->failedFieldsCount,
            'failed_fields' => array_keys($errors)
        ]);

        parent::__construct($message, $code, $previous, $context);
    }

    /**
     * 获取所有验证错误
     * 
     * @return array<string, string|array<string>> 验证错误数组
     */
    public function getErrors(): array 
    {
        return $this->errors;
    }

    /**
     * 获取指定字段的验证错误
     * 
     * @param string $field 字段名
     * 
     * @return string|array<string>|null 字段的错误信息，不存在则返回null
     */
    public function getFieldErrors(string $field): string|array|null 
    {
        return $this->errors[$field] ?? null;
    }

    /**
     * 获取验证失败的字段名列表
     * 
     * @return array<string> 字段名数组
     */
    public function getFailedFields(): array 
    {
        return array_keys($this->errors);
    }

    /**
     * 获取验证失败的字段数量
     * 
     * @return int 失败字段的数量
     */
    public function getFailedFieldsCount(): int 
    {
        return $this->failedFieldsCount;
    }

    /**
     * 检查指定字段是否有验证错误
     * 
     * @param string $field 字段名
     * 
     * @return bool 如果字段有错误返回true
     */
    public function hasFieldError(string $field): bool 
    {
        return isset($this->errors[$field]);
    }

    /**
     * 获取第一个验证错误消息
     * 
     * 通常用于只需要显示一个错误消息的场景
     * 
     * @return string 第一个错误消息
     */
    public function getFirstError(): string 
    {
        $firstError = reset($this->errors);
        
        if (is_array($firstError)) {
            return reset($firstError);
        }
        
        return $firstError;
    }

    /**
     * 获取所有错误消息的扁平数组
     * 
     * 将多维错误数组转换为一维消息数组
     * 
     * @return array<string> 所有错误消息的数组
     */
    public function getAllMessages(): array 
    {
        $messages = [];
        
        foreach ($this->errors as $field => $error) {
            if (is_array($error)) {
                $messages = array_merge($messages, $error);
            } else {
                $messages[] = $error;
            }
        }
        
        return $messages;
    }

    /**
     * 获取格式化的错误消息字符串
     * 
     * 将所有错误消息连接成一个字符串，便于日志记录或显示
     * 
     * @param string $separator 错误消息之间的分隔符
     * @param bool $includeFieldNames 是否包含字段名
     * 
     * @return string 格式化的错误消息
     * 
     * @example
     * ```php
     * $exception = new ValidationException([
     *     'name' => '姓名不能为空',
     *     'email' => '邮箱格式不正确'
     * ]);
     * 
     * echo $exception->getFormattedMessage(); 
     * // 输出: "姓名不能为空; 邮箱格式不正确"
     * 
     * echo $exception->getFormattedMessage(', ', true);
     * // 输出: "name: 姓名不能为空, email: 邮箱格式不正确"
     * ```
     */
    public function getFormattedMessage(string $separator = '; ', bool $includeFieldNames = false): string 
    {
        $messages = [];
        
        foreach ($this->errors as $field => $error) {
            if (is_array($error)) {
                foreach ($error as $msg) {
                    $messages[] = $includeFieldNames ? "{$field}: {$msg}" : $msg;
                }
            } else {
                $messages[] = $includeFieldNames ? "{$field}: {$error}" : $error;
            }
        }
        
        return implode($separator, $messages);
    }

    /**
     * 将验证错误转换为数组格式
     * 
     * 重写父类方法以包含验证特定的信息
     * 
     * @param bool $includeTrace 是否包含堆栈跟踪
     * @param bool $includeContext 是否包含上下文信息
     * 
     * @return array<string, mixed> 包含错误信息的数组
     */
    public function toArray(bool $includeTrace = false, bool $includeContext = true): array 
    {
        $result = parent::toArray($includeTrace, $includeContext);
        
        // 添加验证特定的信息
        $result['errors'] = $this->errors;
        $result['failed_fields'] = $this->getFailedFields();
        $result['failed_count'] = $this->failedFieldsCount;
        $result['formatted_message'] = $this->getFormattedMessage();
        
        return $result;
    }

    /**
     * 获取验证错误的详细信息数组
     * 
     * 专门用于获取验证错误信息，不包含异常堆栈等技术细节
     * 
     * @param bool $includeFieldNames 是否在消息中包含字段名
     * 
     * @return array<string, mixed> 验证错误的详细信息
     */
    public function getValidationDetails(bool $includeFieldNames = false): array 
    {
        return [
            'message' => $this->getMessage(),
            'errors' => $this->errors,
            'failed_fields' => $this->getFailedFields(),
            'failed_count' => $this->failedFieldsCount,
            'formatted_message' => $this->getFormattedMessage(', ', $includeFieldNames)
        ];
    }

    /**
     * 创建单字段验证异常的静态方法
     * 
     * 便于快速创建单个字段的验证异常
     * 
     * @param string $field 字段名
     * @param string $message 错误消息
     * @param string|null $exceptionMessage 异常主消息
     * 
     * @return static 新的ValidationException实例
     */
    public static function single(string $field, string $message, ?string $exceptionMessage = null): static 
    {
        return new static(
            [$field => $message], 
            $exceptionMessage ?? "字段 {$field} 验证失败"
        );
    }
}
