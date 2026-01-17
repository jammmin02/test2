<?php

// namespace 작성

namespace Tripmate\Backend\Modules\Trips\Repositories;

// use 작성
use PDO;
use Throwable;
use Tripmate\Backend\Common\Exceptions\DbException;
use Tripmate\Backend\Core\DB;
use Tripmate\Backend\Core\Repository;

// TripsRepository class 정의
// - 공통 Repository 상속 받아 사용
// - 모든 DB 예외는 DbException으로 래핑하여 던짐
class TripsRepository extends Repository
{
    // 생성자에서 DB 접속 및 pdo 초기화
    public function __construct(PDO $pdo)
    {
        // 반드시 같은 PDO 인스턴스를 사용
        parent::__construct($pdo);
    }

    // 1. Trip 생성
    // - 성공시 삽입된 trip_id 반환, 실패시 false 반환
    public function insertTrip(
        int $userId,
        int $regionId,
        string $title,
        string $startDate,
        string $endDate
    ): int {
        try {
            // 1-1. SQL 작성
            $sql = '
                INSERT INTO Trip 
                (user_id, region_id, title, start_date, end_date, created_at, updated_at)
                VALUES (:user_id, :region_id, :title, :start_date, :end_date, NOW(), NOW())
                ';

            // 1-2. 쿼리 준비
            $this->execute($sql, [
                'user_id' => $userId,
                'region_id' => $regionId,
                'title' => $title,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            // 1-3. 삽입된 trip_id 조회
            // - 성공시 trip_id 반환
            // - 실패시 DbException 발생
            $id = $this->lastInsertId();
            if ($id === false) {
                throw new DbException('TRIP_INSERT_NO_ID', '여행 생성 후 ID 조회에 실패했습니다.');
            }
            return $id;
        } catch (Throwable $e) {
            \error_log('[TripsRepository::insertTrip][PDO] ' . $e->getMessage());
            throw new DbException('TRIP_INSERT_FAILED', '여행 생성 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }

    // 2. Trip 단건 조회
    // 조회 성공시 배열 반환, 실패시(존재하지 않을 경우) null 반환
    public function findTripById(int $tripId, int $userId): ?array
    {
        try {
            // 2-1. SQL 작성
            $sql = '
            SELECT 
              trip_id, user_id, region_id, title, 
              start_date, end_date, created_at, updated_at
            FROM Trip
            WHERE trip_id = :trip_id AND user_id = :user_id
            LIMIT 1
        ';

            // 2-2. 쿼리 실행 및 단건 조회
            return $this->fetchOne($sql, [
              'trip_id' => $tripId,
              'user_id' => $userId,
            ]);
        } catch (Throwable $e) {
            throw new DbException('TRIP_FETCH_FAILED', '여행 조회 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }

    // 3. tripday 생성
    // - 성공시 true, 실패시 false 반환
    public function insertTripDay(
        int $tripId,
        int $dayNo,
        string $memo = ''
    ): bool {
        try {
            // 3-1. SQL 작성
            $sql = '
            INSERT INTO TripDay 
            (trip_id, day_no, memo, created_at, updated_at)
            VALUES (:trip_id, :day_no, :memo, NOW(), NOW())
        ';

            // 3-2. 쿼리 실행 및 성공 여부 반환
            // - 영향 받은 행(row) 수가 0보다 크면 성공
            return $this->execute($sql, [
              'trip_id' => $tripId,
              'day_no' => $dayNo,
              'memo' => $memo,
            ]) > 0;
        } catch (Throwable $e) {
            throw new DbException('TRIPDAY_INSERT_FAILED', '여행일정 생성 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }

    // 4. Trip 목록 조회 (페이지네이션)
    // - 경계값 보정은 Controller에서 처리
    // - 성공시 배열 반환 , 실패시 null 반환
    public function findTripsByUserId(
        int $userId,
        int $page,
        int $size,
        ?string $sort = null
    ): array {
        try {
            // Cnotroller에서 page, size 보장
            // offset 계산
            $offset = ($page - 1) * $size;

            // 4-1. 총 개수 조회
            $total = (int)$this->query(
                'SELECT COUNT(*) FROM Trip WHERE user_id = :user_id',
                ['user_id' => $userId]
            )->fetchColumn();

            // 4-2. 정렬 화이트리스트
            $fieldMap = [
              'created_at' => 't.created_at',
              'start_date' => 't.start_date',
              'end_date' => 't.end_date',
              'title' => 't.title',
              'region_id' => 't.region_id',
            ];
            $orderBy = 't.created_at DESC'; // 기본 정렬

            // 4-3. 정렬 파라미터가 유효하면 적용
            if ($sort !== null && $sort !== '') {
                $direction = 'ASC';
                $field = $sort;

                if (\str_contains($sort, ':')) {
                    [$field, $dirRaw] = \explode(':', $sort, 2);
                    $direction = \strtoupper($dirRaw) === 'DESC' ? 'DESC' : 'ASC';
                } elseif ($sort[0] === '-') {
                    $field = \substr($sort, 1);
                    $direction = 'DESC';
                }

                if (isset($fieldMap[$field])) {
                    $orderBy = $fieldMap[$field] . ' ' . $direction;
                }
            }

            // 4-4. SQL 작성
            // - region 이름도 함께 조회
            $sql = "
          SELECT 
            t.trip_id, t.user_id, t.region_id, t.title, 
            t.start_date, t.end_date, t.created_at, t.updated_at,
            r.name AS region_name
          FROM Trip AS t
          LEFT JOIN Region AS r ON t.region_id = r.region_id
          WHERE t.user_id = :user_id
          ORDER BY {$orderBy}
          LIMIT {$size} OFFSET {$offset}
        ";

            // 4-5. 쿼리 실행 및 다건 조회
            $items = $this->fetchAll($sql, ['user_id' => $userId]);

            // 4-6. 페이지네이션 정보 반환
            return [
              'items' => $items,
              'total' => $total,
              'page' => $page,
              'per_page' => $size,
              'total_pages' => (int)\ceil($total / $size),
            ];
        } catch (Throwable $e) {
            throw new DbException('TRIP_LIST_FETCH_FAILED', '여행 목록 조회 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }

    // 5. Trip 수정
    // - 영행 받은 행(row) 수 > 0 이면 true 반환, 아니면 false 반환
    public function updateTrip(
        int $userId,
        int $tripId,
        int $regionId,
        string $title,
        string $startDate,
        string $endDate
    ): bool {
        try {
            // 5-1. SQL 작성
            $sql = '
          UPDATE Trip
          SET region_id = :region_id,
              title = :title,
              start_date = :start_date,
              end_date = :end_date,
              updated_at = NOW()
          WHERE trip_id = :trip_id AND user_id = :user_id
        ';

            // 5-2. 쿼리 실행 및 성공 여부 반환
            return $this->execute($sql, [
              'region_id' => $regionId,
              'title' => $title,
              'start_date' => $startDate,
              'end_date' => $endDate,
              'trip_id' => $tripId,
              'user_id' => $userId,
            ]) > 0;
        } catch (Throwable $e) {
            throw new DbException('TRIP_UPDATE_FAILED', '여행 수정 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }

    // 6. Trip 삭제
    // - 성공시 true, 실패시 false 반환
    public function deleteTrip(int $userId, int $tripId): bool
    {
        try {
            // 6-1. SQL 작성
            $sql = '
          DELETE FROM Trip
          WHERE trip_id = :trip_id AND user_id = :user_id
        ';

            // 6-2. 쿼리 실행 및 성공 여부 반환
            return $this->execute($sql, [
              'trip_id' => $tripId,
              'user_id' => $userId,
            ]) > 0;
        } catch (Throwable $e) {
            throw new DbException('TRIP_DELETE_FAILED', '여행 삭제 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }

    // 7. 특정 Trip의 TripDay 모두 삭제
    public function deleteTripDaysByTripId(int $tripId): bool
    {
        try {
            // 7-1. SQL 작성
            $sql = '
          DELETE FROM TripDay
          WHERE trip_id = :trip_id
        ';

            // 7-2. 쿼리 실행 및 성공 여부 반환
            return $this->execute($sql, [
              'trip_id' => $tripId,
            ]) > 0;
        } catch (Throwable $e) {
            throw new DbException('TRIPDAY_DELETE_FAILED', '여행일정 삭제 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }
}
