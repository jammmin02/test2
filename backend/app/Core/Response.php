<?php

// namespace App\Core;
// 1. namespace 작성

namespace Tripmate\Backend\Core;

// 2. 표준 응답 포멧 클레스
class Response
{
    // 3. 프로퍼티 정의
    protected int $status = 200; // HTTP 상태 코드
    protected array $headers = []; // 응답 헤더 (내부적으로 확인하기 위해)

    // 4. 상태 코드만 설정 (body가 없는 경우의 응답)
    public function status(int $status): self
    {
        $this->status = $status;
        \http_response_code($status);
        return $this;
    }

    // 5. 헤더 설정 메서드
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        \header("$name: $value");
        return $this;
    }

    // 6. JSON 응답 출력 메서드
    public function json(array $data, int $status = 200): self
    {
        $this->status = $status; // 상태 코드 설정
        \http_response_code($status);
        $this->setHeader('Content-Type', 'application/json; charset=utf-8'); // Content-Type 헤더 설정

        // JSON 인코딩 및 출력
        $json = \json_encode(
            $data, // 인코딩할 데이터
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES // 유니코드 및 슬래시 이스케이프 방지
        );

        if ($json === false) {
            \http_response_code(500);
            echo '{
        "success":false,
        "error":{"code":"ENCODE_ERROR",
        "message":"응답 인코딩 실패"}}';
            return $this;
        }
        echo $json;
        return $this;
    }

    // 7. 표준 성공읍답 메서드 (기본 200)
    // - API 요청 성공 시 data 반환
    // - meta 정보가 있을 경우 추가 (페이징 등)
    public function success(array $data = [], array $meta = []): self
    {
        // 7-1. 페이로드 생성
        $payLoad = [
          'success' => true,
          'data' => $data
        ];
        // 7-2. 메타 정보가 있을 경우 추가
        if ($meta !== []) {
            $payLoad['meta'] = $meta;
        }
        return $this->json($payLoad, 200); // JSON 응답 200 반환
    }

    // 8. 생성 응답 메서드 (201)
    // - 리소스 생성 성공 시 사용
    public function created(array $data = []): self
    {
        return $this->json([
          'success' => true,
          'data' => $data
        ], 201);
    }

    // 9. 내용 없음 응답 메서드 (204)
    // - 바디 없이 204 상태 코드 반환
    public function noContent(): self
    {
        return $this->status(204);
    }

    // 10. 표준 오류 응답 메서드
    public function error(
        string $code,
        string $message,
        int $status = 400,
        ?array $details = null
    ): self {
        // 10-1. error 생성
        $error = [
          'code' => $code, // 오류 코드
          'message' => $message // 오류 메시지
        ];

        // 10-2. 상세 정보(details)가 있을 경우 추가
        if ($details !== null) {
            $error['details'] = $details;
        }

        return $this->json([
          'success' => false, // 성공 여부 false
          'error' => $error // 에러 객체 반환
        ], $status);
    }
}
