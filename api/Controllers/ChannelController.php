<?php

namespace Api\Controllers;

use Api\Http\ApiRequest;
use Api\Http\ApiResponse;
use Api\Models\Social\Channel;
use Psr\Container\ContainerInterface;

class ChannelController extends BaseController
{
    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        // db 커넥션 연결
        $container->get('db');
    }

    /**
     * 채널 목록 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param Api\Http\ApiResponse $response
     * @param array $args
     * @return Api\Http\ApiResponse
     */
    public function index(ApiRequest $request, ApiResponse $response, array $args)
    {   
        $channels = Channel::where('active', true)->get();

        return response()->ok($channels);
    }

    /**
     * 채널의 플랫폼 목록 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param Api\Http\ApiResponse $response
     * @param array $args
     * @return Api\Http\ApiResponse
     */
    public function getPlatforms(ApiRequest $request, ApiResponse $response, array $args)
    {
        $channelId = $args['channel_id'] ?? null;
        if($channelId === null) {
            api_abort('`channel_id` should not empty.');
        }
        $channel = Channel::find($channelId);
        if($channel === null) {
            api_abort_404(Channel::class);
        }
        $platforms = $channel->platforms_with_setting;

        return response()->ok($platforms);
    }
}
