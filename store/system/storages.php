<?php

$rootDir = dirname(dirname(__DIR__));
if(!defined('DS'))
	define('DS', DIRECTORY_SEPARATOR);
require_once($rootDir . DS . 'lib' . DS . 'config.php');

use Proxima\core\Session;
use Proxima\core\Response;
use Proxima\core\ApiRequest;
use Proxima\models\system\Storage;

Session::init();

$api = new \Proxima\core\ApiRequest();
/*
storage_id
type
login_id
login_pw
path
name
group_name
mac_address
authority
describe
description
virtual_path
path_for_mac
path_for_unix
read_limit
write_limit
path_for_win
*/
$api->get(function($params) {
    $where = null;
    if(!empty($params['type'])) {
        $type = strtoupper($params['type']);
        $where = "type = '{$type}'";
    }
    $storages = Storage::all($where);

    $data = [];
    foreach($storages as $storage) {
        $data[] = [
            'storage_id' => $storage->get('storage_id'),
            'login_id' => $storage->get('login_id'),
            'login_pw' => $storage->get('login_pw'),
            'path' => $storage->get('path'),
            'name' => $storage->get('name'),
            'group_name' => $storage->get('group_name'),
            'mac_address' => $storage->get('mac_address'),
            'authority' => $storage->get('authority'),
            'describe' => $storage->get('describe'),
            'description' => $storage->get('description'),
            'virtual_path' => $storage->get('virtual_path'),
            'path_for_mac' => $storage->get('path_for_mac'),
            'path_for_unix' => $storage->get('path_for_unix'),
            'read_limit' => $storage->get('read_limit'),
            'write_limit' => $storage->get('write_limit'),
            'path_for_win' => $storage->get('path_for_win')
        ];
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
