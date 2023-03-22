<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');


$year = date('Y');

$user_id = $_POST['user_id'];
if(empty($user_id)){
	$user_id =$_SESSION['user']['user_id'];
}

$select_user= $mdb->queryAll("select user_id, name from member");

$login_month = array(
	'success' => true,
	'login_return' => array()
);

for($i = 1; $i < 13; $i++){
	$month = str_pad($i, 2, "0", STR_PAD_LEFT);
	$query = "select count(id) from log where action='login' and user_id = '$user_id' and created_time between ".$year.$month."00000000 and ".$year.$month."31240000";
	$each_month = $mdb->queryOne($query);
	array_push($login_month['login_return'], array('month'=>$month.'월' , 'value'=>$each_month));
}

echo json_encode(
	$login_month
);
?>

