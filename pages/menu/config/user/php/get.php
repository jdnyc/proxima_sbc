<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
fn_checkAuthPermission($_SESSION);
try {
	/* 2010-12-17 기존 주석처리
	if(in_array(ADMIN_GROUP, $_SESSION['user']['groups']) || $_SESSION['user']['is_admin'] == 'Y'){
		$query = "select * from member order by {$_POST['sort']} {$_POST['dir']}";
	}else{
		$query = "select * from member where user_id='{$_SESSION['user']['user_id']}' order by {$_POST['sort']} {$_POST['dir']}";
	}
	$rows = $db->queryAll($query);

	for($i= 0 ; $i<count($rows); $i++){
		$groups = $db->queryAll("select g.name from member_group g, member_group_member gm where gm.member_id={$rows[$i]['member_id']} and gm.member_group_id=g.member_group_id");

		if($groups){
			$rows[$i]['group'] = array();
			foreach($groups as $group){
				array_push($rows[$i]['group'], $group['name']);
			}
			$rows[$i]['group'] = implode(', ', $rows[$i]['group']);
		}
	}

	echo json_encode(array(
		'success' => true,
		'total' => count($rows),
		'data' => $rows
	));
	*/

	// 2010-12-01 사용자 네비게이션 기능 추가 by CONOZ
	$limit = $_POST['limit'];
	$start = $_POST['start'];
	if(empty($limit)){
		$limit = 30;
	}
	// 2010-12-16 사용자 검색 기능 추가 by CONOZ
	if(trim($_POST['search_field']) != '' && trim($_POST['search_value']) != ''){
		$search_field=trim($_POST['search_field']);
		$search_value=trim($_POST['search_value']);
		if($search_field == 's_created_time'){
			$created_time_tmp=str_replace("-","",$search_value);
			$addWhere=" where substr(created_date, 1,8) = '{$created_time_tmp}' and del_yn = 'N'";
		}elseif($search_field == 's_job_position'){
			$addWhere=" where job_position like '%{$search_value}%'  and del_yn = 'N'";
		}elseif(in_array($search_field, array('s_name', 'user_nm'))){
			$addWhere=" where user_nm like '%{$search_value}%'  and del_yn = 'N'";
		}elseif($search_field == 's_user_dept' || $search_field == 's_dept_nm'){
			$addWhere=" where dept_nm like '%{$search_value}%'  and del_yn = 'N'";
		}elseif($search_field == 's_user_id'){
			$addWhere=" where lower(user_id) like '%{$search_value}%'  and del_yn = 'N'";
		}
	}else{
		$addWhere="where del_yn = 'N'";
	}
	$db->setLimit($limit,$start);

	if($search_field == 's_group' ){
		if(true){
			$query = "	
							SELECT m.* ,b.top_menu_mode, b.action_icon_slide_yn 
							FROM bc_member m
							LEFT OUTER JOIN bc_member_option b ON m.member_id = b.member_id
							LEFT OUTER JOIN bc_member_group_member gm  ON m.member_id = gm.member_id
							WHERE gm.member_group_id='{$search_value}'
							AND m.del_yn = 'N' 
							ORDER BY {$_POST['sort']} {$_POST['dir']},user_id ASC
							";
		}else{
			if($addWhere){
				$addWhere.="and user_id='{$_SESSION['user']['user_id']}'";
			}else{
				$addWhere="where user_id='{$_SESSION['user']['user_id']}'";
			}
			$query = "
							SELECT m.* ,b.top_menu_mode, b.action_icon_slide_yn 
							FROM bc_member m
							LEFT OUTER JOIN bc_member_option b ON m.member_id = b.member_id
							LEFT OUTER JOIN bc_member_group_member gm  ON m.member_id = gm.member_id
							WHERE gm.member_group_id='{$search_value}' 
							AND m.del_yn = 'N'
							ORDER BY {$_POST['sort']} {$_POST['dir']},user_id ASC
						";
		}
		$totalquery = "select count(*) from bc_member m,bc_member_group_member gm where  m.member_id=gm.member_id and gm.member_group_id='{$search_value}' and m.del_yn = 'N'";
		$total = $db->queryOne($totalquery);
		$rows = $db->queryAll($query);
	}
	else{
		if(true){
			//SELECT A.*, B.TOP_MENU_MODE, B.ACTION_ICON_SLIDE_YN  FROM bc_member A LEFT OUTER JOIN BC_MEMBER_OPTION B ON A.MEMBER_ID = B.MEMBER_ID ORDER BY A.user_nm ASC, A.user_id asc

			$query = "SELECT a.*, b.top_menu_mode, b.action_icon_slide_yn from bc_member a LEFT OUTER JOIN bc_member_option b ON a.member_id = b.member_id {$addWhere} ORDER BY a.{$_POST['sort']} {$_POST['dir']}, a.user_id ASC";
		}else{
			if($addWhere){
				$addWhere.="and user_id='{$_SESSION['user']['user_id']}'";
			}else{
				$addWhere="where user_id='{$_SESSION['user']['user_id']}'";
			}
			$query = "SELECT a.*, b.top_menu_mode, b.action_icon_slide_yn from bc_member a LEFT OUTER JOIN bc_member_option b ON a.member_id = b.member_id {$addWhere} ORDER BY a.{$_POST['sort']} {$_POST['dir']},a.user_id ASC";

		}
		$totalquery = "select count(*) from bc_member {$addWhere}";
		$total = $db->queryOne($totalquery);
		$rows = $db->queryAll($query);

	}

	for($i= 0 ; $i<count($rows); $i++){
		$rows[$i]['password'] ='';
		$rows[$i]['ori_password'] ='';

		$groups = $db->queryAll("select g.member_group_name from bc_member_group g, bc_member_group_member gm where gm.member_id={$rows[$i]['member_id']} and gm.member_group_id=g.member_group_id");

		// 부서 관련 추가
		$deptCodeSetId = $db->queryOne("select id from dd_code_set where code_set_code = 'DEPT'");
		$dept = $db->queryRow("select id,code_itm_nm,code_itm_code from dd_code_item where code_set_id = {$deptCodeSetId} and code_itm_code = '{$rows[$i]['dept_nm']}'");
		if(count($dept) === 0)
			$dept = $db->queryRow("select id,code_itm_nm,code_itm_code from dd_code_item where code_set_id = {$deptCodeSetId} and code_itm_nm = '{$rows[$i]['dept_nm']}'");
        if (!empty($dept)) {
            $rows[$i]['dept'] = $dept;
        }
        $org = $db->queryRow("select id,code_itm_nm,code_itm_code from dd_code_item where code_set_id = 214 and code_itm_code = '{$rows[$i]['org_id']}'");
        if (!empty($org)) {
            $rows[$i]['org'] = $org;
        }
		if($groups){
			$rows[$i]['group'] = array();
			foreach($groups as $group){
				array_push($rows[$i]['group'], $group['member_group_name']);
			}
			$rows[$i]['group'] = implode(', ', $rows[$i]['group']);
		}
	}
	echo json_encode(array(
		'success' => true,
		'total' => $total,
		'data' => $rows
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