<?php

$server->register('requestPublishContents',
	array(
		'action' => 'xsd:string',
		'xml' => 'xsd:string',
		'usr_id' => 'xsd:string'
	),
	array(
		'success' => 'xsd:string',
		'msg' => 'xsd:string',
		'code' => 'xsd:string',
		'xml' => 'xsd:string'
	),
	$namespace,
	$namespace.'#requestPublishContents',
	'rpc',
	'encoded',
	'requestPublishContents'
);

function requestPublishContents($action, $xml, $usr_id) {
	global $db, $server;

	try{
		$response = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'.chr(10).'<response />');
		
		$cur_date = date('YmdHis');
		
		libxml_use_internal_errors(true);
		$xml_data = simplexml_load_string(trim($xml));
		
		$task = new TaskManager($db);
		
		$artcl_id = $xml_data->data->artcl_id;
		$contents = $xml_data->data->objectdata;
		
		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/zodiac_requestPublish_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] artcl_id ===> '.$artcl_id."\r\n", FILE_APPEND);
		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/zodiac_requestPublish_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] contents ===> '.print_r($contents->record, true)."\r\n", FILE_APPEND);
		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/zodiac_requestPublish_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] user_id ===> '.$user_id."\r\n", FILE_APPEND);
		
		switch($action) {
			case 'request' :
				if( is_null($usr_id) ){
					throw new Exception ('invalid request from unknown user', 101 );
				}
				
				if( is_null($xml) ) {
					throw new Exception ('invalid request xml', 101 );
				}
				
				$channel = 'publish_zodiac';

				foreach($contents->record as $content){
					$content_id = $content->object_id;
					$trg_filenm = $content->file_nm;
					$ori_path = $db->queryOne("
									SELECT	PATH
									FROM	BC_MEDIA
									WHERE	MEDIA_TYPE = 'original'
									AND		CONTENT_ID = $content_id
								");
					$ori_ext = pathinfo($ori_path, PATHINFO_EXTENSION );
					$arr_param_info = array(
							array(
									'target_path' => $trg_filenm.".".$ori_ext
							)
					);
					$task_id = $task->start_task_workflow($content_id, $channel, $usr_id, $arr_param_info);
					if(empty($task_id)) throw new Exception('전송작업 등록에 실패하였습니다');
					$r = $db->exec("
							INSERT INTO TB_ZODIAC_PUBLISH
								(ARTCL_ID, REQ_USER_ID, REQ_DATETIME, CONTENT_ID, STATUS, TASK_ID)
							VALUES
								('$artcl_id', '$usr_id', '$cur_date', $content_id, '".ZODIAC_PUBLISH_QUEUE."', $task_id)
						");
				}

				$success = 'true';
				$msg = '콘텐츠 전송요청에 성공했습니다';
				$code = 200;
			break;
			default :
				throw new Exception ('invalid action', 101);
			break;
		}
		
		return array(
				'success' => $success,
				'msg' => $msg,
				'code' => $code
		);
	} catch(Exception $e) {
		$msg = $e->getMessage();
		$code = $e->getCode();
		$success = 'false';
		
		return array(
				'success' => $success,
				'msg' => $msg
		);
	}
}