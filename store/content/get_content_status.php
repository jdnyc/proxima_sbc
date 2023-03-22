<?php
require_once(dirname(dirname(__DIR__)) . '/vendor/autoload.php');

use Proxima\core\Request;
use Proxima\core\Response;

$filter = Request::get('filter');

if(!empty($filter)) {
    $filter = explode(',', $filter);
}

$contentStatusList = \Proxima\models\content\ContentStatus::all();

$data = [];
foreach($contentStatusList as $contentStatus) {
    if(!empty($filter) && !in_array($contentStatus->get('code'), $filter)) {
        continue;           
    }
    $data[] = $contentStatus->getAll();
}

Response::echoJsonOk($data);
