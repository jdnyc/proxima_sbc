<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
    
global $db;

$ud_content_id = $_POST['ud_content_id'];
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];

$limit = $_POST['rows'];
if(empty($limit))
{
    $limit = 10;
}
$start = $_POST['page'];
if(empty($start))
{
    $start = 1;
}

$offset = ($start-1)*$limit;

if(empty($ud_content_id))
{
    $ud_content_id = '358';
}


$query = "select * from bc_content where ud_content_id = '$ud_content_id'";
$db->setLimit($limit, $offset);
$contents = $db->queryAll($query);

$query = "select count(*) from bc_content 
           where ud_content_id = '$ud_content_id'";
$get_total = $db->queryOne($query);

$items = array();
foreach($contents as $content)
{
    $category_id = $content['category_id'];
    $query = "select arc_method from bc_category_env where category_id = '$category_id'";
    $arc_method = $db->queryOne($query);
    
    $content_category = $content['category_id'];
    $query = "select category_title from bc_category where category_id = '$content_category'";
    $category_title = $db->queryOne($query);

    if($arc_method == 'A')
    {
        $arc_status = '자동';
        $status = '자동등록대기';
    }
    else if($arc_method == 'M')
    {
        $arc_status = '수동대기';
        $status = '수동등록대기';
        $complete_datetime = '-';
    }
    else    
    {
        $arc_status ='아카이브제외';
        $status = '아카이브제외';
        $complete_datetime = '-';
    }

    $query = "select ud_content_title from bc_ud_content where ud_content_id = '$ud_content_id'";
    $ud_content_title = $db->queryOne($query);
    
    $content_id = $content['content_id'];
    
    $query = "select count(sgl.media_id) from sgl_archive sgl, bc_media bm
               where sgl.media_id = bm.media_id
                 and bm.content_id = '$content_id'
                 and bm.media_type = 'archive'"; 
    $is_archive = $db->queryOne($query);
    
    if($is_archive == 0)
    {
        $status = '자동등록대기';
        $complete_datetime ='';        
    }
    else
    {
        $query = "select sgl.task_id from sgl_archive sgl, bc_media bm
                   where sgl.media_id = bm.media_id
                     and bm.content_id = '$content_id'
                     and bm.media_type ='archive'";
        $get_task_id = $db->queryOne($query);
 //       echo "task_id===>".$get_task_id;
        $query = "select complete_datetime, status from bc_task where task_id = '$get_task_id'";
        $task_info =  $db->queryRow($query);
        $status = $task_info['status'];
        switch($status)
        {
            case 'complete' :
                $status = '등록완료';
                $complete_datetime = $task_info['complete_datetime'];
            break;
            
            case 'error' :
                $status = '등록에러';
                $complete_datetime = '-';
            break;
            
            case 'queue' :
                $status = '등록대기';
                $complete_datetime = '-';
            break;
        }
    }
    $item = array(
        'seperator' => $arc_status,
        'content_sep' => $ud_content_title,
        'category_title' =>$category_title,
        'title' => $content['title'],
        'complete_datetime' => $complete_datetime,
        'status' => $status
    );
        
    array_push($items, $item);
}

    $result = array();
    
    $result["total"] = $get_total;
    $result["rows"] = $items;

    echo json_encode($result);

?>
