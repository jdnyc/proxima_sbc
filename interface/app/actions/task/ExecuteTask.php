<?php
require_once BASEDIR . '/lib/archive.class.php';

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

$server->register('ExecuteTask',
    array(
        'channel' => 'xsd:string',
        'content_id' => 'xsd:string',
        'req_no' => 'xsd:string',
        'user_id' => 'xsd:string',
    ),
    array(
        'task_id' => 'xsd:string'
    ),
    $namespace,
    $namespace.'#ExecuteTask',
    'rpc',
    'encoded',
    'ExecuteTask'
);

function ExecuteTask($channel, $content_id, $req_no, $user_id) {
    global $db;

    $logger = new Logger('name');
    $logger->pushHandler(new RotatingFileHandler(BASEDIR . '/log/' . substr(basename(__FILE__), 0, strrpos(basename(__FILE__), '.')) . '.log', 14));

    try {
        $channel = trim($channel);
        $content_id = trim($content_id);
        $req_no = trim($req_no);
        $user_id = trim($user_id);

        $channel = strtoupper($channel);

        if (empty($channel)) {
            throw new Exception('channel 값이 없습니다.');
        }

        if (empty($content_id)) {
            throw new Exception('content_id 값이 없습니다.');
        }

        if ( ! is_numeric($content_id)) {
            throw new Exception('content_id 값이 정수가 아닙니다.');
        }

        if (empty($req_no)) {
            throw new Exception('req_no 값이 없습니다.');
        }

        if ( ! is_numeric($req_no)) {
            throw new Exception('req_no 값이 정수가 아닙니다.');
        }

        if (empty($user_id)) {
            throw new Exception('user_id 값이 없습니다.');
        }

        $db->exec("UPDATE ARCHIVE_REQUEST
                      SET DAS_CONTENT_ID = '$req_no'
                    WHERE CONTENT_ID = $content_id");

        $insert_task = new TaskManager($db);
        $task_id = $insert_task->start_task_workflow($content_id, $channel, $user_id);
    } catch (Exception $e) {
        $logger->error($e->getMessage());
        $task_id = new nusoap_fault(-1, null, $e->getMessage());
    }

    return $task_id;
}
