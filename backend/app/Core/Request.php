<?php

// namespace App\Core;

namespace Tripmate\Backend\Core;

// 1. 요청 데이터 처리 클래스
class Request
{
    // 요청 바디
    protected array $attributes = []; // 미들웨어 등에서 추가하는 속성 저장

    // 3. 생성자: 호출시 요청 메서드, path, query, 헤더, 바디 처리
    public function __construct(
        // 3-1. 생성자 매개변수 정의
        protected string $method,
        protected string $path,
        protected array $queryParam,
        protected array $headers,
        protected array $body
    ) {
    }

    // 4. request 객체를 생성 후 반환하는 정적 메서드
    public static function fromGlobals(): self
    {
        // 4-1. 메서드 및 경로 추출
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET'; // 기본값 GET
        $uri = $_SERVER['REQUEST_URI'] ?? '/'; // 기본값 /
        $path = \parse_url((string) $uri, PHP_URL_PATH) ?? '/'; // 기본값 /

        // 4-2. header 추출
        $headers = []; // 초기화

        // - getallheaders 함수가 있을 경우 사용
        if (\function_exists('getallheaders')) {
            foreach (\getallheaders() as $k => $v) {
                $headers[$k] = $v;
            }
        }

        // - 함수가 없을 경우 직접 추출
        else {
            foreach ($_SERVER as $k => $v) {
                if (\str_starts_with($k, 'HTTP_')) {
                    $name = \str_replace(' ', '-', \substr($k, 5));
                    $headers[$name] = $v;
                }
            }
        }

        // 4-3. json 바디 파싱
        $rowBody = \file_get_contents('php://input') ?: ''; // 빈 문자열 방지
        $bodyArray = []; // 초기화
        $contentType = $headers['Content-Type'] ?? $headers['content-type'] ?? ''; // - Content-Type 헤더 확인
        // - application/json 일 경우 json 디코딩
        if ($rowBody !== '' && \stripos((string) $contentType, 'application/json') !== false) {
            // - json 디코딩
            $decoded = \json_decode($rowBody, true);
            // - 배열일 경우에만 bodyArray에 할당
            $bodyArray = \is_array($decoded) ? $decoded : [];
        }

        // 4-4. 인스턴스 생성
        return new self($method, $path, $_GET ?? [], $headers, $bodyArray);
    }

    // 5. 메서드/경로 조회
    public function method(): string
    {
        return $this->method;
    } // HTTP 메서드
    public function path(): string
    {
        return $this->path;
    } // 요청 경로

    // 6. 쿼리 파라미터 조회
    // - key가 null이면 전체 반환, 있으면 해당 key 값 반환 (없으면 default)
    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->queryParam;
        }
        return $this->queryParam[$key] ?? $default;
    }

    // 7. 헤더 조회
    public function header(string $name, mixed $default = null): mixed
    {
        // 대소문자 구분 없이 조회
        foreach ($this->headers as $k => $v) {
            if (\strcasecmp($k, $name) === 0) {
                return $v;
            }
        }
        return $default;
    }
    // 7-1. 전체 헤더가 필요한 경우 : headers
    public function headers(): array
    {
        return $this->headers;
    }

    // 8. 바디 조회
    public function body(?string $key = null, mixed $default = null): mixed
    {
        // key가 null이면 전체 반환, 있으면 해당 key 값 반환 (없으면 default)
        if ($key === null) {
            return $this->body;
        }
        return $this->body[$key] ?? $default;
    }

    // 9. 속성 조회/설정 메서드
    // - 인증 미들웨어가 user_id 등을 저장할 때 사용
    // - 라우터가 경로 파라미터를 저장할 때 사용
    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }
    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    // 10. user_id 편의 메서드
    public function userId(): ?int
    {
        $userId = $this->getAttribute('user_id');
        return \is_numeric($userId) ? $userId : null;
    }
}
