<?php
die();
session_start();

$session_expire = $_POST['session_expire'];
$session_user_id = $_POST['session_user_id'];
$session_super_admin = $_POST['session_super_admin'];
$session_prevent_duplicate_login = $_POST['session_prevent_duplicate_login'];	// 중복로그인 허용여부 (Y:불가 , N: 허용)

$has_session = true;

if($session_expire > time()) {
	if(empty($_SESSION['user']) || strtolower($_SESSION['user']['user_id']) == 'temp') {
        require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
		// Recreate Session
		reCreateSession($session_user_id, $session_super_admin);
	}
} else {
	if (empty($_SESSION['user']) || strtolower($_SESSION['user']['user_id']) == 'temp' || $_SESSION['user']['session_expire'] < time()) {
		$has_session = false;
	
		if(!empty($_SESSION['user'])) {
			session_destroy();
		}
	}
}

/*
 * duplicate login check
 * 기존에는 중복로그인 허용여부를 session_check.php에서 확인하여 config.php가 계속 호출 되어
 * 세션이 종료되지 않는 현상이 발생하여 로그인시 중복로그인 허용여부를 세션정보에 포함하도록 소스 수정
 * 2018.01.12 Alex
 */
if($has_session){

	if( $session_prevent_duplicate_login == 'Y' ) {
		require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

		$check_duplication = true;

		$user_session = $db->queryOne("
							SELECT	CHECK_SESSION
							FROM	BC_MEMBER
							WHERE	USER_ID = '".$_SESSION['user']['user_id']."' and del_yn='N'
						");

		if(trim($user_session) == trim($_SESSION['user']['check_session'])) {
			$check_duplication = false;
		} else {
			$has_session = false;
			$check_duplication = true;
			if(!empty($_SESSION['user'])) {
				session_destroy();
			}
		}
	}
}

echo json_encode(array(
	'has_session' => $has_session,
	'check_duplication' => $check_duplication,
	'check_session' => $user_session,
	'user_id' => $_SESSION['user']['user_id'],
	'session_expire' => $_SESSION['user']['session_expire']
));
?>