<?php

function request($request)
{
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

			$a = trim($render_data['a']);
			$b = trim($render_data['b']);

		}else if( $type == 'XML' ){

			$a = trim($render_data->a);
			$b = trim($render_data->b);

		}else{
			throw new Exception ('invalid request', 101 );
		}

		if( empty($a) || empty($b) ){
			throw new Exception ('invalid request', 101 );
		}

		/////////////////////////////////////////////////////
		$info = '';

		if($type == 'JSON'){
			$response['data'] = $info;
		}else{
			$items_xml = $response->addChild('data');
			foreach($info as $key => $val ){
				$items_xml->addChild($key, $val );
			}
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
?>