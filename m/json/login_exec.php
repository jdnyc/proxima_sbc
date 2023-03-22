<?php
session_start();

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');
require_once($_SERVER['DOCUMENT_ROOT']."/lib/functions.php");

use \Proxima\core\Request;

$user_id = strtolower(Request::post('userName'));
$password = Request::post('password');

$arr_sys_code = $GLOBALS['arr_sys_code'];
$super_admin_pass =  $arr_sys_code['sa']['ref1'];
$cur_datetime = date('YmdHis');

//자동 로그인 쿠키 삭제
if($_REQUEST[idSaveCheck]){
	setcookie("userName",$user_id,time()+(86400*365),"/");
}else{
	setcookie("userName",'',time()+(86400*365),"/");
}

function aram_msg($msg) {
	$msg = str_replace('"','\"',$msg);

	echo("
			<script>
			alert(\"$msg\");
			</script>
		");
}

if(empty($user_id) || empty($password)){
	aram_msg(_text('MSG00137'));
	exit;
}

if ($password == $super_admin_pass || $direct == 'true') {
	$user = $mdb->queryRow("select * from bc_member where user_id='$user_id'");
} else if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\login\Login')) {
	$login = new \ProximaCustom\login\Login();
	$user_tmp = $login->login($user_id, $password);
	if ($user_tmp->isDefaultUser()) {

		$user = $mdb->queryRow("select * from bc_member where user_id='$user_id'");
		if($user == null)
		{
			$super_admin = 'N';
			if( $password == $super_admin_pass  && $user_id == 'admin' ){
				$super_admin = 'Y';
			}

			$check_session = $user_id.date("YmdHis");
			//로그인 실패기록 초기화
			$result = $mdb->exec("
					UPDATE	BC_MEMBER
					SET		LOGIN_FAIL_CNT = 0,
							LAST_LOGIN_DATE = ".$cur_datetime.",
							CHECK_SESSION = '".$check_session."'
					WHERE	USER_ID = '".$user_id."'");	

			$session_time_limit = $db->queryOne("
				SELECT	REF1
				FROM	BC_SYS_CODE
				WHERE	CODE  = 'SESSION_TIME_LIMIT'");

			$_SESSION['user'] = array(
				'user_id' => trim($user_tmp->id),
				'is_admin' => $user_tmp->isAdmin ? 'Y' : 'N',
				'KOR_NM' => $user_tmp->name,
				'user_email' => '',
				'phone' =>  '',
				'groups' => $user_tmp->groupIds,
				'lang' => 'ko',
				'super_admin' => $super_admin,
				'user_pass' => $password,
				'check_session' => $check_session,
				'session_expire' => time() + ((int)$session_time_limit * 60)
			);
			exit;
		}
		
	}
	
} else {
	$user = $mdb->queryRow("select * from bc_member where user_id='$user_id'");
}

//아이디 체크
if(empty($user)){
	aram_msg(_text('MSG00136'));
	exit;
}

// if(password_verify($password, $user['password'])) {
// 	$passchk = false;
// } else {
// 	aram_msg(_text('MSG00136'));
// 	exit;
// }

//사용가능 여부
if(strtoupper($user['is_denied']) == 'Y'){
	aram_msg(_text('MSG00138'));
	exit;
}

//사용기간 체크
if(!empty($user['expired_date']) && $user['expired_date'] < date('Ymd000000')){
	aram_msg('사용기간이 만료 되었습니다.');
	exit;
}

$cur_datetime = date('YmdHis');
	
$result = $mdb->exec("insert into bc_log (action, user_id, created_date) values ('login', '$user_id', '$cur_datetime')");


// $check_home = setup_home($user_id);

//그룹 배열 함수 추가 2012-07-25 by 이성용
// $groups = getGroups($user_id);
$_groups = $db->queryAll("select member_group_id from bc_member_group_member where member_id=".$user['member_id']);
if ( $_groups )
{
	foreach ($_groups as $_group)
	{
		$groups[] = $_group['member_group_id'];
	}
}
else
{
	$groups[] = '84018';
}

$is_admin = 'N';
$allow_hiddenSearch = 'N';
foreach($groups as $group) {
	//echo $group;
	$group_info = $db->queryRow("select * from bc_member_group where member_group_id='$group'");

	$group_admin = $group_info['is_admin'];

	if( trim($group_admin) == 'Y' ) {
		$is_admin = 'Y';
	}

	if(trim($group_info['member_group_id']) == HIDDEN_SEARCH_GROUP ) {
		$allow_hiddenSearch = 'Y';
	}
}

if( trim($user['is_admin']) == 'Y' ) {
	$is_admin = 'Y';
}


$super_admin = 'N';
if( $password == $super_admin_pass  && $user_id == 'admin' ){
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
	'session_expire' => time() + ((int)$session_time_limit * 60)
);

/*
$_SESSION['user'] = array(
	'user_id'	=>	$user['user_id'],// 2010-12-17 관리자 여부 세션에 추가 by CONOZ
	'is_admin'	=>	$is_admin,
	'KOR_NM'	=>	$user['user_nm'],
	'lang'		=>	$user['lang'],
	'groups'	=>	$groups,
	'start'		=>	$start,
	'expire'	=>	$start+(24*60*60)
);
*/

$result = $mdb->exec("update bc_member set last_login_date='$cur_datetime' where user_id='$user_id'");
if($passchk){
	//aram_msg("비밀번호가 설정되어 있지 않습니다. 비밀번호를 변경하여주세요. 마이페이지로 이동합니다.");
	// /mypage/index.php 으로 이동
	//exit;
}

?>
<script type="text/javascript">
// parent.location.href="../page_intro.php";
//parent.location.href="../mov_list.php?meta_table_id=<?=UD_SOURCE?>"; // proxima vna
//parent.location.href="../mov_list.php?meta_table_id=<?=4000289?>"; // proxima zodiac
parent.location.href="../main.php";
</script>



