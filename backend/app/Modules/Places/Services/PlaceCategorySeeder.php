<?php

namespace Tripmate\Backend\Modules\Places\Services;

use Tripmate\Backend\Core\DB;
use Tripmate\Backend\Core\Repository;

class PlaceCategorySeeder extends Repository
{
    public function __construct()
    {
        parent::__construct(DB::conn());
    }

    public function Category(): void
    {
        // 코드와 이름 매핑
        $categories = [
                ['code' => 'cafe', 'name' => '카페'],
                ['code' => 'restaurant', 'name' => '음식점'],
                ['code' => 'bakery', 'name' => '베이커리'],
                ['code' => 'fastfood', 'name' => '패스트푸드'],
                ['code' => 'convenience_store', 'name' => '편의점'],
                ['code' => 'bar', 'name' => '술집'],
                ['code' => 'gym', 'name' => '헬스장'],
                ['code' => 'department_store', 'name' => '백화점'],
                ['code' => 'book_store', 'name' => '서점'],
                ['code' => 'beauty_salon', 'name' => '미용실'],
                ['code' => 'laundry', 'name' => '세탁소'],
                ['code' => 'car_wash', 'name' => '세차장'],
                ['code' => 'bank', 'name' => '은행'],
                ['code' => 'post_office', 'name' => '우체국'],
                ['code' => 'movie_theater', 'name' => '영화관'],
                ['code' => 'park', 'name' => '공원'],
                ['code' => 'museum', 'name' => '박물관'],
                ['code' => 'art_gallery', 'name' => '미술관'],
                ['code' => 'zoo', 'name' => '동물원'],
                ['code' => 'amusement_park', 'name' => '놀이공원'],
                ['code' => 'stadium', 'name' => '경기장'],
                ['code' => 'hotel', 'name' => '호텔'],
                ['code' => 'motel', 'name' => '모텔'],
                ['code' => 'hospital', 'name' => '병원'],
                ['code' => 'dentist', 'name' => '치과'],
                ['code' => 'pharmacy', 'name' => '약국'],
                ['code' => 'airport', 'name' => '공항'],
                ['code' => 'bus_station', 'name' => '버스 정류장'],
                ['code' => 'parking', 'name' => '주차장'],
                ['code' => 'train_station', 'name' => '기차역'],
                ['code' => 'subway_station', 'name' => '지하철역'],
                ['code' => 'gas_station', 'name' => '주유소'],
                ['code' => 'school', 'name' => '학교'],
                ['code' => 'library', 'name' => '도서관'],
                ['code' => 'etc', 'name' => '기타']

        ];

        // db 값 추가
        foreach ($categories as $category) {
            $sql = 'INSERT INTO PlaceCategory (code, name) VALUES (:code, :name)
                        ON DUPLICATE KEY UPDATE name = :name_update';
            $param = ['code' => $category['code'], 'name' => $category['name'], 'name_update' => $category['name']];
            $this->query($sql, $param);
        }
    }
}
