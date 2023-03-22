<?php

$server->register('getGroupInfo',
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
	$namespace.'#getGroupInfo',
	'rpc',
	'encoded',
	'getGroupInfo'
);

function getGroupInfo($action, $usr_id) {
	global $db, $server;

	try{
		$response = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'.chr(10).'<response />');
		
		$cur_date = date('YmdHis');
		
		switch($action) {
			case 'get' :
				if( is_null($usr_id) ){
					throw new Exception ('invalid request from unknown user', 101 );
				}
				
				$groups = $db->queryAll("
								SELECT	MEMBER_GROUP_ID, MEMBER_GROUP_NAME
								FROM	BC_MEMBER_GROUP
								ORDER BY MEMBER_GROUP_NAME
							");
				$data = $response->addChild("data");
				foreach($groups as $group) {
					$record = $data->addChild("record");
					$record->addChild("deptcaption",$group['member_group_name']);
					$record->addChild("deptcodename",$group['member_group_id']);
				}
				
				$success = 'true';
				$msg = '그룹정보 조회에 성공했습니다';
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