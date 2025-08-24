<?php

namespace App\Core\Exceptions;

use Exception;
use Throwable;

/**
 * 应用程序基础异常类
 */
class AppException extends Exception {
    protected array $context = [];

    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null, array $context = []) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    public function getContext(): array {
        return $this->context;
    }

    public static function run(){
        try {
            // 运行应用程序代码
        } catch (Throwable $e) {
            throw new self("应用程序运行时发生错误", 0, $e);
        }
    }
    
}
