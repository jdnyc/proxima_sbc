<?php

$server->register('getPathThumb',
	array(
		'request' => 'xsd:string'
	),
	array(
		'response' => 'xsd:string'
	),
	$namespace,
	$namespace.'#getPathThumb',
	'rpc',
	'encoded',
	'getPathThumb'
);

function getPathThumb($request) {
	global $db;

	try{
		$Interface = new InterfaceClass();
		InterfaceClass::_LogFile($filename,'request',$request);

		//변환
		$ReqRender		=  InterfaceClass::checkSyntax($request);
		$type			= $ReqRender['type'];
		$render_data	= $ReqRender['data'];

		//리턴
		$response = $Interface->DefualtResponse($type);

		if( $type == 'JSON' ){

			$content_id = $render_data['content_id'];

		}else if( $type == 'XML' ){

			$content_id = $render_data->content_id;

		}else{
			throw new Exception ('invalid request', 102 );
		}


		$query = "
			SELECT  *
			FROM    BC_MEDIA
			WHERE   CONTENT_ID = ".$content_id."
			and     media_type = 'thumb'
		";
		$content_info = $db->queryRow($query);

		$server_pre = "http://".SERVER_IP."/data/".$content_info['path'];

		if($type == 'JSON'){
			$response['data'] = $server_pre;
		}else{
			$response->addChild('data', $server_pre);
		}

		$return = $Interface->ReturnResponse($type,$response);
		InterfaceClass::_LogFile($filename,'return',$return);
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
