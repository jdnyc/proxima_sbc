<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$user_id		= $_SESSION['user']['user_id'];
$is_admin		= $_SESSION['user']['is_admin'];
$content_ids	= $_POST['content_ids'];
$bs_content_id = $_POST['bs_content_id'];
$ud_content_id = $_POST['ud_content_id'];

switch($bs_content_id){
	// case WAIT:
	// break;
	case MOVIE:
	case SEQUENCE:
		include_once($_SERVER['DOCUMENT_ROOT'].'/javascript/ext.ux/BatchEditMetaPanel/media.php');
	break;

	case SOUND:
		include_once($_SERVER['DOCUMENT_ROOT'].'/javascript/ext.ux/BatchEditMetaPanel/sound.php');
	break;
	// case DCART:
	// 	include_once($_SERVER['DOCUMENT_ROOT'].'/javascript/ext.ux/BatchEditMetaPanel/media.php');
	// break;

	case DOCUMENT:
		include_once($_SERVER['DOCUMENT_ROOT'].'/javascript/ext.ux/BatchEditMetaPanel/document.php');
	break;

	// case TOPIC:
	// 	include_once($_SERVER['DOCUMENT_ROOT'].'/javascript/ext.ux/BatchEditMetaPanel/topic.php');
	// break;

	case IMAGE:
		include_once($_SERVER['DOCUMENT_ROOT'].'/javascript/ext.ux/BatchEditMetaPanel/image.php');
	break;
	default:
		echo 'Undefined Content type';
	break;

}
?>
