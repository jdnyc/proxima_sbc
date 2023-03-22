<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/util.php';
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

switch($_REQUEST['action']) {
	case 'delete_table':
		delete_table($_POST['bs_content_id']);
		break;

	case 'delete_field':
		delete_field($_POST['sys_meta_field_id_list']);
		break;

	default:
		echo json_encode(array(
					'success' => false,
					'msg' => 'no action'
				));
		break;
}


function delete_table($bs_content_id)
{
	global $db;

	$sys_meta_field_id = $db->queryOne('select sys_meta_field_id from bc_sys_meta_field where bs_content_id=' . $bs_content_id);

	if ($sys_meta_field_id) {
		_print(_text('MSG00209'));
	}

//	if ($content_field_id) {
//		$r = $db->exec('delete from content_value where content_field_id=' . $content_field_id);
//
//		$r = $db->exec('delete from content_field where content_type_id=' . $content_type_id);
//	}

	$query = MetaDataClass::dropTableQuery('sys' ,  $bs_content_id );
	$r = $db->exec($query);

	$r = $db->exec('delete from bc_bs_content where bs_content_id=' . $bs_content_id);

	echo json_encode(array(
		'success' => true,
		'msg' => _text('MSG00211')
	));
}

function delete_field($sys_meta_field_id_list)
{
	global $db;

	$sys_meta_field_id_list = explode(',', $sys_meta_field_id_list);
	foreach ($sys_meta_field_id_list as $sys_meta_field_id)
	{
		if(empty($sys_meta_field_id)) continue;

		$sys_meta_field = $db->queryRow('select * from bc_sys_meta_field where sys_meta_field_id='.$sys_meta_field_id);

		$query = MetaDataClass::delFieldQuery('sys' ,  $sys_meta_field[bs_content_id] , $sys_meta_field[sys_meta_field_code] );
		$r = $db->exec($query);

		//$db->exec('delete from bc_sys_meta_value where sys_meta_value_id = ' . $sys_meta_field_id);
		$r = $db->exec('delete from bc_sys_meta_field where sys_meta_field_id = ' . $sys_meta_field_id);
	}

	echo json_encode(array(
		'success' => true,
		'msg' =>  _text('MSG00210')
	));
}


?>