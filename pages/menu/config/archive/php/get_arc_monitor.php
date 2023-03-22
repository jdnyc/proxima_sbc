<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
    
global $db;

$ud_content_id = $_GET['ud_content_id'];
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];

$arc_type =$_POST['arc_type'];
$arc_status = $_POST['arc_status'];

if(empty($arc_type))
{
    $arc_type = 'all';
}
if(empty($arc_status))
{
    $arc_status = 'all';
}

$index = $_POST['index'];

$limit = $_POST['rows'];
if(empty($limit))
{
    $limit = 20;
}
$start = $_POST['page'];
if(empty($start))
{
    $start = 1;
}

$offset = ($start-1)*$limit;

// 아카이브 된 컨텐츠만 선택(ud_content_id 가 입력값과 같고, archive 타입이 있으며, 삭제가 안된 컨텐츠)
$query = "select bc.content_id from bc_content bc, bc_media bm
           where bc.ud_content_id = $ud_content_id and bc.content_id = bm.content_id 
             and bm.media_type = 'archive' and bc.is_deleted = 'N'";
$db->setLimit($limit, $offset);
$arc_content_ids = $db->queryAll($query);
$results = array();
foreach($arc_content_ids as $arc)
{
    $content_id = $arc['content_id'];
    $query = "select task_id from bc_task 
               where source = (select distinct(path) from bc_media where content_id = $content_id and media_type ='archive') and type=110";
    $task_id = $db->queryOne($query);
	
if(!empty($task_id))
{
    $query = "select count(*) from sgl_archive where task_id=$task_id";

    $is_archive = $db->queryOne($query);
    if($is_archive != 0)
    {
        $query = "select distinct(bt.task_id), bc.title, bc.category_full_path, bt.progress,
                         bt.status, bt.start_datetime, bt.complete_datetime, bt.creation_datetime
                  from bc_content bc, bc_media bm, bc_task bt
                 where bc.content_id = $content_id and bt.task_id = $task_id
                   and bt.type = 110";
        $arc_info = $db->queryRow($query);
        
        if($arc_info['progress'] != 100 && $arc_info['status'] == 'complete' )
        {
            $arc_info['arc_status'] = '에러';
        }
        else if($arc_info['progress'] == 100 && $arc_info['status'] == 'complete')
        {
            $arc_info['arc_status'] = '완료';
        }
        else if($arc_info['status'] == 'processing')
        {
            $arc_info['arc_status'] = '진행중';
        }
        
	$item = array(
		'task_id' => $arc_info['task_id'],
		'title' => $arc_info['title'],
		'complete_datetime' => $arc_info['complete_datetime'],
		'creation_datetime' => $arc_info['creation_datetime'],
		'start_datetime' => $arc_info['start_datetime'],
		'progress' => $arc_info['progress'],
		'status' => $arc_info['arc_status']
	);
	
        array_push($results, $item);
    }
}
}

$result = array();
    
$result['total'] = count($results);
$result['rows'] = $results;

echo json_encode($result);
?>
