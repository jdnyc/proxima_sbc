<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$member_group_id	= $_POST['member_group_id'];
$administrator		= $_POST['administrator'];
$is_default			= $_POST['is_default'];
if(empty($is_default))
{
	$is_default = 'N';
}

try 
{
	if ($administrator == 'Y')
	{
		switch($_POST['action'])
		{
			case 'add':
				$cur_date					= date('YmdHis');
				$member_group_id			= getSequence('SEQ_MEMBER_GROUP_ID');
				$_POST['member_group_id']	= $member_group_id;

				$db->exec("insert into bc_member_group (member_group_id, member_group_name, is_default, description, created_date, parent_group_id) values ($member_group_id, '{$_POST['name']}', '$is_default', '{$_POST['description']}', '$cur_date', {$_POST['parent_group_id']})");
			break;

			case 'edit':
				$db->exec("update bc_member_group 
					set member_group_name='{$_POST['name']}',
						is_default='$is_default', 
						description='{$_POST['description']}',
						parent_group_id={$_POST['parent_group_id']}
					where member_group_id=$member_group_id");
			break;
		}
	}
	else
	{
		switch($_POST['action'])
		{
			case 'add':
				$cur_date					= date('YmdHis');
				$member_group_id			= getSequence('SEQ_MEMBER_GROUP_ID');
				$_POST['member_group_id']	= $member_group_id;

				$db->exec("insert into bc_member_group (member_group_id, member_group_name, is_default, description, created_date, parent_group_id) values ($member_group_id, '{$_POST['name']}', '$is_default','{$_POST['description']}', '$cur_date', {$_POST['parent_group_id']})");
			break;

			case 'edit':
				$db->exec("update bc_member_group 
					set member_group_name='{$_POST['name']}',
						is_default='$is_default',
						description='{$_POST['description']}',
						parent_group_id={$_POST['parent_group_id']}
					where member_group_id=$member_group_id");
			break;
		}
	}

	//change_content_grant($_POST);

	die(json_encode(array(
		'success' => true
	)));
	
}

catch(Exception $e){
	die(json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	)));
}

function change_content_grant($ud_content_list)
{
	global $db, $member_group_id;

	$user_content_list = get_user_content_list();
	$r = $db->exec("delete from bc_ud_content_grant 
					where ud_content_id in ($user_content_list) 
					and member_group_id=$member_group_id");

	foreach ($ud_content_list as $k=>$v) 
	{
		if (preg_match('/^m_/', $k)) 
		{
			list($ud_content_id, $ud_content_grant) = explode('_', substr($k, 2));

			$r = $db->exec("insert into bc_ud_content_grant 
								(ud_content_id, granted_right, member_group_id) 
							values 
								($ud_content_id, '$ud_content_grant', $member_group_id)");
		}
	}
}

function get_user_content_list()
{
	global $db;

	$all = $db->queryAll("select ud_content_id from bc_ud_content");
	foreach ($all as $item) 
	{
		$_t[] = $item['ud_content_id'];
	}

	return implode(', ', $_t);
}
?>