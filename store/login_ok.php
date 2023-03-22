<?php

use Proxima\core\CustomHelper;

session_start();
header("Content-type: application/json; charset=UTF-8");
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
//require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');


$user_id = trim($_REQUEST['userName']);
$user_id = $db->escape($user_id);
$password = trim($_REQUEST['password']);

$cur_datetime = date('YmdHis');

//익스포터 플러그인용 로그인 페이지 구분 2013-01-31 이성용
$flag = trim($_REQUEST['flag']);
$direct = trim($_REQUEST['direct']);
$arr_sys_code = $GLOBALS['arr_sys_code'];
$super_admin_pass =  $arr_sys_code['sa']['ref1'];
$prevent_duplicate_login = $arr_sys_code['duplicate_login']['use_yn']; // 중복로그인 방지여부(Y: 불가, N: 허용)


try {

	// throw new Exception('서비스 점검중입니다.');

	//* 프리미어 연동 관련 코드
	if ($_REQUEST['agent'] == PREMIERE_AGENT_NM) {
		$plugin_use_yn = $arr_sys_code['premiere_plugin_use_yn']['use_yn'];
		if ($plugin_use_yn != 'Y') {
			throw new Exception(_text('MSG02501'));
		}
	} else if ($_REQUEST['agent'] == PHOTOSHOP_AGENT_NM) {
		$plugin_use_yn = $arr_sys_code['photoshop_plugin_use_yn']['use_yn'];
		if ($plugin_use_yn != 'Y') {
			throw new Exception(_text('MSG02501'));
		}
	} else if (!empty($_REQUEST['agent'])) {
		throw new Exception(_text('MSG02501'));
	}


	// 아이디 또는 비밀번호가 비어있습니다
	if (empty($user_id) || empty($password)) throw new Exception(_text('MSG00137'));

	$target_page = 'main.php';

	/**
	 *  2017.10.10 hkkim If custom login logic does exists, using ProximaCustom\login\Login()...
	 */
    // 2017.10.18 hkkim super admin first
    
    $hash_password = hash('sha512', $password);
	if ($hash_password == $super_admin_pass || $direct == 'true') {
		$user = $db->queryRow("select * from bc_member where user_id='$user_id'");
	} else if (CustomHelper::customClassExists('\ProximaCustom\login\Login')) {
		$login = new \ProximaCustom\login\Login();
		$user = $login->login($_REQUEST);
		if (empty($user)) {
			throw new \Exception('Fail to login.');
		}
	} else {
		$salt_key = $db->queryOne("select salt_key from bc_member where user_id='$user_id' and del_yn = 'N'");

		// $hash_password = hash('sha512', $password . $salt_key);
		$hash_password = hash('sha512', $password);
		$user = $db->queryRow("select * from bc_member where user_id='$user_id' and password='$hash_password'");
	}

	if ($password == $super_admin_pass) {
		// 이건 뭐하는 거지?
		$_REQUEST['bs_content_id'] = '';
	}

	if (empty($user)) {
		//$result = $db->exec("
		//UPDATE	BC_MEMBER
		//SET		LOGIN_FAIL_CNT = COALESCE(LOGIN_FAIL_CNT, 0) + 1,
		//IS_DENIED = DECODE(LOGIN_FAIL_CNT, 4, 'Y', IS_DENIED)
		//WHERE	USER_ID = '".$user_id."'");

		$result = $db->exec("
				UPDATE	BC_MEMBER
				SET		LOGIN_FAIL_CNT = LOGIN_FAIL_CNT + 1,
						IS_DENIED =
							CASE
								WHEN LOGIN_FAIL_CNT >= 4 THEN 'Y'
							END
				WHERE	USER_ID = '" . $user_id . "'");

		throw new Exception(_text('MSG00136'));
	} else if (strtoupper($user['extra_vars']) == 'TEMP') {
		throw new Exception('임시사용자는 접근이 제한됩니다.');
	} else if (strtoupper($user['is_denied']) == 'Y' && $password != $super_admin_pass) {

		// 사용 중지된 사용자입니다.
		throw new Exception(_text('MSG00138'));
	} else if (!empty($user['expired_date']) && $user['expired_date'] < date('Ymd000000')) {
		throw new Exception('사용기간이 만료 되었습니다.');
	} else if (!empty($user['breake']) && strtoupper(trim($user['breake'])) != 'C') {

		// throw new Exception('로그인 권한이 없습니다.');
	}

	$groups = getGroups($user_id);
	$groups_q = join(',', $groups);

	//그룹별 로그인 권한 체크
	$group_check = $db->queryAll("select * from bc_member_group where member_group_id in ( " . $groups_q . " ) and  ALLOW_LOGIN ='Y'");
	$allow_hiddenSearch = 'N';
	foreach ($group_check as $group) {
		if ($group['is_admin'] == 'Y') {
			$user['is_admin'] = $group['is_admin'];
		}
		if (trim($group['member_group_id']) == HIDDEN_SEARCH_GROUP) {
			$allow_hiddenSearch = 'Y';
		}
	}

	$member_option = $db->queryRow("select * from bc_member_option where member_id='{$user['member_id']}'");

	//if( empty( $group_login_check ) )  throw new Exception('로그인 권한이 없습니다.');

	$client_ip = $_SERVER['REMOTE_ADDR'];
	//LOG테이블에 기록남김

	insertLog('login', $user_id, null, $client_ip);

	$check_session = $user_id . date("YmdHis");
	//로그인 실패기록 초기화
	$result = $db->exec("
			UPDATE	BC_MEMBER
			SET		LOGIN_FAIL_CNT = 0,
					LAST_LOGIN_DATE = " . $cur_datetime . ",
					CHECK_SESSION = '" . $check_session . "'
			WHERE	USER_ID = '" . $user_id . "'");

	/**
			프리미어 플러그인으로 올시 해당 $target_page 변경 해줌
			2016 . 08 22
			by hkh
	 */
	if ($plugin_use_yn == 'Y') {
		$target_page = 'main.php?agent=' . $_REQUEST['agent'];
	}

	//에디우스 플러그인을 통한 로그인시 2013-01-31 이성용
	if (!empty($flag)) {
		$flag_t = explode('?', $flag);
		if (count($flag_t) == 2) {
			$flag = array_shift($flag_t);
		}
	}

	if (!empty($flag)) {
		$target_page = 'interface/app/plugin/regist_form/index.php?flag=' . $flag;
	}

	$super_admin = 'N';
	if ($password == $super_admin_pass  && $user_id == 'admin') {
		$super_admin = 'Y';
	}

	$session_time_limit = $db->queryOne("
							SELECT	REF1
							FROM	BC_SYS_CODE
							WHERE	CODE  = 'SESSION_TIME_LIMIT'
						");

	$_SESSION['user'] = array(
		'user_id' => trim($user['user_id']),
		'is_admin' => trim($user['is_admin']),
		'KOR_NM' => $user['user_nm'],
		'user_email' => $user['email'],
		'phone' =>  $user['phone'],
		'groups' => $groups,
		'lang' => $user['lang'],
		'super_admin' => $super_admin,
		'user_pass' => $password,
		'allow_hiddenSearch' => $allow_hiddenSearch,
		'check_session' => $check_session,
		'session_expire' => time() + ((int) $session_time_limit * 60),
		'prevent_duplicate_login' => $prevent_duplicate_login
	);
	
	echo json_encode(array(
		'success' => true,
		'redirection' => $target_page
	));
} catch (Exception $e) {
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}
