<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

try
{
	$user_id = $_SESSION['user']['user_id'];
	if( empty($user_id) || $user_id=='temp' ) throw new Exception("재로그인 해주세요");

	$content_id = $_POST['content_id'];
	$content_ids = json_decode($_POST['content_ids'],true);

	if(!$content_ids)  throw new Exception("파라미터 오류");

	foreach($content_ids as $content)
	{
		$content_id = $content['content_id'];
		$ud_content_id =$content['ud_content_id'];

		$mediaList = $db->queryAll("select * from bc_media where content_id= '$content_id' order by media_id");

		$delInfo = $db->queryRow("select * from DELETE_CONTENT_LIST  content_id= '$content_id'");

		foreach($mediaList as $media)
		{
			if( $media[media_type] == 'original'){
				$target_path = $media[path];
			}

			if( $media[media_type] == 'proxy'){
				$proxy_media = $media;
			}
		}

		$root = $db->queryOne("select s.path from bc_ud_content_storage us,bc_storage s where  us.us_type='backup' and us.storage_id=s.storage_id and us.ud_content_id='$ud_content_id'");

		//$filename = "//192.168.10.207/Storage/".'EBS_SD원본'.".mov";
		$target_path_conv = iconv( "UTF-8", "EUC-KR", $target_path );
		$full_path = $root.'/'.$target_path_conv;

	//	if(empty($root)) throw new Exception("백업스토리지 정보가 없습니다");

		if( $delInfo['created_date'] < date('YmdHis', strtotime("-10 day") ) )  throw new Exception("파일이 존재하지 않습니다.");
	//	if ( !file_exists($full_path) ) throw new Exception("파일이 존재하지 않습니다.");

		//복원 워크플로우 등록

		$channel = 'recovery';
		$task = new TaskManager($db);
		$task_id = $task->start_task_workflow($content_id, $channel, $user_id );

		if($task_id){

			if( $proxy_media['status'] == '1' ){
				$channel = 'recovery_proxy';
				$task = new TaskManager($db);
				$task_id = $task->start_task_workflow($content_id, $channel, $user_id );

			}

			$r = $db->exec("update bc_content set is_deleted='N' where content_id='$content_id'");
			$r = $db->exec("delete from DELETE_CONTENT_LIST where content_id='$content_id' ");
			//검색엔진 재 등록
		}
	}

	$msg = '성공';
	echo json_encode(array(
		'success' => true,
		'msg'=> $msg
	));
}
catch (Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg'=> $e->getMessage()
	));
}
?>