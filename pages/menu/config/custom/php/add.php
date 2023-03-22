<?php
//11-11-01 승수. bc_ud_content 테이블에 만료기간 expire_date필드 추가
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/util.php';
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

switch($_REQUEST['action']) {
	case 'add_table':
		 // 2011 - 12 - 07 폐기기간 추가 수정  by 허광회 
		
		add_table();
		break;
	
	case 'field':
		add_field();
		break;
	
	default: 
		echo json_encode(array(
			'success' => false,
			'msg' => _text('MSG01022')
		));
	break;
}


function add_table()
{
	 // 2011 - 12 - 07 폐기기간 추가 수정  by 허광회 
	global $db;
	
	$bs_content_id = $_POST['bs_content_id'];
	$ud_content_title = $_POST['ud_content_title'];
	$allowed_extension = $_POST['allowed_extension'];
	$use_common_category = $_POST['use_common_category'];
	$description = $_POST['description'];
	$expire_date = $_POST['year']*365 + $_POST['month']*30 + $_POST['day'];

	$is_exists = $db->queryOne("
						SELECT	COUNT(*)
						FROM	BC_UD_CONTENT
						WHERE	UD_CONTENT_TITLE = '$ud_content_title'
				");
	if($is_exists > 0) _print(_text('MSG01048'));
	
	 //print_r($_POST);
	// 만료일 계산해서 넣어줌 우선 
   
	$content_expire_date = check_limit_date($_POST['content_expire_date']);  
		
 	$expire = array();
	$data =json_decode($_POST['expire']);
	
	foreach($data as $key => $v)
	{
		$expire[$key]=$v;		
	}	
   
	$contents_expire_date = $expire['contents_expire_date'];
   
	//echo(" contents_exire_date : $contents_expire_date ");
	
	$curtime = date('YmdHis');
	
	$query = "SELECT MAX(SHOW_ORDER)+1 FROM BC_UD_CONTENT";
	$show_order = $db->queryOne($query);
	
	$query = "
				INSERT	INTO BC_UD_CONTENT
					(BS_CONTENT_ID, UD_CONTENT_TITLE, ALLOWED_EXTENSION, DESCRIPTION, CREATED_DATE, EXPIRED_DATE, CON_EXPIRE_DATE ,SHOW_ORDER)
				VALUES
					($bs_content_id,  '$ud_content_title', '$allowed_extension', '$description','$curtime','$content_expire_date','$contents_expire_date',$show_order)
			";

	$result = $db->exec($query);		
	$seq = $db->queryOne("SELECT MAX(UD_CONTENT_ID) FROM BC_UD_CONTENT");	
	$query = "
				INSERT	INTO BC_UD_CONTENT_DELETE_INFO
					(UD_CONTENT_ID, TYPE_CODE, DATE_CODE, CODE_TYPE)
				VALUES
					($seq,'$_POST[content_expire_date]','$_POST[content_expire_date]','UCSDDT')
			";
	$db->exec($query);

	$query = "
				INSERT	INTO BC_UD_CONTENT_DELETE_INFO
					(UD_CONTENT_ID, TYPE_CODE, DATE_CODE, CODE_TYPE)
				VALUES
					($seq,'$con_expire_date','$con_expire_date','UCDDDT')
			";
	$db->exec($query);
	
	$query = "SELECT * FROM BC_CODE WHERE CODE_TYPE_ID IN (SELECT ID FROM BC_CODE_TYPE CT WHERE CT.CODE = 'FLDLNM')";
	$filetype_code = $db->queryAll($query);	
	
	foreach($filetype_code as $f_code)
	{
		$ck_name = 'del_'.$f_code['code'].'_checkbox';
		$dt_name = 'del_'.$f_code['code'].'_date';		
		
		if(!strcmp("on",$expire[$ck_name])) // exist
		{		
			if($expire[$dt_name])
			{
				$query = "
							INSERT	INTO BC_UD_CONTENT_DELETE_INFO
								(UD_CONTENT_ID, TYPE_CODE, DATE_CODE , CODE_TYPE)
							VALUES
								($seq  ,'$f_code[code]','$expire[$dt_name]','FLDLNM')
						";
				$db->exec($query);
			}			
		}		
	}

	if ($use_common_category == 'N')
	{
		$ud_content_id = $db->queryOne("SELECT MAX(UD_CONTENT_ID) FROM BC_UD_CONTENT");
		//addExclusiveCategory($ud_content_id, $ud_content_title); 함수선언이 안되어있음 우선 주석처리
	}
	
	echo json_encode(array(
		'success' => true,
		'msg' => _text('MSG01055')
	));
}

function add_field()
{
	global $db;

	// 2010-11-08 추가 (컨테이너 추가 by CONOZ)
	$container_id		= !empty($_POST['container_id'])		? $_POST['container_id']	: '';

	$ud_content_id			= $_POST['ud_content_id'];
	$usr_meta_field_title	= $_POST['usr_meta_field_title'];
	$usr_meta_field_type	= $_POST['usr_meta_field_type'];
	$is_required		= !empty($_POST['is_required'])			? $_POST['is_required']		: 0;
	$is_editable		= !empty($_POST['is_editable'])			? $_POST['is_editable']		: 0;
	$is_show			= !empty($_POST['is_show'])				? $_POST['is_show']			: 0;
	$is_search_reg		= !empty($_POST['is_search_reg'])		? $_POST['is_search_reg']	: 0;
	$default_value		= !empty($_POST['default_value'])		? $_POST['default_value']	: '';

	$is_exists = $db->queryOne("
					SELECT	COUNT(*)
					FROM	BC_USR_META_FIELD
					WHERE	UD_CONTENT_ID = $ud_content_id
					AND		USR_META_FIELD_TITLE = '$usr_meta_field_title'
				");
	if($is_exists > 0) _print(_text('MSG01049'));

	if (empty($ud_content_id))			_print(_text('MSG01050').'(' . $ud_content_id . ')');
	if (empty($usr_meta_field_title))	_print(_text('MSG01051').'(' . $usr_meta_field_title . ')');
	if (empty($usr_meta_field_type))	_print(_text('MSG01052').'(' . $usr_meta_field_type . ')');
		
	$seq = getSequence('seq_usr_meta_field_id');
	$show_order = $db->queryOne("
						SELECT	DECODE(MAX(SHOW_ORDER), NULL, 1, MAX(SHOW_ORDER)+1)
						FROM	BC_USR_META_FIELD
						WHERE	UD_CONTENT_ID = $ud_content_id
				");

	// 2010-11-08 추가 (컨테이너 추가 by CONOZ)
	if($container_id)
	{
		$depth=$db->queryOne("
					SELECT	DEPTH
					FROM	BC_USR_META_FIELD
					WHERE	USR_META_FIELD_ID = $container_id
				");
		if($usr_meta_field_type == 'container')
		{
			if($depth > 0)
			{
				_print(_text('MSG01053'));
			}
		}
		$depth=$depth+1;
	}
	else
	{
		if($usr_meta_field_type != 'container')
		{
			_print(_text('MSG01054').'(' . $type . ')');
		}
		else
		{
			$container_id=$seq;
			$depth=0;
		}
	}

	$summary_field_cd_array = array();

	foreach($_POST as $key => $taget)
	{
		if( strstr($key, 'summary_field_cd') )
		{
			$list = explode('-', $key);

			array_push($summary_field_cd_array ,$list[1]);
		}		
	}
	
	array_sum($summary_field_cd_array);

	$summary_field_cd	= array_sum($summary_field_cd_array);


	// 2010-11-08 container_id, depth 추가 (컨테이너 추가 by CONOZ)
	$r = $db->exec("
				INSERT	INTO BC_USR_META_FIELD
					(USR_META_FIELD_ID, UD_CONTENT_ID, SHOW_ORDER, USR_META_FIELD_TITLE, USR_META_FIELD_TYPE, IS_REQUIRED, IS_EDITABLE, IS_SHOW, IS_SEARCH_REG, DEFAULT_VALUE, CONTAINER_ID, DEPTH, SUMMARY_FIELD_CD) 
				VALUES
					($seq, $ud_content_id, $show_order, '$usr_meta_field_title', '$usr_meta_field_type', $is_required, $is_editable, $is_show, $is_search_reg, '$default_value', '$container_id', '$depth' , '$summary_field_cd')
		");

	echo json_encode(array(
		'success' => true,
		'msg' => _text('MSG02057')
	));
}
?>