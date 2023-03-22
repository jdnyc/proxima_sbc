<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
fn_checkAuthPermission($_SESSION);
//print_r($_SESSION);
$user_id		= $_SESSION['user']['user_id'];
$is_admin		= $_SESSION['user']['is_admin'];
$content_id		= $_POST['content_id'];
$editing		= $_POST['editing'];
$mode		= $_POST['mode'];

$content_data = $db->queryRow("select bs_content_id, ud_content_id from bc_content where content_id=$content_id");
$bs_content_id	= $content_data['bs_content_id'];
$meta_table		= $content_data['ud_content_id'];

if( strtolower($user_id) =='temp' || empty($user_id) ) {
	//throw new Exception(_text('MSG02041'));//'세션이 만료되어 로그인이 필요합니다.'
	HandleError(_text('MSG02041'));
}

if ( ! checkAllowUdContentGrant($user_id, $meta_table, GRANT_READ)) {
	HandleError(_text('MSG01002'));//권한이 지정되지 않았습니다. HandleError('읽기 권한이 없습니다.');
}

if ( empty($content_id) ){
	HandleError(_text('MSG00048'));//선택되어진 콘텐츠가 없습니다 HandleError('콘텐츠를 선택하세요.');
}

// content테이블에 read기록 합산. +1)
if (in_array($content_data['ud_content_id'], $CG_LIST)) {
	$mode = 'cg';
}

// log테이블에 기록 남김 /function 으로 변경 2011-04-11 by 이성용
$action = 'read';
$user_id = $_SESSION['user']['user_id'];
$description = '';
insertLog($action, $user_id, $content_id, $description);

switch($bs_content_id){
	case MOVIE:
	case SEQUENCE:
		include_once($_SERVER['DOCUMENT_ROOT'].'/javascript/ext.ux/DetailPanel/media.php');
	break;

	case SOUND:
		include_once($_SERVER['DOCUMENT_ROOT'].'/javascript/ext.ux/DetailPanel/sound.php');
	break;

	case DOCUMENT:
		include_once($_SERVER['DOCUMENT_ROOT'].'/javascript/ext.ux/DetailPanel/document.php');
	break;

	case IMAGE:
		include_once($_SERVER['DOCUMENT_ROOT'].'/javascript/ext.ux/DetailPanel/image.php');
	break;
	default:
		echo 'Undefined Content type';
	break;

}
?>
