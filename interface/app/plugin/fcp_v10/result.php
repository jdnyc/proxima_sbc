<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/interface.class.php');

@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/fcp_result_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] request ===> '.print_r($_REQUEST, true)."\r\n", FILE_APPEND);
$content_id = $_REQUEST['UID'];
$filepath = $_REQUEST['filepath'];
$errorcode = $_REQUEST['errorcode'];
$errormsg = $_REQUEST['errormsg'];
$remote_ip = $_SERVER['REMOTE_ADDR'];

try {

	// 파일경로와 Content_id로 task_id를 찾아야 됨
	// 파일경로는 storage 경로를 제외한 파일 경로로 찾아야됨
	// 전송률 업데이트가 가능한지도 확인 필요

	if(empty($content_id)) {
		throw new Exception('empty uid');
	}

	$task_id = $db->queryOne("
					SELECT	TASK_ID
					FROM	TB_FCP_MAP
					WHERE	CONTENT_ID = $content_id
				");

	$task_info = $db->queryRow("
					SELECT	*
					FROM	BC_TASK
					WHERE	TASK_ID = $task_id
				");

	if(empty($task_info)) {
		throw new Exception("can not find the job");
	}

	$task_type = $task_info['type'];
	
	switch($errorcode) {
		case '0':
			$status = 'complete';
			$progress = '100';
		break;
		default:
			$status = 'error';
			$progress = '0';
		break;
	}

	if(!empty($status)) {
		$request = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Request />');
		$task = new TaskManager($db);

		$request->addChild("TaskID", $task_id );
		$request->addChild("TypeCode", $task_type );
		$request->addChild("Progress", $progress );
		$request->addChild("Status", $status );
		$request->addChild("Ip", $remote_ip);
		$request->addChild("Log", $errormsg);
		$sendxml =  $request->asXML();
@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/fcp_result_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] sendxml ===> '.$sendxml."\r\n", FILE_APPEND);
		$result = $task->Post_XML_Soket($_SERVER['HTTP_HOST'], '/workflow/update_task_status.php', $sendxml );
		$result_content = substr( $result , strpos( $result, '<'));
		$result_content_xml = InterfaceClass::checkSyntax($result_content);

		if($result_content_xml[data]->Result != 'success') throw new Exception( $result_content_xml[data]->Result, 107);
	}


	$rtn_msg = array("uid"=>$content_id,"filepath"=>$filepath,"errorcode"=>0,"errormsg"=>"");
	
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/fcp_result_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] success::rtn_msg ===> '.print_r($rtn_msg, true)."\r\n", FILE_APPEND);
	
	echo json_encode($rtn_msg);

} catch (Exception $e) {
	$rtn_msg = array("uid"=>$content_id,"filepath"=>$filepath,"errorcode"=>1,"errormsg"=>$e->getMessage());
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/fcp_result_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] error::rtn_msg ===> '.print_r($rtn_msg, true)."\r\n", FILE_APPEND);
	echo json_encode($rtn_msg);
}

?>