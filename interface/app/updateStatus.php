<?php

function updateStatus($request)
{
	global $db;

	try{
		$Interface = new InterfaceClass();
		InterfaceClass::_LogFile('','request',$request);

		//변환
		$ReqRender		=  InterfaceClass::checkSyntax($request);
		$type			= $ReqRender['type'];
		$render_data	= $ReqRender['data'];

		//리턴
		$response = $Interface->DefualtResponse($type);

		if( $type == 'XML' ){
			$render_data_encode = json_encode($render_data);
			$render_data = json_decode($render_data_encode, true);
		}

		if( $type == 'JSON' || $type == 'XML' ){
			$action		= $render_data['action'];
			$task_id	= $render_data['task_id'];
			$status		= $render_data['status'];
			$progress	= empty($render_data['progress']) ? 0 : $render_data['progress'];
			$ip			= $render_data['ip'];
			$log		= $render_data['log'];

			$filesize		= $render_data['filesize'];
			$filename		= $render_data['filename'];
			$filename		= str_replace("\\\\","\\",$filename);
			$filename		= str_replace("\\","/",$filename);
			$filename		= pathinfo($filename, PATHINFO_BASENAME);
			InterfaceClass::_LogFile('','filename',print_r($filename,true));
			InterfaceClass::_LogFile('','request',print_r($render_data,true));
		}else{
			throw new Exception ('invalid request', 101 );
		}

		if( empty($action) || empty($task_id) ){
			throw new Exception ('invalid request', 101 );
		}

		$task_info  = $db->queryRow("select t.media_id, t.task_id , t.assign_ip ,  t.type  from bc_task t where  t.task_id=$task_id");

		if( empty($task_info) )  throw new Exception('not found TaskInfo', 106);

		$task_type = $task_info['type'];

		$task = new TaskManager($db);

		switch ($action)
		{
			case 'update':

				if( empty($status) ) throw new Exception ('invalid request', 101 );

				$request = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Request />');
				$request->addChild("TaskID", $task_id );
				$request->addChild("TypeCode", $task_type );
				$request->addChild("Progress", $progress );
				$request->addChild("Status", $status );
				$request->addChild("Ip", $ip);
				$request->addChild("Log", $log);
				$sendxml =  $request->asXML();

				$mediaInfo = $db->queryRow("select * from bc_media where media_id= {$task_info['media_id']}");
				if( empty($mediaInfo) ) throw new Exception ('not found mediaInfo', 106 );

				if( !empty($filesize) ){
					$task->update_filesize($mediaInfo['media_id'] , $filesize);
				}

				if( !empty($filename) ){
					$task->update_filename($mediaInfo['media_id'] , $filename);
				}

				$task = new TaskManager($db);
				$result = $task->Post_XML_Soket($_SERVER['HTTP_HOST'], '/workflow/update_task_status.php', $sendxml );
				$result_content = substr( $result , strpos( $result, '<'));
				$result_content_xml = InterfaceClass::checkSyntax($result_content);

				if($result_content_xml[data]->Result != 'success') throw new Exception( $result_content_xml[data]->Result, 107);

			break;

			case 'rename':

				if( empty($filename) ) throw new Exception ('invalid request', 101 );

				$target_array = explode('/',$task_info['target']);
				array_pop($target_array);
				array_push($target_array, $filename);
				$new_target = $db->escape(join('/', $target_array));

				$r = $db->exec("update bc_task set target='$new_target' where task_id=$task_id");

				$mediaInfo = $db->queryRow("select * from bc_media where media_id= {$task_info['media_id']}");
				if( empty($mediaInfo) ) throw new Exception ('not found mediaInfo', 106 );

				$path_array = explode('/',$mediaInfo['path']);
				array_pop($path_array);
				array_push($path_array, $filename);
				$new_path = $db->escape(join('/', $path_array));

				$r = $db->exec("update bc_media set path='$new_path' where media_id={$task_info['media_id']}");

				//$task->update_filesize($media_id , $size);
				//$task->update_filename($media_id , $size);

			break;

			default:
				 throw new Exception('unknown action', 106);
			break;
		}

		if($type == 'JSON'){
			$response['data'] = $result_content_xml;
		}else{
			$items_xml = $response->addChild('data');
			foreach($userInfo as $key => $val ){
				$items_xml->addChild($key, $val );
			}
		}

		$return = $Interface->ReturnResponse($type,$response);
		InterfaceClass::_LogFile('','return',$return);
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