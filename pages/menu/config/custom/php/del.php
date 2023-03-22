<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/util.php';
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

switch($_REQUEST['action'])
{
	case 'delete_table':
		delete_table($_POST['ud_content_id']);
	break;

	case 'field':
		delete_field($_POST['usr_meta_field_id_list']);
	break;

	default:
		echo json_encode(array(
					'success' => false,
					'msg' => 'no action'
				));
		break;
}


function delete_table($ud_content_id)
{
	global $db;

	$usr_meta_field_id = $db->queryOne('select usr_meta_field_id from bc_usr_meta_field where ud_content_id=' . $ud_content_id);

	if ($usr_meta_field_id)
	{
		_print(_text('MSG00209'));
	}

//	if ($meta_field_id) {
//		$r = $db->exec('delete from meta_value where meta_field_id=' . $meta_field_id);
//
//		$r = $db->exec('delete from meta_field where meta_table_id=' . $meta_table_id);
//	}

	$category_id = $db->queryOne('select category_id from bc_category_mapping where ud_content_id=' . $ud_content_id);

	$db->exec('delete from bc_ud_content where ud_content_id=' . $ud_content_id);
	$db->exec('delete from bc_category_mapping where ud_content_id=' . $ud_content_id);

	// 부모를 잃은 카테고리가 생김
	// deleteChildrenCategory($category_id)
	$db->exec('delete from bc_category where category_id=' . $category_id);
	
	//관련 파일 삭제/폐기 기한 일 제거 
	$db->exec('delete from bc_ud_content_delete_info where ud_content_id = $ud_content_id');

	echo json_encode(array(
				'success' => true,
				'msg' => _text('MSG00211')
	));
}

function delete_field($usr_meta_field_id_list)
{
	global $db;

	$usr_meta_field_id_list = explode(',', trim($usr_meta_field_id_list, ','));
	foreach($usr_meta_field_id_list as $usr_meta_field_id)
	{
		if(empty($usr_meta_field_id)) continue;

		$r = $db->exec('delete from bc_usr_meta_value where usr_meta_field_id=' . $usr_meta_field_id);

		// 2010-11-08 추가 (컨테이너 추가 by CONOZ)
		$usr_meta_field_type = $db->queryOne('select usr_meta_field_type from bc_usr_meta_field where usr_meta_field_id='.$usr_meta_field_id);
		if($usr_meta_field_type == 'container')
		{
			$r = $db->exec('delete from bc_usr_meta_field where container_id='.$usr_meta_field_id);
		}
		$r = $db->exec('delete from bc_usr_meta_field where usr_meta_field_id='.$usr_meta_field_id);
	}


	echo json_encode(array(
				'success' => true,
				'msg' => _text('MSG00210')
		));
}


?>