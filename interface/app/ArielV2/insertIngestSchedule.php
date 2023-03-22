<?php

function insertIngestSchedule($request)
{
	global $db;

	try{
		$Interface = new InterfaceClass();
		InterfaceClass::_LogFile($filename,'insertIngestSchedule request',$request);
		$return;
		$response_json = array();
		$response_xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<Response />");

		$ReqRender		= InterfaceClass::checkSyntax($request);
                $type			= $ReqRender['type'];
		$render_data            = $ReqRender['data'];

		//리턴
		$response = $Interface->DefualtResponse($type);

		if( $type == 'JSON' ){
			$action 		= $render_data[action];
			$schedule_id            = $render_data[schedule_id];
			$ingest_system_ip	= $render_data[ingest_system_ip];
			$channel		= $render_data[channel];
			$schedule_type 		= $render_data[schedule_type];
			$ingest_day		= $render_data[ingest_day];
			$ingest_date		= $render_data[ingest_date];
			$start_time		= $render_data[start_time];
                        $duration	   	= $render_data[duration];
                        $create_time        	= $render_data[create_time];
			$status 		= $render_data[status];
			$category_id		= $render_data[category_id];
			$content_type_id	= $render_data[content_type_id];
                        $meta_table_id	   	= $render_data[meta_table_id];
                        $title	        	= $render_data[title];
			$user_id		= $render_data[user_id];
			$is_use			= $render_data[is_use];
			$usr_program		= $render_data[usr_program];
			$usr_prog_id		= $render_data[usr_prog_id];
			$usr_subprog		= $render_data[usr_subprog];
			$usr_content		= $render_data[usr_content];
			$usr_mednm		= $render_data[usr_mednm];
			$usr_producer		= $render_data[usr_producer];
			$usr_grade		= $render_data[usr_grade];
			$usr_keyword		= $render_data[user_keyword];

                } else if ( $type == 'XML' ) {
                        $action 		= $render_data->action;
                        $schedule_id            = $render_data->schedule_id;
			$ingest_system_ip	= $render_data->ingest_system_ip;
			$channel		= $render_data->channel;
			$schedule_type 		= $render_data->schedule_type;
			$ingest_day		= $render_data->ingest_day;
			$ingest_date		= $render_data->ingest_date;
			$start_time		= $render_data->start_time;
                        $duration	   	= $render_data->duration;
                        $create_time        	= $render_data->create_time;
			$status 		= $render_data->status;
			$category_id		= $render_data->category_id;
			$content_type_id	= $render_data->content_type_id;
                        $meta_table_id	   	= $render_data->meta_table_id;
                        $title	        	= $render_data->title;
			$user_id		= $render_data->user_id;
			$is_use			= $render_data->is_use;
			$usr_program		= $render_data->usr_program;
			$usr_prog_id		= $render_data->usr_prog_id;
			$usr_subprog		= $render_data->usr_subprog;
			$usr_content		= $render_data->usr_content;
			$usr_mednm		= $render_data->usr_mednm;
			$usr_producer		= $render_data->usr_producer;
			$usr_grade		= $render_data->usr_grade;
			$usr_keyword		= $render_data->user_keyword;

		} else {
			throw new Exception ('invalid request', 101 );
		}
                
                if( empty($action) ) throw new Exception("invaild request", 101);
                
		$ud_content_id = '4000282';
		
		if( $action == 'add' && empty($schedule_id) ){
			$schedule_id = getSequence('IM_SCHEDULE_SEQ');
			InterfaceClass::insertIngestSchedule($title, $ingest_system_ip, $channel, $schedule_type, $ingest_day, $ingest_date, $start_time, $duration, $is_use, $ud_content_id, $user_id, $usr_prog_id);
                        InterfaceClass::insertIngestScheduleMeta($action, $schedule_id, $usr_program, $usr_prog_id, $usr_subprog, $usr_content, $usr_mednm, $usr_producer, $usr_grade, $usr_keyword);
                        $success = 'true';
                        $msg = '인제스트 스케줄이 성공적으로 등록되었습니다';
		} else if($action == 'edit') {
			InterfaceClass::editIngestSchedule($schedule_id, $title, $ingest_system_ip, $channel, $schedule_type, $ingest_day, $ingest_date, $start_time, $duration, $is_use, $ud_content_id, $usr_prog_id);
			InterfaceClass::insertIngestScheduleMeta($action, $schedule_id, $usr_program, $usr_prog_id, $usr_subprog, $usr_content, $usr_mednm, $usr_producer, $usr_grade, $usr_keyword);
			$success = 'true';
                        $msg = '인제스트 스케줄이 성공적으로 수정되었습니다';
		} else if($action == 'del') {
			InterfaceClass::delIngestSchedule($schedule_id);
			InterfaceClass::insertIngestScheduleMeta($action, $schedule_id, $usr_program, $usr_prog_id, $usr_subprog, $usr_content, $usr_mednm, $usr_producer, $usr_grade, $usr_keyword);
			$success = 'true';
                        $msg = '인제스트 스케줄이 성공적으로 삭제되었습니다';
		}


		if($type == 'JSON'){
			$response['success'] = $success;
			$response['message'] = $msg;
		}else{
			$response->addChild('success', $success);
			$response->addChild('message', $msg);
		}

		$return = $Interface->ReturnResponse($type,$response);
		InterfaceClass::_LogFile($filename,'insertIngestSchedule return',$return);
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