<?php

namespace Tripmate\Backend\Modules\Places\Controllers;

use Tripmate\Backend\Core\Controller;
use Tripmate\Backend\Core\Request;
use Tripmate\Backend\Core\Response;
use Tripmate\Backend\Core\Validator;
use Tripmate\Backend\Modules\Places\Services\PlacesService;

class PlacesController extends Controller
{
    public Validator $validator;
    public PlacesService $service;

    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);

        $this->validator = new Validator();
        $this->service = new PlacesService();
    }

    // 임시 CORS
    public function cors(): void
    {
        \header('Access-Control-Allow-Origin: *'); // 모든 출처(도메인, 포트) 허용
        \header('Access-Control-Allow-Methods: GET, POST, OPTIONS'); // 요청 방식 허용
        \header('Access-Control-Allow-Headers: Content-Type, Authorization');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { // 사전 요청 처리
            \http_response_code(200);
            exit();
        }
    }

    /**
     * auto 자동검색기능
     */
    public function autocomplete(): Response
    {
        $this->cors();

        return $this->run(function (): array {
            $query = $this->request->query();
            $this->validator->validatePlace($query);

            return $this->service->autoPlace($query['input'], $query['session_token']);
        });
    }

    /**
     * 장소 검색 컨트롤러
     * API를 호출하여 장소 검색 후 장소 반환
     */
    public function search(): Response
    {
        $this->cors();

        return $this->run(function (): array {
            $query = $this->request->query();
            $this->validator->validatePlace($query);

            $place = $query['place'] ?? null;
            $token = $query['pageToken'] ?? null;

            return $this->service->searchByText($place, $token);
        });
    }

    /**
     * Geocoding (좌표->주소) 변환 컨트롤러
     */
    public function reverseGeocoding(): Response
    {
        $this->cors();

        return $this->run(function () {
            $query = $this->request->query();
            $this->validator->validatereverseGeocoding($query);

            $lat = $query['lat'];
            $lng = $query['lng'];

            return $this->service->getAddressFromCoordinates($lat, $lng);
        });
    }

    /**
     * 장소의 Id 받아 장소 반환 컨트롤러
     */
    public function placeGeocoding(): Response
    {
        $this->cors();

        return $this->run(function (): array {
            $query = $this->request->query();
            $this->validator->validatePlaceGeocoding($query);

            $placeId = $query['place_id'];

            return $this->service->getPlaceDetailsById($placeId);
        });
    }

    /**
     * 주변 지역 검색
     */
    public function searchNearby($lat, $lng): Response
    {
        $this->cors();

        return $this->run(function (): array {
            $query = $this->request->query();
            $this->validator->validateReverseGeocoding($query);

            $lat = $query['lat'];
            $lng = $query['lng'];
            $radius = 1000; // 고정값

            $result = $this->service->nearbyPlaces($lat, $lng, $radius);

            return $result;
        });
    }

    /**
     * 사용자가 선택한 외부 결과 중 하나 내부 저장
     */
    public function placeUpsert(): Response
    {
        return $this->run(function () {
            $this->requireAuth();

            $data = $this->request->body();
            $this->validator->validatePlaceCategory($data);

            return $this->service->upsert($data);
        });
    }

    // 단건 조회
    public function singlePlaceSearch(): Response
    {
        return $this->run(function (): ?array {
            $placeId = $this->request->getAttribute('place_id');
            $this->validator->validatePlaceId($placeId);

            return $this->service->singlePlace($placeId);
        });
    }
}
