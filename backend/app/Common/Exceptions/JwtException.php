<?php

namespace Tripmate\Backend\Common\Exceptions;

use Exception;

class JwtException extends Exception
{
    public function __construct(protected string $error, string $message = 'JWT 오류가 발생했습니다.')
    {
        // 부모 생성자로 메세지 전달
        parent::__construct($message, 0);
    }

    //
    public function getError(): string
    {
        return $this->error;
    }
}
