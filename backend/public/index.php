<?php
declare(strict_types=1);

// 0. autoload 로드
require_once __DIR__ . '/../vendor/autoload.php';

// use 작성
use Tripmate\Backend\Core\Request;
use Tripmate\Backend\Core\Response;
use Tripmate\Backend\Common\Exceptions\HttpException;

// 1. 공용 객체 생성 (Request, Response, AltoRouter)
$request  = Request::fromGlobals();
$response = new Response();
$router   = new AltoRouter();

// 2. 모듈 라우터 자동 등록 (미작성 Routes.php 는 조용히 PASS)
// - 각 Modules/*/Routes.php 는 콜러블을 return 해야 함
// - function (AltoRouter $router, Request $request, Response $response): void
foreach (glob(__DIR__ . '/../app/Modules/*/Routes.php') as $routeFile) {
    // require의 기본 반환은 보통 1 (return이 없을 때)
    $register = (static function (string $file) { return require $file; })($routeFile);

    // 아직 미작성(1 또는 null) → 조용히 스킵
    if ($register === 1 || $register === null) {
        continue;
    }

    // 콜러블이 아니면 스킵 
    if (!is_callable($register)) {
        continue;
    }

    // 콜러블이면 라우터 등록 실행
    $register($router, $request, $response);
}

// 3. 라우팅 매칭
$match = $router->match();

// 4. 매칭 실패 시 404 반환
if ($match === false) {
    $response->error('NOT_FOUND', 'Route not found', 404);
    exit;
}

// 5. 매칭된 파라미터를 Request에 주입
// - 컨트롤러에서 $this->request->getAttribute('paramName') 으로 접근 가능
if (!empty($match['params']) && is_array($match['params'])) {
    foreach ($match['params'] as $key => $value) {
        $request->setAttribute($key, $value);
    }
}

// 6. 타겟(라우트 대상) 추출
$target = $match['target'];

// 7. 예외 처리 블록
try {
    // 7-1. 컨트롤러 배열형([Controller::class, 'method']) 처리
    if (is_array($target) && count($target) === 2) {
        [$class, $method] = $target;

        // 7-1-1. 컨트롤러 클래스 존재 확인
        if (!class_exists($class)) {
            throw new HttpException(500, 'CLASS_NOT_FOUND', "{$class} 클래스를 찾을 수 없습니다");
        }

        // 7-1-2. 컨트롤러 인스턴스 생성 및 Request/Response 주입
        $controller = new $class($request, $response);

        // 7-1-3. 실제로 호출 가능한지 확인
        if (!is_callable([$controller, $method])) {
            throw new HttpException(500, 'METHOD_NOT_CALLABLE', "{$class}::{$method} 는 호출할 수 없습니다");
        }

        // 7-1-4. 컨트롤러 메서드 실행
        $result = $controller->$method();
    }
    // 7-2. 콜러블(클로저) 형태의 타겟 처리
    elseif (is_callable($target)) {
        $result = $target($request, $response);
    }
    // 7-3. 둘 다 아닌 경우 예외 처리
    else {
        throw new HttpException(500, 'INVALID_ROUTE_TARGET', '라우트 타겟이 잘못되었습니다.');
    }

    // 7-4. 컨트롤러가 Response 객체 반환 시 종료
    if ($result instanceof Response) {
        return;
    }

    // 컨트롤러가 null 이외의 값(배열/스칼라 등)을 반환했다면
    // 필요 시 프로젝트 규약에 맞게 자동 래핑 가능 (여기선 침묵)
    // if ($result !== null) {
    //     $response->json(['data' => $result], 200);
    //     return;
    // }

} catch (HttpException $e) {
    // 8-1. 명시적 HTTP 예외 처리
    $codeName = method_exists($e, 'getCodeName') ? $e->getCodeName() : 'HTTP_ERROR';
    $status   = method_exists($e, 'getStatus') ? $e->getStatus() : ($e->getCode() ?: 500);
    $response->error($codeName, $e->getMessage(), $status);
    return;
} catch (Throwable $e) {
    // 8-2. 알 수 없는 예외 처리 (간단 로그 + 500)
    error_log("[UNHANDLED] {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}");
    $response->error('INTERNAL_SERVER_ERROR', '서버 내부 오류가 발생했습니다.', 500);
    return;
}
