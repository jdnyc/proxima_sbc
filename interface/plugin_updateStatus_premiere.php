<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/interface.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/MetaData.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/soap/nusoap.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');


$request = file_get_contents('php://input');
@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/plugin_updateStatus_premiere'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] request '.$request."\n", FILE_APPEND);
$remote_ip = $_SERVER['REMOTE_ADDR'];
try{
	$Interface = new InterfaceClass();
	InterfaceClass::_LogFile('','request',$request);

	//변환
	$ReqRender		=  InterfaceClass::checkSyntax($request);
	$type			= $ReqRender['type'];
	$render_data	= $ReqRender['data'];

	//리턴
	$response_json = $Interface->DefualtResponse($type);
	$response = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?> \n<response><result /></response>");

	if( $type == 'XML' ){
		$render_data_encode = json_encode($render_data);
		$render_data = json_decode($render_data_encode, true);
	}

	if( $type == 'JSON' || $type == 'XML' ){
		$action		= $render_data['action'];
		$task_id	= $render_data['task_id'];
		$status		= $render_data['status'];
		$progress	= empty($render_data['progress']) ? 0 : $render_data['progress'];
		$ip			= $remote_ip;
		$log		= $render_data['log'];
		
		if(!$log) {
			$log = "";
		}

		$filesize		= $render_data['filesize'];
		$filename		= $render_data['filename'];
	}else{
		throw new Exception ('invalid request', 101 );
	}

	if( empty($action) || empty($task_id) ){
		throw new Exception ('invalid request', 101 );
	}

	$task = new TaskManager($db);

	switch ($action)
	{
		case 'update':
			//EDIUS 등록시엔 task_id자리에 content_id를 넣어주기
			//채널A는 이 페이지에서 메타등록+Task시작, 렌더 끝날시 task_id로 상태값 처리했음. 그래서 task_id가 존재.
			//KT사내방송은 메타등록만 여기서 하고, 렌더 끝날시 content_id가지고 Task시작 해 주기 위해서.
			$content_id = $task_id;
			$content_info = $db->queryRow("select * from bc_content where content_id=".$content_id);
			$user_id = $content_info['reg_user_id'];

			//filename이 전체경로로 넘어오므로 변환
			$filepath = str_replace('\\', '/', $filename);
			$filepath = trim($filepath, '/');
			$filepath_array = explode('/', $filepath);
			$filename = array_pop($filepath_array);
			$filename_array = explode('.',$filename);
			$file_ext = array_pop($filename_array);
			
			$job_priority = 1;

			$channel = 'premiere';
//echo "insert_task_query_outside_data($content_id, $channel, $job_priority, $user_id, $filename)";exit;
			$task_id = $task->insert_task_query_outside_data($content_id, $channel, $job_priority, $user_id, $filename);

		break;

		case 'rename':

			

		break;

		default:
			 throw new Exception('unknown action', 106);
		break;
	}

	if($type == 'JSON') {
		$response_json['data'] = $result_content_xml;
		$return = $Interface->ReturnResponse($type,$response_json);
	} else if($type == 'XML') {
		$response->result->addAttribute('success', 'true');
		$response->result->addAttribute('msg', 'ok');
		$response->result->addChild('success', 'true' );
		$response->result->addChild('msg', 'ok');
		$return = $response->asXML();
	}

	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/plugin_updateStatus_premiere'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] return '.$return."\n", FILE_APPEND);
	InterfaceClass::_LogFile('','return',$return);
	echo $return;

}
catch(Exception $e){

	$msg = $e->getMessage();
	$code = $e->getCode();
	$success = 'false';

	if($type == 'JSON'){
		$response_json['success'] = $success;
		$response_json['message'] = $msg;
		$response_json['status'] = $code;
		$return = $Interface->ReturnResponse($type,$response_json); 
	}else{
		$response->success = $success;
		$response->message = $msg;
		$response->status = $code;
		$return = $response->asXML();
	}
	

	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/plugin_updateStatus_premiere'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] error '.$return."\n", FILE_APPEND);
	InterfaceClass::_LogFile('','return',$return);
	return $return;
}

?>