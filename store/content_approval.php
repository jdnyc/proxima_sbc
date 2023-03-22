<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');

$content_list_id = json_decode($_POST['content_list'], true);
$mode = $_POST['mode'];
$job = $_POST['job'];;

if($mode == 1){
	/*simple approval*/
	try{
		if($job == 'approve'){
			$query = "
				UPDATE	bc_content
				SET		approval_yn = 'Y'
				WHERE	content_id in (".join(', ',$content_list_id).")
			";
		}elseif($job == 'unapprove'){
			$query = "
				UPDATE	bc_content
				SET		approval_yn = 'N'
				WHERE	content_id in (".join(', ',$content_list_id).")
			";
		}
		
		$db->exec($query);

		echo (json_encode(array(
			'success' => true,
			'msg'	=>	 'Sucessfully'
		)));
	}catch (Exception $e) {
		echo (json_encode(array(
			'success' => false,
			'msg' => $e->getMessage()
		)));
	}
	
	//'msg'	=>	 _text('MSG01009')
	
}elseif ($mode == 2) {
	/* complex approval */

}else{

}

