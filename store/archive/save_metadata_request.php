<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/MetaData.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/interface/app/ODS_D/save_xml_meta.php');
try
{

	$user_id = $_SESSION['user']['user_id'];
	$type_job = $_POST['job_type'];
	$content_ids = json_decode($_POST['contents'], true);
	$comment = $_POST['comment'];
	$pfr_title = $_POST['new_title'];
	$pfr_start = $_POST['start'];
	$pfr_end = $_POST['end'];
	$creation_datetime = date('YmdHis');
	$v_return = array();
	$v_reslt_arr =	array();
	$results =	array();

	fn_save_meta($content_ids[0],$type_job);
	// if($type_job =='save_xml_meta'){
	// 	require_once($_SERVER['DOCUMENT_ROOT'].'/interface/app/ODS_D/save_xml_meta.php');
	// 	// require_once($_SERVER['DOCUMENT_ROOT'].'/interface/app/ODS_D/client/ExecuteTaskODA.php');
	// 	fn_save_meta($content_ids[0]);
	// }
	// switch($type_job) {
	// 	case 'save_xml_meta':
	// 		fn_save_meta($content_ids[0]);
	// 	//fn_save_meta($content_id);
	// 	break;
	// 	case 'save_text_meta':
	// 		fn_save_meta($content_ids[0]);
	// 	//fn_save_meta($content_id);
	// 	break;
	// 	case 'save_json_meta':
	// 		fn_save_meta($content_ids[0]);
	// 	//fn_save_meta($content_id);
	// 	break;
	// }
	

}
catch (Exception $e)
{
	switch($e->getCode())
	{
		case ERROR_QUERY:
			$msg = $e->getMessage().'( '.$db->last_query . ' )';
		break;

		default:
			$msg = $e->getMessage();
		break;
	}

	die(json_encode(array(
		'success' => false,
		'msg' => $msg
	)));
}

?>