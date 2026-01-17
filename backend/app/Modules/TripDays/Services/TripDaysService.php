<?php

// 1. 네임스페이스 선언

namespace Tripmate\Backend\Modules\TripDays\Services;

// 2. TripsRepository 클래스 로드 및 Date 유틸리티 로드
use PDO;
use Tripmate\Backend\Common\Exceptions\DbException;
use Tripmate\Backend\Common\Exceptions\HttpException;
use Tripmate\Backend\Common\Utils\Date;
use Tripmate\Backend\Core\Service;
use Tripmate\Backend\Modules\TripDays\Repositories\TripDaysRepository;

// TripDaysService 클래스 정의
// - TripDays 관련 비즈니스 로직 처리
// - 공통 Service 상속 받아 사용
class TripDaysService extends Service
{
    // 프러퍼티 정의
    public TripDaysRepository $tripDaysRepository;

    // 같은 PDO로 레포를 생성해 동일 트랜잭션 공유
    public function __construct(PDO $pdo, ?TripDaysRepository $tripDaysRepository = null)
    {
        parent::__construct($pdo);
        $this->tripDaysRepository = $tripDaysRepository ?? new TripDaysRepository($pdo);
    }

    // 5. TripDay 생성
    // - 실패시 HttpException 발생
    public function addTripDay(
        int $userId,
        int $tripId,
        int $dayNo,
        ?string $memo = null
    ): int {

        try {
            // 1-1. tripId가 userId 소유인지 확인
            if (!$this->tripDaysRepository->isTripOwner($tripId, $userId)) {
                throw new HttpException(403, 'FORBIDDEN', '해당 여행에 대한 권한이 없습니다.');
            }

            // 1-2. 트랜잭션 시작
            return $this->transaction(function (PDO $pdo) use ($tripId, $dayNo, $memo): int {
                // 1-3. 삽입
                $newId = $this->tripDaysRepository->createTripDay($tripId, $dayNo, $memo);
                // 1-4. dayCount 동기화
                $this->tripDaysRepository->updateTripDayCount($tripId);
                // 1-5. 새로 생성된 tripDay ID 반환
                return $newId;
            });
        } catch (DbException) {
            throw new HttpException(500, 'TRIPDAY_CREATION_FAILED', '여행 일자 생성에 실패했습니다.');
        }
    }

    // 6. 여행 단건 조회 메서드
    public function getTripDay(
        int $userId,
        int $tripId,
        int $dayNo
    ): array {
        try {
            // 6-1. tripId가 userId 소유인지 확인
            if (!$this -> tripDaysRepository -> isTripOwner($tripId, $userId)) {
                \error_log(message: "Unauthorized access attempt by user ID: $userId for trip ID: $tripId");
                throw new HttpException(403, 'FORBIDDEN', '해당 여행에 대한 권한이 없습니다.');
            }

            // 6-2. 여행 일자 조회
            $tripDay = $this -> tripDaysRepository -> findByTripAndDayNo($tripId, $dayNo);

            // 6-3. 결과 반환
            if ($tripDay === null) {
                throw new HttpException(404, 'TRIPDAY_NOT_FOUND', '해당하는 여행 일자를 찾을 수 없습니다.');
            }
            return $tripDay;
        } catch (DbException) {
            throw new HttpException(500, 'TRIPDAY_RETRIEVAL_FAILED', '여행 일자 조회에 실패했습니다.');
        }
    }

    // 7. 여행 일자 삭제 메서드
    public function deleteTripDay(
        int $userId,
        int $tripId,
        int $dayNo
    ): bool {
        try {
            // 7-1. tripId가 userId 소유인지 확인
            if (!$this->tripDaysRepository->isTripOwner($tripId, $userId)) {
                throw new HttpException(403, 'FORBIDDEN', '해당 여행에 대한 권한이 없습니다.');
            }

            // 7-2. 트랜잭션 시작
            return $this->transaction(function (PDO $pdo) use ($tripId, $dayNo): bool {
                // 7-3. 여행 일자 삭제 + 재졍룔
                $deleted = $this->tripDaysRepository->deleteTripDayById($tripId, $dayNo);
                if (!$deleted) {
                    throw new HttpException(404, 'TRIPDAY_NOT_FOUND', '해당하는 여행 일자를 찾을 수 없습니다.');
                }
                // 7-4. dayCount 동기화
                $this->tripDaysRepository->updateTripDayCount($tripId);
                return true;
            });
        } catch (DbException) {
            throw new HttpException(500, 'TRIPDAY_DELETION_FAILED', '여행 일자 삭제에 실패했습니다.');
        }
    }

    // 8. 일차 목록 조회
    public function selectTripDaysList($tripId, $userId)
    {
        // tripId가 userId 소유인지 확인
        if (!$this->tripDaysRepository->isTripOwner($tripId, $userId)) {
            throw new HttpException(403, 'FORBIDDEN', '해당 여행에 대한 권한이 없습니다.');
        }
        try {
            return $this->tripDaysRepository->selectTripDays($tripId, $userId);
        } catch (DbException) {
            throw new HttpException(500, 'TRIPDAY_LIST_FAIL', '일차 목록 조회에 실패하였습니다.');
        }
    }

    // 9. 노트 수정
    public function UpdateTripDayNoteEdit($tripId, $dayNo, $memo, $userId)
    {
        // tripId가 userId 소유인지 확인
        if (!$this->tripDaysRepository->isTripOwner($tripId, $userId)) {
            throw new HttpException(403, 'FORBIDDEN', '해당 여행에 대한 권한이 없습니다.');
        }

        try {
            $result = $this->tripDaysRepository->updateTripdayNoteEdit($tripId, $dayNo, $memo);
            if ($result == null) {
                throw new HttpException(404, 'MEMO_EDIT_ERROR', '메모 수정에 실패하였습니다.');
            }
            return $result;
        } catch (DbException) {
            throw new HttpException(500, 'TRIP_NOTE_EDIT_FAIL', '일차 메모 수정에 실패하였습니다.');
        }
    }

    // 10. 일자 재배치
    public function updateRelocationTripDays($tripId, $orders, $userId)
    {
        // tripId가 userId 소유인지 확인
        if (!$this->tripDaysRepository->isTripOwner($tripId, $userId)) {
            throw new HttpException(403, 'FORBIDDEN', '해당 여행에 대한 권한이 없습니다.');
        }

        try {
            return $this->transaction(fn (): array => $this->tripDaysRepository->updateRelocationDays($tripId, $orders));
        } catch (DbException $e) {
            switch ($e->getCodeName()) {
                case 'NOT_REORDER':
                    throw new HttpException(403, 'NOT_REORDER', '재정렬 중 trip_dayid를 찾는 것에 실패하였습니다.');
                default:
                    throw new HttpException(500, 'TRIP_REORDER_FAIL', '일차 재배치에 실패하였습니다.');
            }
        }
    }
}
