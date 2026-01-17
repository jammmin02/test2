<?php

namespace Tripmate\Backend\Modules\Places\Services;

class DummyPlaces
{
    // 더미데이터 필터링
    /**
     * @return mixed[]
     */
    public static function getPlaces($query): array
    {
        $arr = [];

        foreach (self::$places as $place) {
            $name = $place['name'];
            $category = $place['category'];
            $address = $place['address'];

            // 이름 또는 주소, 카테고리에 해당 검색값이 들어가는지 확인
            if (\str_contains((string) $name, (string) $query) || \str_contains((string) $category, (string) $query) || \str_contains((string) $address, (string) $query)) {
                $arr[] = $place;
            }
        }


        return $arr;
    }

    // 더미데이터
    private static array $places = [
        [
            'external_ref' => 'ChIJ001A',
            'name' => '스타벅스 홍대입구역점',
            'category' => '카페',
            'address' => '서울특별시 마포구 양화로 178',
            'lat' => 37.5563,
            'lng' => 126.9234,
            'url' => 'https://www.google.com/maps/place/?q=external_ref:ChIJ001A'
        ],
        [
            'external_ref' => 'ChIJ002B',
            'name' => '맥도날드 홍대점',
            'category' => '패스트푸드',
            'address' => '서울특별시 마포구 어울마당로 123',
            'lat' => 37.5569,
            'lng' => 126.9235,
            'url' => 'https://www.google.com/maps/place/?q=external_ref:ChIJ002B'
        ],
        [
            'external_ref' => 'ChIJ003C',
            'name' => '파리바게뜨 합정역점',
            'category' => '베이커리',
            'address' => '서울특별시 마포구 양화로 45',
            'lat' => 37.5501,
            'lng' => 126.9142,
            'url' => 'https://www.google.com/maps/place/?q=external_ref:ChIJ003C'
        ],
        [
            'external_ref' => 'ChIJ004D',
            'name' => '이디야커피 상수역점',
            'category' => '카페',
            'address' => '서울특별시 마포구 와우산로 64',
            'lat' => 37.5472,
            'lng' => 126.9238,
            'url' => 'https://www.google.com/maps/place/?q=external_ref:ChIJ004D'
        ],
        [
            'external_ref' => 'ChIJ005E',
            'name' => 'CGV 홍대',
            'category' => '영화관',
            'address' => '서울특별시 마포구 양화로 153',
            'lat' => 37.5555,
            'lng' => 126.9228,
            'url' => 'https://www.google.com/maps/place/?q=external_ref:ChIJ005E'
        ],
        [
            'external_ref' => 'ChIJ006F',
            'name' => '교보문고 합정점',
            'category' => '서점',
            'address' => '서울특별시 마포구 양화로 45 메세나폴리스몰',
            'lat' => 37.5500,
            'lng' => 126.9137,
            'url' => 'https://www.google.com/maps/place/?q=external_ref:ChIJ006F'
        ],
        [
            'external_ref' => 'ChIJ007G',
            'name' => '롯데시네마 홍대입구',
            'category' => '영화관',
            'address' => '서울특별시 마포구 양화로 176',
            'lat' => 37.5561,
            'lng' => 126.9232,
            'url' => 'https://www.google.com/maps/place/?q=external_ref:ChIJ007G'
        ],
        [
            'external_ref' => 'ChIJ008H',
            'name' => '세븐일레븐 홍대입구역점',
            'category' => '편의점',
            'address' => '서울특별시 마포구 양화로 186',
            'lat' => 37.5569,
            'lng' => 126.9241,
            'url' => 'https://www.google.com/maps/place/?q=external_ref:ChIJ008H'
        ],
        [
            'external_ref' => 'ChIJ009I',
            'name' => '신촌 세브란스병원',
            'category' => '병원',
            'address' => '서울특별시 서대문구 연세로 50-1',
            'lat' => 37.5621,
            'lng' => 126.9369,
            'url' => 'https://www.google.com/maps/place/?q=external_ref:ChIJ009I'
        ],
        [
            'external_ref' => 'ChIJ010J',
            'name' => '연세대학교',
            'category' => '대학교',
            'address' => '서울특별시 서대문구 연세로 50',
            'lat' => 37.5658,
            'lng' => 126.9386,
            'url' => 'https://www.google.com/maps/place/?q=external_ref:ChIJ010J'
        ],
        [
            'external_ref' => 'ChIJ011K',
            'name' => '서울서부경찰서',
            'category' => '경찰서',
            'address' => '서울특별시 마포구 연희로 135',
            'lat' => 37.5643,
            'lng' => 126.9282,
            'url' => 'https://www.google.com/maps/place/?q=external_ref:ChIJ011K'
        ],
        [
            'external_ref' => 'ChIJ012L',
            'name' => '망원한강공원',
            'category' => '공원',
            'address' => '서울특별시 마포구 망원로 467',
            'lat' => 37.5564,
            'lng' => 126.8962,
            'url' => 'https://www.google.com/maps/place/?q=external_ref:ChIJ012L'
        ],
        [
            'external_ref' => 'ChIJ013M',
            'name' => '홍익대학교 미술관',
            'category' => '미술관',
            'address' => '서울특별시 마포구 와우산로 94',
            'lat' => 37.5511,
            'lng' => 126.9259,
            'url' => 'https://www.google.com/maps/place/?q=external_ref:ChIJ013M'
        ],
        [
            'external_ref' => 'ChIJ014N',
            'name' => '우리은행 홍대지점',
            'category' => '은행',
            'address' => '서울특별시 마포구 양화로 166',
            'lat' => 37.5557,
            'lng' => 126.9227,
            'url' => 'https://www.google.com/maps/place/?q=external_ref:ChIJ014N'
        ],
        [
            'external_ref' => 'ChIJ003C',
            'name' => '올리브영 홍대중앙점',
            'category' => '상점',
            'address' => '서울특별시 마포구 홍익로 15',
            'lat' => 37.5555, 'lng' => 126.9238,
            'url' => 'http://googleusercontent.com/maps.google.com/2'
        ],
        [
            'external_ref' => 'ChIJ004D',
            'name' => '홍대 쭈꾸미',
            'category' => '음식점',
            'address' => '서울특별시 마포구 어울마당로 145-1',
            'lat' => 37.5550, 'lng' => 126.9220,
            'url' => 'http://googleusercontent.com/maps.google.com/3'
        ],
        [
            'external_ref' => 'ChIJ005E',
            'name' => '커피프린스 1호점',
            'category' => '카페',
            'address' => '서울특별시 마포구 와우산로29길 5',
            'lat' => 37.5529, 'lng' => 126.9240,
            'url' => 'http://googleusercontent.com/maps.google.com/4'
        ],
        [
            'external_ref' => 'ChIJ015O',
            'name' => '이마트24 합정역점',
            'category' => '편의점',
            'address' => '서울특별시 마포구 양화로 55',
            'lat' => 37.5507,
            'lng' => 126.9149,
            'url' => 'https://www.google.com/maps/place/?q=external_ref:ChIJ015O'
        ]
    ];
}
