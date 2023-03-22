<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$s_date = $_POST['start_date'];
$e_date = $_POST['end_date'];
$today = date('Ymd');
$one_month = mktime (0,0,0,date("m")-1, date("d"), date("Y"));
$one_month = date('YmdHis', $one_month);

/* 2010-12-08 주석처리 by CONOZ
$user_group = array(
	'success' => true,
	'user_off' => array()
);
*/

/*
if(empty($s_date)){
	$d= $mdb->queryAll("select * from member where last_login < $one_month order by last_login asc");
	foreach($d as $v){
		$group_name = $mdb->queryAll("select member_group_id from member_group_member where member_id = {$v['member_id']} order by member_group_id desc");
		foreach($group_name as $group){
			$get_name = $mdb->queryOne("select name from member_group where member_group_id = {$group['member_group_id']} ");
			array_push($user_group['user_off'], array('group_name'=>$get_name, 'user_id'=>$v['user_id'], 'user_name'=>$v['name'] ,'date'=>$v['created_time'], 'last_login'=>$v['last_login']));
		}
	}
}else{ */
	/* 2010-12-08 주석처리 by CONOZ

	$d= $mdb->queryAll(" select * 
									from member 
									where user_id not in (select user_id from log 
																	where action='login' 
																	and created_time between $s_date and $e_date group by user_id) order by last_login asc");
	foreach($d as $v){
		$group_name = $mdb->queryAll("select mgm.member_group_id 
														from member_group_member mgm, member			m, member_group mg 
														where mgm.member_id ={$v['member_id']} 
														and mgm.member_id = m.member_id 
														and mg.member_group_id =								mgm.member_group_id 
														order by member_group_id desc");
		foreach($group_name as $group){
			$get_name = $mdb->queryOne("select name 
														from member_group 
														where member_group_id = {$group['member_group_id']} ");
			array_push($user_group['user_off'], array('group_name'=>$get_name, 'user_id'=>$v['user_id'], 'user_name'=>$v['name'] ,'date'=>$v['created_time'], 'last_login'=>$v['last_login']));
		}
	}
	*/
// 2010-12-08 [START] 네비게이션 by CONOZ
//print_r($_POST);
if($s_date && $s_date){
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
					g.member_group_id=mg.member_group_id and mg.member_id = m.member_id and m.user_id not in (select user_id from bc_log where action='login' and created_date between {$s_date} and {$e_date} group by user_id) order by last_login_date asc";

	$rows = $mdb->queryAll($query);
	$totalquery = "select count(*) from bc_member_group g, bc_member_group_member mg, bc_member m where g.member_group_id=mg.member_group_id and mg.member_id = m.member_id and m.user_id not in ( select user_id from bc_log where action='login' and created_date between {$s_date} and {$e_date} group by user_id
	)";
	$total = $mdb->queryOne($totalquery);

	$user_group = array(
		'success' => true,
		'total' => $total,
		'user_off' => array()
	);

	foreach($rows as $user){
		//$group_user = $mdb->queryRow("select * from member where member_id = {$user['member_id']} order by created_time asc");
		array_push($user_group['user_off'], array('group_name'=>$user['group_name'], 'user_id'=>$user['user_id'], 'user_name'=>$user['user_name'] ,'date'=>$user['created_date'], 'last_login'=>$user['last_login_date'] ));	
	}
	// 2010-12-08 [END] 네비게이션 by CONOZ
}else{
	$user_group = array(
		'success' => false,
		'total' => $total,
		'user_off' => array()
	);
}


//}
//print_r($user_group);
echo json_encode(
	$user_group
);



//그룹이름 / 유저id / 유저 이름/ 가입일/ 최종 로그인

?>