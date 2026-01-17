<?php

// namespace 작성

namespace Tripmate\Backend\Modules\ScheduleItems\Services;

// use 작성
use PDO;
use Tripmate\Backend\Common\Exceptions\DbException;
use Tripmate\Backend\Common\Exceptions\HttpException;
use Tripmate\Backend\Core\Service;
use Tripmate\Backend\Modules\ScheduleItems\Repositories\ScheduleItemsRepository;
use Tripmate\Backend\Modules\TripDays\Repositories\TripDaysRepository;

// ScheduleItemsService
// - 공통 Service 상속 받아 사용
// - ScheduleItems 관련 비즈니스 로직 처리
class ScheduleItemsService extends Service
{
    // 4. 프러퍼티 정의
    public ScheduleItemsRepository $scheduleItemsRepository;
    public TripDaysRepository $tripDaysRepository;

    // 5. 생성자에서 ScheduleItemsRepository 초기화
    public function __construct(
        PDO $pdo,
        ?ScheduleItemsRepository $scheduleItemsRepository = null,
        ?TripDaysRepository $tripDaysRepository = null
    ) {
        parent::__construct($pdo);
        $this->scheduleItemsRepository = $scheduleItemsRepository ?? new ScheduleItemsRepository($pdo);
        $this->tripDaysRepository      = $tripDaysRepository ?? new TripDaysRepository($pdo);
    }

    // trip_day 확인 + 소유권 검사 후 trip_day_id 반환 헬퍼 메서드
    private function verifyTripDayOwnership(int $userId, int $tripId, int $dayNo): int
    {
        // trip_day 존재 여부
        $tripDayId = $this->tripDaysRepository->getTripDayId($tripId, $dayNo);
        // trip_day_id 없으면 예외 발생
        if ($tripDayId === null || $tripDayId === false) {
            throw new HttpException(404, 'TRIP_DAY_NOT_FOUND', '해당하는 여행 일정이 존재하지 않습니다.');
        }

        // 소유권 확인
        $isOwner = $this->tripDaysRepository->isTripOwner($tripId, $userId);
        if (!$isOwner) {
            throw new HttpException(403, 'FORBIDDEN', '해당 여행에 대한 접근 권한이 없습니다.');
        }

        // 검증 통과 시 trip_day_id 반환
        return $tripDayId;
    }

    // 1. 일정 추가 메서드
    public function createScheduleItem(
        int $userId,
        int $tripId,
        int $dayNo,
        ?int $placeId,
        ?string $visitTime,
        ?string $memo
    ): int {
        try {
            // 1-1. 트래잭션 시작
            return $this->transaction(function (PDO $pdo) use ($userId, $tripId, $dayNo, $placeId, $visitTime, $memo): int {
                // 1-2. trip_day 확인 + 소유권 검사
                $tripDayId = $this->verifyTripDayOwnership($userId, $tripId, $dayNo);

                // 1-3. ScheduleItemsRepository의 createScheduleItem 메서드 호출
                $scheduleItemId = $this->scheduleItemsRepository->createScheduleItem(
                    $tripDayId,
                    $placeId,
                    $visitTime,
                    $memo
                );

                // 1-4. 생성된 scheduleItemId 반환
                return $scheduleItemId;
            });
        } catch (DbException) {
            throw new HttpException(500, 'SCHEDULE_ITEM_CREATION_FAILED', '일정 아이템 생성에 실패했습니다.');
        }
    }

    // 2. 일정 목록 조회 메서드
    public function getScheduleItems(
        int $userId,
        int $tripId,
        int $dayNo
    ): array {
        try {
            // 2-1. trip_day 확인 + 소유권 검사
            $tripDayId = $this->verifyTripDayOwnership($userId, $tripId, $dayNo);

            //2-2. ScheduleItemsRepository의 getScheduleItemsByTripDayId 메서드 호출
            return $this->scheduleItemsRepository->getScheduleItemsByTripDayId($tripDayId);
        } catch (DbException) {
            throw new HttpException(500, 'SCHEDULE_ITEMS_RETRIEVAL_FAILED', '일정 아이템 조회에 실패했습니다.');
        }
    }

    // 3. 일정 아이템 부분 수정 메서드 (visit_time, memo)
    public function updateScheduleItem(
        int $userId,
        int $tripId,
        int $itemId,
        int $dayNo,
        ?string $visitTime,
        ?string $memo
    ): array {
        try {
            return $this->transaction(function (PDO $pdo) use ($userId, $tripId, $itemId, $dayNo, $visitTime, $memo): array {
                // 3-1. trip_day 확인 + 소유권 검사
                $tripDayId = $this->verifyTripDayOwnership($userId, $tripId, $dayNo);

                // 3-2. 일정 아이템이 해당 trip_day에 속하는지 검증
                $itemsTripDayId = $this->scheduleItemsRepository->getTripDayIdByItemId($itemId);
                if ($itemsTripDayId === false || $itemsTripDayId !== $tripDayId) {
                    throw new HttpException(404, 'SCHEDULE_ITEM_NOT_FOUND', '해당 일정 아이템을 찾을 수 없습니다.');
                }

                // 3-3. 업데이트 후 갱신 된 일정 아이템 목록 반환
                return $this->scheduleItemsRepository->updateScheduleItem(
                    $itemId,
                    $visitTime,
                    $memo
                );
            });
        } catch (DbException) {
            throw new HttpException(500, 'SCHEDULE_ITEM_UPDATE_FAILED', '일정 아이템 수정에 실패했습니다.');
        }
    }

    // 4. 일정 아이템 삭제 메서드
    // - 삭제 이후 seq_no 재정렬 처리
    public function deleteScheduleItem(
        int $userId,
        int $tripId,
        int $dayNo,
        int $itemId
    ): bool {
        try {
            return $this->transaction(function (PDO $pdo) use ($userId, $tripId, $dayNo, $itemId): bool {
                // 4-1. trip_day 확인 + 소유권 검사
                $this->verifyTripDayOwnership($userId, $tripId, $dayNo);

                // 4-2. 조회 후 삭제 후 제정렬 진행
                if (!$this->scheduleItemsRepository->deleteScheduleDayById($itemId, $dayNo, $itemId)) {
                    throw new DbException('SCHEDULE_ITEM_DELETE_FAILED', '일정 아이템 삭제에 실패했습니다.');
                }

                // 4-3. 삭제 및 재정렬 성공 시 true 반환
                return true;
            });
        } catch (DbException) {
            throw new HttpException(500, 'SCHEDULE_ITEM_DELETION_FAILED', '일정 아이템 삭제에 실패했습니다.');
        }
    }

    // 5. 일정 아이템 재배치 메서드
    public function reorderSingleScheduleItem(
        int $userId,
        int $tripId,
        int $dayNo,
        int $scheduleItemId,
        int $newSeqNo
    ): array {
        try {
            return $this->transaction(function (PDO $pdo) use ($userId, $tripId, $dayNo, $scheduleItemId, $newSeqNo): array {
                // 5-1. trip_day 확인 + 소유권 검사
                $tripDayId = $this->verifyTripDayOwnership($userId, $tripId, $dayNo);

                // 5-2. 일정 아이템이 해당 trip_day에 속하는지 검증
                $itemsTripDayId = $this->scheduleItemsRepository->getTripDayIdByItemId($scheduleItemId);
                if ($itemsTripDayId === false || $itemsTripDayId !== $tripDayId) {
                    throw new HttpException(404, 'SCHEDULE_ITEM_NOT_FOUND', '해당 일정 아이템을 찾을 수 없습니다.');
                }

                // 5-3. max seq_no 확인 및 보정
                $maxSeqNo = $this->scheduleItemsRepository->getMaxSeqNo($tripDayId);
                if ($maxSeqNo < 1) {
                    throw new HttpException(400, 'NO_ITEMS_TO_REORDER', '재배치할 일정 아이템이 없습니다.');
                }
                if ($newSeqNo < 1) {
                    $newSeqNo = 1;
                } elseif ($newSeqNo > $maxSeqNo) {
                    $newSeqNo = $maxSeqNo;
                }

                // 5-4. 재배치 수행 및 최신 목록 반환
                return $this->scheduleItemsRepository->reorderSingleScheduleItem(
                    $scheduleItemId,
                    $newSeqNo,
                );
            });
        } catch (DbException) {
            throw new HttpException(500, 'SCHEDULE_ITEM_REORDER_FAILED', '일정 아이템 재배치에 실패했습니다.');
        }
    }
}
