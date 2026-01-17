<?php

namespace Tripmate\Backend\Modules\Regions\Controllers;

use Tripmate\Backend\Core\Controller;
use Tripmate\Backend\Core\Request;
use Tripmate\Backend\Core\Response;
use Tripmate\Backend\Core\Validator;
use Tripmate\Backend\Modules\Regions\Services\RegionsService;

/**
 * 지역 컨트롤러(지역 검색)
 */
class RegionsController extends Controller
{
    private readonly RegionsService $regionsService;
    private readonly Validator $validator;

    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);
        $this->regionsService = new RegionsService();
        $this->validator = new Validator();
    }

    /**
     * 지역 검색 컨트롤러
     *
     */
    public function getRegion(): Response
    {
        return $this->run(function () {
            $region = $this->request->query(); // 지역 이름 데이터
            $this->validator->validateRegionSearch($region);

            $query = $region['query'] ?? null;
            $country = $region['country'] ?? 'KR';

            // 쿼리가 있을 경우
            if (!empty($query)) {
                return $this->regionsService->searchRegions($query);
            }

            return $this->regionsService->listRegions($country);
        });
    }
}
