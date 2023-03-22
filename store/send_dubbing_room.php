<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/store/cuesheet/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

use Monolog\Handler\RotatingFileHandler;

try {
    $logger->pushHandler(new RotatingFileHandler(BASEDIR . '/log/' . basename(__FILE__), 14));

    $user_id = $_SESSION['user']['user_id'];
    $content_id = $_POST['content_id'];
    $channel = 'TM_DUBBING_ROOM';
   
    $insert_task = new TaskManager($db);
    $task_id = $insert_task->start_task_workflow($content_id, $channel, $user_id);

     echo json_encode(array(
        'success' => true,
        'msg' => '더빙실 전송요청이 완료되었습니다'
    ));
} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'msg' => $e->getMessage()
    ));
}
?>