<?php

use Proxima\models\content\Content;
use ProximaCustom\services\CasService;

// 채널 정보 업데이트

$rootDir = dirname(dirname(dirname(__DIR__)));
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

require_once($rootDir . DS . 'vendor' . DS .'autoload.php');

$query = "SELECT * FROM bc_content WHERE is_deleted='N' AND status >= 0";
$contents = Content::queryList($query);

$i = 1;
$service = new CasService();
foreach ($contents as $content) {
    $meta = $content->userMetadata();
    $channelCode = $meta->get('usr_channel_code');
    if (empty($channelCode)) {
        continue;
    }
    $channel = $service->getChannel($channelCode);
    $meta->set('usr_channel_name', $channel['name']);
    $meta->save();
    $i++;
}

echo 'Job done. (' . $i . ')';
