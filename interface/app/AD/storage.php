<?php

function storage($request)
{
	global $db;

	try{
		$Interface = new InterfaceClass();
		InterfaceClass::_LogFile($filename,'request',$request);


		$AD = new ActiveDirectory();

		//변환
		$ReqRender		=  InterfaceClass::checkSyntax($request);
		$type			= $ReqRender['type'];
		$render_data	= $ReqRender['data'];

		//리턴
		$response = $Interface->DefualtResponse($type);

		if( $type == 'JSON' ){
			$request_type			= $render_data[request_type];
			$category_id			= $render_data[category_id];
			$path					= $render_data[path];
			$requestjson			= $render_data[requestjson];
		}else if( $type == 'XML' ){
			$requestjson	= json_decode($render_data->requestjson,true);
		}else{
			throw new Exception ('invalid request', 101 );
		}

		if($request_type == 'list'){
			$lists = $AD->getList($category_id , $path);

			if($type == 'JSON'){
				$response['data'] = $lists;
			}else{
				$items_xml = $response->addChild('data');
				foreach($lists as $key => $val ){
					$items_xml->addChild($key, $val );
				}
			}
		}else if($request_type == 'update'){

			if( empty($requestjson) ) throw new Exception ('invalid request', 101 );

			foreach($requestjson as $list)
			{
				$category_id	= $list[category_id];
				$path			= $list[path];
				$usage	= $list[usage];
				if( is_null($usage) ) throw new Exception("empty usage",106);
				if( empty($category_id) && empty($path) ) throw new Exception("empty id",106);

				$r = $AD->update($category_id , $path, $usage);
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