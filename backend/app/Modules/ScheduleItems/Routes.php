<?php

// 1. use 작성
use Tripmate\Backend\Modules\ScheduleItems\Controllers\ScheduleItemsController;

// 2. 콜백을 위한 익명함수 작성
return function (AltoRouter $altoRouter): void {

    // 2-1. 일정 생성 : POST /api/v1/trips/{trip_id}/days/{day_no}/items
    $altoRouter->map(
        'POST',
        '/api/v1/trips/[i:trip_id]/days/[i:day_no]/items',
        [ScheduleItemsController::class, 'createScheduleItem']
    );

    // 2-2. 일정 목록 조회 : GET /api/v1/trips/{trip_id}/days/{day_no}/items
    $altoRouter->map(
        'GET',
        '/api/v1/trips/[i:trip_id]/days/[i:day_no]/items',
        [ScheduleItemsController::class, 'getScheduleItems']
    );

    // 2-3. 일정 수정 : PATCH /api/v1/trips/{trip_id}/days/{day_no}/items/{item_id}
    $altoRouter->map(
        'PATCH',
        '/api/v1/trips/[i:trip_id]/days/[i:day_no]/items/[i:item_id]',
        [ScheduleItemsController::class, 'updateScheduleItem']
    );

    // 2-4. 일정 삭제 : DELETE /api/v1/trips/{trip_id}/days/{day_no}/items/{item_id}
    $altoRouter->map(
        'DELETE',
        '/api/v1/trips/[i:trip_id]/days/[i:day_no]/items/[i:item_id]',
        [ScheduleItemsController::class, 'deleteScheduleItem']
    );

    // 2-5. 일정 재배치 : POST /api/v1/trips/{trip_id}/days/{day_no}/items:reorder
    $altoRouter->map(
        'POST',
        '/api/v1/trips/[i:trip_id]/days/[i:day_no]/items:reorder',
        [ScheduleItemsController::class, 'reorderSingleScheduleItem']
    );
};
