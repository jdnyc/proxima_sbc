<?php
/**
 * Created by PhpStorm.
 * User: cerori
 * Date: 2015-01-15
 * Time: 오후 4:06
 */

use Monolog\Handler\RotatingFileHandler;

$server->register('SoapUpdateTaskStatus',
    array(
        'task_id' => 'xsd:string',
        'cartridge_id' => 'xsd:string',
        'content_id' => 'xsd:string',
        'progress' => 'xsd:string',
        'status' => 'xsd:string'
    ),
    array(
        'code' => 'xsd:string',
        'msg' => 'xsd:string'
    ),
    $namespace,
    $namespace.'#SoapUpdateTaskStatus',
    'rpc',
    'encoded',
    'SoapUpdateTaskStatus'
);

function SoapUpdateTaskStatus($task_id, $cartridge_id=null, $content_id, $progress, $status) {
    global $db, $server, $logger;

    $logger->pushHandler(new RotatingFileHandler($_SERVER['DOCUMENT_ROOT'] . '/log/func_' . __FUNCTION__ . '.log', 14));

	 try {
		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/odaUpdateStatus_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] task_id ===> '.$task_id.' | cartridge_id ==> '.$cartridge_id.' | content_id ==> '.$content_id.' | progress ==> '.$progress.' | status ==> '.$status."\r\n", FILE_APPEND);

		if($status != 'complete'){
			$query_update_task = "
				UPDATE	BC_TASK SET
					PROGRESS = ".$progress.",
					STATUS = '".$status."'
				WHERE	TASK_ID = '".$task_id."'
			";
			$db->exec($query_update_task);
		}


		//2016-06-22 요청상태 변경[1-Request, 3-Reject, 5-Approve, 7-Processing, 9-Failed, 11-Complete]
		 switch ($status) {
			case 'queue':
				$update_status = 1;
				$description = "작업 시작";
			break;
			case 'processing':
				//$update_status = 2;
				$update_status = 7;
				$description = "진행중...".$progress."%";
			break;
			case 'complete':
				$description = "작업 완료...".$progress."%";
				//$update_status = 3;
				$update_status = 11;
				$task_type = 160;
				$task_info = $db->queryRow("SELECT * FROM BC_TASK WHERE TASK_ID = '$task_id'");
				if($task_info['destination'] == 'regist_restore_pfr') {
					$request = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Request />');
					$request->addChild("TaskID", $task_id );
					$request->addChild("TypeCode", $task_type );
					$request->addChild("Progress", $progress );
					$request->addChild("Status", $status );
					$request->addChild("Ip", $ip);
					$request->addChild("Log", $log);
					$sendxml =  $request->asXML();

					$task = new TaskManager($db);
					$result = $task->Post_XML_Soket($_SERVER['HTTP_HOST'], '/workflow/update_task_status.php', $sendxml );
					$result_content = substr( $result , strpos( $result, '<'));
					$result_content_xml = InterfaceClass::checkSyntax($result_content);

					if($result_content_xml[data]->Result != 'success') throw new Exception( $result_content_xml[data]->Result, 107);
				}else{
					$query_update_task = "
						UPDATE	BC_TASK SET
							PROGRESS = ".$progress.",
							STATUS = '".$status."'
						WHERE	TASK_ID = '".$task_id."'
					";
					$db->exec($query_update_task);
				}
			break;
			case 'error':
				//$update_status = 4;
				$update_status = 9;
				$description = "실패";
			break;
			default:
				//$update_status = 4;
				$update_status = 9;
				$description = "실패";
			break;
		}
		if($task_id)
		 {
			$task_log_query = "
				INSERT INTO BC_TASK_LOG
				(TASK_ID, DESCRIPTION, CREATION_DATE, STATUS, PROGRESS)
				VALUES
				(".$task_id.", '".$description."', '".date('YmdHis')."', '".$status."', ".$progress.")
			";
			$insert_task_log = $db->exec($task_log_query);
		 }

		 if($content_id)
		 {
			 $query_update = "
				UPDATE	ARCHIVE_REQUEST
				SET		STATUS = '".$update_status."'
				WHERE	CONTENT_ID = ".$content_id."
			";
			$update_archive = $db->exec($query_update);
		 }

		return array(
			'code' => '0',
			'msg' => 'success'
		);

	} catch (Exception $e) {
		return array(
			'code' => '1',
			'msg' => $e->getMessage()
		);

	}
}
