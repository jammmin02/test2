<?php

// namespace 작성

namespace Tripmate\Backend\Modules\Trips\Services;

// use 작성
use PDO;
use Tripmate\Backend\Common\Exceptions\DbException;
use Tripmate\Backend\Common\Exceptions\HttpException;
use Tripmate\Backend\Common\Utils\Date;
use Tripmate\Backend\Core\Service;
use Tripmate\Backend\Modules\Trips\Repositories\TripsRepository;

// TripsService
// - 공통 Service 상속 받아 사용
// - Trip 관련 비즈니스 로직 처리
class TripsService extends Service
{
    // 프러퍼티 정의
    public TripsRepository $tripsRepository;

    // 같은 PDO로 레포를 생성해 동일 트랜잭션 공유
    public function __construct(PDO $pdo, ?TripsRepository $tripsRepository = null)
    {
        parent::__construct($pdo);
        $this->tripsRepository = $tripsRepository ?? new TripsRepository($pdo);
    }

    // 1. Trip 생성
    // - 실패시 HttpException 발생
    public function createTrip(
        int $userId,
        int $regionId,
        string $title,
        string $startDate,
        string $endDate
    ): int {
        // 1-1. 일수 계산
        // - 날짜 형식 controller에서 수행
        $dayCount = Date::calcInclusiveDays($startDate, $endDate);
        if ($dayCount <= 0) {
            throw new HttpException(400, 'INVALID_DATE_RANGE', '유효하지 않은 날짜 범위입니다.');
        }

        try {
            // 1-2. 트랜잭션 시작
            $tripId = $this->transaction(function (PDO $pdo) use ($userId, $regionId, $title, $startDate, $endDate, $dayCount): int {

                // 1-3. TripsRepository의 insertTrip 메서드 호출
                $tripId = $this->tripsRepository->insertTrip(
                    $userId,
                    $regionId,
                    $title,
                    $startDate,
                    $endDate
                );


                // 1-4. dayCount 수만큼 TripDay 생성 (insertTripDay 호출)
                for ($dayNo = 1; $dayNo <= $dayCount; $dayNo++) {
                    if (!$this->tripsRepository->insertTripDay($tripId, $dayNo)) {
                        throw new DbException('TRIP_DAY_INSERT_FAILED', '여행 일자 생성에 실패했습니다.');
                    }
                }
                // 1-5. 생성된 tripId 반환
                return $tripId;
            });

            // 1-6. 최종 생성된 tripId 반환
            return $tripId;
        } catch (DbException $e) {
            throw new HttpException(500, 'TRIP_CREATION_FAILED', '여행 생성에 실패했습니다.', $e);
        }
    }

    // 2. Trip 단건 조회
    // - 실패시 HttpException 발생
    public function findTripById(int $tripId, int $userId): array
    {
        try {
            // 2-1. TripsRepository의 findTripById 메서드 호출
            $trip = $this->tripsRepository->findTripById($tripId, $userId);
            if ($trip === null) {
                throw new HttpException(404, 'TRIP_NOT_FOUND', '해당 여행을 찾을 수 없습니다.');
            }

            // 2-2. 조회 성공 시 여행 정보 배열 반환
            return $trip;
        } catch (DbException $e) {
            throw new HttpException(500, 'TRIP_RETRIEVAL_FAILED', '여행 조회에 실패했습니다.', $e);
        }
    }

    // 3. Trip 목록 조회
    // - 페이지네이션 지원
    public function findTrips(
        int $userId,
        int $page,
        int $size,
        ?string $sort
    ): array {
        try {
            // 3-1. TripsRepository의 findTripsByUserId 메서드 호출
            return $this->tripsRepository->findTripsByUserId(
                $userId,
                $page,
                $size,
                $sort
            );
        } catch (DbException $e) {
            throw new HttpException(500, 'TRIPS_RETRIEVAL_FAILED', '여행 목록 조회에 실패했습니다.', $e);
        }
    }

    // 4.Trip 수정 메서드
    // - 기본정보 갱신 -> 기존 TripDay 전부 삭제 -> TripDay 새로 생성
    // - 실패시 HttpException 발생
    public function updateTrip(
        int $userId,
        int $tripId,
        int $regionId,
        string $title,
        string $startDate,
        string $endDate
    ): bool {
        // 4-1. 일수 계산
        // - 날짜 형식 controller에서 수행
        $dayCount = Date::calcInclusiveDays($startDate, $endDate);
        if ($dayCount <= 0) {
            throw new HttpException(500, 'INVALID_DATE_RANGE', '유효하지 않은 날짜 범위입니다.');
        }

        try {
            // 4-2. 트랜잭션 시작
            return $this->transaction(function (PDO $pdo) use ($userId, $tripId, $regionId, $title, $startDate, $endDate, $dayCount): bool {
                // 4-3. TripsRepository의 updateTrip 메서드 호출
                $updated = $this->tripsRepository->updateTrip(
                    $userId,
                    $tripId,
                    $regionId,
                    $title,
                    $startDate,
                    $endDate
                );
                if (!$updated) {
                    throw new DbException('TRIP_UPDATE_FAILED', '여행 기본정보 수정에 실패했습니다.');
                }

                // 4-4. 기존 TripDay 전부 삭제
                if (!$this->tripsRepository->deleteTripDaysByTripId($tripId)) {
                    throw new DbException('TRIP_DAY_DELETION_FAILED', '기존 여행 일자 삭제에 실패했습니다.');
                }

                // 4-5. dayCount 수만큼 TripDay 새로 생성
                for ($dayNo = 1; $dayNo <= $dayCount; $dayNo++) {
                    if (!$this->tripsRepository->insertTripDay($tripId, $dayNo)) {
                        throw new DbException('TRIP_DAY_INSERT_FAILED', '여행 일자 생성에 실패했습니다.');
                    }
                }
                // 4-6. 모든 작업 성공 시 true 반환
                return true;
            });
        } catch (DbException $e) {
            throw new HttpException(500, 'TRIP_UPDATE_FAILED', '여행 수정에 실패했습니다.', $e);
        }
    }

    // 5. Trip 삭제
    // - TripDay 삭제 -> Trip 기본정보 삭제
    // - 실패시 HttpException 발생
    public function deleteTrip(int $userId, int $tripId): bool
    {
        try {
            // 5-1. 트랜잭션 시작
            return $this->transaction(function (PDO $pdo) use ($userId, $tripId): bool {
                // 5-2. TripsRepository의 deleteTripDaysByTripId 메서드 호출
                if (!$this->tripsRepository->deleteTripDaysByTripId($tripId)) {
                    throw new DbException('TRIP_DAY_DELETION_FAILED', '여행 일자 삭제에 실패했습니다.');
                }

                // 5-3. TripsRepository의 deleteTrip 메서드 호출
                $deleted = $this->tripsRepository->deleteTrip($userId, $tripId);
                if (!$deleted) {
                    throw new DbException('TRIP_DELETION_FAILED', '여행 삭제에 실패했습니다.');
                }

                // 5-4. 모든 작업 성공 시 true 반환
                return true;
            });
        } catch (DbException $e) {
            throw new HttpException(500, 'TRIP_DELETION_FAILED', '여행 삭제에 실패했습니다.', $e);
        }
    }
}
