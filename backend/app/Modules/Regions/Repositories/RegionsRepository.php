<?php

namespace Tripmate\Backend\Modules\Regions\Repositories;

use PDO;
use Tripmate\Backend\Core\Repository;

/**
 * 지역 리포지토리
 */
class RegionsRepository extends Repository
{
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    /**
     * 지역 매칭 리포지토리
     */
    public function selectRegion(mixed $region)
    {
        $sql = "SELECT region_id, name, country_code
                    FROM Region WHERE name LIKE CONCAT('%', :region, '%');";
        $param = ['region' => $region];
        $data = $this->fetchAll($sql, $param);
        if ($data === []) {
            return null;
        }

        return $data;
    }

    /**
     * 지역 조회 리포지토리
     * @return mixed[]
     */
    public function getSelectRegion($country): array
    {
        $sql = 'SELECT *
                    FROM Region WHERE country_code = :country;';
        $param = ['country' => $country];

        return $this->fetchAll($sql, $param);
    }
}
