<?php

namespace Tripmate\Backend\Common\Middleware;

use Exception;
use Tripmate\Backend\Common\Exceptions\JwtException;
use Tripmate\Backend\Common\Utils\Jwt;
use Tripmate\Backend\Core\Request;

// Bearer 토큰 검증 → req->user 주입
class AuthMiddleware
{
    // 발급 요청
    public static function tokenRequest($userId): string
    {
        try {
            // 발급 함수 호출
            $jwt = Jwt::encode($userId);
        } catch (Exception) {
            // jwt 발급 시 생길 수 있는 모든 문제의 에러
            throw new JwtException('TOKEN_ISSUE_FAILED', '토큰 발급 중 오류가 발생했습니다.');
        }

        // 에러가 없을 경우 JWT
        return $jwt;
    }

    // 검증 요청
    public static function tokenResponse(Request $request)
    {

        // 헤더가 없으면 null로 설정
        $headerToken = $request->header('authorization') ?? $request->header('Authorization') ?? null;

        // 헤더가 없어 null로 설정된 경우
        if ($headerToken === null) {
            throw new JwtException('TOKEN_MISSING', '토큰이 제공되지 않았습니다.');
        }

        // Bearer 제거 후 파싱
        if (\str_starts_with((string) $headerToken, 'Bearer ')) {
            $jwt = \substr((string) $headerToken, 7);
        } else {
            throw new JwtException('TOKEN_FORMAT_INVALID', '토큰 형식이 올바르지 않습니다.');
        }

        // 토큰 검증
        $userId = Jwt::decode($jwt);

        $request->setAttribute('user_id', $userId);

        return $userId;
    }
}
