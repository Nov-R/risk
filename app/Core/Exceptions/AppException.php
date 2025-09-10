<?php

namespace App\Core\Exceptions;

use Exception;
use Throwable;

/**
 * 应用程序基础异常类
 * 
 * 这是整个应用程序异常体系的根基类，所有自定义异常都应继承此类。
 * 提供了以下核心功能：
 * - 异常上下文信息的存储和获取
 * - 统一的异常消息格式化
 * - 异常日志记录的标准化接口
 * - 支持异常链传递和堆栈跟踪
 * 
 * 设计遵循以下原则：
 * - 异常信息完全中文化，便于开发人员理解
 * - 支持上下文数据传递，便于调试和错误分析
 * - 保持向后兼容性，符合 PHP 异常处理标准
 * 
 * @author 风险管理系统开发组
 * @version 1.0
 * @since 2025-09-10
 * 
 * @example
 * ```php
 * // 抛出带上下文信息的异常
 * throw new AppException(
 *     '用户操作失败', 
 *     500, 
 *     $previousException, 
 *     ['user_id' => 123, 'action' => 'create_risk']
 * );
 * 
 * // 获取异常上下文
 * try {
 *     // 某些操作
 * } catch (AppException $e) {
 *     $context = $e->getContext();
 *     Logger::error($e->getMessage(), $context);
 * }
 * ```
 */
class AppException extends Exception 
{
    /**
     * 异常上下文数据
     * 
     * 存储与异常相关的额外信息，如用户ID、操作参数、
     * 系统状态等，便于问题诊断和日志记录
     * 
     * @var array<string, mixed>
     */
    protected array $context = [];

    /**
     * 构造应用程序异常实例
     * 
     * @param string $message 异常消息（应为中文描述）
     * @param int $code 异常代码（建议使用HTTP状态码或自定义错误码）
     * @param Throwable|null $previous 上一个异常（用于异常链）
     * @param array<string, mixed> $context 异常上下文数据
     * 
     * @example
     * ```php
     * // 基本用法
     * throw new AppException('操作失败');
     * 
     * // 带错误代码
     * throw new AppException('数据验证失败', 400);
     * 
     * // 带上下文信息
     * throw new AppException('用户不存在', 404, null, ['user_id' => $userId]);
     * 
     * // 异常链传递
     * try {
     *     $this->databaseOperation();
     * } catch (PDOException $e) {
     *     throw new AppException('数据库操作失败', 500, $e);
     * }
     * ```
     */
    public function __construct(
        string $message = "应用程序发生未知错误", 
        int $code = 0, 
        ?Throwable $previous = null, 
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * 获取异常上下文数据
     * 
     * 返回创建异常时传入的上下文信息，可用于：
     * - 错误日志记录
     * - 调试信息输出  
     * - 错误报告生成
     * - 监控数据统计
     * 
     * @return array<string, mixed> 异常上下文数据数组
     * 
     * @example
     * ```php
     * try {
     *     $userService->createUser($userData);
     * } catch (AppException $e) {
     *     $context = $e->getContext();
     *     
     *     // 记录详细日志
     *     Logger::error($e->getMessage(), array_merge($context, [
     *         'exception_file' => $e->getFile(),
     *         'exception_line' => $e->getLine(),
     *         'stack_trace' => $e->getTraceAsString()
     *     ]));
     *     
     *     // 发送错误报告
     *     ErrorReporter::send($e, $context);
     * }
     * ```
     */
    public function getContext(): array 
    {
        return $this->context;
    }

    /**
     * 设置或更新异常上下文数据
     * 
     * 允许在异常创建后添加或更新上下文信息，
     * 这在异常传播过程中添加额外信息时很有用
     * 
     * @param array<string, mixed> $context 要设置的上下文数据
     * @param bool $merge 是否与现有上下文合并（true）还是完全替换（false）
     * 
     * @return self 返回当前实例，支持链式调用
     * 
     * @example
     * ```php
     * $exception = new AppException('操作失败');
     * 
     * // 添加上下文信息
     * $exception->setContext(['user_id' => 123], true);
     * 
     * // 链式调用
     * throw $exception->setContext(['timestamp' => time()], true);
     * ```
     */
    public function setContext(array $context, bool $merge = true): self 
    {
        if ($merge) {
            $this->context = array_merge($this->context, $context);
        } else {
            $this->context = $context;
        }
        
        return $this;
    }

    /**
     * 格式化异常信息为数组
     * 
     * 将异常信息转换为结构化的数组格式，
     * 便于JSON序列化和API响应返回
     * 
     * @param bool $includeTrace 是否包含堆栈跟踪信息
     * @param bool $includeContext 是否包含上下文信息
     * 
     * @return array<string, mixed> 格式化后的异常信息
     * 
     * @example
     * ```php
     * try {
     *     // 某些操作
     * } catch (AppException $e) {
     *     $errorData = $e->toArray(true, true);
     *     
     *     // 返回JSON响应
     *     Response::error($errorData, $e->getCode() ?: 500);
     * }
     * ```
     */
    public function toArray(bool $includeTrace = false, bool $includeContext = true): array 
    {
        $result = [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];

        if ($includeContext && !empty($this->context)) {
            $result['context'] = $this->context;
        }

        if ($includeTrace) {
            $result['trace'] = $this->getTraceAsString();
        }

        return $result;
    }
}
