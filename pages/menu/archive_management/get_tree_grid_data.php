<?php
//작성일 : 2013.03.08
//작성자 : 임찬모
//아카이브 관리 트리그리드를 불러오는 페이지

//2013.03.25 수정 del 정보 수정
//2013.04.25 수정 abrogate 정보 수정

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

global $db;

$category_id = $_POST['node'];

if(empty($category_id) || strstr($category_id, 'xnode') !== false) {
	$category_id = 0;
}

$result = array();

$category_infos = $db->queryAll("
						SELECT	*
						FROM	BC_CATEGORY
						WHERE	PARENT_ID = '$category_id'
						ORDER BY SHOW_ORDER ASC
				");

foreach($category_infos as $info) {
	$category = $info['category_id'];
	$category_title = $info['category_title'];
	$has_child = (boolean)$info['no_children'];	

	$query = "select * from bc_category_env where category_id = '$category'";
	$row = $db->queryRow($query);

	//요청승인 관련 설정
	$acpt_method = methodMapping($row['acpt_method']);	
	$acpt_period = periodMapping($row['acpt_period']);

	//아카이브 관련 설정
	$arc_method = methodMapping($row['arc_method']);	
	$arc_period = periodMapping($row['arc_period']);

	//아카이브 삭제 관련 설정
	$del_method = methodMapping($row['del_method']);
	$ori_del_method = methodMapping($row['ori_del_method']);	
	$del_period = periodMapping($row['del_period']);
	$ori_del_period = periodMapping($row['ori_del_period']);

	//리스토어 삭제관련 설정
	$res_method = methodMapping($row['res_method']);
	$res_period = periodMapping($row['res_period']);

	//자동폐기 관련 설정
	$abr_method = methodMapping($row['abr_method']);
	$abr_period = periodMapping($row['abr_period']);
	
	//마스터 이관 설정 관련
	$is_master = $row['is_master'];
	switch($is_master)
	{
		case '' :
			$is_master = '';
		break;
		case 'on':
			$is_master = 'on';
		break;
		case 'NO':
			$is_master = '';
		break;
	}
 
	$tr_category = $row['tr_category'];
	if(empty($tr_category))
	{
		$tr_category_nm = '';
	}
	else
	{
		$query = "select category_title from bc_category where category_id = $tr_category";
		$tr_category_nm = $db->queryOne($query);
	}
	
	if(empty($row['edit_date']))
	{
		$edit_date = '';
	}
	else
	{
		$edit_date = date('Y-m-d H:i:s', strtotime($row['edit_date']));
	}
	
	if(empty($row['edit_user_id']))
	{
		$edit_user_id = '';
	}
	else
	{
		$edit_user_id = $row['edit_user_id'];
	}
	if(!empty($tr_category))
	{
		$category_path = '/0'.getCategoryFullPath($tr_category);
		$root_category_id = '0';

		$arr_full_path = explode("/".$root_category_id."/", $category_path);

		if(count($arr_full_path) > 1)
		{
				$mapping_category_path = $root_category_id.'/'.$arr_full_path[1];
		}
		else
		{
				$mapping_category_path = $root_category_id.'/'.$arr_full_path[0];
		}
		$catPathTitle = getCategoryPathTitle($mapping_category_path, '>');
	}
	else
	{
		$category_path = '';
		$catPathTitle = '';
	}
	
	
	$data['id']	= $category;
	$data['category_title'] = $category_title;
	$data['leaf'] = $has_child;
	$data['icon'] = '/led-icons/folder.gif';
	$data['acpt_method'] = $acpt_method;
	$data['acpt_period'] = $acpt_period;
	$data['arc_method'] = $arc_method;
	$data['arc_period'] = $arc_period;
	$data['del_period'] = $del_period;
	$data['del_method'] = $del_method;
	$data['ori_del_period'] = $ori_del_period;
	$data['ori_del_method'] = $ori_del_method;
	$data['res_period'] = $res_period;
	$data['res_method'] = $res_method;
	$data['edit_date'] = $edit_date;
	$data['abr_method'] = $abr_method;
	$data['abr_period'] = $abr_period;
	$data['edit_user_id'] = $edit_user_id;
	$data['is_master'] = $is_master;
	$data['category_path'] = $category_path;
	$data['catPathTitle'] = $catPathTitle;
	$data['tr_category'] = $tr_category;
	$data['tr_category_nm'] = $tr_category_nm; 
	$data['state'] = $child;
  
	array_push($result, $data);
}

echo json_encode($result);

function has_child($id)
{
	global $db;
	$query = "select count(*) from bc_category where parent_id = '$id'";
	$has_child = $db->queryOne($query);
	
	if($has_child > 0)
	{
		return true;
	}
	else
	{
		return false;
	}
}

function methodMapping($val)
{
	switch($val)
	{
		case 'A' :
			$method = '자동';
		break;
		case 'M' :
			$method = '수동';
		break;
		case 'N' :
			$method = '미지정';
		break;
		default :
			$method = '미지정';
		break;
	}

	return $method;
}

function periodMapping($val)
{
	$val = trim($val);
	switch($val)
	{
		case '0' :
			$period = '즉시';
		break;
		case 'M' :
			$period = '수동대기';
		break;
		case '' :
			$period = '';
		break;
		default :
			$period = $val.'일 후';
		break;
	}

	return $period;
}

?>
