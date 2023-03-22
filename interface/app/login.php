<?php

function login($request)
{
	global $db, $arr_sys_code;

	try {
		$Interface = new InterfaceClass();
		InterfaceClass::_LogFile($filename, 'request', $request);

		//변환
		$ReqRender		=  InterfaceClass::checkSyntax($request);
		$type			= $ReqRender['type'];
		$render_data	= $ReqRender['data'];

		//리턴
		$response = $Interface->DefualtResponse($type);

		if ($type == 'JSON') {

			$user_id = trim($render_data['userid']);
			$password = trim($render_data['password']);
		} else if ($type == 'XML') {

			$user_id = trim($render_data->userid);
			$password = trim($render_data->password);
		} else {
			throw new Exception('invalid request', 101);
		}

		if (empty($user_id) || empty($password)) {
			throw new Exception(_text('MSG00137'), 101);
		}

		$super_admin_pass = $arr_sys_code['sa']['ref1'];
		//$sha512_password = hash('sha512', $password);

		$salt_key = $db->queryOne("select salt_key from bc_member where user_id='$user_id' and del_yn = 'N'");
		// $hash_password = hash('sha512', $sha512_password.$salt_key);
		$hash_password = hash('sha512', $password);

		if ($hash_password == $super_admin_pass) {
			$user = $db->queryRow("select * from bc_member where user_id='$user_id'");
		} else {
			$user = $db->queryRow("select * from bc_member where user_id='$user_id' and password='$hash_password' and del_yn = 'N'");
			//$user = $db->queryRow("select * from bc_member where user_id='$user_id' and password='$password'");
		}

		if ($password != $super_admin_pass && defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\HMLogin')) {
			$user = \ProximaCustom\core\HMLogin::loginHM($user_id, $password, 'iv');
			$user_id = $user['user_id']; //AD계정으로 로그인 했을경우 iValue계정과 다르므로 iValue계정으로 한번 더 맞춰줌.
		}

		if (empty($user)) throw new Exception(_text('MSG00136'));

		if ($type == 'JSON') {
			$response['data'] = $user;
		} else {
			$items_xml = $response->addChild('data');
			foreach ($user as $key => $val) {
				$items_xml->addChild($key, $val);
			}
		}

		$return = $Interface->ReturnResponse($type, $response);
		InterfaceClass::_LogFile($filename, 'return', $return);
		return $return;
	} catch (Exception $e) {

		$msg = $e->getMessage();
		$code = $e->getCode();
		$success = 'false';

		if ($type == 'JSON') {
			$response['success'] = $success;
			$response['message'] = $msg;
			$response['status'] = $code;
		} else {
			$response->success = $success;
			$response->message = $msg;
			$response->status = $code;
		}
		$return = $Interface->ReturnResponse($type, $response);

		InterfaceClass::_LogFile($filename, 'return', $return);
		return $return;
	}
}
