<?php

namespace Tripmate\Backend\Modules\Regions\Services;

use Tripmate\Backend\Common\Exceptions\DbException;
use Tripmate\Backend\Common\Exceptions\HttpException;
use Tripmate\Backend\Core\DB;
use Tripmate\Backend\Core\Service;
use Tripmate\Backend\Modules\Regions\Repositories\RegionsRepository;

/**
 *  지역 서비스
 */
class RegionsService extends Service
{
    private readonly RegionsRepository $regionsRepository;

    public function __construct()
    {
        parent::__construct(DB::conn());
        $this->regionsRepository = new RegionsRepository($this->db);
    }

    /**
     * 지역 매칭 서비스
     * @return array{item: array[]|string}
     */
    public function searchRegions(mixed $region)
    {
        try {
            return $this->transaction(function () use ($region) {
                $result = $this->regionsRepository->selectRegion($region);
                if ($result == null) {
                    throw new HttpException(404, 'NOT_FOUND_REGION', '일치하는 지역이 없습니다.');
                }

                return $result;
            });
        } catch (DbException $e) {
            throw new HttpException(500, 'REGION_FOUND_ERROR', '지역 조회에 실패하였습니다.', $e);
        }
    }

    /**
     * 지역 목록 조회
     */
    public function listRegions($country): array
    {
        try {
            return $this->regionsRepository->getSelectRegion($country);
        } catch (DbException $e) {
            throw new HttpException(500, 'REGION_NOT_FOUND', '지역 목록 ', $e);
        }
    }
}
