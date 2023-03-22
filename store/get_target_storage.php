<?php
require_once '../lib/config.php';

$method = $_POST['method'];
$channel = $_POST['channel'];

$storage_id = $_POST['storage_id'];
$ip = trim($_POST['ip']);
$port = trim($_POST['port']);
$login_id = trim($_POST['id']);
$login_pw = trim($_POST['password']);

if ($method == 'update') {

    $path = $ip . ':' . $port;
    $db->exec("
        UPDATE  BC_STORAGE
        SET     PATH = '$path', LOGIN_ID = '$login_id', LOGIN_PW = '$login_pw'
        WHERE   STORAGE_ID = $storage_id
    ");

    echo json_encode(array(
        'success' => true
    ));
} else {
    $storage = $db->queryRow("
        SELECT D.*
        FROM    BC_TASK_WORKFLOW A, BC_TASK_WORKFLOW_RULE B, BC_TASK_RULE C, BC_STORAGE D
        WHERE   A.REGISTER = '$channel'
        AND     A.TASK_WORKFLOW_ID = B.TASK_WORKFLOW_ID
        AND     B.TASK_RULE_ID = C.TASK_RULE_ID
        AND     C.TARGET_PATH = D.STORAGE_ID
    ");

    list($ip, $port) = explode(':', $storage['path']);

    $data = array(
        'storage_id' => $storage['storage_id'],
        'ip' => $ip,
        'port' => $port,
        'id' => $storage['login_id'],
        'password' => $storage['login_pw']
    );

    echo json_encode(array(
        'success' => true,
        'data' => $data
    ));
}
