<?php
/**
 * 2019-01-23 이승수
 * 
 * 첨부파일 삭제기능. Web에서 바로 삭제 후 bc_media에서도 삭제
 */
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

try
{
    $media_id = $_REQUEST['media_id'];
    $ud_content_id = $_REQUEST['ud_content_id'];
    $user_id = $_SESSION['user']['user_id'];

    if(empty($media_id) || empty($ud_content_id)) {
        throw new Exception(_text('MSG02201'));//잘못된 접근입니다. 다시 시도 해 주시기 바랍니다.
    }

    $media_info = $db->queryRow("select * from bc_media where media_id=".$media_id);
    
    $storage_info = $db->queryRow("
						SELECT S.*
						FROM BC_STORAGE S, BC_UD_CONTENT_STORAGE US
						WHERE S.STORAGE_ID = US.STORAGE_ID
						AND US.US_TYPE = 'lowres'
						AND US.UD_CONTENT_ID = '".$ud_content_id."'
					");
	if(SERVER_TYPE == 'linux'){
		$storage_path = $storage_info['path_for_unix'];
	}else{
		$storage_path = $storage_info['path'];
	}

	$storage_path_array = explode('/', $storage_path);
	$storage_path_array2 = $storage_path_array;

	$storage_path = join('/', $storage_path_array2);
    $file_full_path = stripslashes($storage_path."/".$media_info['path']);

    $del_query = "delete from bc_media where media_id=".$media_id;
    if(!file_exists($file_full_path)) {
        //파일을 못찾는다면 DB삭제
        //throw new Exception('no file('.$file_full_path.')');
        $db->exec($del_query);
    } else if(unlink($file_full_path)) {
        //파일 삭제. 성공하면 DB삭제
        $db->exec($del_query);
    } else {
        //파일 삭제 실패시
        throw new Excpeiton(_text('MSG02052'));//다시 시도 해 주시기 바랍니다.
    }
    
    echo json_encode(array(
        'success' => true,
        'msg' => $msg
    ));
}
catch (Exception $e)
{
    $msg = $e->getMessage();

    echo json_encode(array(
        'success' => false,
        'msg' => $msg
    ));
}

?>