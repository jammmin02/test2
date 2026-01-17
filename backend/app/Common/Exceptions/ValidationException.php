<?php

namespace Tripmate\Backend\Common\Exceptions;

use Exception;

class ValidationException extends Exception
{
    // 상세 오류 정보 프로퍼티 (getter 필요)
    protected array $details;

    public function __construct(array $details, ?string $field = null, string $message = '잘못된 입력값입니다.')
    {
        // Respect\Validation이 단일 값이면 숫자 인덱스 배열임 -> 필드명 붙이기
        if ($field !== null && isset($details[0])) {
            $details = [$field => $details[0]];
        }

        // 부모 생성자로 메세지 전달
        // ValidationException에서는 코드가 필요 없기 때문에 0으로 전달
        parent::__construct($message, 0);

        // 에러 코드 매핑
        $this->details = $this->mapToErrorCodes($details);
    }

    // 세부 오류 정보 getter
    public function getDetails(): array
    {
        return $this->details;
    }

    // 에러 코드
    private function mapToErrorCodes(array $details): array
    {
        $errorCodeMap = [
        'email'        => 'EMAIL_INVALID',
        'password'     => 'PASSWORD_INVALID',
        'nickname'     => 'NICKNAME_INVALID',
        'title'        => 'TITLE_INVALID',
        'region_id'    => 'REGION_ID_INVALID',
        'start_date'   => 'START_DATE_INVALID',
        'end_date'     => 'END_DATE_INVALID',
        'memo'         => 'MEMO_INVALID',
        'day_no'       => 'DAY_NO_INVALID',
        'new_day_no'   => 'NEW_DAY_NO_INVALID',
        'orders'       => 'ORDERS_INVALID',
        'place'        => 'PLACE_INVALID',
        'name'         => 'NAME_INVALID',
        'category'     => 'CATEGORY_INVALID',
        'address'      => 'ADDRESS_INVALID',
        'external_ref' => 'EXTERNAL_REF_INVALID',
        'lat'          => 'LAT_INVALID',
        'lng'          => 'LNG_INVALID',
        'url'          => 'URL_INVALID',
        'visit_time'   => 'VISIT_TIME_INVALID',
        'seq_no'       => 'SEQ_NO_INVALID',
        'place_id'     => 'PLACE_ID_INVALID',
        'item_id'      => 'ITEM_ID_INVALID',
        'new_seq_no'   => 'NEW_SEQ_NO_INVALID',
        'trip_id'      => 'TRIP_ID_INVALID',
        'query'        => 'QUERY_INVALID',
        'page'         => 'PAGE_INVALID',
        'sort'         => 'SORT_INVALID',
        'country'      => 'COUNTRY_INVALID'
        ];

        $mapped = [];

        // 에러 매핑
        foreach (\array_keys($details) as $field) {
            $mapped[$field] = $errorCodeMap[$field] ?? 'UNKNOWN_VALIDATION_ERROR';
        }

        return $mapped;
    }
}
