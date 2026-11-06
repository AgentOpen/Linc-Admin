<?php

declare(strict_types=1);

namespace Linc\Core;

use RuntimeException;

/**
 * 业务异常。
 *
 * code() 进入响应体的 code 字段；HTTP 状态码由异常处理器统一映射（BusinessException 恒为 400）。
 */
class BusinessException extends RuntimeException
{
    private int $errorCode;

    public function __construct(string $message, int $code = 400)
    {
        parent::__construct($message);
        $this->errorCode = $code;
    }

    public function code(): int
    {
        return $this->errorCode;
    }
}
