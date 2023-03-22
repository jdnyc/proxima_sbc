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
    $destination = $_POST['destination'];
    $contents = array(
        array(
            'content_id' => $_POST['content_id'],
            'title' => $_POST['title'],
            'folder' => $_POST['folder']
        )
    );

    $date = date('Ymd');

    switch($destination) {
    case 'A' :
        $channel = 'TM_SUBA';
        break;
    case 'B' :
        $channel = 'TM_SUB_B_TITLE';
        break;
    }
   
    $insert_task = new TaskManager($db);
    foreach ($contents as $content) {
        $options = null;
        $content_id = $content['content_id'];
        $_channel = $channel;

        $logger->addInfo('content_id : ' . $content_id);

        $extension = getOriginalFileExtension($content_id);

        $logger->addInfo('extension : ' . $extension);
        if (strtoupper($extension) == 'MXF') {
            $_channel .= '_MXF';

            if ($destination == 'A') {

                // root path 제거
                $options = array(
                    'change_target_path' => '/' . str_replace('\\', '/', trim($content['folder'], '/')) . '/' . $content['title'] . '.mxf',
                );
            }
        }

        $logger->addInfo('channel : ' . $_channel);

        $task_id = $insert_task->start_task_workflow($content_id, $_channel, $user_id, null, $options);
    }

     echo json_encode(array(
        'success' => true,
        'msg' => '전송요청이 완료되었습니다'
    ));
} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'msg' => $e->getMessage()
    ));
}
?>
