<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/fcp_filepath_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] request ===> '.print_r($_REQUEST, true)."\r\n", FILE_APPEND);
$content_id = $_REQUEST['UID'];

try {

	$content = $db->queryRow("
					SELECT	M.PATH AS MEDIA_PATH, S.PATH_FOR_MAC AS STORAGE_PATH
					FROM	BC_MEDIA M, BC_UD_CONTENT_STORAGE US, BC_CONTENT C, BC_STORAGE S
					WHERE	M.CONTENT_ID = C.CONTENT_ID
					ANd		M.MEDIA_TYPE ='original'
					AND		C.UD_CONTENT_ID = US.UD_CONTENT_ID
					AND		US.STORAGE_ID = S.STORAGE_ID
					AND		US.US_TYPE = 'highres'
					AND		M.CONTENT_ID = $content_id
				");

	if(empty($content['storage_path']) || empty($content['media_path'])) {
		throw new Exception('empty path for the content');
	}

	$filepath = '/'.str_replace('\\', '/', $content['storage_path'].'/'.$content['media_path']);

	// test용으로 사용하기 위한 경로. 실사용시 mam과 연동 가능하도록 수정해야됨
	//$filepath = '/'.str_replace('\\', '/', '/Volumes/Macintosh HD/fcpx_export/'.$content['media_path']);

	$rtn_msg = array("uid"=>$content_id,"filepath"=>$filepath,"errorcode"=>0,"errormsg"=>"");
	
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/fcp_filepath_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] success::rtn_msg ===> '.print_r($rtn_msg, true)."\r\n", FILE_APPEND);
	
	//$rtn_msg = array("uid"=>$content_id,"errorcode"=>1,"errormsg"=>'test error');
	echo json_encode($rtn_msg);

} catch (Exception $e) {
	$rtn_msg = array("uid"=>$content_id,"errorcode"=>1,"errormsg"=>$e->getMessage());
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/fcp_filepath_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] error::rtn_msg ===> '.print_r($rtn_msg, true)."\r\n", FILE_APPEND);
	echo json_encode($rtn_msg);
}

?>