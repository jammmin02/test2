<?php

// 네임스페이스 선언

namespace Tripmate\Backend\Modules\TripDays\Repositories;

// use 작성
use PDO;
use Throwable;
use Tripmate\Backend\Common\Exceptions\DbException;
use Tripmate\Backend\Core\DB;
use Tripmate\Backend\Core\Repository;

// TripDaysRepository 클래스 정의
class TripDaysRepository extends Repository
{
    // 생성자에서 DB 접속 및 pdo 초기화
    public function __construct(PDO $pdo)
    {
        // 반드시 같은 PDO 인스턴스를 사용
        parent::__construct($pdo);
    }

    // 1. trip id가 user_id의 소유인지 확인
    public function isTripOwner(int $tripId, int $userId): bool
    {
        try {
            // 1-1. SQL 작성
            $sql =
            '
        SELECT EXISTS(
        SELECT 1 
        FROM Trip 
        WHERE trip_id = :trip_id AND user_id = :user_id
      ) AS result;
      ';
            // 1-2. 쿼리 실행
            $result = (int) $this->query($sql, [
              'trip_id' => $tripId,
              'user_id' => $userId
            ])->fetchColumn();

            // 1-3. 결과 반환
            return $result === 1;
        } catch (Throwable $e) {
            \error_log('[TripDaysRepository::isTripOwner][PDO] ' . $e->getMessage());
            throw new DbException('TRIPDAY_OWNER_CHECK_FAILED', '트립 일차 소유자 확인 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }

    // 2. trip meta 조회
    // - 값이 없을 경우 null 반환
    public function getTripMeta(int $tripId): ?array
    {
        try {
            // 2-1. SQL 작성
            $sql =
            '
        SELECT trip_id, start_date, end_date, day_count
        FROM Trip
        WHERE trip_id = :trip_id
        LIMIT 1
      ';

            // 2-2. 쿼리 실행 및 반환
            return $this->fetchOne($sql, [
              'trip_id' => $tripId
            ]);
        } catch (Throwable $e) {
            \error_log('[TripDaysRepository::getTripDayMeta][PDO] ' . $e->getMessage());
            throw new DbException('TRIPDAY_META_FETCH_FAILED', 'trip 메타정보 조회 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }

    // 3. 해당 day_no 존재 여부
    public function existsDayNo(int $tripId, int $dayNo): bool
    {
        try {
            // 3-1. SQL 작성
            $sql =
            '
        SELECT EXISTS(
          SELECT 1 
          FROM TripDay 
          WHERE trip_id = :trip_id AND day_no = :day_no
        ) AS result;
      ';

            // 3-2. 쿼리 실행
            $result = (int) $this->query($sql, [
              'trip_id' => $tripId,
              'day_no' => $dayNo
            ])->fetchColumn();

            // 3-3. 결과 반환
            return $result === 1;
        } catch (Throwable $e) {
            \error_log('[TripDaysRepository::existsDayNo][PDO] ' . $e->getMessage());
            throw new DbException('TRIPDAY_DAYNO_CHECK_FAILED', 'trip 일차 존재 여부 확인 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }

    // 4. 해당 trip의 최대 day_no 조회
    // - 값이 없을 경우 0 반환
    public function getMaxDayNo(int $tripId): int
    {
        try {
            // 4-1. SQL 작성
            $sql =
            '
        SELECT COALESCE(MAX(day_no), 0) AS max_day_no
        FROM TripDay
        WHERE trip_id = :trip_id
      ';

            // 4-2. 쿼리 실행 및 결과 반환
            return (int) $this->query($sql, [
              'trip_id' => $tripId
            ])->fetchColumn();
        } catch (Throwable $e) {
            \error_log('[TripDaysRepository::getMaxDayNo][PDO] ' . $e->getMessage());
            throw new DbException('TRIPDAY_MAX_DAYNO_FETCH_FAILED', 'trip 일차 최대값 조회 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }

    // 5. 중간 삽입 시 day_no 조정
    // - day_no >= $addDay 인 일차들의 day_no를 +1 씩 증가
    public function shiftDayNos(int $tripId, int $addDay): bool
    {
        try {
            // 5-1. SQL 작성
            $sql =
            '
        UPDATE TripDay
        SET day_no = day_no + 1, updated_at = NOW()
        WHERE trip_id = :trip_id AND day_no >= :day_no
        ORDER BY day_no DESC
      ';

            // 5-2. 쿼리 실행 및 영향 받은 행(row) 수 반환
            return $this->execute($sql, [
              'trip_id' => $tripId,
              'day_no' => $addDay
            ]) >= 0;
        } catch (Throwable $e) {
            \error_log('[TripDaysRepository::shiftDayNos][PDO] ' . $e->getMessage());
            throw new DbException('TRIPDAY_DAYNO_SHIFT_FAILED', 'trip 밀어내기 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }

    // 6. tripday 단건 삽입
    // - 성공시 trip_day_id 반환, 실패시 예외 던짐
    public function insertTripDay(
        int $tripId,
        int $dayNo,
        ?string $memo = null
    ): int {
        try {
            // 6-1. SQL 작성
            $sql = '
          INSERT INTO TripDay 
          (trip_id, day_no, memo, created_at, updated_at)
          VALUES (:trip_id, :day_no, :memo, NOW(), NOW())
      ';

            // 6-2. 쿼리 실행
            $this->execute($sql, [
              'trip_id' => $tripId,
              'day_no' => $dayNo,
              'memo' => $memo,
            ]);

            // 6-3. 삽입된 trip_day_id 반환
            $id = $this->lastInsertId();

            // 6-4. 결과 반환
            if ($id <= 0) {
                throw new DbException('TRIPDAY_INSERT_FAILED', '트립 일차 생성에 실패했습니다.');
            }
            return $id;
        } catch (Throwable $e) {
            \error_log('[TripDaysRepository::insertTripDay][PDO] ' . $e->getMessage());
            throw new DbException('TRIPDAY_INSERT_FAILED', '트립 일차 생성 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }

    // 7. tripday를 실제 개수로 동기화
    public function updateTripDayCount(int $tripId): bool
    {
        try {
            // 7-1. SQL 작성
            $sql = '
          UPDATE Trip
          SET updated_at = NOW()
          WHERE trip_id = :trip_id
      ';
            // 7-2. 쿼리 실행 및 성공 여부 반환
            return $this->execute($sql, [
              'trip_id' => $tripId
            ]) >= 0;
        } catch (Throwable $e) {
            \error_log('[TripDaysRepository::updateTripDayCount][PDO] ' . $e->getMessage());
            throw new DbException('TRIPDAY_COUNT_UPDATE_FAILED', 'trip 개수 동기화 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }

    // 8. tripday 생성 메인 로직
    // - $dayNo가 null일 경우 마지막에 추가, 아닐경우 중간 삽입
    // - 성공시 trip_day_id 반환, 실패시 예외 던짐
    public function createTripDay(int $tripId, ?int $dayNo = null, ?string $memo = null): int
    {
        try {
            // 8-1. 현재 Trip 존재 확인
            $trip = $this->getTripMeta($tripId);
            if ($trip === null) {
                throw new DbException('TRIP_NOT_FOUND', '해당하는 여행이 존재하지 않습니다.');
            }

            // 8-2. max day_no 조회
            $maxDayNo = $this->getMaxDayNo($tripId);
            // 8-3. dayNo가 null일 경우 마지막에 추가
            $targetDayNo = $dayNo !== null ? \max(1, $dayNo) : ($maxDayNo + 1);

            // 8-4. 유효한 day_no인지 확인
            if ($targetDayNo > $maxDayNo + 1) {
                throw new DbException('TRIPDAY_INVALID_DAYNO', '유효하지 않은 일차 번호입니다.');
            }

            // 8-5. 중간 삽입일 경우 day_no 조정
            if ($targetDayNo <= $maxDayNo) {
                $this->shiftDayNos($tripId, $targetDayNo);
            }

            // 8-6. tripday 삽입
            $newId = $this->insertTripDay($tripId, $targetDayNo, $memo);

            // 8-7. 새로운 id 반환
            return $newId;
        } catch (Throwable $e) {
            \error_log('[TripDaysRepository::createTripDay][PDO] ' . $e->getMessage());
            throw new DbException('TRIPDAY_CREATE_FAILED', 'trip day 생성 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }

    // 9. tripday 단건 조회
    // - 조회 성공시 배열 반환, 실패시(존재하지 않을 경우) null 반환
    // - date 계산 포함
    public function findByTripAndDayNo(int $tripId, int $dayNo): ?array
    {
        try {
            // 9-1. SQL 작성
            $sql = '
          SELECT 
            td.trip_day_id, td.trip_id, td.day_no, td.memo,
            DATE_ADD(t.start_date, INTERVAL (td.day_no - 1) DAY) AS date
          FROM TripDay td
          JOIN Trip t ON td.trip_id = t.trip_id
          WHERE td.trip_id = :trip_id AND td.day_no = :day_no
          LIMIT 1
      ';

            // 9-2. 쿼리 실행 및 단건 조회
            return $this->fetchOne($sql, [
              'trip_id' => $tripId,
              'day_no' => $dayNo,
            ]);
        } catch (Throwable $e) {
            throw new DbException('TRIPDAY_FETCH_FAILED', 'trip 조회 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }

    // 10. trip_day_id 조회
    // - 조회 성공시 trip_day_id 반환, 실패시(존재하지 않을 경우) null 반환
    public function getTripDayId(int $tripId, int $dayNo): ?int
    {
        try {
            // 10-1. SQL 작성
            $sql = '
          SELECT trip_day_id
          FROM TripDay
          WHERE trip_id = :trip_id AND day_no = :day_no
          LIMIT 1
      ';

            // 10-2. 쿼리 실행 및 반환
            $result = $this->query($sql, [
              'trip_id' => $tripId,
              'day_no' => $dayNo,
            ])->fetchColumn();

            // 10-3. 결과 반환
            return $result === false ? null : (int)$result;
        } catch (Throwable $e) {
            \error_log('[TripDaysRepository::getTripDayId][PDO] ' . $e->getMessage());
            throw new DbException('TRIPDAY_ID_FETCH_FAILED', 'trip_day_id 조회 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }

    // 11. tripday 행 삭제
    public function deleteTripDay(int $tripDayId): bool
    {
        try {
            // 11-1. SQL 작성
            $sql = '
          DELETE FROM TripDay 
          WHERE trip_day_id = :trip_day_id
      ';

            // 11-2. 쿼리 실행 및 성공 여부 반환
            return $this->execute($sql, [
              'trip_day_id' => $tripDayId
            ]) > 0;
        } catch (Throwable $e) {
            \error_log('[TripDaysRepository::deleteTripDay][PDO] ' . $e->getMessage());
            throw new DbException('TRIPDAY_DELETE_FAILED', 'tripday 삭제 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }

    // 12. 삭제 후 day_no 재조정
    // - 삭제된 day_no 이후의 일차들을 -1 씩 감소
    public function reorderDayNos(int $tripId, int $deletedDayNo): bool
    {
        try {
            // 12-1. SQL 작성
            $sql = '
        UPDATE TripDay
        SET day_no = day_no - 1, updated_at = NOW()
        WHERE trip_id = :trip_id AND day_no > :day_no
      ';

            // 12-2. 쿼리 실행 및 성공 여부 반환
            return $this->execute($sql, [
              'trip_id' => $tripId,
              'day_no' => $deletedDayNo
            ]) >= 0;
        } catch (Throwable $e) {
            \error_log('[TripDaysRepository::reorderDayNos][PDO] ' . $e->getMessage());
            throw new DbException('TRIPDAY_DAYNO_REORDER_FAILED', 'trip 일차 재조정 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }

    // 13. tripday 삭제 메인 메서드
    // - day_no 기준 삭제 및 재조정
    public function deleteTripDayById(int $tripId, int $dayNo): bool
    {
        try {
            // 13-1. tripday ID 조회
            $tripDayId = $this->getTripDayId($tripId, $dayNo);
            if ($tripDayId === null) {
                return false;
            }

            // 13-2. tripday  삭제
            if (!$this->deleteTripDay($tripDayId)) {
                return false;
            }

            // 13-3. day_no 재조정 및 반환
            return $this->reorderDayNos($tripId, $dayNo);
        } catch (Throwable $e) {
            \error_log('[TripDaysRepository::deleteTripDayById][PDO] ' . $e->getMessage());
            throw new DbException('TRIPDAY_DELETE_FAILED', 'trip 일차 삭제 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }

    // 일차 목록 조회
    public function selectTripDays($tripId, $userId)
    {
        try {
            $sql = 'SELECT * 
              FROM TripDay 
              WHERE trip_id=:trip_id
              ORDER BY day_no;';

            $param = ['trip_id' => $tripId];

            // 값 반환
            $data = $this->fetchAll($sql, $param);
            return $data;
        } catch (Throwable $e) {
            throw new DbException('TRIPDAY_LIST_FAIL', '일차 목록 중 오류가 발생하였습니다.', $e);
        }
    }

    // 일차 메모 수정
    public function updateTripdayNoteEdit($tripId, $dayNo, $memo): ?array
    {
        try {
            $sql = 'UPDATE TripDay
              SET memo = :memo
              WHERE trip_id = :trip_id AND day_no = :day_no;';
            $param = ['memo' => $memo, 'trip_id' => $tripId, 'day_no' => $dayNo];
            $updateSql = $this->execute($sql, $param);

            // 수정된 행이 없을 경우
            if ($updateSql === 0) {
                return null;
            }

            // 조회
            $selectSql = 'SELECT *
                  FROM TripDay 
                  WHERE trip_id = :trip_id AND day_no = :day_no;';
            $selectParam = ['trip_id' => $tripId, 'day_no' => $dayNo];

            return $this->fetchOne($selectSql, $selectParam);
        } catch (Throwable $e) {
            throw new DbException('TRIPDAY_NOTE_ERROR', '메모 수정에 실패했습니다.', $e);
        }
    }

    // 일차 재배치
    public function updateRelocationDays($tripId, $orders): array
    {
        try {
            // 큰 수를 임시로 day_no에 업데이트
            $updateSql = 'UPDATE TripDay
                        SET day_no = day_no + 1000
                        WHERE trip_id = :trip_id 
                        AND day_no = :day_no;';

            // 반복하여 쿼리 업데이트
            foreach ($orders as $order) {
                $updateParam = ['trip_id' => $tripId, 'day_no' => $order['day_no']];
                $this->query($updateSql, $updateParam);
            }

            // 일차 재정렬 쿼리 작성
            $upSql = 'UPDATE TripDay
                  SET day_no = :new_day_no
                  WHERE trip_id = :trip_id AND day_no = :day_no;';

            foreach ($orders as $order) {
                $dayNo = $order['day_no'];
                $newDayNo = $order['new_day_no'];
                $tempDayNo = $dayNo + 1000;

                // 실행
                $upParam = ['new_day_no' => $newDayNo, 'trip_id' => $tripId, 'day_no' => $tempDayNo];
                $this->query($upSql, $upParam);

                // 스케쥴 아이템 조정을 위한 day_id 획득
                $selectSql = 'SELECT trip_day_id FROM TripDay 
                        WHERE trip_id = :trip_id AND day_no = :day_no;';
                $selectParam = ['trip_id' => $tripId, 'day_no' => $newDayNo];

                // 실행
                $tripDayId = $this->fetchOne($selectSql, $selectParam);
                if (!$tripDayId) {
                    throw new DbException('NOT_REORDER', '재정렬 중 trip_dayid를 찾는 것에 실패하였습니다.');
                }
                $tripDayId = $tripDayId['trip_day_id'];

                // 일정 아이템의 날짜 수정
                $offset = $newDayNo - $dayNo;

                if ($offset != 0) {
                    // trip_day_id를 기준으로 스케쥴 아이템 날짜 이동 수 만큼 조정
                    $scheduleItemSql = 'UPDATE ScheduleItem si
                              JOIN TripDay td ON si.trip_day_id = td.trip_day_id
                              SET si.visit_time = DATE_ADD(si.visit_time, INTERVAL :offset DAY)
                              WHERE td.trip_day_id = :trip_day_id';
                    $scheduleItemParam = ['offset' => $offset, 'trip_day_id' => $tripDayId];
                    $this->query($scheduleItemSql, $scheduleItemParam);
                }
            }

            // 반환
            $tripDaysSql = 'SELECT trip_day_id, trip_id, day_no, memo
                        FROM TripDay
                        WHERE trip_id = :trip_id
                        ORDER BY day_no ASC';
            $tripDaysParam = ['trip_id' => $tripId];

            return $this->fetchAll($tripDaysSql, $tripDaysParam);
        } catch (Throwable $e) {
            throw new DbException('NOT_TRIPDAT_REORDER', '날짜 재정렬에 실패하였습니다.', $e);
        }
    }
}
