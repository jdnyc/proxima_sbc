<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

try {
	
	$limit = $_POST['limit'];
	$start = $_POST['start'];
	if(empty($limit)){
		$limit = 30;
	}
	
        // 입력받은 group_id로 해당 그룹에 속해있는 member_id를 구해옴 2014,06,20 임찬모
        $group_id = $_POST['member_group_id'];
        $query = "select bm.member_id, bm.user_id, bm.user_nm, bm.dept_nm 
                from bc_member_group_member bmgm, bc_member bm 
                where bmgm.member_group_id = '$group_id' and bmgm.member_id = bm.member_id";
        
        $members = $db->queryAll($query);

	echo json_encode(array(
		'success' => true,
		'total' => count($members),
		'data' => $members
	));
}
catch(Exception $e){
	$msg = $e->getMessage();

	switch($e->getCode()){
		case ERROR_QUERY:
			$msg = $msg.'( '.$db->last_query.' )';
		break;
	}

	die(json_encode(array(
		'success' => false,
		'msg' => $msg
	)));
}
?>