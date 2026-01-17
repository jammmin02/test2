<?php

// namespace App\Core;

namespace Tripmate\Backend\Core;

// 2. use 작성
use Closure;
use Throwable;
use Tripmate\Backend\Common\Exceptions\HttpException;
use Tripmate\Backend\Common\Exceptions\JwtException;
use Tripmate\Backend\Common\Exceptions\ValidationException;
use Tripmate\Backend\Common\Middleware\AuthMiddleware;

// 3. 공통 컨트롤러 클래스
class Controller
{
    // 4. 생성자 (request, response 초기화)
    public function __construct(protected Request $request, protected Response $response)
    {
    }

    // 5. 실행 메서드
    // - HttpExceptions -> 표준 에러 JSON 응답 처리
    // - 알 수 없는 예외 -> 500 에러 응답 처리
    // - Response 반환 시 그대로 통과
    // - null 반환 시 204 No Content
    // - 배열/스칼라 반환 시 success(JSON)으로 래핑
    protected function run(Closure $action): Response
    {
        try {
            // 5-1. 액션 실행
            $result = $action($this->request, $this->response);

            // 5-2. 액션이 Response 직접 반환한 경우 → 그대로 반환
            if ($result instanceof Response) {
                return $result;
            }

            // 5-3. 액션 내에서 이미 응답 완료(null 반환) → 204 처리
            if ($result === null) {
                return $this->response->noContent();
            }

            // 5-4. 액션 결과가 배열/스칼라인 경우 → 성공 응답 래핑
            //      (스칼라는 result 키로 감싸 일관성 유지)
            $payload = \is_array($result) ? $result : ['result' => $result];
            return $this->response->success($payload);
        } catch (ValidationException $e) {
            // 5-5. ValidationException 예외 처리
            return $this->response->error(
                'VALIDATION_ERROR',
                $e->getMessage(),
                422,
                $e->getDetails()
            );
        } catch (JwtException $e) {
            // 5-6. JwtException 예외 처리
            return $this->response->error(
                'JWT_ERROR',
                $e->getMessage(),
                401
            );
        } catch (HttpException $e) {
            // 5-7. HttpExceptions 예외 처리
            return $this->response->error(
                $e->getCodeName(),
                $e->getMessage(),
                $e->getStatus()
            );
        } catch (Throwable $e) {
            // 5-8. 알 수 없는 예외 처리 (500 에러)
            \error_log("[UNHANDLED] {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}");
            return $this->response->error(
                'INTERNAL_SERVER_ERROR',
                '서버 내부 오류가 발생했습니다.',
                500
            );
        }
    }

    // 6. 토큰 검증 후 userId 반환 (유효하지 않으면 예외)
    public function getUserId(): int
    {
        return AuthMiddleware::tokenResponse($this->request);
    }

    // 7. 인증만 실행 (userId 값은 사용하지 않음)
    //  - 유효하지 않으면 예외로 중단, 성공 시 아무 것도 반환하지 않음
    public function requireAuth(): void
    {
        // 반환 없이 토큰 검증
        $this->getUserId();
    }

    // 8. pagenation 파라미터 파싱 메서드
    // - page, size, sort 파라미터를 정리하여 반환
    protected function parsePaging(
        int $defaultPage = 1,         // 기본 페이지
        int $defaultSize = 20,        // 기본 페이지 크기
        int $maxSize = 100            // 최대 페이지 크기
    ): array {
        // 8-1. 쿼리에서 값 조회(없으면 기본값)
        $page = (int) $this->request->query('page', $defaultPage);
        $size = (int) $this->request->query('size', $defaultSize);

        // 8-2. 경계값 보정
        if ($page < 1) {
            $page = $defaultPage;
        }
        if ($size < 1) {
            $size = $defaultSize;
        }
        if ($size > $maxSize) {
            $size = $maxSize;
        }

        // 8-3. 정렬 파라미터(없으면 null)
        $sort = $this->request->query('sort', null);

        // 8-4. 결과 반환
        return [
        'page' => $page,
        'size' => $size,
        'sort' => $sort
        ];
    }
}
