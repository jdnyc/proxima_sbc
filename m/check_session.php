<?php
$has_session = true;

if($session_expire > time()) {
	if(empty($_SESSION['user'])) {
		
		// Recreate Session
		reCreateSession($session_user_id, $session_super_admin);
	}
} else {
	if (empty($_SESSION['user']) || strtolower($_SESSION['user']['user_id']) == 'temp' || $_SESSION['user']['session_expire'] < time()) {
		$has_session = false;
	
		if(!empty($_SESSION['user'])) {
			session_destroy();
			echo '<script type="text/javascript">
			location.href="login_form.php";/**/
			</script>';
		}
	}
}

/*
 * duplicate login check
 */
if($has_session){
	$check_session_yn = $arr_sys_code['duplicate_login']['use_yn'];
	$user_session_expire = $_SESSION['user']['session_expire'];
	$check_duplication = true;

	if( $check_session_yn == 'Y' )
	{
		require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

		$_SESSION['user']['session_expire'] = $user_session_expire;

		$user_session = $db->queryOne("
			SELECT	CHECK_SESSION
			FROM		BC_MEMBER
			WHERE	USER_ID = '".$_SESSION['user']['user_id']."'
		");

		if(trim($user_session) == trim($_SESSION['user']['check_session']))
		{
			$check_duplication = false;
		}
		else
		{
			$has_session = false;
			$check_duplication = true;
			if(!empty($_SESSION['user'])) {
				session_destroy();
				echo '<script type="text/javascript">
				location.href="login_form.php";/**/
				</script>';
			}
		}
	}
}
?>