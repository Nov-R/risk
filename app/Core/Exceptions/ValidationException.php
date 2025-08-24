<?php

namespace App\Core\Exceptions;

/**
 * 验证异常类
 */
class ValidationException extends AppException {
    private array $errors;

    public function __construct(array $errors, string $message = "数据验证失败", int $code = 400) {
        parent::__construct($message, $code, null, ['errors' => $errors]);
        $this->errors = $errors;
    }

    public function getErrors(): array {
        return $this->errors;
    }
}
