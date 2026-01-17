<?php

namespace Tripmate\Backend\Modules\Places\Services;

use Tripmate\Backend\Common\Exceptions\DbException;
use Tripmate\Backend\Common\Exceptions\HttpException;
use Tripmate\Backend\Core\DB;
use Tripmate\Backend\Core\Service;
use Tripmate\Backend\Modules\Places\Repositories\PlacesRepository;

/**
 * 장소 관련 서비스
 */
class PlacesService extends Service
{
    private readonly PlacesRepository $placesRepository;

    public function __construct()
    {
        parent::__construct(DB::conn());
        $this->placesRepository = new PlacesRepository(DB::conn());
    }

    // 외부 API 엔드포인트
    private const API_AUTO = 'https://places.googleapis.com/v1/places:autocomplete';
    private const API_TEXT_SEARCH = 'https://places.googleapis.com/v1/places:searchText';
    private const API_REVERS_GEOCODING = 'https://maps.googleapis.com/maps/api/geocode/json';
    private const API_NEARBY = 'https://places.googleapis.com/v1/places:searchNearby';
    private const API_PLACE_DETAILS = 'https://maps.googleapis.com/maps/api/place/details/json';

    // FieldMask 정의
    private const MASK_SEARCH = 'places.id,places.displayName,places.formattedAddress,places.location,places.primaryType';
    private const MASK_NEARBY = 'places.id,places.displayName,places.formattedAddress,places.location,places.primaryType';
    private const MASK_PLACE_DETAILS = 'place_id,name,formatted_address,geometry/location,types';

    /**
     * @return array<mixed, array<'address'|'name'|'place_id', mixed>>
     */
    public function autoPlace($place, $session): array
    {
        $postData = [
            'input' => $place,
            'languageCode' => 'ko',
            'sessionToken' => $session
        ];

        $result = GoogleApi::post(self::API_AUTO, $postData, []);

        $suggestions = $result['suggestions'] ?? [];

        $formattedSuggestions = [];
        // 장소 ID, 이름, 주소만 반환
        foreach ($suggestions as $suggestion) {
            $prediction = $suggestion['placePrediction'];
            $structure = $prediction['structuredFormat'] ?? [];

            $formattedSuggestions[] = [
                // 1. 장소 ID
                'place_id' => $prediction['placeId'],
                // 2. 장소/가게 이름
                'name'     => $structure['mainText']['text']
                            ?? $prediction['text']['text'],
                // 3. 주소
                'address'  => $structure['secondaryText']['text'] ?? ''
            ];
        }

        return $formattedSuggestions;
    }

    /**
     * 외부 API를 불러와 장소 검색
     * @return array{data: array, meta: array{next_page_token: mixed}}
     */
    public function searchByText(mixed $place, mixed $token): array
    {
        // 본문 작성
        $postData = [
            'languageCode' => 'ko',
            'pageSize' => 5
        ];

        // 페이지네이션
        if (!empty($token)) {
            $postData['pageToken'] = $token; // pagetoken만 사용(textQuery 사용 불가)
        } else {
            $postData['textQuery'] = $place; // 첫 페이지는 place로 요청
        }

        // 헤더 작성
        $headers = ['X-Goog-FieldMask:' . self::MASK_SEARCH];

        // 외부 API 요청
        $result = GoogleApi::post(self::API_TEXT_SEARCH, $postData, $headers);

        return $this->placeResponse($result);
    }

    // 좌표 기준 주소로 변경
    public function getAddressFromCoordinates(string $lat, string $lng)
    {
        $parmas = ['latlng' => $lat . ',' . $lng ];

        // API 요청
        $result = GoogleApi::get(self::API_REVERS_GEOCODING, $parmas);

        // 비어있거나 'OK'가 아니면 예외 처리
        if (empty($result['results']) || $result['status'] !== 'OK') {
            throw new HttpException(404, 'GEOCODING_ZERO_RESULTS', '해당 좌표의 주소를 찾을 수 없습니다.');
        }

        return $result['results'][0]['formatted_address'];
    }

    // 좌표를 장소로
    public function getPlaceDetailsById($placeId): array
    {
        $params = ['place_id' => $placeId,
                   'fields' => self::MASK_PLACE_DETAILS];

        $result = GoogleApi::get(self::API_PLACE_DETAILS, $params);

        $place = $result['result'] ?? null;
        if (empty($place) || $result['status'] !== 'OK') {
            throw new HttpException(404, 'PLACE_NOT_FOUND', 'Place ID로 장소를 찾을 수 없거나 API 오류가 발생했습니다.');
        }

        // 응답 처리
        $formattedPlace = [
            'place_id' => $place['place_id'] ?? null,
            'name' => $place['name'] ?? null,
            'address' => $place['formatted_address'] ?? null,
            'lat' => $place['geometry']['location']['lat'] ?? null,
            'lng' => $place['geometry']['location']['lng'] ?? null,
            'category_code' => $place['types'][0] ?? 'etc'
        ];

        return $formattedPlace;
    }


    // 내 주변 지역 장소 검색
    public function nearbyPlaces($lat, $lng, $radius): array
    {
        $headers = ['X-Goog-FieldMask:' . self::MASK_NEARBY];

        // 본문 작성
        $postData = [
            'languageCode' => 'ko',
            'pageSize' => 20,
            'locationRestriction' => [
            'circle' => [
                'center' => [
                    'latitude' => $lat,
                    'longitude' => $lng
                ],
                'radius' => $radius
            ]
            ]
        ];

        // 함수 실행
        $result = GoogleApi::post(self::API_NEARBY, $postData, $headers);

        return $this->placeResponse($result);
    }

    // 반환값 재사용 함수
    public function placeResponse($result): array
    {
        // 데이터 처리
        $formattedPlaces = [];

        if (!empty($result['places'])) {
            foreach ($result['places'] as $place) {
                $formattedPlaces[] = [
                    'place_id' => $place['id'] ?? null,
                    'name' => $place['displayName']['text'] ?? '이름 없음',
                    'address' => $place['formattedAddress'] ?? null,
                    'lat' => $place['location']['latitude'] ?? null,
                    'lng' => $place['location']['longitude'] ?? null,
                    'category' => $place['primaryType'] ?? 'etc'
                ];
            }
        }

        // 페이지네이션
        $nextToken = $result['nextPageToken'] ?? null;

        return [
            'meta' => [
                'next_page_token' => $nextToken
            ],
            'data' => $formattedPlaces
        ];
    }

    // 외부 결과 중 하나를 내부로 저장
    public function upsert($data)
    {
        try {
            return $this->transaction(function () use ($data): ?array {
                // data값 꺼내기
                $name = $data['name'];
                $category = $data['category'];
                $address = $data['address'];
                $externalRef = $data['external_ref'];
                $lat = $data['lat'];
                $lng = $data['lng'];

                // DB 전달
                $result = $this->placesRepository->upsertRepository($name, $category, $address, $externalRef, $lat, $lng);

                return $result;
            });
        } catch (DbException) {
            throw new HttpException(500, 'NO_DATE', '외부 결과를 내부로 불러오는 중 실패했습니다.');
        }
    }

    // 장소 단건 조회
    public function singlePlace($placeId): ?array
    {
        try {
            // db 전달
            $result = $this->placesRepository->placeRepository($placeId);

            return $result;
        } catch (DbException) {
            throw new HTTPException(500, 'PLACE_NOT', '장소를 찾던 도중 실패했습니다.');
        }
    }
}
