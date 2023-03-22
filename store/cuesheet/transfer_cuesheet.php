<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/store/cuesheet/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

use Monolog\Handler\RotatingFileHandler;

try {
    $logger->pushHandler(new RotatingFileHandler(BASEDIR . '/log/' . basename(__FILE__), 14));

    $user_id        = $_SESSION['user']['user_id'];
    $cuesheet_id    = $_REQUEST['cuesheet_id'];
    $cuesheet_nm    = $_REQUEST['cuesheet_nm'];
    $cuesheet_type  = $_REQUEST['cuesheet_type'];
    $subcontrol_room = $_REQUEST['subcontrol_room'];
    $evcr_channel = 'TM_EVCR';
    $date = date('Ymd');
    $modified_date = date('YmdHis');

    $insert_task = new TaskManager($db);

    $cuesheet_contents = $db->queryAll("select * from bc_cuesheet_content where cuesheet_id = '$cuesheet_id' order by show_order");

    foreach ($cuesheet_contents as $content) {
        $content_id = $content['content_id'];
        $ori_task_id = $content['task_id'];

        $logger->addInfo('content_id : ' . $content_id);

        $extension = getOriginalFileExtension($content_id);

        $logger->addInfo('extension : ' . $extension);

        $_channel = 'ftp_transfer';

        $logger->addInfo('channel : ' . $_channel);

        // task_id 가 비어 있을 경우 전송이 안된 항목으로 간주하여 전송
        if (empty($ori_task_id)) {
            $task_id = $insert_task->start_task_workflow($content_id, $_channel, $user_id);
            $query = "update bc_cuesheet_content set task_id = $task_id where cuesheet_id = $cuesheet_id and content_id = $content_id";
            $db->exec($query);
        } else {
            $task_status = $db->queryOne("select status from bc_task where task_id = '$ori_task_id'");
            // task_id를 가지고 task의 상태를 확인하여 error(실패)이면 재전송
            if ($task_status == 'error') {
                $task_id = $insert_task->start_task_workflow($content_id, $_channel, $user_id);
                $query = "update bc_cuesheet_content set task_id = $task_id where cuesheet_id = $cuesheet_id and content_id = $content_id";
                $db->exec($query);
            }
        }

//      $evcr_task_id = $insert_task->start_task_workflow($content_id, $evcr_channel, $user_id);
    }

    $xml = createCuesheetXML($cuesheet_id, 'admin');

    $xml_storage = $db->queryOne("select path from bc_storage where name = 'LOCAL_CUESHEET_XML'");

    // 파일명에서 특수문자 제거
    $xml_nm = preg_replace('/[\\\\\/:*?"<>|]/', '_', $cuesheet_nm).'.xml';

    $local_file = iconv('utf-8', 'euc-kr', $xml_storage.'/'.$xml_nm);

    $logger->addInfo($local_file);
    $logger->addInfo($remote_file);
    $logger->addInfo($xml);

    $options = array('ftp' => array('overwrite' => true)); 
    $stream = stream_context_create($options);
    file_put_contents($local_file, $xml, 0, $stream);

    $takers = $db->queryAll("select * from bc_storage where name='CUESHEET XML'");
    foreach ($takers as $taker) {
        $path = $taker['path'];
        $ftp_server = explode(':', $path);
        $ftp_server_ip = $ftp_server[0];
        $ftp_server_port = $ftp_server[1];
        $ftp_user_name = $taker['login_id'];
        $ftp_user_pass = $taker['login_pw'];
        // set up basic connection
        $conn_id = ftp_connect($ftp_server_ip, $ftp_server_port);

        // login with username and password
        $login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
        // upload a file
        $remote_path = preg_replace('/[\\\\\/:*?"<>|]/', '_', $cuesheet_nm);
        $pushd = ftp_pwd($conn_id);

        if ($pushd !== false && @ftp_chdir($conn_id, $remote_path))
        {
            ftp_chdir($conn_id, $pushd);   
        } else {
            ftp_mkdir($conn_id, $remote_path);
        }

        $remote_file = $remote_path.'/'.$xml_nm;
        if ( ! ftp_put($conn_id, $remote_file, $local_file, FTP_ASCII)) {
            throw new Exception('XML 전송에 실패하였습니다');
        }

        $cuesheet_contents = $db->queryAll("select * from bc_cuesheet_content where cuesheet_id = '$cuesheet_id' order by show_order");
        foreach ($cuesheet_contents as $content) {
            $content_id = $content['content_id'];
            $ori_task_id = $content['task_id'];
            $_channel = 'ftp_transfer_GWR_B';
            $storage_info = $db->queryRow("
                SELECT  PATH
                        ,VIRTUAL_PATH
                FROM    VIEW_UD_STORAGE
                WHERE   UD_CONTENT_ID = (SELECT UD_CONTENT_ID FROM BC_CONTENT WHERE CONTENT_ID = '".$content_id."')
                AND     US_TYPE = 'highres'
            ");

            $storage_path = $storage_info['path'];
            $root = $db->queryOne("SELECT PATH 
                        FROM BC_CONTENT C, BC_UD_CONTENT UC, BC_STORAGE S
                        WHERE C.CONTENT_ID=$content_id
                        AND C.UD_CONTENT_ID=UC.UD_CONTENT_ID
                        AND UC.STORAGE_ID=S.STORAGE_ID");
            $root = rtrim(str_replace('upload/', '', str_replace('\\', '/', $root)), '/').'/highres';

            $ori_media = $db->queryRow("SELECT PATH, MEDIA_ID AS ID
                                    FROM BC_MEDIA 
                                    WHERE CONTENT_ID=$content_id
                                    AND MEDIA_TYPE='original'");
            $storage_path = $storage_path.'/'.$ori_media['path'];
            $extension = getOriginalFileExtension($content_id);
            $remote_file = $remote_path.'/'.$content_id.'.'.$extension;
            if (file_exists($storage_path) && ftp_put($conn_id, $remote_file, $storage_path, FTP_BINARY)) {
                if (empty($ori_task_id)) {
                } else {
                    $now = date('YmdHis');
                    $task_query = "update bc_task set status='complete', progress=100, complete_datetime= '$now' where task_id=".$ori_task_id;
                    $db->exec($task_query);
                }
            }

    //      $evcr_task_id = $insert_task->start_task_workflow($content_id, $evcr_channel, $user_id);
        }
        // close the connection
        ftp_close($conn_id);
    }

    if ( ! empty($cuesheet_id)) {
        $db->exec("update bc_cuesheet set modified_date = '$modified_date' where cuesheet_id = $cuesheet_id");
    }

     echo json_encode(array(
        'success' => true,
        'msg' => _text('MSG02509'),
        'modified_date' => $modified_date
    ));
} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'msg' => $e->getMessage()
    ));
}
?>
