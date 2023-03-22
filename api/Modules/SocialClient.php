<?php

namespace Api\Modules;

use Api\Core\HttpClient;


class SocialClient extends HttpClient
{
    /**
     * 생성자
     */
    public function __construct($baseUrl)
    {        
        parent::__construct($baseUrl);
    }


    /**
     * SNS 게시요청
     *
     * @param string $platform
     * @param array $account
     * @param array $data
     * @param boolean $yppAccount
     * @param array $uploadOption
     * @param array $callback
     * @param string $apiPath
     * @return void
     */
    public function publish($platform, $account, $data, $yppAccount, $uploadOption, $defaultLang, $callback, $apiPath = null)
    {
        $url = $apiPath ?? 'api/v1/sns/publish';
        $options = [
            'headers' => [
                'content-type' => 'application/json'
            ],
            'body' => json_encode([
                'platform' => $platform,
                'account' => $account,
                'data' => $data,
                'ypp_account' => $yppAccount,
                'upload_option' => $uploadOption,
                'default_lang' => $defaultLang,
                'callback' => $callback
            ])
        ];

        return $this->post($url, $options);
    }

    /**
     * SNS 메타데이터 업데이트
     * 
     * @return object
     * @param string $apiPath
     */
    public function updateMetadata($platform, $accountInfo, $data, $callback, $apiPath = null)
    {
        $url = $apiPath ?? 'api/v1/sns/update';
        $options = [
            'headers' => [
                'content-type' => 'application/json'
            ],
            'body' => json_encode([
                'platform' => $platform,
                'account' => $accountInfo,
                'data' => $data,
                'callback' => $callback
            ])
        ];

        $result = $this->post($url, $options);
        return json_decode($result->getContents());
    }

    /**
     * 사용자 요청에 의거 메타데이터 수동 동기화(1시간전/24시간전)
     *
     * @param string $platform
     * @param array $accountInfo
     * @param array $data
     * @return void
     */
    public function manualSyncByUser($platform, $accountInfo, $data)
    {
        $url = 'sns/sync-by-user';
        $options = [
            'headers' => [
                'content-type' => 'application/json'
            ],
            'body' => json_encode([
                'platform' => $platform,
                'account' => $accountInfo,
                'data' => $data
            ])
        ];

        return $this->post($url, $options);
    }

    /**
     * SNS 정보(메타데이터/통계) 수동 동기화
     * 
     * @return object
     */
    public function manualSyncForPlatform($platform, $accountInfo, $data, $syncType)
    {
        $url = 'sns/manual-sync';
        $options = [
            'headers' => [
                'content-type' => 'application/json'
            ],
            'body' => json_encode([
                'platform' => $platform,
                'account' => $accountInfo,
                'data' => $data,
                'syncType' => $syncType
            ])
        ];

        return $this->post($url, $options);
    }

    /**
     * SNS 채널(페이지) 이미지 동기화
     *
     * @param mixed $platform
     * @param mixed $accountInfo
     * @param array $data
     * @return void
     */
    public function syncChannelImage($platform, $accountInfo, $data)
    {
        $url = 'sns/sync-channel-image';
        $options = [
            'headers' => [
                'content-type' => 'application/json'
            ],
            'body' => json_encode([
                'platform' => $platform,
                'account' => $accountInfo,
                'data' => $data
            ])
        ];

        $result = $this->post($url, $options);
        return json_decode($result->getContents());
    }

    /**
     * SNS 번역데이터 업데이트(현재는 Youtube만 지원)
     *
     * @param mixed $platform
     * @param mixed $accountInfo
     * @param array $data
     * @return void
     */
    public function updateTranslation($platform, $accountInfo, $data)
    {
        $url = 'sns/translation-update';
        $options = [
            'headers' => [
                'content-type' => 'application/json'
            ],
            'body' => json_encode([
                'platform' => $platform,
                'account' => $accountInfo,
                'data' => $data
            ])
        ];
        
        $result = $this->post($url, $options);
        return json_decode($result->getContents());
    }

    public function archiveVideo($platform, $data)
    {
        $url = 'sns/archive';
        $options = [
            'headers' => [
                'content-type' => 'application/json'
            ],
            'body' => json_encode([
                'platform' => $platform,
                'data' => $data
            ])
        ];
        
        return $this->post($url, $options);
    }

    /**
     * SNS 영상 다운로드
     *
     * @param mixed $platform
     * @param array $data
     * @return void
     */
    public function downloadVideo($platform, $data)
    {
        $url = 'sns/download';
        $options = [
            'headers' => [
                'content-type' => 'application/json'
            ],
            'body' => json_encode([
                'platform' => $platform,
                'data' => $data
            ])
        ];
        
        return $this->post($url, $options);
    }

    /**
     * 게시물 삭제
     *
     * @param mixed $platform
     * @param mixed $accountInfo
     * @param array $data
     * @return void
     */
    public function deleteVideo($platform, $accountInfo, $data)
    {
        $url = 'sns/delete';
        $options = [
            'headers' => [
                'content-type' => 'application/json'
            ],
            'body' => json_encode([
                'platform' => $platform,
                'account' => $accountInfo,
                'data' => $data
            ])
        ];
        
        return $this->post($url, $options);
    }

    public function getTokenInfo($platform, $data)
    {
        $url = 'sns/token';
        $options = [
            'headers' => [
                'content-type' => 'application/json'
            ],
            'body' => json_encode([
                'platform' => $platform,
                'data' => $data
            ])
        ];
        
        $result = $this->post($url, $options);
        return json_decode($result->getContents());
    }

    /**
     * 로그인한 페이스북 계정의 페이지들을 가져온다.
     *
     */
    public function getFacebookPages($platform, $userToken)
    {
        $url = 'sns/facebook-pages';
        $options = [
            'headers' => [
                'content-type' => 'application/json'
            ],
            'body' => json_encode([
                'platform' => $platform,
                'user_token' => $userToken
            ])
        ];
        
        $result = $this->post($url, $options);
        return json_decode($result->getContents());
    }

    /**
     * 배포 완료된 항목에 대한 메타데이터 동기화
     *
     * @param mixed $platform
     * @param mixed $account
     * @param array $data
     * @return void
     */
    public function getPublishedMetadata($platform, $account, $data)
    {
        $url = 'sns/published-metadata';
        $options = [
            'headers' => [
                'content-type' => 'application/json'
            ],
            'body' => json_encode([
                'platform' => $platform,
                'account' => $account,
                'data' => $data
            ])
        ];

        $result = $this->post($url, $options);
        return json_decode($result->getContents());
    }

    /**
     * 번역
     *
     * @param array $translateScript
     * @param array $targetLangs
     * @return void
     */
    public function translate($translateScript, $targetLangs)
    {
        $url = 'google/translate';
        $options = [
            'headers' => [
                'content-type' => 'application/json'
            ],
            'body' => json_encode([
                'translateScript' => $translateScript,
                'targetLangs' => $targetLangs
            ])
        ];
        $result = $this->post($url, $options);
        return json_decode($result->getContents());
    }

    /**
     * 자막 수정
     *
     * @param mixed $platform
     * @param mixed $account
     * @param array $data
     * @return void
     */
    public function updateCaption($platform, $account, $data, $lang)
    {
        $url = 'sns/caption-update';
        $options = [
            'headers' => [
                'content-type' => 'application/json'
            ],
            'body' => json_encode([
                'platform' => $platform,
                'account' => $account,
                'data' => $data,
                'lang' => $lang
            ])
        ];

        $result = $this->post($url, $options);
        return json_decode($result->getContents());
    }

    /**
     * 자막삭제
     *
     * @param mixed $platform
     * @param mixed $account
     * @param array $data
     * @return void
     */
    public function deleteCaption($platform, $account, $data)
    {
        $url = 'sns/caption-delete';
        $options = [
            'headers' => [
                'content-type' => 'application/json'
            ],
            'body' => json_encode([
                'platform' => $platform,
                'account' => $account,
                'data' => $data
            ])
        ];

        return $this->post($url, $options);
    }

    /**
     * 섬네일 업데이트
     *
     * @param mixed $platform
     * @param mixed $account
     * @param array $data
     * @return void
     */
    public function updateThumbnail($platform, $account, $data, $apiPath = null)
    {
        $url = $apiPath ?? 'api/v1/sns/thumbnail';
        $options = [
            'headers' => [
                'content-type' => 'application/json'
            ],
            'body' => json_encode([
                'platform' => $platform,
                'account' => $account,
                'data' => $data
            ])
        ];

        $result = $this->post($url, $options);
        return json_decode($result->getContents());
    }

    /**
     * OPS 공개전환 후 섬네일 정보 조회해서 업데이트 하도록
     * Social 작업 요청
     *
     * @param string $opsId
     * @param string $postId
     * @return void
     */
    public function syncOpsThumburl($opsId, $postId)
    {
        $url = 'sns/sync-ops-thumbnail';
        $options = [
            'headers' => [
                'content-type' => 'application/json'
            ],
            'body' => json_encode([
                'ops_id' => $opsId,
                'post_id' => $postId
            ])
        ];

        $result = $this->post($url, $options);
        return json_decode($result->getContents());
    }

    public function migrateSns($platform, $account, $data)
    {
        $url = 'sns/migration';
        $options = [
            'headers' => [
                'content-type' => 'application/json'
            ],
            'body' => json_encode([
                'platform' => $platform,
                'account' => $account,
                'data' => $data
            ])
        ];

        return $this->post($url, $options);
    }

    public function getAnalytics4depth($platform, $account, $data)
    {
        $url = 'sns/collect-analytics-4depth';
        $options = [
            'headers' => [
                'content-type' => 'application/json'
            ],
            'body' => json_encode([
                'platform' => $platform,
                'account' => $account,
                'data' => $data
            ])
        ];

        $result = $this->post($url, $options);
        return json_decode($result->getContents());
    }

    /**
     * 트위터 oauth url을 가져온다.
     *
     */
    public function getTwitterOauthUrl()
    {
        $url = 'sns/twitter-oauth-url';
        $result = $this->get($url, null);
        
        return json_decode($result->getContents());
    }

    /**
     * 트위터 oauth 정보로 토큰 정보를 받아온다.
     *
     */
    public function getTwitterAccessToken($oauthInfo)
    {
        $url = 'sns/twitter-token';
        $options = [
            'headers' => [
                'content-type' => 'application/json'
            ],
            'body' => json_encode([
                'oauthInfo' => $oauthInfo
            ])
        ];

        $result = $this->post($url, $options);
        return json_decode($result->getContents());
    }

    /**
     * SNS에서 자막을 다운로드해서 동기화처리
     *
     * @param string $platform
     * @param array $account
     * @param int $contentId
     * @param int $platformId
     * @param string $postId
     * @return void
     */
    public function downloadCaption($platform, $account, $contentId, $platformId, $postId)
    {
        $url = 'sns/caption-download';
        $options = [
            'headers' => [
                'content-type' => 'application/json'
            ],
            'body' => json_encode([
                'account' => $account,
                'platform' => $platform,
                'platform_id' => $platformId,
                'content_id' => $contentId,
                'post_id' => $postId
            ])
        ];

        $result = $this->post($url, $options);
        return json_decode($result->getContents());
    }
    /**
     * 자막 수동 동기화 요청
     *
     * @param array $accountInfo    SNS 계정정보
     * @param int $contentId    콘텐츠ID
     * @param string $postId    게시물ID
     * @return void
     */
    public function migrateCaption($accountInfo, $contentId, $postId)
    {
        $url = 'sns/caption-migrate';
        $options = [
            'headers' => [
                'content-type' => 'application/json'
            ],
            'body' => json_encode([
                'account' => $accountInfo,
                'content_id' => $contentId,
                'post_id' => $postId
            ])
        ];

        $result = $this->post($url, $options);
        return json_decode($result->getContents());
    }

    /**
     * 페이스북 페이지의 교차게시가 가능한 다른 페이지들을 조회한다.
     *
     */
    public function getCrosspostingEnabledPages($account)
    {
        $url = 'sns/crossposting-enabled-pages';
        $options = [
            'headers' => [
                'content-type' => 'application/json'
            ],
            'body' => json_encode([
                'account' => $account
            ])
        ];
        
        $result = $this->post($url, $options);
        return json_decode($result->getContents());
    }

    /**
     * 페이스북 교차게시
     */
    public function crosspost($sourceAccount, $targetAccount, $sourceVideoId, $data)
    {
        $url = 'sns/crosspost';
        $options = [
            'headers' => [
                'content-type' => 'application/json'
            ],
            'body' => json_encode([
                'source_account' => $sourceAccount,
                'target_account' => $targetAccount,
                'source_video_id' => $sourceVideoId,
                'data' => $data
            ])
        ];
        
        $result = $this->post($url, $options);
        return json_decode($result->getContents());
    }
}