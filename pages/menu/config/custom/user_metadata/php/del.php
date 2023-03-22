<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/util.php';
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

switch($_REQUEST['action'])
{
	case 'delete_table':
		delete_table($_POST['ud_content_id']);
	break;

	case 'field':
		delete_field($_POST['ud_content_id'], $_POST['usr_meta_field_id_list']);
	break;

	case 'delete_container_field':
		delete_container_field($_POST['ud_content_id'], $_POST['usr_meta_field_id_list']);
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

	$usr_meta_field_id = $db->queryOne("select usr_meta_field_id from bc_usr_meta_field where ud_content_id=" . $ud_content_id ." and usr_meta_field_type != 'container' and depth !=0");

	if ($usr_meta_field_id)
	{
		_print(_text('MSG00209'));
	}

//	if ($meta_field_id) {
//		$r = $db->exec('delete from meta_value where meta_field_id=' . $meta_field_id);
//
//		$r = $db->exec('delete from meta_field where meta_table_id=' . $meta_table_id);
//	}

//	$category_id = $db->queryOne('select category_id from bc_category_mapping where ud_content_id=' . $ud_content_id);

	$query = MetaDataClass::dropTableQuery('usr' ,  $ud_content_id );
	$r = $db->exec($query);

	$db->exec('delete from BC_USR_META_FIELD where ud_content_id=' . $ud_content_id);
	$db->exec('delete from bc_ud_content where ud_content_id=' . $ud_content_id);
	//$db->exec('delete from bc_category_mapping where ud_content_id=' . $ud_content_id);

	// 부모를 잃은 카테고리가 생김
	// deleteChildrenCategory($category_id)
	//$db->exec('delete from bc_category where category_id=' . $category_id);

	//관련 파일 삭제/폐기 기한 일 제거
	$query = "delete from bc_ud_content_delete_info where ud_content_id ={$ud_content_id}";
	$db->exec($query);

	echo json_encode(array(
				'success' => true,
				'msg' => _text('MSG00211')
	));
}

function delete_field($ud_content_id , $usr_meta_field_id_list)
{
	global $db;

	$usr_meta_field_id_list = explode(',', trim($usr_meta_field_id_list, ','));

	$hasData = 0;
	foreach($usr_meta_field_id_list as $usr_meta_field_id)
	{
		if(empty($usr_meta_field_id)) continue;

		$usr_meta_field = $db->queryRow('select * from bc_usr_meta_field where ud_content_id='.$ud_content_id.' and  usr_meta_field_id='.$usr_meta_field_id);
		$usr_meta_field_type = $usr_meta_field[usr_meta_field_type];
		$usr_meta_field_code = strtoupper($usr_meta_field[usr_meta_field_code]);

		if($usr_meta_field_type == 'container'){
			$sub_usr_meta_fields = $db->queryAll('select * from bc_usr_meta_field where ud_content_id='.$ud_content_id.' and  container_id='.$usr_meta_field_id);
			foreach($sub_usr_meta_fields as $sub_field)
			{
				if($sub_field[usr_meta_field_type] != 'container'){
					$sub_field_code = strtoupper($sub_field[usr_meta_field_code]);
					$fieldname = MetaDataClass::getFieldName('usr', $sub_field_code);
					$tablename = MetaDataClass::getTableName('usr', $ud_content_id);
					$rowCound = $db->queryRow('select count('.$fieldname.') as count from '.$tablename);
					$hasData += $rowCound[count];
				}
			}
		}else{
			$fieldname = MetaDataClass::getFieldName('usr', strtoupper($usr_meta_field_code) );
			$tablename = MetaDataClass::getTableName('usr', $ud_content_id);
			$rowCound = $db->queryRow('select count('.$fieldname.') as count from '.$tablename);
			$hasData += $rowCound[count];
		}
	}
	if ($hasData == 0){
		foreach($usr_meta_field_id_list as $usr_meta_field_id)
		{
			if(empty($usr_meta_field_id)) continue;

			$usr_meta_field = $db->queryRow('select * from bc_usr_meta_field where ud_content_id='.$ud_content_id.' and  usr_meta_field_id='.$usr_meta_field_id);
			$usr_meta_field_type = $usr_meta_field[usr_meta_field_type];
			$usr_meta_field_code = strtoupper($usr_meta_field[usr_meta_field_code]);

			if($usr_meta_field_type == 'container'){
				$sub_usr_meta_fields = $db->queryAll('select * from bc_usr_meta_field where ud_content_id='.$ud_content_id.' and  container_id='.$usr_meta_field_id);
				foreach($sub_usr_meta_fields as $sub_field)
				{
					if($sub_field[usr_meta_field_type] != 'container'){
						$sub_field_code = strtoupper($sub_field[usr_meta_field_code]);
						$query = MetaDataClass::delFieldQuery('usr' ,  $ud_content_id , $sub_field_code );
						$r = $db->exec($query);
					}
				}
			}else{
				$query = MetaDataClass::delFieldQuery('usr' ,  $ud_content_id , $usr_meta_field_code );
				$r = $db->exec($query);
			}

			//$r = $db->exec('delete from bc_usr_meta_value where ud_content_id='.$ud_content_id.' and usr_meta_field_id=' . $usr_meta_field_id);

			if($usr_meta_field_type == 'container')
			{
				$r = $db->exec('delete from bc_usr_meta_field where ud_content_id='.$ud_content_id.' and container_id='.$usr_meta_field_id);
			}
			$r = $db->exec('delete from bc_usr_meta_field where ud_content_id='.$ud_content_id.' and usr_meta_field_id='.$usr_meta_field_id);
		}


		echo json_encode(array(
					'success' => true,
					'msg' => _text('MSG00210')
			));
	} else {
		foreach($usr_meta_field_id_list as $usr_meta_field_id)
		{
			if(empty($usr_meta_field_id)) continue;

			$usr_meta_field = $db->queryRow('select * from bc_usr_meta_field where ud_content_id='.$ud_content_id.' and  usr_meta_field_id='.$usr_meta_field_id);
			$usr_meta_field_type = $usr_meta_field[usr_meta_field_type];

			if($usr_meta_field_type == 'container')
			{
				$r = $db->exec('delete from bc_usr_meta_field where ud_content_id='.$ud_content_id.' and container_id='.$usr_meta_field_id);
			}
			$r = $db->exec('delete from bc_usr_meta_field where ud_content_id='.$ud_content_id.' and usr_meta_field_id='.$usr_meta_field_id);
		}
		echo json_encode(array(
					'success' => true,
					'msg' => _text('MSG00210')
			));
	}	
}

function delete_container_field($ud_content_id , $usr_meta_field_id_list)
{
	global $db;

	$usr_meta_field_id_list = explode(',', trim($usr_meta_field_id_list, ','));

	$has_sub_metafield = 0;
	foreach($usr_meta_field_id_list as $usr_meta_field_id)
	{
		if(empty($usr_meta_field_id)) continue;

		$usr_meta_field = $db->queryRow('select * from bc_usr_meta_field where ud_content_id='.$ud_content_id.' and  usr_meta_field_id='.$usr_meta_field_id);
		$usr_meta_field_type = $usr_meta_field[usr_meta_field_type];
		$usr_meta_field_code = strtoupper($usr_meta_field[usr_meta_field_code]);

		if($usr_meta_field_type == 'container'){
			$sub_usr_meta_fields = $db->queryRow("select count(*) as count from bc_usr_meta_field where ud_content_id=".$ud_content_id." and  container_id=".$usr_meta_field_id." and usr_meta_field_type != 'container'");
			$has_sub_metafield += $sub_usr_meta_fields[count];
		}
	}
	if ($has_sub_metafield == 0){
		foreach($usr_meta_field_id_list as $usr_meta_field_id)
		{
			if(empty($usr_meta_field_id)) continue;

			$usr_meta_field = $db->queryRow('select * from bc_usr_meta_field where ud_content_id='.$ud_content_id.' and  usr_meta_field_id='.$usr_meta_field_id);
			$usr_meta_field_type = $usr_meta_field[usr_meta_field_type];
			$usr_meta_field_code = strtoupper($usr_meta_field[usr_meta_field_code]);

			if($usr_meta_field_type == 'container'){
				$sub_usr_meta_fields = $db->queryAll('select * from bc_usr_meta_field where ud_content_id='.$ud_content_id.' and  container_id='.$usr_meta_field_id);
				foreach($sub_usr_meta_fields as $sub_field)
				{
					if($sub_field[usr_meta_field_type] != 'container'){
						$sub_field_code = strtoupper($sub_field[usr_meta_field_code]);
						$query = MetaDataClass::delFieldQuery('usr' ,  $ud_content_id , $sub_field_code );
						$r = $db->exec($query);
					}
				}
			}
			//$r = $db->exec('delete from bc_usr_meta_value where ud_content_id='.$ud_content_id.' and usr_meta_field_id=' . $usr_meta_field_id);

			if($usr_meta_field_type == 'container')
			{
				$r = $db->exec('delete from bc_usr_meta_field where ud_content_id='.$ud_content_id.' and container_id='.$usr_meta_field_id);
			}
			$r = $db->exec('delete from bc_usr_meta_field where ud_content_id='.$ud_content_id.' and usr_meta_field_id='.$usr_meta_field_id);
		}


		echo json_encode(array(
					'success' => true,
					'msg' => _text('MSG00210')
			));
	} else {
		echo json_encode(array(
					'success' => false,
					'msg' => _text('MSG02082')
			));
	}
}


?>