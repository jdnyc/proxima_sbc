<?php
set_time_limit(0);
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/MetaData.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');//2011.12.17 Adding Task Manager Class

try{
    $content_id = $_POST['content_id'];
    $content_id_arr = explode(',', $content_id);

    $user_id = $_POST['user_id'];
    $channel = $_POST['channel'];
    
    $task = new TaskManager($db);
    
    $ud_info = $db->queryRow("
        SELECT UD_CONTENT_ID, BS_CONTENT_ID
        FROM BC_CONTENT
        WHERE CONTENT_ID=".$content_id_arr[0]."
    ");
    $storage_info = $db->queryRow("
                        SELECT S.*
                        FROM BC_STORAGE S, BC_UD_CONTENT_STORAGE US
                        WHERE S.STORAGE_ID = US.STORAGE_ID
                        AND US.US_TYPE = 'upload'
                        AND US.UD_CONTENT_ID = '".$ud_info['ud_content_id']."'
                    ");
    
    if(SERVER_TYPE == 'linux'){
        $storage_path = $storage_info['path_for_unix'];
    }else{
        $storage_path = $storage_info['path'];
    }
    @file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/extension'.date('Ymd').'.log', print_r($_FILES['FileUpload'], true), FILE_APPEND);
    for($i=0;$i<count($content_id_arr);$i++){
        $extension = pathinfo($_FILES['FileUpload']['name'][$i], PATHINFO_EXTENSION );
        $filename = $content_id_arr[$i].'.'.$extension;
        @file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/filename'.date('Ymd').'.log', print_r($filename, true), FILE_APPEND);
        $new_name = $storage_path.'/'.$filename;
        $tmp_filename = $_FILES['FileUpload']['tmp_name'][$i];
        @file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/tmp_filename'.date('Ymd').'.log', print_r($tmp_filename, true), FILE_APPEND);
        if(move_uploaded_file($tmp_filename, $new_name)){
            $task->insert_task_query_outside_data($content_id_arr[$i], $channel, 1, $user_id, $filename);
        } else {
            throw new Exception('Failed to register file');
        }
    }
    
    $filepath_arr = explode('/', $file_path);

    $dirs = '';
    foreach($filepath_arr as $dir){
        $dirs .= $dir;
        if(!is_dir($dirs)){
            @mkdir($dirs, 0777);
        }
        $dirs .= '/';
    }

    

    echo json_encode( array(
        'success' => true,
        'result' => 'success'
    ));
}
catch(Exception $e){
    die(json_encode(array(
        'success' => false,
        'result' => 'failure',
        'msg' => $e->getMessage()
    )));
}
?>