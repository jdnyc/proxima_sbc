<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/config.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/db.php';
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/Search.class.php');

$user_id = $_SESSION['user']['user_id'];
try
{
	if( empty($user_id) || $user_id=='temp') throw new Exception("로그인해주세요.");
	$content_id = getSequence('SEQ_CONTENT_ID');
	$ud_content_id		= $_POST['k_ud_content_id'];
	$title				= $db->escape($_POST['k_title']);
	$category_id		= $_POST['k_category_id'];
	$category_full_path	= empty($category_id)? '/0'	: getCategoryFullPath($_POST['k_category_id']);
	$expired_date		= date('YmdHis', strtotime($_POST['c_expired_date']));
	$modified_time		= date('YmdHis');

	$pre_query = "insert into bc_content(CATEGORY_ID, CATEGORY_FULL_PATH, BS_CONTENT_ID,UD_CONTENT_ID,
					CONTENT_ID,TITLE,REG_USER_ID,
					CREATED_DATE,LAST_MODIFIED_DATE,EXPIRED_DATE,
					STATUS,PARENT_CONTENT_ID)
			values('".$category_id."','".$category_full_path."','".DOCUMENT."','".$ud_content_id."',
					'".$content_id."','".$title."','".$user_id."',
					'".$modified_time."','".$modified_time."','".$expired_date."',
					'2','".$content_id."')";
	$db->exec($pre_query);

	foreach ( $_POST as $k=>$v )
	{
		$v = $db->escape($v);

		if (preg_match('/^k\_|^c\_/', $k) || strstr($k, 'ext') ) continue;

		// meta_field 가 날짜형식일경우 14자리로 변환 by 이성용 2011-2-7
		$date_field_chk = $db->queryOne("select USR_META_FIELD_TYPE from bc_usr_meta_field where usr_meta_field_id='$k' ");

		if( $date_field_chk == 'datefield' ){
			if( !empty($v) ){
				$v= date('YmdHis', strtotime($v));
			}
		}

		if( $date_field_chk == 'checkbox' ){
			if( !empty($v) ){
				$v= '1';
			}
		}
	}

	$fieldKey = array();
	$fieldValue = array();
	//필드 목록 배열
	$metaFieldInfo = MetaDataClass::getMetaFieldInfo ('usr' , $ud_content_id );	//필드의 id => name
	$fieldNameMap = MetaDataClass::getFieldIdtoNameMap('usr' , $ud_content_id );	//테이블 명
	$tablename = MetaDataClass::getTableName('usr', $ud_content_id );	//기본 데이터유형 변환
	$metaValues = MetaDataClass::getDefValueRender('usr' , $ud_content_id , $_POST);
	foreach($fieldNameMap as $usr_meta_field_id => $name ){
		$value = $metaValues[$usr_meta_field_id];
		$value = $db->escape($value);
		array_push($fieldKey, $name );
		array_push($fieldValue, $value);
	}

	if( MetaDataClass::isNewMeta('usr', $ud_content_id , $content_id) ){
		//신규 등록
		array_push($fieldKey, 'usr_content_id' );
		array_push($fieldValue, $content_id );
		$query = $db->InsertQuery($tablename ,$fieldKey, $fieldValue);
	}else{
		//업데이트
		$query = $db->UpdateQuery($tablename ,$fieldKey, $fieldValue, " usr_content_id='$content_id' " );
	}
	$r = $db->exec($query);

	$description = '토픽 추가';
	insertLog('regist', $user_id, $content_id, $description);

	echo json_encode(array(
		'success' => true,
		'msg' => $msg,
		'query'=> $query,
		'content_id' => $content_id
	));
}
catch ( Exception $e )
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}


function EditContentCode($_POST)
{
	global $db;

	$content_id		= $_POST['k_content_id'];//	4431818

	$formbaseymd	= $_POST['k_formbaseymd'];
	$subprogcd		= $_POST['k_subprogcd'];
	$progcd			= $_POST['k_progcd'];
	$brodymd		= $_POST['k_brodymd'];
	$medcd			= $_POST['k_medcd'];

	$check = $db->queryOne("select content_id from content_code_info where content_id='$content_id'");

	if( empty($check) )
	{
		$content_update_field_array = array();
		$content_update_value_array = array();
		$content_update_field_array[] = " content_id ";
		$content_update_value_array[] = " '$content_id' ";

		if( !empty($formbaseymd) )
		{
			$content_update_field_array[] = " formbaseymd ";
			$content_update_value_array[] = " '$formbaseymd' ";
		}

		if( !empty($subprogcd) )
		{
			$content_update_field_array[] = " subprogcd ";
			$content_update_value_array[] = " '$subprogcd' ";
		}

		if( !empty($progcd) )
		{
			$content_update_field_array[] = " progcd ";
			$content_update_value_array[] = " '$progcd' ";
		}

		if( !empty($brodymd) )
		{
			$content_update_field_array[] = " brodymd ";
			$content_update_value_array[] = " '$brodymd' ";
		}

		if( !empty($medcd) )
		{
			$content_update_field_array[] = " medcd ";
			$content_update_value_array[] = " '$medcd' ";
		}

		$r = $db->exec("insert into content_code_info ( ".join(',' , $content_update_field_array)." ) values ( ".join(',' , $content_update_value_array)." )");
	}
	else
	{

		$content_update_field_array = array();
		$content_update_field_array[] = " content_id='$content_id' ";

		if( !empty($formbaseymd) )
		{
			$content_update_field_array[] = " formbaseymd='$formbaseymd' ";
		}

		if( !empty($subprogcd) )
		{
			$content_update_field_array[] = " subprogcd='$subprogcd' ";
		}

		if( !empty($progcd) )
		{
			$content_update_field_array[] = " progcd='$progcd' ";
		}

		if( !empty($brodymd) )
		{
			$content_update_field_array[] = " brodymd='$brodymd' ";
		}

		if( !empty($medcd) )
		{
			$content_update_field_array[] = " medcd='$medcd' ";
		}

		$r = $db->exec("update content_code_info set ".join(',' , $content_update_field_array)." where content_id='$content_id' ");
	}
}

?>