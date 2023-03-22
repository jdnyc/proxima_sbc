<?php
/**
 * 채널 API
 */
$rootDir = dirname(dirname(dirname(__DIR__)));
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
    
require_once($rootDir . DS . 'vendor' . DS .'autoload.php');

use Proxima\core\Response;
use Proxima\core\ApiRequest;
use ProximaCustom\services\CasService;

$api = new ApiRequest();
// 채널 조회
$api->get(function ($params, $request) {
    // 방송채널 여부가 있으면 방송채널만 필터링 한다.
    $broadCastChannel = (($request->broadcast ?? false) === 'true');
    $channelOnly = (($request->channel_only ?? false) === 'true');

    $service = new CasService();
    $channels = $service->getChannels();
    if (!$channelOnly) {
        $data = [
            [
                'id' => 99,
                'code' => 'none',
                'name' => '선택',
                'broadcast' => false
            ]
        ];
    }
    if ($broadCastChannel) {
        foreach ($channels as $channel) {
            if ($channel['broadcast']) {
                $data[] = $channel;
            }
        }
    } else {
        $data = array_merge($data, $channels);
    }

    Response::echoJsonOk($data);
});

$api->run();
