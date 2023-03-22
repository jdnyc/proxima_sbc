<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');
        
global $db;


file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/autoArchive_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] Scheduler excuted'."\r\n", FILE_APPEND);

$query = "select category_id from bc_category_env where arc_method = 'A'";
$categories = $db->queryAll($query);
$cur_time = time();

foreach($categories as $category)
{
    $category_id = $category['category_id'];
    $query = "select content_id, ud_content_id, created_date from bc_content where category_id = '$category_id' and is_deleted='N' order by content_id asc";
    $contents = $db->queryAll($query);
    
    $query = "select arc_period from bc_category_env where category_id = '$category_id'";
    $arc_info = $db->queryRow($query);
    
    foreach($contents as $content)
    {
       
        $arc_period = $arc_info['arc_period'];
        $created_time = strtotime($content['created_date']);

        $content_id = $content['content_id'];
        $ud_content_id = $content['ud_content_id'];
        switch($ud_content_id)
        {
            case 202 :
                $channel = 'tape_archive';
            break;
            case 314 :
                $channel = 'sgl_archive';
            break;
            case 334 :
                $channel = 'news_archive';
            break;
            case 358 :
                $channel = 'nps_archive';
            break;
            case 374 :
                $channel = 'tape_archive';
            break;
            case 397 :
                $channel = 'nps_archive';
            break;
        }
        $query = "select media_id from bc_media where media_type = 'archive' and content_id = '$content_id'";
        $media_id = $db->queryOne($query);
        $query = "select count(media_id) from bc_media where media_type = 'archive' and content_id = '$content_id'";
        $has_media_id = $db->queryOne($query);
        $query = "select count(session_id) from sgl_archive where media_id = '$media_id' ";
        $is_archive = $db->queryOne($query);
       
        if($has_media_id == 0 && $is_archive == 0)
        {
            
                $arc_time = $created_time + (60 * 60 * 24 * (int) $arc_period);
file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/autoArchive_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] arc_time ===> '.$arc_time."\r\n", FILE_APPEND);            
            if($arc_time < $cur_time)
            {
                $task_mgr = new TaskManager($db);
                
                $task_mgr->start_task_workflow($content_id, $channel, 'system');
               
file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/autoArchive_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] content_id = '.$content_id.' channel = '.$channel."\r\n", FILE_APPEND);
            }
        }
    }
}

?>