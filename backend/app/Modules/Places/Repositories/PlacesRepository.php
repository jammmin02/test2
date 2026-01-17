<?php

namespace Tripmate\Backend\Modules\Places\Repositories;

use PDO;
use Tripmate\Backend\Core\DB;
use Tripmate\Backend\Core\Repository;

class PlacesRepository extends Repository
{
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    // upsert db 로직
    public function upsertRepository($name, $category, $address, $externalRef, $lat, $lng): ?array
    {
        // category_name → category_id 매핑
        $sql = 'SELECT category_id FROM PlaceCategory WHERE code = :code';
        $param = ['code' => $category];
        $data = $this->fetchOne($sql, $param);
        if ($data === null || $data === []) {
            $sql = 'SELECT category_id FROM PlaceCategory WHERE code = :etc';
            $data = $this->fetchOne($sql, ['etc' => 'etc']);
            $categoryId = $data['category_id'];
        } else {
            $categoryId = $data['category_id'];
        }

        // Place 테이블에 저장 (external_ref 기준)
        $insertSql = 'INSERT INTO Place(category_id, name, address, lat, lng, external_ref)
                        VALUE (:cid, :name, :addr, :lat, :lng, :ext)
                        ON DUPLICATE KEY UPDATE
                        name = :name_up, category_id = :cid_up, address = :addr_up, lat = :lat_up, lng = :lng_up;';
        $insertParam = ['cid' => $categoryId, 'name' => $name, 'addr' => $address, 'lat' => $lat, 'lng' => $lng, 'ext' => $externalRef,
                        'cid_up' => $categoryId, 'name_up' => $name, 'addr_up' => $address, 'lat_up' => $lat, 'lng_up' => $lng];
        $this->query($insertSql, $insertParam);

        // 클라이언트 반환 값
        $selectSql = 'SELECT place_id, category_id, name, address, lat, lng, external_ref FROM Place WHERE external_ref = :exr;';
        $selectParm = ['exr' => $externalRef];

        return $this->fetchOne($selectSql, $selectParm);
    }

    // 장소 단건 조회
    public function placeRepository($placeId): ?array
    {

        // place 조회
        $sql = 'SELECT p.place_id, p.name, p.address, p.lat, p.lng, pc.name AS category 
                    FROM Place p 
                    LEFT JOIN PlaceCategory pc ON p.category_id=pc.category_id 
                    WHERE p.place_id = :place_id;';
        $param = ['place_id' => $placeId];

        return $this->fetchOne($sql, $param);
    }
}
