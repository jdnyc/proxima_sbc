<?php

namespace Api\Services;

use Api\Models\ApiJob;
use Api\Models\Media;
use Api\Models\Social\SnsPost;
use Api\Modules\SocialClient;
use Api\Types\ApiJobType;
use Api\Types\JobStatus;
use Api\Types\OSType;
use Api\Types\Social\PrivacyStatus;
use Api\Types\Social\PublishStatus;
use Proxima\core\Directory;
use Proxima\core\Path;

/**
 * SNS 게시 서비스 클라이언트
 */
class SnsPostService
{
    /**
     * SocialClient
     *
     * @var \Api\Modules\SocialClient
     */
    private $client;

    /**
     * publish callback url
     *
     * @var string
     */
    private $publishCallbackUrl;

    public function __construct()
    {
        $socialConfig = config('social');
        $this->publishCallbackUrl = $socialConfig['publish_callback_url'];

        $baseUrl = $socialConfig['url'];
        $this->client = new SocialClient($baseUrl);
    }

    public function find($id)
    {
        $snsPost = SnsPost::find($id);
        return $snsPost;
    }

    public function findByTaskId($taskId)
    {
        $snsPost = SnsPost::where('task_id', $taskId)
            ->first();
        return $snsPost;
    }

    public function findOrFail($id)
    {
        $snsPost = $this->find($id);
        if ($snsPost === null) {
            api_abort_404(SnsPost::class);
        }
        return $snsPost;
    }

    /**
     * 콘텐츠 별 SNS 게시 리스트
     *
     * @param mixed $contentId
     * @return \Illuminate\Support\Collection SNS 게시 리스트
     */
    public function getPostsByContentId($contentId)
    {
        $posts = SnsPost::with([
            'media' => function ($q) {
                $q->select(['media_id', 'path', 'filesize']);
            },
            'thumbnail' => function ($q) {
                $q->select(['media_id', 'path', 'filesize']);
            },
            'channel' => function ($q) {
                $q->select(['id', 'name', 'logo_url']);
            },
            'platform' => function ($q) {
                $q->select(['id', 'name', 'slug']);
            },
            'user' => function ($q) {
                $q->select(['user_id', 'user_nm']);
            }])->where('content_id', $contentId)
            ->get();

        // 썸네일 url 생성
        foreach ($posts as $post) {
            $url = Path::join('/data', $post->thumbnail->path);
            $post->thumbnail->url = Path::fixSeparator($url, '/');
        }
        return $posts;
    }

    /**
     * SNS 게시
     *
     * @param array $data 게시 데이터
     * @param \Api\Models\User $user
     * @return \Api\Models\Social\SnsPost
     */
    public function publish($data, $user)
    {
        $snsPost = new SnsPost();
        $snsPost->content_id = $data['content_id'];
        $snsPost->media_id = $data['media_id'] ?? null;
        $snsPost->thumb_media_id = $data['thumb_media_id'] ?? null;
        $snsPost->channel_id = $data['channel_id'];
        $snsPost->platform_id = $data['platform_id'];
        $snsPost->category_id = $data['category_id'] ?? null;
        $snsPost->title = $data['title'];
        $snsPost->description = $data['description'];
        $snsPost->tag = $data['tag'] ?? null;
        $snsPost->media_type = $data['media_type'] ?? null;
        $snsPost->privacy_status = $data['privacy_status'];
        if ($snsPost->privacy_status === PrivacyStatus::BOOK) {
            $snsPost->booked_at = new \Carbon\Carbon($data['booked_at']);
        }
        $snsPost->user_id = $user->user_id;

        if (isset($data['booked_at']) && $data['booked_at'] !== null) {
            $snsPost->booked_at = $data['booked_at'];
        }

        // 미디어 변환 워크플로우 등록
        require_once dirname(dirname(__DIR__)) . '/workflow/lib/task_manager.php';
        $params = [];
        global $db;
        $task = new \TaskManager($db);
        $taskId = $task->start_task_workflow($snsPost->content_id, 'tc_sns_publish_media', $user->user_id, $params);
        $snsPost->task_id = $taskId;
        $snsPost->save();

        return $snsPost;
    }

    /**
     * 메타데이터 수정
     *
     * @param \Api\Models\Social\SnsPost $snsPost
     * @param array $data
     * @return \Api\Models\Social\SnsPost
     */
    public function updateMetadata($snsPost, $data)
    {
        $snsPost->title = $data['title'];
        $snsPost->description = $data['description'];
        if ($snsPost->privacy_status !== $data['privacy_status']) {
            $snsPost->privacy_status = $data['privacy_status'];

            if ($snsPost->privacy_status !== PrivacyStatus::BOOK) {
                $snsPost->booked_at = $data['booked_at'];
            } else if ($snsPost->privacy_status === PrivacyStatus::BOOK) {
                $snsPost->booked_at = null;
            }
        }

        $snsPost->tag = $data['tag'] ?? null;

        $snsPost->save();
        return $snsPost;
    }

    /**
     * 썸네일 등록
     *
     * @param $uploadedFile
     * @param \Api\Models\Content $content
     * @param \Api\Models\Social\Platform $platform
     * @param \Api\Models\Social\SnsPost $snsPost
     * @return \Api\Models\Media
     */
    public function registerThumbnail($uploadedFile, $content, $platform, $snsPost = null)
    {
        $thumb = null;
        if ($snsPost) {
            // 업데이트
            $thumb = $snsPost->thumbnail;
            /** @var \Api\Models\Storage $storage */
            $storage = $thumb->storage;
            $thumbFullPath = Path::join(Directory::getServerStoragePath($storage), $thumb->path);

            // 기존 파일 삭제
            if (file_exists($thumbFullPath)) {
                chmod($thumbFullPath, 0755);
                unlink($thumbFullPath);
            } else {
                // 기존 파일 혹시 없으면 디렉터리 부터 만들자
                $dir = Path::getDirectoryPath($thumbFullPath);
                mkdir($dir, 0777, true);
            }
            // 업로드 파일 이동
            // $uploadedFile->moveTo($thumbFullPath);는 UNC path에서 오동작 해서 사용안함
            if (!move_uploaded_file($_FILES['file']['tmp_name'], $thumbFullPath)) {
                api_abort('Fail to move file', 'fail_move_file', 500);
            }

            $thumb->filesize = $uploadedFile->getSize();
            $thumb->save();
        } else {
            // 신규 등록
            $mediaType = $platform->slug . '_thumb';

            /**
             * @var \Api\Models\UserContent $userContent
             * */
            $userContent = $content->userContent;
            $storage = $userContent->getLowresStorage();

            $thumb = new Media();
            $thumb->content_id = $content->content_id;
            $thumb->storage_id = $storage->storage_id;
            $thumb->media_type = $mediaType;
            $proxyRootPath = Directory::getProxyDirPath($content, false, true);

            // 프록시 미디어에서 경로를 얻어오자
            $dir = Path::join($proxyRootPath, 'SNSThumbnail');
            $path = Path::join($dir, "thumb_{$platform->slug}_{$content->content_id}.jpg");
            $thumb->path = Path::fixSeparator($path, '/');
            $thumbFullPath = Path::join(Directory::getServerStoragePath($storage), $thumb->path);
            // 디렉터리 생성
            $dir = Path::getDirectoryPath($thumbFullPath);
            mkdir($dir, 0777, true);
            // 업로드 파일 이동
            // $uploadedFile->moveTo($thumbFullPath);는 UNC path에서 오동작 해서 사용안함
            if (!move_uploaded_file($_FILES['file']['tmp_name'], $thumbFullPath)) {
                api_abort('Fail to move file', 'fail_move_file', 500);
            }
            $thumb->filesize = $uploadedFile->getSize();
            $thumb->reg_type = 'attach';
            $thumb->expired_date = '99981231000000';

            $thumb->save();
        }

        return $thumb;
    }

    /**
     * 게시할 데이터 정합성 체크
     *
     * @param array $input
     * @return void
     */
    public function publishValidate($input)
    {
        $channelId = $input['channel_id'] ?? null;
        if ($channelId === null) {
            api_abort('channel_id should not empty.', 400);
        }

        $platformId = $input['platform_id'] ?? null;
        if ($platformId === null) {
            api_abort('platform_id should not empty.', 400);
        }
    }

    /**
     * SNS 게시 API 작업 생성
     *
     * @param \Api\Models\Social\SnsPost $post
     * @return \Api\Models\ApiJob
     */
    public function createPublishApiJob($post)
    {
        $apiJob = new ApiJob();
        $apiJob->owner_id = $post->id;
        $apiJob->type = ApiJobType::SNS_PUBLISH;

        // 한번 저장하고
        $apiJob->save();

        $platform = $post->platform;

        $payload = [
            'sns_post_id' => $post->id,
            'content_id' => $post->content_id,
            'channel_id' => $post->channel_id,
        ];

        $channelWithSetting = $platform->channels_with_setting()
            ->where('channel_id', $post->channel_id)
            ->first();
        $setting = $channelWithSetting->setting;

        $snsData = $this->makeSNSData($platform, $post->media, $post, $channelWithSetting);
        // 콜백 주소
        $socialConfig = config('social');
        $apiJob->url = $socialConfig['url'] . '/sns/publish';
        $apiJob->method = 'post';
        $apiJob->headers = [
            'content-type' => 'application/json',
        ];

        $callbackUrl = $socialConfig['publish_callback_url'] . '/' . $apiJob->id;
        $payload['sns_publish'] = [
            'platform' => $platform->slug,
            'account' => json_decode($setting->sns_account_info, true),
            'data' => $snsData,
            'callback' => [
                'url' => $callbackUrl,
                'method' => 'PUT',
            ],
        ];

        // payload 설정 후 다시 업데이트
        $apiJob->payload = $payload;
        $apiJob->status = JobStatus::QUEUED;
        $apiJob->save();
        return $apiJob;
    }

    /**
     * SNS 별 등록 데이터 생성
     *
     * @param \Api\Models\Social\Platform $platform
     * @param \Api\Models\Media $publishFile
     * @param \Api\Models\Social\SnsPost $post
     * @param \Api\Models\Social\Channel $channel
     * @return array
     */
    private function makeSNSData($platform, $publishFile, $post, $channel)
    {
        // SNS 게시서비스가 리눅스 서버에 올라갈 예정
        $osType = OSType::LINUX;
        switch ($platform->slug) {
            case 'yt':
                {
                    return [
                        'video_path' => $publishFile->fileFullPath($osType),
                        'title' => $post->title,
                        'privacy_status' => $post->privacy_status,
                        'categoryId' => $post->category_id,
                    ];
                }
            case 'fb':
                {
                    return [
                        'video_path' => $publishFile->fileFullPath($osType),
                        'title' => $post->title,
                        'privacy_status' => $post->privacy_status,
                    ];
                }
            default:
                {
                    throw new \Exception('Invalid platform slug.');
                }
        }
        return [];
    }

    /**
     * API Job(게시작업) 결과에 따른 Post 업데이트
     *
     * @param \Api\Models\ApiJob $apiJob
     * @return void
     */
    public function updateStatus($apiJob)
    {
        $post = SnsPost::find($apiJob->owner_id);
        switch ($apiJob->status) {
            case JobStatus::FINISHED:
                $post->status = PublishStatus::UPLOADED;
                break;
            case JobStatus::FAILED:
                $post->status = PublishStatus::FAILED;
                break;
            case JobStatus::WORKING:
                $post->status = PublishStatus::UPLOADING;
                break;
            default:
                break;
        }
        $post->save();
    }

    public function getUploadedPosts()
    {
        $posts = SnsPost::whereNull('post_id');

    }
}
