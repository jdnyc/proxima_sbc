<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');

$type =  $_REQUEST['type'];//임시사용자여부
$empno = $_REQUEST['userId'];
$ori_pw = $_REQUEST['pw'];
$pw = hash('sha512', $_REQUEST['pw']);
$name = $_REQUEST['name'];
$dept_nm = $_REQUEST['dept_nm'];
$job_position = $_REQUEST['job_position'];
$expired_date = $_REQUEST['expired_date'];

$category_id =	$_REQUEST['category_id'];
$groups = explode(',', $_REQUEST['groups']);

if(!is_null($category_id) )
{
	$group_id = $db->queryOne("select member_group_id from path_mapping where category_id='$category_id'");

	$groups = explode(',', $group_id );
}


try {

	switch($_POST['action']){
		case 'add':
		//2010.01.11 인제스트사용자추가를 위해 웹에서 사용자 추가를 할수있게 코드 수정. --김성민

			$cur_date = date('YmdHis');
			//시퀀스를 사용하지않고 멤버아이디값의 최대값에 +1을 한다. 2011.01.11 김성민
			//$member_id = getNextSequence();
			$member_id = ($db->queryOne("select max(member_id) as max_member_id from bc_member"))+1;

			$r = $db->queryOne("select count(*) from bc_member where user_id='$empno'");
			if($r > 0) throw new Exception('동일한 아이디가 존재 합니다.');

			$add_user = "insert into bc_member (member_id, user_id, password, user_nm, dept_nm, job_position, expired_date, created_date, last_login_date, ori_password, extra_vars) values ('$member_id', '$empno', '$pw', '$name', '$dept_nm', '$job_position', '$expired_date', '$cur_date', '$cur_date', '$ori_pw' , '$type')";
			$r = $db->exec($add_user);

//			foreach($groups as $group_id)
//			{
//				if ($group_id == ADMIN_GROUP)
//				{
//					$db->exec("update bc_member set is_admin='Y' where user_id='$empno'");
//				}
//
//				$r = $db->exec("insert into bc_member_group_member values ($member_id, $group_id)");
//			}

			if( !is_null($category_id) )
			{

				$query ="insert into user_mapping ( CATEGORY_ID,USER_ID) values ('$category_id','$empno')";
				$r = $db->exec($query);
			}

		break;

		case 'edit':
			$member_id = $_POST['member_id'];

			$update_query = "update bc_member set user_id='$empno', user_nm = '$name', dept_nm = '$dept_nm', job_position = '$job_position' where member_id = '$member_id'";
			$r = $db->exec($update_query);

			$reset_group = $db->exec("delete bc_member_group_member where member_id = $member_id");

			$db->exec("update bc_member set is_admin='N' where user_id='$empno'");
			foreach($groups as $group_id)
			{
				if ($group_id == ADMIN_GROUP)
				{
					$db->exec("update bc_member set is_admin='Y' where user_id='$empno'");
				}

				$r = $db->exec("insert into bc_member_group_member values ($member_id, $group_id)");
			}

		break;
		case 'del':

			$node_id = $_POST['node_id'];

			$node_array = explode('-', $node_id);
			$category_id = $node_array[0];
			$user_id = $node_array[1];


			$info = $db->queryRow("select * from bc_member where user_id='$user_id'");
			$member_id = $info['member_id'];

			if( !is_null($user_id) && !is_null($member_id) )
			{
				$r = $db->exec("delete from user_mapping where user_id='$user_id'");
				$r = $db->exec("delete from bc_member_group_member where member_id = '$member_id' ");
				$r = $db->exec("delete from bc_member where user_id = '$user_id' ");

				die(json_encode(array(
					'success' => true,
					'msg' => '삭제 성공'
				)));
			}
			else
			{
				throw new Exception('유저정보 오류');
			}
		break;

		default:
			throw new Exception('action 값이 없습니다.');
		break;
	}

	die(json_encode(array(
		'success' => true,
		'q' => $add_user
	)));
}
catch(Exception $e){
	die(json_encode(array(
		'success' => false,
		'msg' => $e->getMessage(),
		'q' => $db->last_query
	)));
}
?>