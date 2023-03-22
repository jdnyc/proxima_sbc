<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$s_date = $_POST['start_date'];
$e_date = $_POST['end_date'];
/*
2010-12-08 주석처리 by CONOZ
$user_join = array(
	'success' => true,
	'user_join' => array()
);

$d= $mdb->queryAll("select * from member where created_time between ".$s_date." and ".$e_date." order by created_time asc");
foreach($d as $v){
//	echo $v."<br />";
	$group_name = $mdb->queryAll("select member_group_id from member_group_member where member_id = {$v['member_id']} order by member_group_id desc");
	foreach($group_name as $group){
		$get_name = $mdb->queryOne("select name from member_group where member_group_id = {$group['member_group_id']} ");
		array_push($user_join['user_join'], array('group_name'=>$get_name, 'user_id'=>$v['user_id'], 'user_name'=>$v['name'] ,'date'=>$v['created_time'], 'last_login'=>$v['last_login']));
	}

}
*/
// 2010-12-08 [START] 네비게이션 by CONOZ
$limit = $_POST['limit'];
$start = $_POST['start'];
if(empty($limit)){
	$limit = 50;
}
if(empty($start)){
	$start = 0;
}
$mdb->setLimit($limit,$start);
$query = "select 
				g.member_group_name as group_name, mg.member_group_id, mg.member_id, m.user_id, m.user_nm as user_name, m.created_date, m.last_login_date 
			from 
				bc_member_group g, bc_member_group_member mg, bc_member m 
			where 
				g.member_group_id=mg.member_group_id  and mg.member_id = m.member_id and m.created_date between ".$s_date." and ".$e_date." order by m.created_date asc";

$rows = $mdb->queryAll($query);

$totalquery = "select count(*) from bc_member_group g, bc_member_group_member mg, bc_member m where g.member_group_id=mg.member_group_id and mg.member_id = m.member_id and m.created_date between ".$s_date." and ".$e_date." order by m.created_date asc";
$total = $mdb->queryOne($totalquery);

$user_join = array(
	'success' => true,
	'total' => $total,
	'user_join' => array()
);

foreach($rows as $user){
	//$group_user = $mdb->queryRow("select * from member where member_id = {$user['member_id']} order by created_time asc");
	array_push($user_join['user_join'], array('group_name'=>$user['group_name'], 'user_id'=>$user['user_id'], 'user_name'=>$user['user_name'] ,'date'=>$user['created_date'], 'last_login'=>$user['last_login_date'] ));

}
// 2010-12-08 [END] 네비게이션 by CONOZ

//print_r($user_group);
echo json_encode(
	$user_join
);


//그룹이름 / 유저id / 유저 이름/ 가입일

?>