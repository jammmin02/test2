<?php

// 1. use 작성 (Tripmate\Backend\ )
use Tripmate\Backend\Core\Request;
use Tripmate\Backend\Core\Response;
use Tripmate\Backend\Modules\Trips\Controllers\TripsController;

// 2. 콜백을 위한 익명함수 작성
return function (AltoRouter $altoRouter, Request $request, Response $response): void {

    // 2-1. 여행 생성 : POST/api/v1/trips
    $altoRouter->map(
        'POST',
        '/api/v1/trips',
        [TripsController::class, 'createTrip']
    );
    // 2-2. 여행 목록 조회 : GET/api/v1/trips
    $altoRouter->map(
        'GET',
        '/api/v1/trips',
        [TripsController::class, 'getTrips']
    );

    // 2-3. 여행 딘건 조회 : GET /api/v1/trips/{trip_id}
    $altoRouter->map(
        'GET',
        '/api/v1/trips/[i:trip_id]',
        [TripsController::class, 'showTrip']
    );
    // 2-4. 여행 수정 : PUT /api/v1/trips/{trip_id}
    $altoRouter->map(
        'PUT',
        '/api/v1/trips/[i:trip_id]',
        [TripsController::class, 'updateTrip']
    );
    // 2-5. 여행 삭제 : DELETE /api/v1/trips/{trip_id}
    $altoRouter->map(
        'DELETE',
        '/api/v1/trips/[i:trip_id]',
        [TripsController::class, 'deleteTrip']
    );
};
