<?php

function updateStatusSNS($request)
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
		}else{
			throw new Exception ('invalid request', 101 );
		}

		//Do update

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