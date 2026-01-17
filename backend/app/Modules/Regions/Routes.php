<?php

use Tripmate\Backend\Core\Request;
use Tripmate\Backend\Core\response;
use Tripmate\Backend\Modules\Regions\Controllers\RegionsController;

// User 라우트 등록
return function (AltoRouter $altoRouter, Request $request, Response $response): void {
    //  라우팅 등록
    $altoRouter->map('GET', '/api/v1/regions', [RegionsController::class, 'getRegion']);
};
