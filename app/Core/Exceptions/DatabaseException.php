<?php

namespace App\Core\Exceptions;

use Throwable;

/**
 * 数据库异常类
 */
class DatabaseException extends AppException {
    public function __construct(string $message = "发生数据库错误", int $code = 500, ?Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
