<?php

namespace Tripmate\Backend\Common\Utils;

use Exception;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT as JJWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Tripmate\Backend\Common\Exceptions\JwtException;

// JWT 발급 및 검증
class Jwt
{
    // secret_key 함수
    public static function secretKey()
    {
        return $_ENV['JWT_SECRET_KEY'] ?? \getenv('JWT_SECRET_KEY');
    }

    // 알고리즘 함수
    public static function jwtAlgorithm()
    {
        return $_ENV['JWT_ALGORITHM'] ?? 'HS256';
    }

    // JWT 발급
    public static function encode($userId): string
    {
        // 시크릿 키 설정
        $secretKey = self::secretKey();

        // 환경 설정
        $expireTime = (int)($_ENV['JWT_EXPIRE_SECONDS'] ?? 43200);
        $jwtAlgorithm = self::jwtAlgorithm();

        // 페이로드 정의
        $payload = [
            'iss' => $_ENV['JWT_ISS'] ?? \getenv('JWT_ISS'), // 발급자
            'aud' => $_ENV['JWT_AUD'] ?? \getenv('JWT_AUD'), // 대상자
            'iat' => \time(), // 발급 시간
            'exp' => \time() + $expireTime, // 12시간 유효
            'jti' => self::jtiCreate(), // 고유 식별
            'userId' => $userId
        ];

        // JWT 인코딩 생성
        $jwt = JJWT::encode($payload, $secretKey, $jwtAlgorithm);
        return $jwt;
    }

    // JWT 검증
    public static function decode($jwt)
    {
        // 시크릿 키 설정
        $secretKey = self::secretKey();

        // 알고리즘 설정
        $jwtAlgorithm = self::jwtAlgorithm();

        try {
            // 디코딩
            $decode = JJWT::decode($jwt, new Key($secretKey, $jwtAlgorithm));
        } catch (SignatureInvalidException) {
            // 서명 검증 실패 처리
            throw new JwtException('TOKEN_SIGNATURE_INVALID', '토큰 서명이 유효하지 않습니다.');
        } catch (ExpiredException) {
            // 토큰 만료 처리
            throw new JwtException('TOKEN_EXPIRED', '토큰이 만료되었습니다. 다시 로그인해주세요.');
        } catch (Exception) {
            // 이 외 모든 에러 처리
            throw new JwtException('TOKEN_ERROR', '토큰 처리 중 오류가 발생했습니다.');
        }

        // id가 JWT 토큰에 없을 시
        if (empty($decode->userId)) {
            throw new JwtException('TOKEN_UNKNOWN_ERROR', '토큰에 사용자 정보가 없습니다.');
        }

        // 성공적으로 Id 파싱 성공 시 반환
        return $decode->userId;
    }

    // JTI 생성 함수
    private static function jtiCreate(): string
    {
        // 1바이트 당 16진수 2글자로, 총 32글자의 16진수 문자열 반환
        return \bin2hex(\random_bytes(16));
    }
}
