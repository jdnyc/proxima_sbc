<?php
namespace ProximaCustom\services;

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use Proxima\models\system\Storage;

/**
 * CFS업로드 서비스 클라이언트
 */
class CfsUploadService
{
    private $client;
    private $cfsUploadCallbackUrl;
    private $cfsUploadServicePort;

    public function __construct()
    {
        $dotenv = Dotenv::create(dirname(__DIR__), '.env');
        $dotenv->load();

        $this->cfsUploadCallbackUrl = getenv('CFS_UPLOAD_CALLBACK_URL');
        $this->cfsUploadServicePort = getenv('CFS_UPLOAD_SERVICE_PORT');
    }

    /**
     * API 경로 조회
     *
     * @param string $path
     * @return string
     */
    private function getApiPath($path)
    {
        return '/api/v1/' . $path;
    }

    public function upload($serviceEndPoint, $task, $content)
    {
        $serviceEndPoint .= ':' . $this->cfsUploadServicePort;
        // Upload!!
        $client = new Client([
            'base_uri' => $serviceEndPoint
        ]);

        $path = $this->getApiPath('medias');

        $options = $this->getDefaultOptions();

        $options['json'] = $this->makeRequestPayload($task, $content);

        $client->post($path, $options);
    }

    public function uploadSync($serviceEndPoint, $content, $srcFile, $metadata)
    {
        $serviceEndPoint .= ':' . $this->cfsUploadServicePort;
        // Upload!!
        $client = new Client([
            'base_uri' => $serviceEndPoint
        ]);

        $path = $this->getApiPath('files');

        $options = $this->getDefaultOptions();

        $options['json'] = $this->makeSyncRequestPayload($content, $srcFile, $metadata);

        $res = $client->post($path, $options);
        return json_decode($res->getBody(), true);
    }

    private function makeSyncRequestPayload($content, $srcFile, $metadata)
    {
        /*
        { 
            "taskId": 123,
            "srcFile": "d:\\test\\test1.mxf", 
            "metadata": {
                "contentId": 123,
                "type": "media", // media or scene
                "mediaId": 5864,
                "sceneId": null
            },
            "bucket": "public", 
            "destPath": "confirm/assets/201904/20190402" 
        }
        */
        $payload = [
            'srcFile' => $srcFile,
            'metadata' => $metadata,
            'bucket' => 'public',
            'destPath' => $this->makeCfsDestPath($content)
        ];
        return $payload;
    }

    private function makeRequestPayload($task, $content)
    {
        /*
        { 
            "taskId": 123,
            "srcFile": "d:\\test\\test1.mxf", 
            "callbackInfo": {
                "url": "http://localhost/custom/cjos-vms/store/upload-state.php",
                "type": "media", // media or scene
                "mediaId": 5864,
                "sceneId": null
            },
            "bucket": "public", 
            "destPath": "confirm/assets/201904/20190402" 
        }
        */
        $mediaInfo = json_decode($task->get('parameter'), true);
        $payload = [
            'taskId' => (int)$task->get('task_id'),
            'srcFile' => $this->makeSrcFilePath($task),
            'callbackInfo' => [
                'url' => $this->cfsUploadCallbackUrl,
                'type' => $mediaInfo['type'],
                'mediaId' => (int)$mediaInfo['mediaId'] ?? null,
                'sceneId' => (int)$mediaInfo['sceneId'] ?? null
            ],
            'metadata' => [
                'contentId' => (int)$content->get('content_id'),
                'type' => $mediaInfo['type'],
                'mediaId' => (int)$mediaInfo['mediaId'] ?? null,
                'sceneId' => (int)$mediaInfo['sceneId'] ?? null
            ],
            'bucket' => 'public',
            'destPath' => $this->makeCfsDestPath($content)
        ];
        return $payload;
    }

    private function makeSrcFilePath($task)
    {
        $storage = Storage::find($task->get('src_storage_id'));
        $srcFile = $storage->get('path') . '/' . $task->get('source');
        return $srcFile;
    }

    /**
     * Cfs 업로드 대상 경로 만들기
     *
     * @param \Proxima\models\content\Content $content
     * @return void
     */
    private function makeCfsDestPath($content)
    {
        // confirm/assets/201902/20190213/A000008/8ad5d58d212ed8880f9a7c752096315dd0379fa8.mp4
        $basePath = 'confirm/assets';
        $createdAt = new \Carbon\Carbon($content->get('created_date'));
        $ym = $createdAt->format('Ym');
        $ymd = $createdAt->format('Ymd');

        $userMeta = $content->userMetadata();
        $videoCode = $userMeta->get('usr_video_code');
        $cfsDestPath = "{$basePath}/{$ym}/{$ymd}/{$videoCode}";
        return $cfsDestPath;
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
            ]
        ];

        if (!is_null($query)) {
            $options['query'] = $query;
        }
        return $options;
    }
}
