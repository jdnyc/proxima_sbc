<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

try {

	$content_id = getSequence('SEQ_CONTENT_ID');

	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/fcp_getUID_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] content_id ===> '.$content_id."\r\n", FILE_APPEND);

	$rtn_msg = array("uid"=>$content_id,"errorcode"=>0,"errormsg"=>"");
	
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/fcp_getUID_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] success::rtn_msg ===> '.print_r($rtn_msg, true)."\r\n", FILE_APPEND);
	
	echo json_encode($rtn_msg);

} catch (Exception $e) {
	$rtn_msg = array("errorcode"=>1,"errormsg"=>$e->getMessage());
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/fcp_getUID_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] error::rtn_msg ===> '.print_r($rtn_msg, true)."\r\n", FILE_APPEND);
	echo json_encode($rtn_msg);
}

?>