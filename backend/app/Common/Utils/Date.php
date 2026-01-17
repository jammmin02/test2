<?php

// namespace 작성

namespace Tripmate\Backend\Common\Utils;

use DateTimeImmutable;

// Date 클래스 정의
class Date
{
    // 날짜 포맷 상수 정의
    private const FORMAT_YMD = 'Y-m-d';

    // 1. 'YYYY-MM-DD' 형식 및 실제 존재 여부 검증
    public static function isValidDateYmd(string $ymd): bool
    {
        // 1-1. 공백 제거 및 빈 문자열이면 탈락
        $ymd = \trim($ymd);
        if ($ymd === '' || \strlen($ymd) !== 10) {
            return false;
        }

        // 1-2. 포맷에 맞는 DateTime 객체 생성 ('!'로 시간 초기화)
        $dateTime = DateTimeImmutable::createFromFormat('!' . self::FORMAT_YMD, $ymd);
        if ($dateTime === false) {
            return false;
        }

        // 1-3. 포맷 일치 여부 최종 확인
        return $dateTime->format(self::FORMAT_YMD) === $ymd;
    }

    // 2. 시작일이 종료일보다 같거나 빠른가? (둘 다 유효해야 true)
    public static function isBeforeOrEqual(string $startYmd, string $endYmd): bool
    {
        // 2-1. 입력된 두 날짜 모두 형식이 올바른가 확인
        if (!self::isValidDateYmd($startYmd) || !self::isValidDateYmd($endYmd)) {
            return false;
        }

        // 2-2. 'Y-m-d' 문자열 비교는 날짜 순서와 동일하게 동작함
        return $startYmd <= $endYmd;
    }

    // 3. 특정 날짜가 [시작일, 종료일] 구간에 포함되는가?
    public static function betweenInclusive(string $ymd, string $startYmd, string $endYmd): bool
    {
        // 3-1. 세 날짜 모두 형식이 올바른가 확인
        if (
            !self::isValidDateYmd($ymd) ||
            !self::isValidDateYmd($startYmd) ||
            !self::isValidDateYmd($endYmd)
        ) {
            return false;
        }

        // 3-2. 시작일이 종료일보다 늦으면 잘못된 범위로 간주
        if ($startYmd > $endYmd) {
            return false;
        }

        // 3-3. 날짜가 구간 내에 포함되는지 검사
        return $startYmd <= $ymd && $ymd <= $endYmd;
    }

    // 4. 시작일과 종료일을 포함한 총 일수 계산 (end - start + 1)
    public static function calcInclusiveDays(string $startYmd, string $endYmd): int
    {
        // 4-1. 두 날짜가 유효하고 순서가 올바른지 확인
        if (!self::isBeforeOrEqual($startYmd, $endYmd)) {
            return 0;
        }

        // 4-2. 날짜 객체로 변환 후 차이 계산
        $startDate = new DateTimeImmutable($startYmd);
        $endDate   = new DateTimeImmutable($endYmd);

        // 4-3. 두 날짜의 차이(days) + 1 반환
        return $startDate->diff($endDate)->days + 1;
    }

    // 5. 기준일에 일수를 더하거나 빼기 (음수면 과거). 실패 시 null 반환
    public static function addDays(string $ymd, int $days): ?string
    {
        // 5-1. 입력된 날짜 형식 검증
        if (!self::isValidDateYmd($ymd)) {
            return null;
        }

        // 5-2. 기준일 객체 생성
        $base = new DateTimeImmutable($ymd);

        // 5-3. 수정 문자열 생성 ('+3 days', '-2 days' 등)
        $expr = ($days >= 0 ? '+' : '') . $days . ' days';

        // 5-4. modify()로 날짜 연산 후 포맷 반환
        return $base->modify($expr)?->format(self::FORMAT_YMD) ?? null;
    }

    // 6. Tripmate 규약: 시작일 기준 day_no번째 실제 날짜 계산 (1일차 = 시작일)
    public static function getTripDayDate(string $startYmd, int $dayNo): ?string
    {
        // 6-1. dayNo가 1 미만이거나 시작일이 유효하지 않으면 null
        if ($dayNo < 1 || !self::isValidDateYmd($startYmd)) {
            return null;
        }

        // 6-2. 시작일에 (dayNo - 1)일 더한 날짜 반환
        return self::addDays($startYmd, $dayNo - 1);
    }
}
