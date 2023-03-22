<?php

$server->register('getNPSContentMetas',
	array(
		'objectid' => 'xsd:string'
	),
	array(
		'success' => 'xsd:string',
		'msg' => 'xsd:string',
		'code' => 'xsd:string',
		'xml' => 'xsd:string'
	),
	$namespace,
	$namespace.'#getNPSContentMetas',
	'rpc',
	'encoded',
	'getNPSContentMetas'
);

function getNPSContentMetas($objectid) {
	global $db, $server;

	try{
		$response = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'.chr(10).'<response />');
		
		$content_info = $db->queryRow("
							SELECT	*
							FROM	BC_CONTENT
							WHERE	CONTENT_ID = $objectid
						");
		
		$usrmetatable = MetaDataClass::getTableName('usr', $content_info['ud_content_id']);
		$sysmetatable = MetaDataClass::getTableName('sys', $content_info['bs_content_id']);
		
		$usr_metas = $db->queryRow("
						SELECT	*
						FROM	".$usrmetatable."
						WHERE	USR_CONTENT_ID = $objectid
					");
		
		$sys_metas = $db->queryRow("
						SELECT	*
						FROM	".$sysmetatable."
						WHERE	SYS_CONTENT_ID = $objectid
					");
		
		$success = 'true';
		$msg = 'OK';
		
		$result = $response->addChild("result");
		$result->addAttribute("success", $success);
		$result->addAttribute("msg", $msg);
		
		$data = $response->addChild("data");
		$data->addAttribute("totalcount", '1');
		$data->addAttribute("curpage", '');
		$data->addAttribute("rowcount", '');
		
		$records = $data->addChild("records");
		
		// 사용자 메타에 대해서 추가
		// 콘테이너가 추가될 수 있어서 확장성 고려해서 작업함
		// 사용자 정의 메타
		$user_containers = $db->queryAll("
							SELECT	*
							FROM	BC_USR_META_FIELD
							WHERE	UD_CONTENT_ID = ".$content_info['ud_content_id']."
							AND		USR_META_FIELD_TYPE = 'container'
							ORDER BY SHOW_ORDER ASC
						");
		
		foreach($user_containers as $key => $container) {
            if( $key != 0 ) continue;
			$user_record = $records->addChild("record");
			
			$sub_usr_meta = $db->queryAll("
								SELECT	*
								FROM	BC_USR_META_FIELD
								WHERE	UD_CONTENT_ID = ".$content_info['ud_content_id']."
								AND		CONTAINER_ID = ".$container['usr_meta_field_id']."
								AND		USR_META_FIELD_TYPE != 'container'
								ORDER BY SHOW_ORDER ASC
							");
			
			$user_record->addAttribute("title", $container['usr_meta_field_title']);
			$user_record->addAttribute("type", "usr");
			$user_record->addAttribute("count", count($sub_usr_meta));
			
			foreach($sub_usr_meta as $sub) {
				$usr_meta_field_code = strtolower($sub['usr_meta_field_code']);
				$item = $user_record->addChild("item", $usr_metas[$usr_meta_field_code]);
				$item->addAttribute("title", $sub['usr_meta_field_title']);
			}
		}
		
		// 시스템 메타
		$sys_meta = $db->queryAll("
						SELECT	*
						FROM	BC_SYS_META_FIELD
						WHERE	BS_CONTENT_ID = ".$content_info['bs_content_id']."
						ORDER BY SHOW_ORDER ASC
					");
		$sys_record = $records->addChild("record");
		$sys_record->addAttribute("title", "미디어정보");
		$sys_record->addAttribute("type", "sys");
		$sys_record->addAttribute("count", count($sys_meta));

		foreach($sys_meta as $sys) {
			$sys_meta_field_code = 'sys_'.strtolower($sys['sys_meta_field_code']);
			$item = $sys_record->addChild("item", $sys_metas[$sys_meta_field_code]);
			$item->addAttribute("title", $sys['sys_meta_field_title']);
			
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