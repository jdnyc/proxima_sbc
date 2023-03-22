<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

/*
2010-12-08 주석처리 by CONOZ
$user_group = array(
	'success' => true,
	'total' => $total,
	'user_group' => array()
);

$d= $mdb->queryAll("select * from member_group order by member_group_id desc");
foreach($d as $v){
//	echo $v."<br />";
	$users = $mdb->queryAll("select mg.member_id from member_group_member mg, member m where mg.member_group_id = {$v['member_group_id']} and mg.member_id = m.member_id group by mg.member_id");
	foreach($users as $user){
		$group_user = $mdb->queryRow("select * from member where member_id = {$user['member_id']} order by created_time asc");
		array_push($user_group['user_group'], array('group_name'=>$v['name'], 'user_id'=>$group_user['user_id'], 'user_name'=>$group_user['name'] ,'date'=>$group_user['created_time'] ));
	}
}
echo json_encode(
	$user_group
);

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
				g.member_group_name as group_name, mg.member_group_id, mg.member_id, m.user_id, m.user_nm as user_name, m.created_date 
			from 
				bc_member_group g, bc_member_group_member mg, bc_member m 
			where 
				g.member_group_id=mg.member_group_id  and mg.member_id = m.member_id"; // and mg.member_group_id = '84018'
$rows = $mdb->queryAll($query);

$totalquery = "select count(*) from bc_member_group g, bc_member_group_member mg, bc_member m where g.member_group_id=mg.member_group_id and mg.member_id = m.member_id"; //  and mg.member_group_id = '84018'
$total = $mdb->queryOne($totalquery);

$user_group = array(
	'success' => true,
	'total' => $total,
	'user_group' => array()
);

foreach($rows as $user){
	array_push($user_group['user_group'], array('group_name'=>$user['group_name'], 'user_id'=>$user['user_id'], 'user_name'=>$user['user_name'] ,'date'=>$user['created_date'] ));
}
// 2010-12-08 [END] 네비게이션 by CONOZ

//print_r($user_group);
echo json_encode(
	$user_group
);




//1 2 61915 61916
//그룹이름 / 유저id / 유저 이름/ 가입일

?>
