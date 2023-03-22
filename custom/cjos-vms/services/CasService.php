<?php

namespace ProximaCustom\services;

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use Proxima\core\Logger;
use ProximaCustom\core\CasMetadataMapper;

/**
 * CAS 연동 관련 서비스
 */
class CasService
{
    private $client;
    private $logger;
    const API_VER = 'v0.0.1';

    public function __construct()
    {
        $dotenv = Dotenv::create(dirname(__DIR__), '.env');
        $dotenv->load();
        $this->client = new Client([
            'base_uri' => getenv('CAS_API_URL')
        ]);
        $this->logger = new Logger('Cas_Service');
    }

    /**
     * 프로그램 조회
     *
     * @param string $vmChnCd
     * @param string $bdDt
     * @param string $pgmGrpCd
     * @return array
     */
    public function getPgms($vmChnCd, $bdDt, $pgmGrpCd = null)
    {
        $query = [
            'vmChnCd' => $vmChnCd,
            'bdDt' => $bdDt
        ];

        if (!is_null($pgmGrpCd)) {
            $query['pgmGrpCd'] = $pgmGrpCd;
        }

        $options = $this->getDefaultOptions($query);

        $res = $this->client->get($this->getApiPath('/pgmInfoList'), $options);
        return $this->makeReturn($res);
    }

    /**
     * 프로그램의 상품정보
     *
     * @param string $bdDt 방송일
     * @param string $vmChnCd 채널코드
     * @param string $pgmCd 프로그램코드
     * @return array
     */
    public function getPgmItems($bdDt, $vmChnCd, $pgmCd)
    {
        $query = [
            'bdDt' => $bdDt,
            'vmChnCd' => $vmChnCd,
            'pgmCd' => $pgmCd
        ];

        $options = $this->getDefaultOptions($query);

        $res = $this->client->get($this->getApiPath('/pgmItemInfoList'), $options);
        return $this->makeReturn($res);
    }

    /**
     * 상품 검색
     *
     * @param string $chnCd 상품채널코드
     * @param string $itemCd 상품코드
     * @return array
     */
    public function getItem($chnCd, $itemCd, $itemNm, $pageNumber = 1, $pageSize = 10, $needTotalCount = false)
    {
        $payload = [];
        $itemCriteria = [
            'chnCd' => $chnCd
        ];

        if (!empty($itemCd)) {
            $itemCriteria['itemCd'] = $itemCd;
        }

        if (!empty($itemNm)) {
            $itemCriteria['itemNm'] = $itemNm;
        }

        $payload['itemCriteria'] = $itemCriteria;

        $pagingCriteria = [
            'pageNumber' => $pageNumber,
            'pageSize' => $pageSize,
            'needTotalCount' => $needTotalCount
        ];

        $payload['pagingCriteria'] = $pagingCriteria;

        $options = $this->getDefaultOptions();
        $options['json'] = $payload;

        $res = $this->client->post($this->getApiPath('/itemInfo'), $options);
        return $this->makeReturn($res);
    }

    public function getVideoInfo($vmId)
    {
        $query = [
            'vmId' => $vmId
        ];

        $options = $this->getDefaultOptions($query);

        $res = $this->client->get($this->getApiPath('/videoInfo'), $options);
        return $this->makeReturn($res);
    }

    /**
     * 상품 채널 조회
     *
     * @return array
     */
    public function getItemChannels()
    {
        $options = $this->getDefaultOptions();

        $res = $this->client->get($this->getApiPath('/itemChnList'), $options);
        return $this->makeReturn($res);
    }

    /**
     * 프로그램 그룹 조회
     *
     * @return array
     */
    public function getPgmGroups()
    {
        $options = $this->getDefaultOptions();

        $res = $this->client->get($this->getApiPath('/pgmGrpInfoList'), $options);
        return $this->makeReturn($res);
    }

    /**
     * 응답 json 문자열을 Return 배열 생성
     *
     * @param  $response \Psr\Http\Message\ResponseInterface
     * @return array
     */
    private function makeReturn($response)
    {
        return json_decode($response->getBody(), true);
    }

    /**
     * 기본 옵션 조회
     *
     * @param string $query
     * @return array
     */
    private function getDefaultOptions($query = null)
    {
        $options = [
            'headers' => [
                'User-Agent' => 'VMS',
                'Accept'     => 'application/json',
                'X-CALLER-ID' => 'VMS',
            ]
        ];

        if (!is_null($query)) {
            $options['query'] = $query;
        }
        return $options;
    }

    /**
     * API 경로 조회
     *
     * @param string $path
     * @return string
     */
    private function getApiPath($path)
    {
        return '/video/' . self::API_VER . $path;
    }

    /**
     * 제작 채널 조회
     *
     * @return array
     */
    public function getChannels()
    {
        return [
            [
                'id' => 1,
                'code' => 'CJOL',
                'name' => '오쇼핑라이브',
                'broadcast' => true
            ],
            [
                'id' => 2,
                'code' => 'CJOP',
                'name' => '오쇼핑플러스',
                'broadcast' => true
            ],
            [
                'id' => 3,
                'code' => 'CJSL',
                'name' => '쇼크라이브',
                'broadcast' => true
            ],
            [
                'id' => 4,
                'code' => 'CJDD',
                'name' => '다다스튜디오',
                'broadcast' => false
            ],
            [
                'id' => 5,
                'code' => 'CJVD',
                'name' => '협력사',
                'broadcast' => false
            ],
            [
                'id' => 6,
                'code' => 'ETC',
                'name' => '기타',
                'broadcast' => false
            ]
        ];
    }

    public function getChannel($channelCode)
    {
        $channels = $this->getChannels();
        $foundChannel = null;
        foreach ($channels as $channel) {
            if ($channel['code'] === $channelCode) {
                $foundChannel = $channel;
                break;
            }
        }
        return $foundChannel;
    }

    /**
     * 상품상태 조회
     *
     * @param string $slCls 상품상태코드
     * @return string
     */
    public function getItemStatusName($slCls)
    {
        // A:정상, D:영구중단, I:판매중단, S:매진
        $statusMap = [
            'A' => '정상',
            'D' => '영구중단',
            'I' => '판매중단',
            'S' => '매진'
        ];
        return $statusMap[$slCls] ?? '';
    }

    public function syncContent($content)
    {
        $mapper = new CasMetadataMapper($content);
        $casMeta = $mapper->makeCasMetadata();
        $options = $this->getDefaultOptions();
        $options['json'] = $casMeta;
        $this->logger->info('syncContent payload:' . json_encode($casMeta));
        $res = $this->client->post($this->getApiPath('/save'), $options);
        $result = $this->makeReturn($res);
        $this->logger->info('syncContent result:');
        $this->logger->info(json_encode($result));

        if ($result['status'] == 200 && $result['message'] === 'OK') {
            $content->set('status', CONTENT_STATUS_COMPLETE);
            $content->save();
        }
        return $result;
    }
}
