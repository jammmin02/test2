<?php

// namespace 작성

namespace Tripmate\Backend\Modules\ScheduleItems\Controllers;

// use 작성
use Tripmate\Backend\Core\Controller;
use Tripmate\Backend\Core\DB;
use Tripmate\Backend\Core\Request;
use Tripmate\Backend\Core\Response;
use Tripmate\Backend\Core\Validator;
use Tripmate\Backend\Modules\ScheduleItems\Services\ScheduleItemsService;

// ScheduleItemsController 작성
class ScheduleItemsController extends Controller
{
    // 프로퍼티 정의
    public ScheduleItemsService $service;
    public Validator $validator;

    // 생성자에서 Request, Response, Service, Validator 초기화
    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);
        $pdo = DB::conn();
        $this->service   = new ScheduleItemsService($pdo);
        $this->validator = new Validator();
    }

    // 1. 일정 아이템 생성 : POST /api/v1/trips/{trip_id}/days/{day_no}/items
    public function createScheduleItem(): void
    {
        $this->run(function (): null {
            // 1-1. 경로 파라미터
            $tripId = $this->request->getAttribute('trip_id');
            $dayNo  = $this->request->getAttribute('day_no');

            // 1-2. 유효성 검사
            $this->validator->validateTripId($tripId);
            $this->validator->validateDayNo($dayNo);

            // 1-3. 요청 바디
            $body = (array)$this->request->body();
            $placeId   = isset($body['place_id']) ? (int)$body['place_id'] : null;
            $visitTime = $body['visit_time'] ?? null;
            $memo      = $body['memo'] ?? null;

            // 1-4. 서비스 호출
            $userId = $this->getUserId(); // 인증 (공통 클래스 사용)
            $itemId = $this->service->createScheduleItem(
                $userId,
                (int)$tripId,
                (int)$dayNo,
                $placeId,
                $visitTime,
                $memo
            );

            // 1-5. 실패 시 에러 응답
            if ($itemId <= 0) {
                $this->response->error('ITEM_CREATION_FAILED', '일정 아이템 생성에 실패했습니다.', 500);
                return null;
            }

            // 1-6. 성공 응답 (201 Created)
            $this->response->created([
                'item_id'    => $itemId,
                'trip_id'    => (int)$tripId,
                'day_no'     => (int)$dayNo,
                'place_id'   => $placeId,
                'visit_time' => $visitTime,
                'memo'       => $memo,
            ]);
            return null;
        });
    }

    // 2. 일정 아이템 목록 조회 : GET /api/v1/trips/{trip_id}/days/{day_no}/items
    public function getScheduleItems(): void
    {
        $this->run(function (): null {
            // 2-1. 경로 파라미터
            $tripId = $this->request->getAttribute('trip_id');
            $dayNo  = $this->request->getAttribute('day_no');

            // 2-2. 유효성 검사
            $this->validator->validateTripId($tripId);
            $this->validator->validateDayNo($dayNo);

            // 2-3. 서비스 호출
            $userId = $this->getUserId();
            $items = $this->service->getScheduleItems(
                $userId,
                (int)$tripId,
                (int)$dayNo
            );

            // 2-4. 성공 응답
            $this->response->success([
                'trip_id' => (int)$tripId,
                'day_no'  => (int)$dayNo,
                'items'   => $items,
            ]);
            return null;
        });
    }

    // 3. 일정 아이템 수정 : PATCH /api/v1/trips/{trip_id}/days/{day_no}/items/{item_id}
    public function updateScheduleItem(): void
    {
        $this->run(function (): null {
            // 3-1. 경로 파라미터
            $tripId = $this->request->getAttribute('trip_id');
            $dayNo  = $this->request->getAttribute('day_no');
            $itemId = $this->request->getAttribute('item_id');

            // 3-2. 유효성 검사
            $this->validator->validateTripId($tripId);
            $this->validator->validateDayNo($dayNo);
            if ((int)$itemId <= 0) {
                $this->response->error('INVALID_ITEM_ID', '유효하지 않은 item_id입니다.', 400);
                return null;
            }

            // 3-3. 요청 바디
            $body = (array)$this->request->body();
            $visitTime = $body['visit_time'] ?? null;
            $memo      = $body['memo'] ?? null;

            // 3-4. 서비스 호출
            $userId = $this->getUserId();
            $updated = $this->service->updateScheduleItem(
                $userId,
                (int)$tripId,
                (int)$itemId,
                (int)$dayNo,
                $visitTime,
                $memo
            );

            // 3-5. 실패 시 에러 응답
            if ($updated === false) {
                $this->response->error('ITEM_UPDATE_FAILED', '일정 아이템 수정에 실패했습니다.', 500);
                return null;
            }

            // 3-6. 성공 응답
            $this->response->success([
                'trip_id'    => (int)$tripId,
                'day_no'     => (int)$dayNo,
                'item_id'    => (int)$itemId,
                'visit_time' => $updated['visit_time'] ?? $visitTime,
                'memo'       => $updated['memo'] ?? $memo,
            ]);
            return null;
        });
    }

    // 4. 일정 아이템 삭제 : DELETE /api/v1/trips/{trip_id}/days/{day_no}/items/{item_id}
    public function deleteScheduleItem(): void
    {
        $this->run(function (): null {
            // 4-1. 경로 파라미터
            $tripId = $this->request->getAttribute('trip_id');
            $dayNo  = $this->request->getAttribute('day_no');
            $itemId = $this->request->getAttribute('item_id');

            // 4-2. 유효성 검사
            $this->validator->validateTripId($tripId);
            $this->validator->validateDayNo($dayNo);
            if ((int)$itemId <= 0) {
                $this->response->error('INVALID_ITEM_ID', '유효하지 않은 item_id입니다.', 400);
                return null;
            }

            // 4-3. 서비스 호출
            $userId = $this->getUserId();
            $deleted = $this->service->deleteScheduleItem(
                $userId,
                (int)$tripId,
                (int)$dayNo,
                (int)$itemId
            );

            // 4-4. 실패 시 에러 응답
            if (!$deleted) {
                $this->response->error('DELETE_FAILED', '일정 삭제에 실패했습니다.', 500);
                return null;
            }

            // 4-5. 성공 (204 No Content)
            $this->response->noContent();
            return null;
        });
    }

    // 5. 일정 아이템 순서 재배치 : POST /api/v1/trips/{trip_id}/days/{day_no}/items:reorder
    public function reorderSingleScheduleItem(): void
    {
        $this->run(function (): null {
            // 5-1. 경로 파라미터
            $tripId = $this->request->getAttribute('trip_id');
            $dayNo  = $this->request->getAttribute('day_no');

            // 5-2. 유효성 검사
            $this->validator->validateTripId($tripId);
            $this->validator->validateDayNo($dayNo);

            // 5-3. 요청 바디
            $body     = (array)$this->request->body();
            $itemId   = isset($body['item_id']) ? (int)$body['item_id'] : 0;
            $newSeqNo = isset($body['new_seq_no']) ? (int)$body['new_seq_no'] : 0;

            if ($itemId <= 0) {
                $this->response->error('INVALID_ITEM_ID', 'item_id가 필요합니다.', 400);
                return null;
            }
            // newSeqNo는 1 이상
            if ($newSeqNo < 1) {
                $this->response->error('INVALID_SEQ_NO', 'new_seq_no는 1 이상의 정수여야 합니다.', 400);
                return null;
            }

            // 5-4. 서비스 호출
            $userId = $this->getUserId();
            $reordered = $this->service->reorderSingleScheduleItem(
                $userId,
                (int)$tripId,
                (int)$dayNo,
                $itemId,
                $newSeqNo
            );

            // 5-5. 실패 시 에러 응답
            if ($reordered === false) {
                $this->response->error('REORDER_FAILED', '일정 아이템 재배치에 실패했습니다.', 500);
                return null;
            }

            // 5-6. 성공 응답
            $this->response->success([
                'trip_id' => (int)$tripId,
                'day_no'  => (int)$dayNo,
                'items'   => $reordered,
            ]);
            return null;
        });
    }
}
