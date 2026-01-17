<?php

// namespace 작성

namespace Tripmate\Backend\Modules\ScheduleItems\Repositories;

// use 작성
use PDO;
use Throwable;
use Tripmate\Backend\Common\Exceptions\DbException;
use Tripmate\Backend\Core\DB;
use Tripmate\Backend\Core\Repository;

// 3. ScheduleItemsRepository 클래스 정의
class ScheduleItemsRepository extends Repository
{
    // 생성자에서 DB 접속 및 pdo 초기화
    public function __construct(PDO $pdo)
    {
        // 반드시 같은 PDO 인스턴스를 사용
        parent::__construct($pdo);
    }

    // 1. tripday 존재 확인 + 잠금 (sql_no 중복 방지)
    public function lockTripDay(int $tripDayId): bool
    {
        try {
            // 1-1 SQL 작성
            $sql = 'SELECT trip_day_id 
                FROM TripDay 
                WHERE trip_day_id = :trip_day_id 
                FOR UPDATE
                ';

            // 1-2. 쿼리 실행
            $row = $this->fetchOne($sql, [
              'trip_day_id' => $tripDayId
            ]);

            // 1-3. 존재 여부 반환
            return $row !== null;
        } catch (DbException $e) {
            \error_log('[ScheduleItemsRepository::lockTripDay][PDO] ' . $e->getMessage());
            throw new DbException('SCHEDULE_ITEM_LOCK_TRIPDAY_FAILED', 'tripday 확인을 실패하였습니다.', $e);
        }
    }

    // 2. 다음 seq_no 계산
    public function getNextSeqNo(int $tripDayId): int
    {
        try {
            // 2-1. SQL 작성
            $sql = 'SELECT COALESCE(MAX(seq_no), 0) + 1 AS next_seq_no 
                FROM ScheduleItem 
                WHERE trip_day_id = :trip_day_id
                ';

            // 2-2. 쿼리 실행
            $row = $this->fetchOne($sql, [
              'trip_day_id' => $tripDayId
            ]);

            // 2-3. 다음 seq_no 반환
            // - 존재하지 않을 경우 1 반환
            return (int)($row['next_seq_no'] ?? 1);
        } catch (DbException $e) {
            \error_log('[ScheduleItemsRepository::getNextSeqNo][PDO] ' . $e->getMessage());
            throw new DbException('SCHEDULE_ITEM_GET_NEXT_SEQNO_FAILED', '다음 seq_no 계산을 실패하였습니다.', $e);
        }
    }

    // 3. schedule_item 생성 메서드
    public function insertScheduleItem(
        int $tripDayId,
        ?int $placeId,
        int $seqNo,
        ?string $visitTime,
        ?string $memo
    ): int {
        try {
            // 3-1. SQL 작성
            $sql = '
            INSERT INTO ScheduleItem 
            (trip_day_id, place_id, seq_no, visit_time, memo, created_at, updated_at)
            VALUES (:trip_day_id, :place_id, :seq_no, :visit_time, :memo, NOW(), NOW())
        ';

            // 3-2. 쿼리 실행 및 schedule_item ID 반환
            $id = $this->execute($sql, [
              'trip_day_id' => $tripDayId,
              'place_id' => $placeId,
              'seq_no' => $seqNo,
              'visit_time' => $visitTime,
              'memo' => $memo,
            ]);

            $id = $this->lastInsertId();
            if ($id === 0) {
                throw new DbException('SCHEDULE_ITEM_INSERT_NO_ID', '일정 아이템 생성 후 ID 조회에 실패했습니다.');
            }

            return $id;
        } catch (DbException $e) {
            \error_log('[ScheduleItemsRepository::insertScheduleItem][PDO] ' . $e->getMessage());
            throw new DbException('SCHEDULE_ITEM_INSERT_FAILED', '일정 아이템 생성 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }

    // 4. schedule_item 추가 메인 메서드
    public function createScheduleItem(
        int $tripDayId,
        ?int $placeId,
        ?string $visitTime,
        ?string $memo
    ): int {
        try {
            // 4-1. 부모 tripday 잠금
            if (!$this->lockTripDay($tripDayId)) {
                throw new DbException('SCHEDULE_ITEM_CREATE_TRIPDAY_NOT_FOUND', '해당하는 tripday를 찾을 수 없습니다.');
            }

            // 4-2. 다음 seq_no 계산
            $nextSeqNo = $this->getNextSeqNo($tripDayId);

            // 4-3. 일정 아이템 생성
            $scheduleItemId = $this->insertScheduleItem(
                $tripDayId,
                $placeId,
                $nextSeqNo,
                $visitTime,
                $memo
            );

            // 4-4. 생성된 일정 아이템 ID 반환
            return $scheduleItemId;
        } catch (DbException $e) {
            \error_log('[ScheduleItemsRepository::createScheduleItem][PDO] ' . $e->getMessage());
            throw new DbException('SCHEDULE_ITEM_CREATE_FAILED', '일정 아이템 생성 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }

    // 5. 일정 아이템 목록 조회 메서드
    public function getScheduleItemsByTripDayId(int $tripDayId): array
    {
        try {
            // 5-1. SQL 작성
            $sql = 'SELECT 
                item.schedule_item_id,
                item.place_id,
                item.seq_no,
                item.visit_time,
                item.memo,
                place.name AS place_name,
                place.address AS place_address,
                place.lat AS place_lat,
                place.lng AS place_lng
              FROM 
                ScheduleItem AS item
              LEFT JOIN 
                Place AS place ON item.place_id = place.place_id
              WHERE 
                item.trip_day_id = :trip_day_id
              ORDER BY 
                item.seq_no ASC';

            // 5-2. 쿼리 실행 및 결과 반환
            return $this->fetchAll($sql, [
              'trip_day_id' => $tripDayId
            ]);
        } catch (DbException $e) {
            \error_log('[ScheduleItemsRepository::getScheduleItemsByTripDayId][PDO] ' . $e->getMessage());
            throw new DbException('SCHEDULE_ITEM_FETCH_FAILED', '일정 아이템 목록 조회 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }


    // 6. 일정 아이템 부분 수정메서드 (visittime, memo)
    // - 수정된 일정 아이템 배열 반환
    public function updateScheduleItem(
        int $scheduleItemId,
        ?string $visitTime,
        ?string $memo
    ): array {

        try {
            // 6-1. vistit_time, memo가 없을 경우 null 처리
            $visitTime = ($visitTime === '') ? null : $visitTime;
            $memo = ($memo === '') ? null : $memo;

            // 6-2. SQL 작성
            $sql = 'UPDATE 
                  ScheduleItem 
                SET 
                  visit_time = :visit_time,
                  memo = :memo,
                  updated_at = NOW()
                WHERE 
                  schedule_item_id = :schedule_item_id
                ';

            // 6-3. 쿼리 실행
            $this->execute($sql, [
              'visit_time' => $visitTime,
              'memo' => $memo,
              'schedule_item_id' => $scheduleItemId
            ]);

            // 6-4. 수정된 일정 아이템 조회 및 반환
            $sqlSelect = '
          SELECT
            schedule_item_id,
            trip_day_id,
            place_id,
            seq_no,
            visit_time,
            memo,
            created_at,
            updated_at
          FROM
            ScheduleItem
          WHERE
            schedule_item_id = :schedule_item_id
        ';

            $updatedItem = $this->fetchOne($sqlSelect, [
              'schedule_item_id' => $scheduleItemId
            ]);

            // 6-5. 수정된 일정 아이템이 없을 경우 예외 처리
            if ($updatedItem === null) {
                throw new DbException('SCHEDULE_ITEM_UPDATE_NOT_FOUND', '수정된 일정 아이템을 찾을 수 없습니다.');
            }

            // 6-6. 수정된 일정 아이템 배열 반환
            return $updatedItem;
        } catch (DbException $e) {
            \error_log('[ScheduleItemsRepository::updateScheduleItem][PDO] ' . $e->getMessage());
            throw new DbException('SCHEDULE_ITEM_UPDATE_FAILED', '일정 아이템 수정 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }

    // 7. 일정 아이템 단건 삭제 메서드
    public function deleteScheduleItem(int $scheduleItemId): bool
    {
        try {
            // 7-1. SQL 작성
            $sql = '
          DELETE FROM ScheduleItem 
          WHERE schedule_item_id = :schedule_item_id
      ';

            // 7-2. 쿼리 실행 및 성공 여부 반환
            // - 영향 받은 행(row) 수가 0보다 크면 성공
            return $this->execute($sql, [
              'schedule_item_id' => $scheduleItemId
            ]) > 0;
        } catch (Throwable $e) {
            \error_log('[ScheduleItemsRepository::deleteScheduleItem][PDO] ' . $e->getMessage());
            throw new DbException('SCHEDULE_ITEM_DELETE_FAILED', '일정 아이템 삭제 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }

    // 8. seq_no 재정렬 메서드 (
    // seq_no > :deleteSeqNo 인 schedule_item들의 seq_no를 -1씩 감소
    public function reorderSeqNosAfterDeletion(int $tripDayId, int $deleteSeqNo): bool
    {
        try {
            // 8-1. SQL 작성
            $sql = '
          UPDATE ScheduleItem
          SET seq_no = seq_no - 1, updated_at = NOW()
          WHERE trip_day_id = :trip_day_id
            AND seq_no > :delete_seq_no
      ';

            // 8-2. 쿼리 실행 및 성공 여부 반환
            // - 영향 받은 행(row) 수가 0보다 크면 성공
            return $this->execute($sql, [
              'trip_day_id' => $tripDayId,
              'delete_seq_no' => $deleteSeqNo
            ]) >= 0;
        } catch (DbException $e) {
            \error_log('[ScheduleItemsRepository::reorderSeqNosAfterDeletion][PDO] ' . $e->getMessage());
            throw new DbException('SCHEDULE_ITEM_REORDER_SEQNO_FAILED', '일정 아이템 seq_no 재정렬 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }

    // 9. 일정 아이템 삭제 메인 메서드
    public function deleteScheduleDayById(
        int $tripId,
        int $dayNo,
        int $scheduleItemId
    ): bool {
        try {
            // 9-1. 삭제 대상 조회 (trip_day_id / seq_no)
            $findSql = '
        SELECT si.trip_day_id, si.seq_no
        FROM ScheduleItem si
        JOIN TripDay td ON td.trip_day_id = si.trip_day_id
        JOIN Trip t     ON t.trip_id = td.trip_id
        WHERE t.trip_id = :trip_id
          AND td.day_no = :day_no
          AND si.schedule_item_id = :schedule_item_id
      ';

            // 9-2. 쿼리 실행
            $row = $this->fetchOne($findSql, [
              'trip_id' => $tripId,
              'day_no' => $dayNo,
              'schedule_item_id' => $scheduleItemId
            ]);

            // 9-3. 삭제 대상이 없을 경우 false 반환
            if ($row === null) {
                throw new DbException('SCHEDULE_ITEM_DELETE_NOT_FOUND', '삭제할 일정 아이템을 찾을 수 없습니다.');
            }

            /// 9-4. trip_day_id, seq_no 추출
            $tripDayId = (int)$row['trip_day_id'];
            $seqNo = (int)$row['seq_no'];

            // 9-5. 일정 아이템 삭제
            if (!$this->deleteScheduleItem($scheduleItemId)) {
                throw new DbException('SCHEDULE_ITEM_DELETE_FAILED', '일정 아이템 삭제에 실패했습니다.');
            }

            // 9-6. seq_no 재정렬
            if (!$this->reorderSeqNosAfterDeletion($tripDayId, $seqNo)) {
                throw new DbException('SCHEDULE_ITEM_REORDER_SEQNO_FAILED', '일정 아이템 seq_no 재정렬에 실패했습니다.');
            }

            // 9-7. 성공 시 true 반환
            return true;
        } catch (DbException $e) {
            \error_log('[ScheduleItemsRepository::deleteScheduleDayById][PDO] ' . $e->getMessage());
            throw new DbException('SCHEDULE_ITEM_DELETE_BY_DAYID_FAILED', '일정 아이템 삭제 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }

    // 10. 같은 tripday의 scheduleitem 잠금 메서드
    public function lockScheduleItems(int $tripDayId): bool
    {
        try {
            // 10-1. SQL 작성
            $sql = 'SELECT schedule_item_id 
              FROM ScheduleItem 
              WHERE trip_day_id = :trip_day_id 
              FOR UPDATE
              ';

            // 10-2. 쿼리 실행
            $this->fetchAll($sql, [
              'trip_day_id' => $tripDayId
            ]);

            // 10-3. 성공 시 true 반환
            return true;
        } catch (DbException $e) {
            \error_log('[ScheduleItemsRepository::lockScheduleItems][PDO] ' . $e->getMessage());
            throw new DbException('SCHEDULE_ITEM_LOCK_FAILED', '일정 아이템 잠금 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }

    // 11. 특정 item이 속한 trip_day_id 조회 메서드
    public function getTripDayIdByItemId(int $scheduleItemId): int|false
    {
        try {
            // 11-1 SQL 작성
            $sql = 'SELECT trip_day_id 
              FROM ScheduleItem 
              WHERE schedule_item_id = :schedule_item_id';

            // 11-2. 쿼리 실행
            $row = $this->fetchOne($sql, [
              'schedule_item_id' => $scheduleItemId
            ]);

            // 11-3. 결과가 없을 경우 false 반환
            if ($row === null) {
                throw new DbException('SCHEDULE_ITEM_GET_TRIPDAYID_NOT_FOUND', '일정 아이템의 trip_day_id를 찾을 수 없습니다.');
            }

            // 11-4. 성공 시 trip_day_id 반환
            return (int)$row['trip_day_id'];
        } catch (DbException $e) {
            \error_log('[ScheduleItemsRepository::getTripDayIdByItemId][PDO] ' . $e->getMessage());
            throw new DbException('SCHEDULE_ITEM_GET_TRIPDAYID_FAILED', '일정 아이템의 trip_day_id 조회 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }

    // 12. 특정 item의 trip_day_id, seq_no 잠금 조회 메서드
    public function lockTripIdAndSeqNoByItemId(int $scheduleItemId): array
    {
        try {
            // 12-1 SQL 작성
            $sql = '
        SELECT trip_day_id, seq_no
        FROM ScheduleItem
        WHERE schedule_item_id = :schedule_item_id
        FOR UPDATE
        ';

            // 12-2. 쿼리 실행
            $row = $this->fetchOne($sql, [
              'schedule_item_id' => $scheduleItemId
            ]);

            // 12-3. 결과가 없을 경우 예외 처리
            if ($row === null) {
                throw new DbException('SCHEDULE_ITEM_LOCK_TRIPDAYID_SEQNO_NOT_FOUND', '일정 아이템의 trip_day_id와 seq_no를 찾을 수 없습니다.');
            }

            // 12-4. 성공 시 trip_day_id, seq_no 배열 반환
            return [
              'trip_day_id' => (int)$row['trip_day_id'],
              'seq_no' => (int)$row['seq_no']
            ];
        } catch (DbException $e) {
            \error_log('[ScheduleItemsRepository::lockTripIdAndSeqNoByItemId][PDO] ' . $e->getMessage());
            throw new DbException('SCHEDULE_ITEM_LOCK_TRIPDAYID_SEQNO_FAILED', '일정 아이템의 trip_day_id, seq_no 잠금 조회 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }

    // 13. 단일 item 이동을 위한 이동 메서드
    public function shiftScheduleItemSeqNo(
        int $tripDayId, // trip_day_id
        int $fromSeqNo, // 이동 전 seq_no
        int $toSeqNo, // 이동 후 seq_no
        int $moveItemId // 이동 대상 schedule_item_id
    ): bool {
        try {
            // 13-1. SQL 작성
            if ($fromSeqNo < $toSeqNo) {
                // 아래로 이동 시
                $sql = '
          UPDATE ScheduleItem
          SET seq_no = seq_no - 1
          WHERE trip_day_id = :trip_day_id
            AND seq_no > :from_seq_no
            AND seq_no <= :new_seq_no
            AND schedule_item_id <> :move_item_id
        ';
                $params = [
                  'trip_day_id'  => $tripDayId,
                  'from_seq_no'  => $fromSeqNo,
                  'new_seq_no'   => $toSeqNo,
                  'move_item_id' => $moveItemId
                ];
            } else {
                // 위로 이동 시
                $sql = '
          UPDATE ScheduleItem
          SET seq_no = seq_no + 1
          WHERE trip_day_id = :trip_day_id
            AND seq_no >= :new_seq_no
            AND seq_no < :from_seq_no
            AND schedule_item_id <> :move_item_id
        ';
                $params = [
                  'trip_day_id'  => $tripDayId,
                  'from_seq_no'  => $fromSeqNo,
                  'new_seq_no'   => $toSeqNo,
                  'move_item_id' => $moveItemId
                ];
            }

            // 13-2. 쿼리 실행 및 성공 여부 반환
            $this->execute($sql, $params);
            return true;
        } catch (DbException $e) {
            \error_log('[ScheduleItemsRepository::shiftScheduleItemSeqNo][PDO] ' . $e->getMessage());
            throw new DbException('SCHEDULE_ITEM_SHIFT_SEQNO_FAILED', '일정 아이템 seq_no 이동 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }

    // 14. 단일 일정아이템 seq_no 업데이트 메서드
    public function updateItemSeqNo(int $scheduleItemId, int $newSeqNo): bool
    {
        try {
            // 14-1. SQL 작성
            $sql = '
        UPDATE ScheduleItem
        SET seq_no = :new_seq_no, updated_at = NOW()
        WHERE schedule_item_id = :schedule_item_id
      ';

            // 14-2. 쿼리 실행 및 성공 여부 반환
            $this->execute($sql, [
              'new_seq_no' => $newSeqNo,
              'schedule_item_id' => $scheduleItemId,
            ]);

            return true;
        } catch (DbException $e) {
            \error_log('[ScheduleItemsRepository::updateItemSeqNo][PDO] ' . $e->getMessage());
            throw new DbException('SCHEDULE_ITEM_UPDATE_SEQNO_FAILED', '일정 아이템 seq_no 업데이트 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }

    // 15. 단일 일정아이템 재배치 메인 메서드
    public function reorderSingleScheduleItem(int $scheduleItemId, int $newSeqNo): array
    {
        try {
            // 15-1. 이동 대상의 trip_day_id, seq_no 잠금 조회
            $itemInfo = $this->lockTripIdAndSeqNoByItemId($scheduleItemId);
            $tripDayId = $itemInfo['trip_day_id'];
            $oldSeqNo = $itemInfo['seq_no'];

            // 15-2. 같은 tripday의 일정아이템 잠금
            $this->lockScheduleItems($tripDayId);

            // 15-3. 동일 순번이면 현재 목록 반환
            if ($newSeqNo === $oldSeqNo) {
                return $this->getScheduleItemsByTripDayId($tripDayId);
            }

            // 15-4. 이동 대상 임시 크게 설정 (충돌 방지)
            $this->updateItemSeqNo($scheduleItemId, 1000000);

            // 15-5. 다른 일정아이템 seq_no 조정
            $this->shiftScheduleItemSeqNo(
                $tripDayId,
                $oldSeqNo,
                $newSeqNo,
                $scheduleItemId
            );

            // 15-6. 이동 대상의 최종 seq_no 설정
            $this->updateItemSeqNo($scheduleItemId, $newSeqNo);

            // 15-7. 재배치된 일정아이템 목록 반환
            return $this->getScheduleItemsByTripDayId($tripDayId);
        } catch (DbException $e) {
            \error_log('[ScheduleItemsRepository::reorderSingleScheduleItem][PDO] ' . $e->getMessage());
            throw new DbException('SCHEDULE_ITEM_REORDER_SINGLE_FAILED', '단일 일정 아이템 재배치 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }

    // 16. max seq_no 조회 메서드
    public function getMaxSeqNo(int $tripDayId): int
    {
        try {
            // 16-1. SQL 작성
            $sql = '
        SELECT MAX(seq_no) AS max_seq_no
        FROM ScheduleItem
        WHERE trip_day_id = :trip_day_id
      ';

            // 16-2. 쿼리 실행
            $row = $this->fetchOne($sql, [
              'trip_day_id' => $tripDayId
            ]);

            // 16-3. 최대 seq_no 반환 (없을 경우 0)
            return (int)($row['max_seq_no'] ?? 0);
        } catch (DbException $e) {
            \error_log('[ScheduleItemsRepository::getMaxSeqNo][PDO] ' . $e->getMessage());
            throw new DbException('SCHEDULE_ITEM_GET_MAX_SEQNO_FAILED', '최대 seq_no 조회 중 데이터베이스 오류가 발생했습니다.', $e);
        }
    }
}
