<?php

$server->register('getPGMLists',
	array(
		'action' => 'xsd:string',
		'usr_id' => 'xsd:string'
	),
	array(
		'success' => 'xsd:string',
		'msg' => 'xsd:string',
		'code' => 'xsd:string',
		'xml' => 'xsd:string'
	),
	$namespace,
	$namespace.'#getPGMLists',
	'rpc',
	'encoded',
	'getPGMLists'
);

function getPGMLists($action, $usr_id) {
	global $db, $server;

	try{
		$response = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'.chr(10).'<response />');
		
		$cur_date = date('YmdHis');
		
		switch($action) {
			case 'get' :
				if( is_null($usr_id) ){
					throw new Exception ('invalid request from unknown user', 101 );
				}
				
				$programs = $db->queryAll("
								SELECT	C.CATEGORY_TITLE, C.CATEGORY_ID
								FROM	BC_CATEGORY C, PATH_MAPPING P
								WHERE	C.CATEGORY_ID = P.CATEGORY_ID
								AND		P.USING_YN = 'Y'
								ORDER BY C.CATEGORY_TITLE ASC
							");
				$data = $response->addChild("data");
				foreach($programs as $program) {
					$record = $data->addChild("record");
					$record->addChild("deptcaption",$program['category_title']);
					$record->addChild("deptcodename",$program['category_id']);
				}
				
				$success = 'true';
				$msg = '프로그램목록 조회에 성공했습니다';
				$code = 200;
			break;
			default :
				throw new Exception ('invalid action', 101);
			break;
		}
		
		return array(
				'success' => $success,
				'msg' => $msg,
				'code' => $code,
				'xml' => getReturnXML($response)
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