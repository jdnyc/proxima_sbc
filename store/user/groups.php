<?php
/**
 * 2018/04/30 khk
 * 그냥 그룹만 조회하는 페이지
 */

// $rootDir = dirname(dirname(__DIR__));
// define('DS', DIRECTORY_SEPARATOR);

require_once '../../vendor/autoload.php';

use Proxima\core\Response;
use Proxima\core\ApiRequest;
use Proxima\models\user\Group;

$api = new ApiRequest();

$api->get(function($params) {
    
    $groups = Group::all();
    
    $data = [];
    foreach($groups as $group) {
        $data[] = $group->getAll();
    }

    Response::echoJsonOk($data);

});

$api->run();