<?php
/**
 * 사용자목록 API
 */
$rootDir = dirname(dirname(dirname(__DIR__)));
if (!defined("DS")) {
    define("DS", DIRECTORY_SEPARATOR);
}
    
require_once($rootDir . DS . "vendor" . DS ."autoload.php");

use Proxima\core\Response;
use Proxima\core\ApiRequest;
use Proxima\models\user\User;

$api = new ApiRequest();
// 사용자 조회
$api->get(function ($params, $request) {
    $userQuery = $request->user_query;
    $data = [];
    if (!empty($userQuery)) {
        $users = User::search($userQuery);
        foreach ($users as $user) {
            $data[] = [
                'user_id' => $user->get('user_id'),
                'user_nm' => $user->get('user_nm'),
                'dept_nm' => $user->get('dept_nm')
            ];
        }
    }
    
    Response::echoJsonOk($data);
});

$api->run();
