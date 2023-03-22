<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
fn_checkAuthPermission($_SESSION);

$s_date = $_POST['start_date'];
$e_date = $_POST['end_date'];
$types = $mdb->queryAll("select bs_content_id as type, bs_content_title as name from bc_bs_content");

$data = array();
switch ($_POST['type'])
{

	case 'reg':
		foreach($types as $type)
		{
			$regist = $mdb->queryOne("select count(content_id) from bc_content where bs_content_id='{$type['type']}' and created_date between '$s_date' and '$e_date'");
			$del_count = $mdb->queryOne("select count(log_id) from bc_log where bs_content_id = '{$type['type']}' and created_date between '$s_date' and '$e_date'");
			array_push($data, array(
				'name' => $type['name'],
				'count' => $regist-$del_count
			));
		}
	break;

	case 'read':
		foreach($types as $type)
		{
			$read = $mdb->queryOne("
				select count(l.log_id)
				from bc_log l, bc_content c
				where l.content_id = c.content_id
				and c.bs_content_id = '{$type['type']}'
				and l.action = 'read'
				and l.created_date between '$s_date' and '$e_date'");

			array_push($data, array(
				'name' => $type['name'],
				'count' => $read
			));
		}
	break;

	case 'download':
		foreach($types as $type)
		{
			$down = $mdb->queryOne("
				select count(l.log_id)
				from bc_log l, bc_content c
				where l.content_id=c.content_id
				and c.bs_content_id='{$type['type']}'
				and l.action='download'
				and l.created_date between '$s_date' and '$e_date'");

			array_push($data, array(
				'name' => $type['name'],
				'count' => $down
			));
		}
	break;
}

echo json_encode(array(
	'success' => true,
	'data' => $data
))
?>