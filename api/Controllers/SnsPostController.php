<?php

namespace Api\Controllers;

use Api\Http\ApiRequest;
use Api\Http\ApiResponse;
use Api\Models\Social\SnsPost;
use Api\Models\Social\Platform;
use Api\Services\ContentService;
use Api\Services\SnsPostService;
use Psr\Container\ContainerInterface;

class SnsPostController extends BaseController
{
    /**
     * SNS 게시 서비스
     *
     * @var \Api\Services\SnsPostService
     */
    private $snsPostService;

    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->snsPostService = new SnsPostService($container);
        $this->container->get('db');
    }

    /**
     * SNS 게시 목록
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return ApiResponse
     */
    public function indexByContentId(ApiRequest $request, ApiResponse $response, array $args)
    {
        $contentId = $args['content_id'] ?? null;

        if($contentId === null) {
            api_abort('content_id should not empty.', 400);
        }

        $list = $this->snsPostService->getPostsByContentId($contentId);
                        
        return response()->ok($list);
    }


    /**
     * SNS 게시
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return ApiResponse
     */
    public function publish(ApiRequest $request, ApiResponse $response, array $args)
    {
        $contentId = $args['content_id'] ?? null;

        if($contentId === null) {
            api_abort('content_id should not empty.', 400);
        }
        
        $input = $request->all();

        $contentService = new ContentService($this->container);
        $content = $contentService->findOrFail($contentId);

        $snsMedia = $content->medias()
                        ->where('media_type', 'proxy')
                        ->first();

        $input['media_id'] = $snsMedia->media_id;
        $this->snsPostService->publishValidate($input);
        
        $uploadedFiles = $request->getUploadedFiles();  
        
        $uploadedFile = null;
        if($uploadedFiles) {
            $uploadedFile = $uploadedFiles['file'] ?? null;
            if($uploadedFile->getError() !== UPLOAD_ERR_OK) {
                $uploadedFile = null;
            }
        }

        $user = auth()->user();

        // SNS 게시물 생성 또는 수정
        $postId = $input['post_id'] ?? null;    
        $post = null;
        if($postId) {
            $post = $this->snsPostService->find($postId);
        } else {
            /**
             * 게시물 아이디가 없어도 중복 방지를 위해
             * content_id, channel_id, platform_id로 찾아본다.
             */
            $post = SnsPost::where('content_id', $contentId)
                    ->where('channel_id', $input['channel_id'])
                    ->where('platform_id', $input['platform_id'])
                    ->first();            
        }

        $platform = Platform::find($input['platform_id']);
        if($post) {
            // 수정
            if($post->user_id !== $user->user_id) {
                api_abort('fobbiden', 403);
            }
            if($uploadedFile) {
                // 섬네일 교체
                $thumb = $this->snsPostService->registerThumbnail($uploadedFile, $content, $platform, $post);                
            }
            $post = $this->snsPostService->updateMetadata($post, $input);
        } else {
            // 생성
            if($uploadedFile) {
                // 섬네일 교체
                $thumb = $this->snsPostService->registerThumbnail($uploadedFile, $content, $platform);
                $input['thumb_media_id'] = $thumb->media_id;
            }
            $post = $this->snsPostService->publish($input, $user);
        }
                      
        echo json_encode([
            'success' => true,
            'data' => $post->toArray()
        ]);
        die();
    }
}
