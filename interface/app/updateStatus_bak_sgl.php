<?php

function updateStatus($request, $request_id)
{	
	global $db;
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sgl_update'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] updateStatus ===> '.$request."\r\n", FILE_APPEND);
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sgl_update'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] updateStatus request_id ===> '.$request_id."\r\n", FILE_APPEND);

	try{
		//리턴
		$response = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<Response />");
		$response->addChild('message', $message);
		$response->addChild('status', $status);
		$response->addChild('success', $success);

		// request_id 가 없을 경우
		if(empty($request_id)) {
			throw new Exception ('invalid request', 101 );
		}

		libxml_use_internal_errors(true);
		$rtn = simplexml_load_string($request);

		if (!$rtn) {
			foreach(libxml_get_errors() as $error){
					$err_msg .= $error->message . "\n";
			}

			throw new Exception ($err_msg, 101 );
		}

		$jobstatus = $rtn->StatusInfo['JobStatus.DWD'];
		$logkey = $rtn->StatusInfo['LogFileKey.DWD'];
		if(!empty($logkey)){
			$db->exec("
						UPDATE SGL_ARCHIVE
						SET LOGKEY = '$logkey'
						WHERE SESSION_ID = $request_id
					");
		}
		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sgl_update'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] jobstatus ===> '.$jobstatus."\r\n", FILE_APPEND);
		if($jobstatus < 0) {
			$task_status = 'error';
			$task_progress = 0;
		} else if ($jobstatus == 0) {
			// jobstatus가 0일경우에는 JobErrorCode.DWD로 한번더 확인
			// 1: Running(진행) / 11 : Passed(성공) / 16: Stopped(사용자취소) / 17: Failed(실패) / 18: Passed with waring(s)(성공이나 경고 포함) / 24: Killed (서버문제로 실패)
			$job_error_code = $rtn->StatusInfo['JobErrorCode.DWD'];
			switch($job_error_code) {
				case '1' :
					$task_status = 'processing';
					$task_progress = 50;
				break;
				case '11' :
					$task_status = 'complete';
					$task_progress = 100;
				break;
				case '16' :
					$task_status = 'cancel';
					$task_progress = 0;
				break;
				case '17' :
					$task_status = 'error';
					$task_progress = 0;
				break;
				case '18' :
					$task_status = 'complete';
					$task_progress = 100;
				break;
				case '24' :
					$task_status = 'error';
					$task_progress = 0;
				break;
			}
		} else if ($jobstatus == 1) {
			$task_status = 'processing';
			$task_progress = 50;
		} else {
			$task_status = 'queue';
			$task_progress = 0;
		}



		// status가 대기 상태일때는 아무것도 안함
		if($task_status != 'queue') {
			// request_id로 task_id를 조회
			$task_id = $db->queryOne("
							SELECT TASK_ID
							FROM SGL_ARCHIVE
							WHERE SESSION_ID = $request_id
					");
			$ip = $_SERVER['REMOTE_ADDR'];
			// task정보를 조회
			$task_info  = $db->queryRow("select t.media_id, t.task_id as task_id, t.assign_ip as assign_ip, td.job_name as job_name, t.type as type, t.start_datetime
											 from bc_task t,BC_TASK_RULE td where t.TASK_RULE_id=td.TASK_RULE_id and t.task_id=$task_id");
			if( empty($task_info) )  throw new Exception('not found TaskInfo', 106);

			$task_type = $task_info['type'];

			$task = new TaskManager($db);

			$task_start_datetime = $task_info['start_datetime'];
			@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sgl_update'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] task_start_datetime ===> '.$task_start_datetime."\r\n", FILE_APPEND);
			// 작업시작 시간이 비었을 경우 작업 시작시간을 넣어주기 위해서 처리
			if( empty($task_start_datetime)) {
				$request_task2 = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Request />');
				$tmp = $request_task2->addChild("Result");
				$tmp->addAttribute('Action','assign');
				$tmp->addChild("TaskID", $task_id);
				$request_task2->addChild("Ip", $ip);

				$sendDatexml = $request_task2->asXML();

				@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sgl_update'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] sendDatexml ===> '.$sendDatexml."\r\n", FILE_APPEND);
				$result2 = $task->Post_XML_Soket($_SERVER['HTTP_HOST'], '/workflow/update_task_status.php', $sendDatexml );
				@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sgl_update'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] result2 ===> '.$result2."\r\n", FILE_APPEND);
				$result_content2 = substr( $result2 , strpos( $result2, '<'));
				$result_content_xml2 = simplexml_load_string($result_content2);

				if (!$result_content_xml2) {
					foreach(libxml_get_errors() as $error){
						$err_msg .= $error->message . "\n";
					}
					throw new Exception ($err_msg, 101 );
				}
				if($result_content_xml2->Result != 'success') throw new Exception( $result_content_xml2->Result, 107);
			}

			if( empty($task_status) ) throw new Exception ('invalid request', 101 );

			$request_task = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Request />');
			$request_task->addChild("TaskID", $task_id );
			$request_task->addChild("TypeCode", $task_type );
			$request_task->addChild("Progress", $task_progress );
			$request_task->addChild("Status", $task_status );
			$request_task->addChild("Ip", $ip);
			$request_task->addChild("Log", $log);
			$sendxml =  $request_task->asXML();
			@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sgl_update'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] sendxml ===> '.$sendxml."\r\n", FILE_APPEND);
			// 상태업데이트
			$result = $task->Post_XML_Soket($_SERVER['HTTP_HOST'], '/workflow/update_task_status.php', $sendxml );
			@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sgl_update'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] result ===> '.$result."\r\n", FILE_APPEND);
			$result_content = substr( $result , strpos( $result, '<'));
			$result_content_xml = simplexml_load_string($result_content);

			@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sgl_update'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] result ===> '.$result."\r\n", FILE_APPEND);

			if (!$result_content_xml) {
				foreach(libxml_get_errors() as $error){
						$err_msg .= $error->message . "\n";
				}
				throw new Exception ($err_msg, 101 );
			}

			if($result_content_xml->Result != 'success') throw new Exception( $result_content_xml->Result, 107);
		}
		$response->success = 'true';
		$response->message = 'Update Complete';
		$response->status = '0';

		if($task_status == 'complete') {
			// 아카이브 성공일 경우에는 bc_content에 archive_date 업데이트
			if($task_type == '110') {
				$sgl_archive_info =  $db->queryRow("
									SELECT 	UNIQUE_ID,
											TASK_ID
									FROM SGL_ARCHIVE
									WHERE SESSION_ID = $request_id
									");
				$content_id = $sgl_archive_info['unique_id'];
				$task_id = $sgl_archive_info['task_id'];

				$now = date('YmdHis');
				$db->exec("
						UPDATE BC_CONTENT
						SET ARCHIVE_DATE = '$now'
						WHERE CONTENT_ID = $content_id
					");
				$content_status_query = "
					UPDATE	BC_CONTENT_STATUS
					SET		ARCHIVE_STATUS	= 'Y'
							,ARCHIVE_DATE = '".$now."'
					WHERE	CONTENT_ID	= ".$content_id
				;
				$db->exec($content_status_query);

				$update_archive_query ="
					UPDATE	BC_ARCHIVE_REQUEST
					SET		PROGRESS			= 100
							,STATUS				= 'COMPLETE'
							,COMPLETE_DATETIME	= '".$now."'
					WHERE	TASK_ID = $task_id
				";
				$db->exec($update_archive_query);

			}
			// 리스토어 성공인경우, bc_restore_ing 테이블에서 제거
			if($task_type == '160') {
				$sgl_archive_info =  $db->queryRow("
									SELECT 	UNIQUE_ID,
											TASK_ID
									FROM SGL_ARCHIVE
									WHERE SESSION_ID = $request_id
							");
				$content_id = $sgl_archive_info['unique_id'];
				$task_id = $sgl_archive_info['task_id'];

				//bc_content의 restore_date를 업데이트
				$now = date('YmdHis');
				$db->exec("
						UPDATE BC_CONTENT
						SET RESTORE_DATE = '$now'
						WHERE CONTENT_ID = $content_id
				");
				$db->exec("delete from bc_restore_ing where content_id = $content_id");

				$content_status_query = "
					UPDATE	BC_CONTENT_STATUS
					SET		RESTORE_DATE = '".$now."'
					WHERE	CONTENT_ID	= ".$content_id
				;
				$db->exec($content_status_query);

				$update_archive_query ="
					UPDATE	BC_ARCHIVE_REQUEST
					SET		PROGRESS			= 100
							,STATUS				= 'COMPLETE'
							,COMPLETE_DATETIME	= '".$now."'
					WHERE	TASK_ID = $task_id
				";
				$db->exec($update_archive_query);
			}

			if($task_type == '140'){
				$sgl_archive_info =  $db->queryRow("
									SELECT 	UNIQUE_ID,
											TASK_ID
									FROM SGL_ARCHIVE
									WHERE SESSION_ID = $request_id
							");
				$content_id = $sgl_archive_info['unique_id'];
				$task_id = $sgl_archive_info['task_id'];

				$update_archive_query ="
					UPDATE	BC_ARCHIVE_REQUEST
					SET		PROGRESS			= 100
							,STATUS				= 'COMPLETE'
							,COMPLETE_DATETIME	= '".$now."'
					WHERE	TASK_ID = $task_id
				";
				$db->exec($update_archive_query);


			}
			// 성공일 경우 다음 진행 작업 있는지 확인하여 신규 작업 전송 필요
		}
		else if($task_status == 'error') {
			if($task_type == '110') {
				$sgl_archive_info =  $db->queryRow("
									SELECT 	UNIQUE_ID,
											TASK_ID
									FROM SGL_ARCHIVE
									WHERE SESSION_ID = $request_id
							");
				$content_id = $sgl_archive_info['unique_id'];
				$task_id = $sgl_archive_info['task_id'];

				//update to bc_content_status and bc_archive_request
				$content_status_query = "
						UPDATE	BC_CONTENT_STATUS
						SET		ARCHIVE_STATUS	= 'N'
						WHERE	CONTENT_ID	= ".$content_id
					;
				$db->exec($content_status_query);

				$update_archive_query ="
					UPDATE	BC_ARCHIVE_REQUEST
					SET		STATUS 	= 'FAILED'
					WHERE	TASK_ID = $task_id
				";
				$db->exec($update_archive_query);
			}
			// 리스토어 실패인경우, bc_restore_ing 테이블에서 제거
			if($task_type == '160') {
				$sgl_archive_info =  $db->queryRow("
									SELECT 	UNIQUE_ID,
											TASK_ID
									FROM SGL_ARCHIVE
									WHERE SESSION_ID = $request_id
							");
				$content_id = $sgl_archive_info['unique_id'];
				$task_id = $sgl_archive_info['task_id'];

				$db->exec("delete from bc_restore_ing where content_id = $content_id");

				$update_archive_query ="
					UPDATE	BC_ARCHIVE_REQUEST
					SET		STATUS 	= 'FAILED'
					WHERE	TASK_ID = $task_id
				";
				$db->exec($update_archive_query);

			}

			if($task_type == '140'){
				$sgl_archive_info =  $db->queryRow("
									SELECT 	UNIQUE_ID,
											TASK_ID
									FROM SGL_ARCHIVE
									WHERE SESSION_ID = $request_id
							");
				$content_id = $sgl_archive_info['unique_id'];
				$task_id = $sgl_archive_info['task_id'];

				$update_archive_query ="
					UPDATE	BC_ARCHIVE_REQUEST
					SET		STATUS 	= 'FAILED'
					WHERE	TASK_ID = $task_id
				";
				$db->exec($update_archive_query);

			}
		}

		return $response->asXML() ;

	}
	catch(Exception $e){

		$msg = $e->getMessage();
		$code = $e->getCode();
		$success = 'false';

		$response->success = $success;
		$response->message = $msg;
		$response->status = $code;

		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sgl_update'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] Exception ===> '.$response->asXML()."\r\n", FILE_APPEND);

		return $response->asXML();
	}
}
?>