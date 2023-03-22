<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');


$year = $_POST['year'];
$year = substr($year, 0, 4);
if(empty($year)){
	$year = date('Y');
}
$user_id = $_POST['user_id'];
if(empty($user_id)){
	$user_id =$_SESSION['user']['user_id'];
}

/*
# 연도별 로그인 횟수
$query = $mdb->queryOne("select count(id) from log where action='login' and created_time between ".$year."0100000000 and  ".$year."1231240000");
*/
//월별 로그인횟수
$login_month = array();
for($i = 1; $i < 13; $i++){
	$month = str_pad($i, 2, "0", STR_PAD_LEFT);
	$query = "select count(id) from log where action='login' and user_id = '$user_id' and created_time between ".$year.$month."00000000 and ".$year.$month."31240000";
	$each_month = $mdb->queryOne($query);
	array_push($login_month, array('month'=>$month.'월' , 'value'=>$each_month));
}
$user_name = $mdb->queryOne("select name from member where user_id = '$user_id'");
?>

{
	title: '<?=$year."년 ".$user_name." 님의 "?>월간 로그인 통계',
	layout: 'fit',
	border: false,

	items: {
		xtype: 'linechart',
		store: new Ext.data.JsonStore({
			fields:['month', 'visits'],
			data: [
			<?php
			foreach($login_month as $month){
			?>
				{month:'<?=$month['month']?>', visits: <?=$month['value']?>},
			<?php
			}
			?>
			]
		}),
		xField: 'month',
		yField: 'visits'
	}
}