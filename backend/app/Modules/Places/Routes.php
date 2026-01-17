<?php

use Tripmate\Backend\Core\Request;
use Tripmate\Backend\Core\response;
use Tripmate\Backend\Modules\Places\Controllers\PlacesController;

// User 라우트 등록
return function (AltoRouter $altoRouter, Request $request, Response $response): void {
    $altoRouter->map('GET|OPTIONS', '/api/v1/places/autocomplete', [PlacesController::class, 'autocomplete']);
    //  장소 검색 라우터
    $altoRouter->map('GET', '/api/v1/places/external-search', [PlacesController::class, 'search']);

    // 좌표 -> 주소 라우터
    $altoRouter->map('GET', '/api/v1/places/reverse-geocode', [PlacesController::class, 'reverseGeocoding']);

    // 좌표 -> 장소 라우터
    $altoRouter->map('GET', '/api/v1/places/place-geocode', [PlacesController::class, 'placeGeocoding']);

    // 지역 주변 장소 검색 라우터
    $altoRouter->map('GET', '/api/v1/places/nearby', [PlacesController::class, 'searchNearby']);

    // 단건조회 라우터
    $altoRouter->map('GET', '/api/v1/places/[i:place_id]', [PlacesController::class, 'singlePlaceSearch']);

    // 외부결과 저장 라우터
    $altoRouter->map('POST', '/api/v1/places/from-external', [PlacesController::class, 'placeUpsert']);
};
