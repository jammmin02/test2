<?php

use Tripmate\Backend\Core\Request;
use Tripmate\Backend\Core\Response;
use Tripmate\Backend\Modules\Auth\Controllers\AuthController;

// Auth 라우트 등록
return function (AltoRouter $altoRouter, Request $request, Response $response): void {
    // 회원가입 라우팅 등록
    $altoRouter->map('POST', '/api/v1/users', [AuthController::class, 'register']);

    // 로그인 라우팅 등록
    $altoRouter->map('POST', '/api/v1/auth/login', [AuthController::class, 'login']);

    // 로그아웃 라우팅 등록
    $altoRouter->map('POST', '/api/v1/auth/logout', [AuthController::class, 'logout']);
};
