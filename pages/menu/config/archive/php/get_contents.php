<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
    
global $db;

if (!empty($_POST['start_date']))
{
    $start_date = $_POST['start_date'];
}
else 
{
    $start_date = date("Ymd", mktime(0, 0, 0, date("m"), date("d") - 30, date("Y")));
}
if (!empty($_POST['end_date']))
{
    $end_date = $_POST['end_date'];
}
else
{
    $end_date = date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
}

$start_date = str_pad($start_date, 14, '0', STR_PAD_RIGHT);
$end_date = str_pad($end_date, 14, '0', STR_PAD_RIGHT);

$category_id = $_POST['category_id'];
//$category_id = 467;



    $query = "select bc.* from bc_content bc, (select bm.content_id from sgl_archive sa, bc_media bm where sa.media_id = bm.media_id) tmp
           where bc.category_id = $category_id 
             and (bc.created_date between '$start_date' and '$end_date')
             and bc.category_id != tmp.content_id";
    $infos = $db->queryAll($query);

    $query = "select count(bc.*) from bc_content bc, (select bm.content_id from sgl_archive sa, bc_media bm where sa.media_id = bm.media_id) tmp
           where bc.category_id = $category_id 
             and (bc.created_date between '$start_date' and '$end_date')
             and bc.category_id != tmp.content_id";
    $get_total = $db->queryOne($query);

    $items = array();

//print_r($infos); 
    foreach ($infos as $info)
    {
        $cat_id = $info['category_id'];
        $query = "select category_title from bc_category where category_id = $cat_id";
        $media_info = $db->queryRow($query);
        $ud_con_id = $info['ud_content_id'];
        $query = "select ud_content_title from bc_ud_content where ud_content_id = $ud_con_id";
        $ud_con_title = $db->queryRow($query);

        $item = array(
            'content_id' => $info['content_id'],
            'ud_content' => $ud_con_title['ud_content_title'],
            'category_title' => $media_info['category_title'],
            'content_title' => $info['title'],
            'created_date' => $info['created_date']
        );
        array_push($items, $item);
    }


$result = array();

$result["total"] = $get_total;
$result["rows"] = $items;


echo json_encode($result);
?>
