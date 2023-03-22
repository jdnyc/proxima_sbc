<?php

$server->register('getFileLocation',
	array(
		'playout_id' => 'xsd:string',
		'usr_id' => 'xsd:string'
	),
	array(
		'success' => 'xsd:string',
		'msg' => 'xsd:string',
		'code' => 'xsd:string'
	),
	$namespace,
	$namespace.'#getFileLocation',
	'rpc',
	'encoded',
	'getFileLocation'
);

function getFileLocation($playout_id, $usr_id) {
	global $db, $server;
	
	try{
		if(empty($usr_id)) {
			throw new Exception ('invalid request user_id', 101 );
		}

		$response = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'.chr(10).'<response />');
		
		$zodiac_workflow = "
				'transmission_graphic_seq_zodiac',
				'transmission_graphic_zodiac',
				'transmission_group_zodiac',
				'transmission_zodiac'
		";
		
		$content = $db->queryRow("
						SELECT	*
						FROM	BC_CONTENT
						WHERE	CONTENT_ID = (
												SELECT	CONTENT_ID
												FROM	TB_ORD_TRANSMISSION_ID
												WHERE	PLAYOUT_ID = '$playout_id'
											)
                    ");
                    
        //이관 자료인경우는

        //미디어 파일명에서 찾아봄

        if (!empty($content)) {
            $content_id = $content['content_id'];
        }else{            
            $media = $db->queryRow("select * from bc_media where media_type='original' and path like '%$playout_id%' ");
            $content_id = $media['content_id'];
        }

        if( !empty($content_id) ){
            $contentService = new \Api\Services\ContentService( app()->getContainer() );      
            $contentStatus = $contentService->findStatusMeta($content_id);

            if ($contentStatus->scr_news_trnsmis_sttus == 'complete' || $contentStatus->scr_trnsmis_sttus == 'complete' || $contentStatus->scr_trnsmis_sttus == 'complete' || $contentStatus->scr_trnsmis_sttus == '3000') {
                $success = 'true';                
                $msg = 'OK';
                
                return array(
                        'success' => $success,
                        'msg' => $msg,
                        'code' => $code
                );
            }
        }
           
		if(empty($content)) {
			throw new Exception ('invalid request playout_id', 101 );
		}
		
		$media_type = 'original';
		
		if($content['bs_content_id'] == SEQUENCE) {
			$media_type = 'seq_mxf';
		}
		
		$task = $db->queryRow("
					SELECT	*
					FROM	BC_TASK
					WHERE	DESTINATION IN ($zodiac_workflow)
					AND		MEDIA_ID = (
										SELECT	MEDIA_ID
										FROM	BC_MEDIA
										WHERE	MEDIA_TYPE = '$media_type'
										AND		CONTENT_ID = ".$content['content_id']."
										)
					AND		TRG_STORAGE_ID = 7
					ORDER BY TASK_ID DESC
				");

		switch($task['status']) {
			case 'complete' :
				$success = 'true';
			break;
			// 진행중이거나 assignin 이면 false로 리턴
			case 'processing' :
			case 'assigning' :
				$success = 'false';
			break;
			// 에러일 경우에는 마지막 로그 확인해서 중복이 아니면 false, 중복이면 true
			case 'error' :
				$task_log = $db->queryOne("
								SELECT	DESCRIPTION
								FROM	BC_TASK_LOG
								WHERE	TASK_ID = ".$task['task_id']."
								ORDER BY TASK_LOG_ID DESC
							");
				if(strpos($task_log, 'Cannot overwrite existing')) {
					$success = 'true';
				} else if (strpos($task_log, 'asset already exists')) {
					$success = 'true';
				} else {
					$success = 'false';
				}
				
			break;
		}
		
		$msg = 'OK';
		
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