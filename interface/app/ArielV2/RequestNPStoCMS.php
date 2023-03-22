<?php

function RequestNPStoCMS($request)
{
	global $db;

	try{
		$Interface = new InterfaceClass();
		InterfaceClass::_LogFile($filename,'RequestNPStoCMS request',$request);
		$return;
		$response_json = array();
		$response_xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<Response />");

		$ReqRender			= InterfaceClass::checkSyntax($request);
		$type				= $ReqRender['type'];
		$render_data		= $ReqRender['data'];

		//리턴
		$response = $Interface->DefualtResponse($type);

		if( $type == 'JSON' ){
			$nps_content_id = $render_data[nps_content_id];
        } else if ( $type == 'XML' ) {
			//  $action 		= $render_data->action;
		} else {
			throw new Exception ('invalid request', 101 );
		}

		$channel = 'NPS_to_CMS_new';
		$task_user_id = 'admin';
		$task = new TaskManager($db);
		$task_id = $task->start_task_workflow($nps_content_id, $channel, $task_user_id);

		if($type == 'JSON'){
			$response['success'] = $success;
			$response['message'] = $msg;
		}else{
			$response->addChild('success', $success);
			$response->addChild('message', $msg);
		}

		$return = $Interface->ReturnResponse($type,$response);
		InterfaceClass::_LogFile($filename,'RequestNPStoCMS return',$return);
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