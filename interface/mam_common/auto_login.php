<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
//print_r($_POST);

$user_id = $_POST['id'];
$user = $mdb->queryRow("select * from bc_member where user_id='$user_id'");
$groups = getGroups($user_id);

if($_POST)
{
	//LOG테이블에 기록남김
	$result = $mdb->exec("insert into bc_log (action, user_id, created_date) values ('login', '$user_id', '".date('YmdHis')."')");

	$_SESSION['user'] = array(
		'user_id' => trim($user['user_id']),
		'is_admin' => trim($user['is_admin']),
		'KOR_NM' => $user['user_nm'],
		'user_email' => $user['email'],
		'groups' => $groups
	);
//print_r($_SESSION);
	echo "<script type=\"text/javascript\">
		location.href='/main.php';
	</script>";
}else{

	echo 'error';
}
?>
<html>

</html>