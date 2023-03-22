<?php
//영상 편집 의뢰 팝업창에서 목록 수정

session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

$gridName = $_POST['grid_name'];
$action = $_POST['action'];
$values = json_decode($_POST['values'], true);
$user_id	= $_SESSION['user']['user_id'];

try{
	switch($action){
		case 'delete':
			$query = deleteRow($gridName, $values);
		break;
		case 'save':
			$query = saveRow($gridName, $values);
		break;
		case 'update_request':
			$query = updateRequest($values, $editor);
		break;
		case 'create_request':
			$query = createRequest($values, $user_id,$_POST['type_content']);
		break;
	}

	echo json_encode(array(
		'success'		=>	 true,
		'query'			=>	 $query
	));
}catch(Exception $e){
  die(json_encode(array(
	'success' => false,
	'msg' => $e->getMessage()
   )));
	//print_r($e->getMessage());exit;
}

function createRequest($values, $user_id, $type_content){
	global $db;

	$title = $db->escape($values['title']);
	$detail = $db->escape($values['detail']);

	if(_text('MN02088') == $type_content){
		$ord_meta_cd = 'graphic';
	}else{
		$ord_meta_cd = 'video';
	}

	//SEQ_ORD_REQUEST
	$seq_ord_request = getSequence('SEQ_ORD_REQUEST');
	$ord_id = date('Ymd').'POR'.str_pad($seq_ord_request, 5, '0', STR_PAD_LEFT);

	if( empty($values['editor_id']) ){
		$status = 'ready';
		$editor = '';
		$dep_cd = '';
	}else{
		$status = 'working';
		$editor = $values['editor_id'];
		$dep_cd = $db->queryOne("
			SELECT	G.MEMBER_GROUP_ID
			FROM		BC_MEMBER_GROUP_MEMBER G, BC_MEMBER M
			WHERE	G.MEMBER_ID = M.MEMBER_ID
				AND	M.USER_ID = '".$values['editor_id']."' ");
	}

	$query = "
		INSERT	INTO	TB_ORD
		(ORD_ID, ORD_CTT, INPUT_DTM, INPUTR_ID, ORD_STATUS, TITLE, ORD_WORK_ID, DEPT_CD, ORD_META_CD)
		VALUES
		('".$ord_id."','".$detail."','".date('YmdHis')."','".$user_id."','".$status."','".$title."','".$editor."','".$dep_cd."', '".$ord_meta_cd."')
	";

	$db->exec($query);

	return $query;
}

function updateRequest($values, $editor){
	global $db;

	$title = $db->escape($values['title']);
	$detail = $db->escape($values['detail']);

	if( empty($editor['editor_id']) ){
	}else{
		$dep_cd = $db->queryOne("
			SELECT	G.MEMBER_GROUP_ID
			FROM		BC_MEMBER_GROUP_MEMBER G, BC_MEMBER M
			WHERE	G.MEMBER_ID = M.MEMBER_ID
				AND	M.USER_ID = '".$editor['editor_id']."' ");
		$edit_editor = "
			ORD_STATUS = 'working',
			ORD_WORK_ID = '".$editor['editor_id']."',
			DEPT_CD = '".$dep_cd."'
		";
	}

	$query = "
		UPDATE	TB_ORD	SET
		TITLE = '".$db->escape($values['title'])."',
		ORD_CTT = '".$db->escape($values['detail'])."',
		".$edit_editor."
		UPDTR_ID = '".$_POST['user_id']."',
		UPDT_DTM = '".date("YmdHis")."'
		WHERE	ORD_ID = '".$values['ord_id']."'
	";

	$db->exec($query);

	return $query;
}
function deleteRow($gridName, $values){

	global $db;

	$ids = array();

	foreach($values as $row){
		array_push($ids, $row['request_id']);
	}
	$query = " DELETE REQUEST_EDIT WHERE REQUEST_ID IN(".join(',', $ids).") ";
	//$db->exec($query);
	return $query;
}

function saveRow($gridName, $values){

	global $db;

	foreach($values as $row){

	}
}
?>