<?php

function RequestTransmissionContent($request)
{
	global $db;

	try{
        $Interface = new InterfaceClass();
        $zodiac = new Zodiac();
		InterfaceClass::_LogFile('','RequestTransmissionContent request',$request);
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

        $channel	= 'transmission_zodiac';
        
        $playout_id = $zodiac->createTransmissionId($content_id);
        InterfaceClass::_LogFile('','RequestTransmissionContent playout_id',$playout_id);

        //송출 목록 생성 TB_ORD_TRANSMISSION
        $ord_tr_id = $zodiac->addListTransmission($content_id, $user_id, $playout_id,'');
        InterfaceClass::_LogFile('','RequestTransmissionContent ord_tr_id',$ord_tr_id);

    
        if ($ord_tr_id) {
            //송출
            //전송 이력 확인
            $contentService = new \Api\Services\ContentService(app()->getContainer());
            $taskService = new \Api\Services\TaskService(app()->getContainer());
            $contentStatus = $contentService->findStatusMeta($content_id);
        }else{
            throw new Exception("목록 생성 실패");
        }

        if ( $contentStatus->scr_trnsmis_sttus == 'complete' || $contentStatus->scr_trnsmis_sttus == '3000' ) {
            //전송 완료

            //콘텐츠에 부조 전송 상태 완료시 바로 완료 처리함
            $trCompletedAt = $contentStatus->scr_trnsmis_end_dt ?? date('YmdHis');         
           
            $r = $db->exec("UPDATE TB_ORD_TRANSMISSION SET COMPLETE_TIME=".$trCompletedAt.",UPDATE_TIME=".$trCompletedAt." ,TR_STATUS='complete', TR_PROGRESS='100' WHERE ORD_TR_ID='$ord_tr_id'");
            $response['trnsf_rate'] = 100;
            $success = 'true';
            $msg = '매핑 성공했습니다.';
        }else if( !empty($contentStatus->scr_trnsmis_sttus) ){
            //진행중인경우
            if( $contentStatus->scr_trnsmis_sttus == 'request' ){
                $result =  array(
                    'success' => false,
                    'msg' => '전송 준비중입니다'
                );
            }else{
                $bfTask = \Api\Models\Task::where('src_content_id' , $content_id)->where('destination','transmission_zodiac')->orderBy('task_id','desc')->first();
                if( !empty($bfTask) ){
                    $task_id = $bfTask->task_id;
                }
                
                if($ord_tr_id && $task_id){
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
        }
        InterfaceClass::_LogFile('','RequestTransmissionContent task_id',$task_id);

		if($type == 'JSON'){
            $response['success'] = $success;
            $response['data'] = $playout_id;
			$response['message'] = $msg;
		}else{
			$response->addChild('success', $success);
            $response->addChild('message', $msg);            
			$response->addChild('data', $playout_id);
		}

		$return = $Interface->ReturnResponse($type,$response);
		InterfaceClass::_LogFile('','RequestTransmissionContent return',$return);
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

		InterfaceClass::_LogFile('','return',$return);
		return $return;
	}
}
?>