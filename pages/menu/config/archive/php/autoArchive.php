<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

//2014.01.08 수정(임찬모)
//HD 영상 워크플로우 채널 변경(mxf->mov)
//2013.12.16 수정(임찬모)
//SD 영상 워크플로우 채널 분리

//global $db;

//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/autoArchive_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] Scheduler excuted'."\r\n", FILE_APPEND);

$query = "select category_id from bc_category_env where arc_method = 'A'";
$categories = $db->queryAll($query);
$cur_time = time();
$category_count = 0;
$is_auto_archive = $db->queryOne("
		    SELECT CODE
		    FROM BC_CODE
		    WHERE NAME = 'auto_archive_config'
		  ");
try{
    if($is_auto_archive == 'enable') {
	foreach($categories as $category)
	{
	    $category_id = $category['category_id'];

	    $query = "select content_id, ud_content_id, created_date from bc_content where category_id = '$category_id' and is_deleted='N' and archive_date is null order by content_id asc";
	    $contents = $db->queryAll($query);

	    $query = "select arc_period from bc_category_env where category_id = '$category_id'";
	    $arc_info = $db->queryRow($query);

	    foreach($contents as $content)
	    {

		    $arc_period = $arc_info['arc_period'];
	//        file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/test_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] arc_period ===> '.$arc_period."\r\n", FILE_APPEND);            

		    $created_time = strtotime($content['created_date']);

		    $content_id = $content['content_id'];
	//        file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/test_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] '.$content_id."\r\n", FILE_APPEND);       
		    $ud_content_id = $content['ud_content_id'];
		    switch($ud_content_id)
		    {
			    case 202 :
				    $channel = 'tape_sd_archive';
			    break;
			    case 314 :
				    $channel = 'sgl_archive';
			    break;
			    case 334 :
				    $channel = 'news_archive';
			    break;
			    case 358 :
				    $channel = 'nps_archive_mov';
			    break;
			    case 374 :
				    $channel = 'tape_hd_archive';
			    break;
			    case 394 :
				    $channel = 'tape_hd_archive';
			    break;
			    case 397 :
				    $channel = 'nps_archive';
			    break;
		    }

	//        $query = "select media_id from bc_media where media_type = 'archive' and content_id = '$content_id'";
	//       $media_id = $db->queryOne($query);
		$query = "select count(media_id) from bc_media where media_type = 'archive' and content_id = '$content_id'";
		$has_media_id = $db->queryOne($query);
	//        $query = "select count(session_id) from sgl_archive where media_id = '$media_id' ";
	//        $is_archive = $db->queryOne($query);


		    if($has_media_id == 0)
		    {
			    $filesize = $db->queryOne("select filesize from bc_media where media_type = 'original' and content_id = '$content_id'");
			    $arc_time = $created_time + (60 * 60 * 24 * (int) $arc_period);

			    if($arc_time < $cur_time && $filesize > 0)
			    {
				    $task_mgr = new TaskManager($db);

			    	$task_mgr->start_task_workflow($content_id, $channel, 'system');

				    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/autoArchive_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] content_id = '.$content_id.' channel = '.$channel."\r\n", FILE_APPEND);
			    }
		    }
	    }
	    $category_count = $category_count + 1;
	    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/autoArchive_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] category_count = '.$category_count."\r\n", FILE_APPEND);
	    }
    } 
} catch ( Exception $e ) {
	$msg = $e->getMessage();
	file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/autoArchive_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] err_msg = '.$msg."\r\n", FILE_APPEND);
}
//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/autoArchive_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] category_count = '.$category_count."\r\n", FILE_APPEND);
?>