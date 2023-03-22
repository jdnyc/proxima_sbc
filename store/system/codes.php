<?php
/**
 * 코드 관련
 */
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Proxima\core\Session;
use Proxima\core\Response;
use Proxima\core\ApiRequest;
use Proxima\models\system\Code;
use Proxima\models\system\Storage;

Session::init();

$api = new \Proxima\core\ApiRequest();

$api->get(function($params) {
    $codes = Code::getCodeList($params['code_type']);
    $data = [];
    foreach($codes as $code) {
        if ($params['use_yn'] == 'Y' && $code->get('use_yn') != 'Y') {
            continue;
        }
        $data[] = $code->getAll();
    }
    
    Response::echoJsonOk($data);
});

$api->post(function($params) {
    Response::echoJsonError('Not Allowed');
});

$api->put(function($params) {
    Response::echoJsonError('Not Allowed');
});

$api->delete(function($params) {
    Response::echoJsonError('Not Allowed');
});

$api->run();
