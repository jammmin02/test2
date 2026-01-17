<?php

// namespace App\Core;
// 1. namespace 작성

namespace Tripmate\Backend\Core;

// 2. use 작성
use PDO;
use PDOStatement;

// 3. 공통 레포지토리 추상화 클래스
// - 반복되는 prepare, execute, fetch 로직을 메서드로 구현
abstract class Repository
{
    // 5. 생성자 정의
    // - DB 커넥션 주입
    public function __construct(protected PDO $pdo)
    {
    }
    // 6. 공통 쿼리 실행 메서드
    // - prepare, execute, fetchAll 를 한번에 처리
    protected function query(string $sql, array $params = []): PDOStatement
    {
        // 6-1. 쿼리 준비
        $stmt = $this->pdo->prepare($sql);

        // 6-2. 파라미터 바인딩
        // - params 배열의 키가 :로 시작하지 않으면 자동으로 추가
        foreach ($params as $key => $value) {
            if (!\str_starts_with($key, ':')) {
                $key = ':' . $key;
            }
            // - 정수형일 경우 PDO::PARAM_INT 로 바인딩
            if (\is_int($value)) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
                continue;
            }
            // - 그 외는 기본 바인딩
            $stmt->bindValue($key, $value);
        }

        // 6-3. 쿼리 실행
        $stmt->execute();

        // 6-4. 결과 반환
        return $stmt;
    }

    // 7. 단건 조회 (없으면 null 반환) 메서드
    protected function fetchOne(string $sql, array $params = []): ?array
    {
        // 7-1. 쿼리 실행 후 단건 조회
        $row = $this->query($sql, $params)->fetch();
        // 7-2. 결과 반환 (없으면 null)
        return $row === false ? null : $row;
    }

    // 8. 다건 조회 메서드
    protected function fetchAll(string $sql, array $params = []): array
    {
        // 8-1. 쿼리 실행 후 다건 조회
        return $this->query($sql, $params)->fetchAll();
    }

    // 9. CUD (삽입, 수정, 삭제) 메서드
    protected function execute(string $sql, array $params = []): int
    {
        // 9-1. 쿼리 실행 후 영향 받은 행(row) 수 반환
        return $this->query($sql, $params)->rowCount();
    }

    // 10. 마지막 삽입된 ID 반환 메서드
    protected function lastInsertId(): int
    {
        // 10-1. 마지막 삽입된 ID 반환
        return (int)$this->pdo->lastInsertId();
    }
}
