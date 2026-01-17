<?php

// 1. use 작성
use Tripmate\Backend\Core\Request;
use Tripmate\Backend\Core\Response;
use Tripmate\Backend\Modules\TripDays\Controllers\TripDaysController;

// 2. 콜백을 위한 익명함수 작성
return function (AltoRouter $altoRouter, Request $request, Response $response): void {

    // 2-1. trip day 생성 : POST /api/v1/trips/{trip_id}/days
    $altoRouter->map(
        'POST',
        '/api/v1/trips/[i:trip_id]/days',
        [TripDaysController::class, 'createTripDay']
    );

    // 2-2 trip day 목록 조회 : GET /api/v1/trips/{trip_id}/days
    $altoRouter->map(
        'GET',
        '/api/v1/trips/[i:trip_id]/days',
        [TripDaysController::class, 'getTripDays']
    );

    // 2-3. trip day 단건 조회 : GET /api/v1/trips/{trip_id}/days/{day_no}
    $altoRouter->map(
        'GET',
        '/api/v1/trips/[i:trip_id]/days/[i:day_no]',
        [TripDaysController::class, 'showTripDay']
    );

    // 2-4. trip day 수정 : PUT /api/v1/trips/{trip_id}/days/{day_no}
    $altoRouter->map(
        'PUT',
        '/api/v1/trips/[i:trip_id]/days/[i:day_no]',
        [TripDaysController::class, 'updateTripDay']
    );

    // 2-5. trip day 삭제 : DELETE /api/v1/trips/{trip_id}/days/{day_no}
    $altoRouter->map(
        'DELETE',
        '/api/v1/trips/[i:trip_id]/days/[i:day_no]',
        [TripDaysController::class, 'deleteTripDay']
    );

    // 2-6. trip day 순서 재배치 : POST /api/v1/trips/{trip_id}/days:reorder
    $altoRouter->map(
        'POST',
        '/api/v1/trips/[i:trip_id]/days:reorder',
        [TripDaysController::class, 'reorderTripDays']
    );
};
