<?php

namespace ProximaCustom\core;

use Proxima\core\Session;
use Proxima\core\WebPath;
use Proxima\models\system\Log;
use Proxima\models\content\Media;
use Proxima\Helpers\TimecodeHelper;
use Proxima\models\content\Catalog;
use Proxima\models\content\Content;
use ProximaCustom\types\ConvertStatus;
use Proxima\models\content\UserMetadata;
use Proxima\models\content\SystemMetadata;

class CasMetadataMapper
{
    /**
     * ContentId
     *
     * @var \Proxima\models\content\Content
     */
    private $content;
    public function __construct($content)
    {
        $this->content = $content;
    }

    public function makeCasMetadata()
    {
        $content = $this->content;
        $meta = UserMetadata::find($content);
        $sysMeta = SystemMetadata::find($content);

        $bdDtm = $meta->get('usr_broad_dtm');
        $bdDtmArray = explode(' ', $bdDtm);
        $bdDt = '';
        if (!empty($bdDtmArray)) {
            $bdDt = $bdDtmArray[0];
        }

        // 사번 처리
        $user = Session::get('user');
        $empId = $user['emp_id'];
        if (empty($empId)) {
            $empId = $content->get('updater_id');
        }

        if (empty($empId)) {
            $empId = $content->get('reg_user_id');
        }

        $posterPathInfo = $this->getPosterPathInfo($content);
        $dynamicThumbPathInfo = $this->getDynamicThumbPathInfo($meta);
        $casMetadata = [
            // 기본 정보
            'tvmMetaInfoEntity' => [
                'vmId' => $meta->get('usr_video_code'),
                'vmTitle' => $content->get('title'),
                'vmBgmYn' => $this->getCasCode('usr_bgm', $meta),
                'vmNarYn' => $this->getCasCode('usr_nar', $meta),
                'vmGrdCd' => $this->getCasCode('usr_grade', $meta),
                'vmDesc' => $meta->get('usr_summary'),
                'useTermSDtm' => $this->formatDate($meta->get('usr_expire_period_from')),
                'useTermEDtm' => $this->formatDate($meta->get('usr_expire_period_to')),
                'useYn' => $this->getCasCode('usr_use', $meta),
                'vmChnCd' => $meta->get('usr_channel_code'),
                'pgmBdDt' => $this->formatDate($bdDt),
                'pgmBdDtmStr' => $this->formatDateTime($bdDtm), //2019-06-27 이승수 pgmBdDtm에서 변경
                // 쇼크라이브인경우 pgm_group 정보를 주도록 수정
                'pgmNm' => $meta->get('usr_channel_code') === 'CJSL' ? $meta->get('usr_pgm_group_name') : $meta->get('usr_pgm_name'),
                'pgmCd' => $meta->get('usr_channel_code') === 'CJSL' ? $meta->get('usr_pgm_group_code') : $meta->get('usr_pgm_code'),
                'vmRatio' => $meta->get('usr_aspect_ratio'),
                'vmViewCnt' => $content->getViewCount(),
                'cateId' => $meta->get('usr_video_category_id'),
                'vmTotalMs' => $sysMeta->getDurationMilliSecond(),
                'convSt' => ConvertStatus::getCasCode($content->get('state')),
                'thumbImgPath' => $posterPathInfo['path'],
                'thumbImgNm' => $posterPathInfo['name'],
                'dynamicImgPath' => $dynamicThumbPathInfo['path'],
                'dynamicImgNm' => $dynamicThumbPathInfo['name'],
                'searchYn' => $this->getCasCode('usr_searchable', $meta),
                'mamId' => $meta->get('usr_mam_id'),
                'contentsId' => $this->getContentsId($content->get('content_id'), $meta),
                'contentId' => $content->get('content_id'),
                'sexTypeCd' => $this->getCasCode('usr_sex', $meta),
                'modId' => $empId,
                'modIp' => $_SERVER['REMOTE_ADDR']
            ],
            // 카탈로그 이미지 리스트
            'tvmCaptureEntityList' => $this->getCatalogList($content, $sysMeta),
            // 동영상리스트
            'tvmEncodingEntityList' => $this->getProxyList($content),
            // 동영상키워드
            'tvmKeywordEntityList' => $this->getKeywordList($meta),
            // 상품정보
            'tvmRelationItemEntityList' => $this->getItemList($meta),
            // 연령정보
            'tvmAgeEntityList' => $this->getAgeList($meta),
            // 장르
            'tvmGenreEntityList' => $this->getGenreList($meta),
            // 콘텐츠 유형
            'tvmContentsTypeEntityList' => $this->getContentTypeList($meta),
        ];

        return $casMetadata;
    }

    private function formatDate($date)
    {
        if (empty($date)) {
            return $date;
        }
        return (new \Carbon\Carbon($date))->format('Ymd');
    }

    private function formatDateTime($dateTime)
    {
        if (empty($dateTime)) {
            return $dateTime;
        }
        return (new \Carbon\Carbon($dateTime))->format('YmdHis');
    }

    private function getPosterPathInfo($content)
    {
        $medias = Media::findByContent($content, [Media::MEDIA_TYPE_THUMB]);
        if (empty($medias)) {
            return [
                'path' => '',
                'name' => ''
            ];
        }
        return WebPath::dividePath($medias[0]->get('url'));
    }

    private function getDynamicThumbPathInfo($meta)
    {
        $dynamicThumbUrl = $meta->get('usr_dynamic_thumb');
        if (empty($dynamicThumbUrl)) {
            return [
                'path' => '',
                'name' => ''
            ];
        }

        // full url 이라 cdn주소를 제거해야함
        $subUrl = WebPath::removeCdnRootPath($dynamicThumbUrl);
        return WebPath::dividePath($subUrl);
    }

    private function getAgeList($meta)
    {
        $age = $meta->get('usr_age');
        if (empty($age)) {
            return [];
        }
        $ageList = [];
        $ages = explode(',', $age);

        foreach ($ages as $value) {
            $code = $this->getCasCode('usr_age', $meta, $value);
            if (empty($code)) {
                continue;
            }
            $ageList[] = [
                'ageTypeCd' => $code
            ];
        }
        return $ageList;
    }

    private function getContentTypeList($meta)
    {
        $contentType = $meta->get('usr_content_type');
        if (empty($contentType)) {
            return [];
        }
        $contentTypeList = [];
        $contentTypes = explode(',', $contentType);
        foreach ($contentTypes as $value) {
            $code = $this->getCasCode('usr_content_type', $meta, $value);
            if (empty($code)) {
                continue;
            }
            $contentTypeList[] = [
                'contTypeCd' => $code
            ];
        }
        return $contentTypeList;
    }

    private function getGenreList($meta)
    {
        $genre = $meta->get('usr_genre');
        if (empty($genre)) {
            return [];
        }
        $genreList = [];
        $genres = explode(',', $genre);
        foreach ($genres as $value) {
            $code = $this->getCasCode('usr_genre', $meta, $value);
            if (empty($code)) {
                continue;
            }
            $genreList[] = [
                'genreTypeCd' => $code
            ];
        }
        return $genreList;
    }

    private function getCasCode($key, $meta, $value = null)
    {
        if (is_null($value)) {
            $value = $meta->get($key);
        }
        if (empty($value)) {
            return $value;
        }
        switch ($key) {
            case 'usr_bgm':
            case 'usr_nar': {
                    return ($value === '있음') ? 'Y' : 'N';
                }
            case 'usr_grade': {
                    switch ($value) {
                        case '전체관람가':
                            return 'ALL';
                        case '12세이상 관람가':
                            return 'TWOELVE';
                        case '15세이상 관람가':
                            return 'FIFTEEN';
                        case '청소년관람불가':
                            return 'NINETEEN';
                        default:
                            return '';
                    }
                }
            case 'usr_use': {
                    return ($value === '사용') ? 'Y' : 'N';
                }
            case 'usr_video_category': {
                    switch ($value) {
                        case '패션':
                            return 'FASHION';
                        case '뷰티':
                            return 'BEUTY';
                        case '리빙':
                            return 'LIVING';
                        case '푸드':
                            return 'FOOD';
                        case '트립':
                            return 'TRIP';
                        case '기타':
                            return 'ETC';
                    }
                }
            case 'usr_sex': {
                    switch ($value) {
                        case '전체':
                            return 'ALL';
                        case '남성':
                            return '01';
                        case '여성':
                            return '02';
                    }
                }
            case 'usr_searchable': {
                    switch ($value) {
                        case '노출':
                            return 'Y';
                        case '미노출':
                            return 'N';
                    }
                }
            case 'usr_age': {
                    switch ($value) {
                        case '전체':
                            return 'ALL';
                        case '10대':
                            return '10';
                        case '20대':
                            return '20';
                        case '30대':
                            return '30';
                        case '40대':
                            return '40';
                        case '50대':
                            return '50';
                        case '60대':
                            return '60';
                        case '70대이상':
                            return '70';
                    }
                }
            case 'usr_genre': {
                    switch ($value) {
                            /*
                    '01' //게임
                    '02' //공포
                    '03' //교육
                    '04' //로맨스
                    '05' //리얼리티
                    '06' //뮤직
                    '07' //스포츠
                    '08' //애니메이션
                    '09' //액션
                    '10' //코미디
                    '11' //판타지
                    */
                        case '게임':
                            return '01';
                        case '공포':
                            return '02';
                        case '교육':
                            return '03';
                        case '로맨스':
                            return '04';
                        case '리얼리티':
                            return '05';
                        case '뮤직':
                            return '06';
                        case '스포츠':
                            return '07';
                        case '애니메이션':
                            return '08';
                        case '액션':
                            return '09';
                        case '코미디':
                            return '10';
                        case '판타지':
                            return '11';
                    }
                }
            case 'usr_content_type': {
                    switch ($value) {
                            /*
                    '01' //ASMR
                    '02' //다이어트/운동
                    '03' //동영상 베스트
                    '04' //리뷰
                    '05' //리서치
                    '06' //먹방
                    '07' //메이크업
                    '08' //모바일라이브-본방
                    '09' //브랜드소개
                    '10' //브이로그
                    '11' //비포앤애프터
                    '12' //사내콘텐츠
                    '13' //서비스소개
                    '14' //연예인/스타
                    '15' //요리법
                    '16' //인터뷰
                    '17' //인트로
                    '18' //조립법 DIY
                    '19' //커버
                    '20' //코디
                    '21' //하이라이트
                    '22' //현장스케치
                    */
                        case 'ASMR':
                            return '01';
                        case '다이어트/운동':
                            return '02';
                        case '동영상 베스트':
                            return '03';
                        case '리뷰':
                            return '04';
                        case '리서치':
                            return '05';
                        case '먹방':
                            return '06';
                        case '메이크업':
                            return '07';
                        case '모바일라이브-본방':
                            return '08';
                        case '브랜드소개':
                            return '09';
                        case '브이로그':
                            return '10';
                        case '비포앤애프터':
                            return '11';
                        case '사내콘텐츠':
                            return '12';
                        case '서비스소개':
                            return '13';
                        case '연예인/스타':
                            return '14';
                        case '요리법':
                            return '15';
                        case '인터뷰':
                            return '16';
                        case '인트로':
                            return '17';
                        case '조립법 DIY':
                            return '18';
                        case '커버':
                            return '19';
                        case '코디':
                            return '20';
                        case '하이라이트':
                            return '21';
                        case '현장스케치':
                            return '22';
                    }
                }
        }
    }

    // 메타에 mam_id가 있다면 mam_content_id를 리턴, 없다면 vms_content_id를 리턴
    private function getContentsId($vms_content_id, $meta)
    {
        $mam_id = $meta->get('usr_mam_id');
        if (!empty($mam_id)) {
            return $meta->get('usr_mam_content_id');
        } else {
            return $vms_content_id;
        }
    }

    private function getCatalogList($content, $sysMeta)
    {
        $frameRate = $sysMeta->get('sys_frame_rate');
        $catalogList = Catalog::getCatalogList($content);
        $list = [];
        foreach ($catalogList as $catalog) {
            if (is_null($catalog) || $catalog->isEmpty()) {
                continue;
            }
            // catalog url은 cdn 주소가 포함되어 있어 replace해줘야 한다.
            $url = $catalog->get('url');
            $cdnUrl = CdnUrl::getUrl();
            $url = str_replace($cdnUrl, '', $url);
            $pathInfo = WebPath::dividePath($url);
            $list[] = [
                'captureIdx' => $catalog->get('show_order'),
                'position' => TimecodeHelper::frameToMillisecond($catalog->get('start_frame'), $frameRate),
                'capturePath' => $pathInfo['path'],
                'captureName' => $pathInfo['name']
            ];
        }
        return $list;
    }

    private function getProxyList($content)
    {
        $medias = Media::findByContent($content, [Media::MEDIA_TYPE_PROXY, 'proxy_hi']);
        if (empty($medias)) {
            return [];
        }
        $list = [];
        foreach ($medias as $media) {
            if (is_null($media) || $media->isEmpty()) {
                continue;
            }
            $pathInfo = WebPath::dividePath($media->get('url'));
            $list[] = [
                'encType' => ($media->get('media_type') === 'proxy_hi') ? 'H' : 'L',
                'encFlPath' => $pathInfo['path'],
                'encFlNm' => $pathInfo['name'],
                'screenSizeX' => $media->get('width'),
                'screenSizeY' => $media->get('height')
            ];
        }
        return $list;
    }

    private function getKeywordList($meta)
    {
        $keywords = json_decode($meta->get('usr_keyword'), true);
        if (empty($keywords)) {
            return [];
        }
        $list = [];

        $i = 1;
        foreach ($keywords as $keyword) {
            if (empty($keyword)) {
                continue;
            }
            $list[] = [
                'dispOrder' => $i++,
                'keyword' => $keyword
            ];
        }
        return $list;
    }

    private function getItemList($meta)
    {
        $itemList = json_decode($meta->get('usr_item_list'), true);
        if (empty($itemList)) {
            return [];
        }
        $list = [];
        foreach ($itemList as $item) {
            if (empty($item)) {
                continue;
            }
            $list[] = [
                'chnCd' => $item['chnCd'],
                'itemCode' => $item['itemCd'],
                'repItemYn' => $item['repItemYn'],
                'dispYn' => $item['dispYn'],
                'dispOrder' => $item['dispOrder']
            ];
        }
        return $list;
    }
}
