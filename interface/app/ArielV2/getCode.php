<?php

function getCode($request)
{
	global $db;

	try{
		$Interface = new InterfaceClass();
		InterfaceClass::_LogFile($filename,'request',$request);
		$return;
		$response_json = array();
		$response_xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<Response />");

		$ReqRender		=  InterfaceClass::checkSyntax($request);
		$type			= $ReqRender['type'];
		$render_data	= $ReqRender['data'];

		//리턴
		$response = $Interface->DefualtResponse($type);

		if( $type == 'JSON' ){

			$request_type		 = $render_data[type];
			$code		 = $render_data[code];

		}else if( $type == 'XML' ){

			throw new Exception ('invalid request', 101 );

		}else{
			throw new Exception ('invalid request', 101 );
		}

		if($request_type == 'metadata'){
			$code = strtoupper($code);
			$datas = $db->queryAll("select usr_code_key key,usr_code_value val from BC_USR_META_CODE where USR_META_FIELD_CODE='$code' order by show_order");
			if(empty($datas)){
				$datas = array();
			}
		}



		if($type == 'JSON'){
			$response['data'] = $datas;
			$response['code'] = $code;
		}else{
			$response->addChild('data', $datas);
			$response->addChild('code', $code);
		}

		$return = $Interface->ReturnResponse($type,$response);
		InterfaceClass::_LogFile($filename,'return',$return);
		return $return ;

	}
	catch(Exception $e){

		$msg = $e->getMessage();
		$code = $e->getCode();
		$success = 'false';
		if(empty($code)){
			$code = '200';
		}
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