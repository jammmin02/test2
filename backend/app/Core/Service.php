<?php

// namespace

namespace Tripmate\Backend\Core;

// use 작성
use PDO;
use Throwable;
use Tripmate\Backend\Common\Exceptions\DbException;
use Tripmate\Backend\Common\Exceptions\HttpException;

// 모든 서비스의 공통 베이스 추상화 class
// - 트랜잭션 처리
abstract class Service
{
    // 생성자에서 PDO 주입
    public function __construct(protected PDO $pdo)
    {
    }

    // 1. 트랜잭션 처리 메서드
    // - 성공 시 커밋, 실패 시 롤백 후 DbException 던짐
    protected function transaction(callable $callback)
    {
        // 1-1. 내가 트랜잭션을 시작했는지 플래그 설정
        $started = false;

        try {
            // 1-2. 트랜잭션이 없으면 시작
            if (!$this->pdo->inTransaction()) {
                $this->pdo->beginTransaction();
                $started = true;
            }

            // 1-3. 콜백 실행 (필요 시 $this->db 사용)
            $result = $callback($this->pdo);

            // 1-4. 내가 시작한 트랜잭션만 커밋
            if ($started && $this->pdo->inTransaction()) {
                $this->pdo->commit();
            }

            // 1-5. 결과 반환
            return $result;
        } catch (Throwable $e) {
            // 1-6. 내가 시작한 트랜잭션만 롤백
            if ($started && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            // 1-7. 이미 DbException이면 그대로 던짐
            if ($e instanceof DbException) {
                throw $e;
            }

            // HTTP 예외라면 (서비스 계층에서 발생한 비즈니스 에러)
            if ($e instanceof HttpException) {
                throw $e;
            }

            // 1-8. 새 DbException 던짐
            throw new DbException('DB_TRANSACTION_FAILED', '트랜잭션을 실패하였습니다.', $e);
        }
    }
}
