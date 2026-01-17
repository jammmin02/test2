<?php

namespace Tripmate\Backend\Modules\Places\Services;

use Tripmate\Backend\Common\Exceptions\HttpException;

class GoogleApi
{
    // API_KEY 함수
    private static function apiKey()
    {
        return $_ENV['API_KEY'] ?? \getenv('API_KEY');
    }

    // POST 요청 함수
    public static function post($endpoint, $postData = [], $headers = [])
    {
        $defaultHeaders = ['Content-Type: application/json',
                        'X-Goog-Api-Key:' . self::apiKey()];

        $mergeHeader = \array_merge($headers, $defaultHeaders);

        return self::searchService($endpoint, 'POST', $mergeHeader, $postData);
    }

    // GET 요청 함수
    public static function get(string $endpoint, array $params = [])
    {
        $params['key'] = self::apiKey();
        $params['language'] = 'ko';

        $url = $endpoint . '?' . \http_build_query($params);

        return self::searchService($url, 'GET');
    }


    // google 공통 API 연결 함수
    public static function searchService($endpoint, $method = 'GET', $headers = [], $postData = null)
    {
        // 서버 전달
        $curl = \curl_init($endpoint); // API 주소 연결

        if ($method == 'POST') {
            \curl_setopt($curl, CURLOPT_POST, true); // 주소 옵션 : POST, 기본값 : GET
            \curl_setopt($curl, CURLOPT_HTTPHEADER, $headers); // 헤더 전달
            \curl_setopt($curl, CURLOPT_POSTFIELDS, \json_encode($postData)); // 본문 전달
        }

        \curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true); // SSL 인증서 확인(true 보안적)
        \curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // 실행 결과를 echo하지 말고 변수로 반환

        $response = \curl_exec($curl); // 실행

        $httpCode = \curl_getinfo($curl, CURLINFO_HTTP_CODE); // cURL 에러

        \curl_close($curl);

        // 에러 처리
        if ($httpCode >= 400 || $response === false) {
            throw new HttpException('500', 'GOOGLE_API_FAILED', '외부 API 호출에 실패했습니다.');
        }

        // 데이터 본문 처리
        $result = \json_decode($response, true);

        return $result;
    }
}
