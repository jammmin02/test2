<?php

// namespace App\Common\Exceptions;
// 1. namespace 작성

namespace Tripmate\Backend\Common\Exceptions;

use RuntimeException;
use Throwable;

// 2. DB 관련 예외 처리를 위한 클래스 작성
class DbException extends RuntimeException
{
    // 에러 코드

    // 4. 생성자 정의
    public function __construct(protected string $codeName, string $message = 'DB 오류가 발생했습니다', ?Throwable $throwable = null)
    {
        parent::__construct($message, 0, $throwable); // 에러 코드 설정
    }

    // 5. 에러 코드 getter 메서드 정의
    public function getCodeName(): string
    {
        return $this->codeName;
    }
}
