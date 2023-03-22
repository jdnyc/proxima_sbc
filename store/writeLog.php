<?php
//프록시 다운로드에 관련된 로그를 기록하기 위하여 만들어 진 페이지 입니다.
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$user_id = $_SESSION['user']['user_id'];
$media_id = $_POST['media_id'];
$media_type = $_POST['media_type'];

$content_ids = $_POST['content_ids'];

try
{
	if( !empty($media_id) )
	{
		if(empty($media_id))
		{
			die('error : Parameter($media_id) is empty');
		}
		else if( empty($user_id) )
		{
			die('error : Parameter($user_id) is empty');
		}

		$content_id = $mdb->queryOne("select content_id from bc_media where media_id=$media_id");

		$action = 'download';
		$description = "$media_type($media_id)";
		insertLog($action, $user_id, $content_id, $description);
	}
	else if( !empty($content_ids) )
	{
		if( empty($user_id) )
		{
			die('error : Parameter($user_id) is empty');
		}
		$contents = $db->queryAll("select content_id, title from bc_content where content_id in ( ".$content_ids." )");

		foreach($contents as $content )
		{
			$title = $db->escape($content['title']);
			$content_id = $content['content_id'];
			$action = 'download';
			$description = "$media_type($title)";
			insertLog($action, $user_id, $content_id, $description);
		}
	}

	
}
catch (Exception $e)
{
	echo 'error: '.$e->getMessage();
}

?>