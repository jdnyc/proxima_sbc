<?php

use Proxima\models\content\Content;
use Proxima\core\VideoHelper;

// 화면 비율 업데이트

$rootDir = dirname(dirname(dirname(__DIR__)));
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

require_once($rootDir . DS . 'vendor' . DS .'autoload.php');

$query = "SELECT * FROM bc_content WHERE is_deleted = 'N'";
$contents = Content::queryList($query);

$i = 1;
foreach ($contents as $content) {
    $sysMeta = $content->systemMetadata();
    if ($sysMeta->isEmpty() || empty($sysMeta->get('sys_display_size'))) {
        continue;
    }
    $displayInfo = $sysMeta->getDisplayInfo();
    
    $userMeta = $content->userMetadata();
    $aspectRatio = VideoHelper::getCustomAspectRatio($displayInfo);
    $userMeta->set('usr_aspect_ratio', $aspectRatio);
    $userMeta->save();

    //die();
    $i++;
}

echo 'Job done. (' . $i . ')';
