<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/db.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/timecode.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/searchengine/solr/solr.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/searchengine/solr/searcher.class.php');

$user_id		= $_SESSION['user']['user_id'];
$content_id		= $_POST['content_id'];
$meta_table_id	= $_POST['meta_table_id'];
$usr_meta_field_id	= $_POST['usr_meta_field_id'];

$action			= $_POST['action'];
//$json_value		= $db->escape($_POST['json_value']);//특수문자 처리 2011-1-21 by 이성용
$json_value		= json_decode($_POST['json_value'], true);
$modified_time	= date('YmdHis');

$del_value = $_POST['del_value'];

try
{

	if( !empty( $json_value ) )
	{
		foreach ($json_value as $k=>$v)
		{
			$tc_start[$k] = $v['columnB'];
		}
		array_multisort($tc_start, SORT_ASC, SORT_STRING, $json_value);
	}
	else
	{
		throw new Exception('정보가 없습니다.', -1);
	}

	//print_r($json_value);


////meta_multi에 추가 변경 삭제 함수생성(libs/functions.php) 2011-02-28 월요일 조훈휘
	updateMultiField($content_id, $usr_meta_field_id, $json_value, $action , $del_value);
//
//	/////////2011-1-20 마지막 수정날짜 업데이트 by 이성용////////////////
	executeQuery("update bc_content set last_modified_date='$modified_time' ".
					"where ".
							"content_id=".$content_id);

//	// 2011-1-20 메타데이터 수정시에 작업정보 - 메타데이터 작업자 입력 by 이성용
//
//	$meta_modifiy_id = $db->queryOne("select usr_meta_field_id from bc_usr_meta_field where ud_content_id='$ud_content_id' and name like '%메타데이터 작업자%'");
//
//	if( !empty( $meta_modifiy_id ) ) //메타필드아이디가 있을때만
//	{
//		executeQuery("update meta_value set value='$user_id' where content_id='$content_id' and meta_field_id='$meta_modifiy_id'");
//	}
//
//	$meta_modifiy_date = $db->queryOne("select usr_meta_field_id from bc_usr_meta_field where ud_content_id='$ud_content_id' and name like '%메타데이터 작업일자%'");
//	if(!empty($meta_modifiy_date)) //메타필드아이디가 있을때만
//	{
//		executeQuery("update bc_usr_meta_value set meta_value='$modified_time' where content_id='$content_id' and usr_meta_field_id='$meta_modifiy_date'");
//	}
//	///////////////////로그 남기기///////////////////
//	$log_id = getNextSequence();
//	$tap_name = queryOne("select usr_meta_field_title from bc_usr_meta_field where usr_meta_field_id='$usr_meta_field_id' ");
//	$content_type_id = queryOne("select bs_content_id from bc_content where content_id='$content_id'");
//	$description = $tap_name.' 수정';
	//executeQuery("insert into bc_log (id, action, user_id, link_table, link_table_meta , link_table_id, created_time, description) values ($log_id, 'edit', '$user_id', '$content_type_id','$meta_table_id' , '$content_id', '$modified_time', '$description')");

//	// 검색엔진에 등록/
	//$s = new Searcher($db);
	//$s->update($content_id, 'DAS');

	/////////////////////////////////////////////////

	echo json_encode(array(
		'success' => true
	));
}
catch (Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}
?>