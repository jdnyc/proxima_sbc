<?php

function RequestTransmissionContent($request)
{
	global $db;

	try{
		$Interface = new InterfaceClass();
		InterfaceClass::_LogFile($filename,'RequestTransmissionContent request',$request);
		$return;
		$response_json = array();
		$response_xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<Response />");

		$ReqRender			= InterfaceClass::checkSyntax($request);
		$type				= $ReqRender['type'];
		$render_data		= $ReqRender['data'];

		//리턴
		$response = $Interface->DefualtResponse($type);

		if( $type == 'JSON' ){
			$content_id = $render_data['content_id'];
			$user_id = $render_data['user_id'];
        } else if ( $type == 'XML' ) {
			$content_id = $render_data->content_id;
			$user_id = $render_data->user_id;
			//  $action 		= $render_data->action;
		} else {
			throw new Exception ('invalid request', 101 );
		}

		$channel	= 'transmission_zodiac_GWR_A';

		//송출 아이디 생성
		$seq = getSequence('SEQ_TB_ORD_TRANSMISSION');
		$ord_tr_id = date('Ymd').'TR'.str_pad($seq, 5, '0', STR_PAD_LEFT);

		//송출 목록 생성 TB_ORD_TRANSMISSION
		$query_insert = "
			INSERT	INTO	TB_ORD_TRANSMISSION
			(ORD_TR_ID, CONTENT_ID, CREATE_TIME, CREATE_USER)
			VALUES
			('".$ord_tr_id."','".$content_id."','".date('YmdHis')."', '".$user_id."')
		";

		if( $db->exec($query_insert) ){
			$title = $db->queryOne("
				SELECT	COUNT(MEDIA_ID)
				FROM		BC_MEDIA
				WHERE	CONTENT_ID = '".$content_id."'
					AND	MEDIA_TYPE = 'original'
					AND	STATUS != '1'
					AND	FILESIZE > 0
			");

			if(empty($title)) {
				// 미디어가 없을 경우 false return
				$success = 'false';
				$msg = '요청하신 콘텐츠의 원본파일이 없습니다.';
			} else {
				// 미디어가 있을 경우에는 전송요청
				$task = new TaskManager($db);
				$task_id = $task->start_task_workflow($content_id, $channel, $user_id);

				if($task_id){
					//송출테이블에 TASK_ID 업데이트
					$query_update = "
						UPDATE	TB_ORD_TRANSMISSION	SET
						TASK_ID = '".$task_id."'
						WHERE	ORD_TR_ID = '".$ord_tr_id."'
					";

					if( $db->exec($query_update) ){
						$success = 'true';
						$msg = '전송 요청 되었습니다.';
					}else{
						$success = 'false';
						$msg = '작업 등록id 업데이트 실패';
					}
				}else{
					$success = 'false';
					$msg = '전송 요청 실패';
				}
			}
		}else{
			$success = 'false';
			$msg = '송출 목록 생성 실패';
		}

		if($type == 'JSON'){
			$response['success'] = $success;
			$response['message'] = $msg;
		}else{
			$response->addChild('success', $success);
			$response->addChild('message', $msg);
		}

		$return = $Interface->ReturnResponse($type,$response);
		InterfaceClass::_LogFile($filename,'RequestTransmissionContent return',$return);
		return $return ;

	}
	catch(Exception $e){

		$msg = $e->getMessage();
		$code = $e->getCode();
		$success = 'false';

		if($type == 'JSON'){
			$response['success'] = $success;
			$response['message'] = $msg;
			$response['status'] = $code;
		}else{
			$response->success = $success;
			$response->message = $msg;
			$response->status = $code;
		}
		$return = $Interface->ReturnResponse($type,$response);

		InterfaceClass::_LogFile($filename,'return',$return);
		return $return;
	}
}
?>