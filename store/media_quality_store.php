<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$content_id = $_REQUEST['content_id'];
$action = $_REQUEST['action'];
$comment = $_REQUEST['comment'];
$grid_data= $_REQUEST['grid_data'];
$media_type = 'original';
$now = date('YmdHis');

if(!$action){

    $is_qc_task_run = $db->queryRow("select * from bc_task where src_content_id=".$content_id." and type='15' order by task_id desc");
    if(!empty($is_qc_task_run) && $is_qc_task_run['status'] != 'complete') {
        //QC
        $data = array();
        die( json_encode(array(
            'success'	=> true,
            'msg' => "QC 작업중입니다."."(".$is_qc_task_run['status'].", ".$is_qc_task_run['progress'].")",
            'data'	=> $data
        )));
    }

	$query = "select mq.*
				from BC_MEDIA_QUALITY mq, BC_MEDIA m 
			   where mq.media_id in (select media_id from bc_media where content_id='$content_id')
				 and mq.media_id=m.media_id
			   order by mq.start_tc, mq.quality_id desc";
	//$query = "select * from BC_MEDIA_QUALITY order by show_order";

	$data = $db->queryAll($query);
	
	$exclude = array();

        echo json_encode(array(
            'success'	=> true,
            'data'	=> $data
        ));
}
else if($action == 'check'){
	$media_id = '';
	foreach($grid_data as $da_ta)
	{
		$da_ta = json_decode($da_ta, true);
		$media_id = $da_ta['media_id'];
		$query = "update bc_media_quality set no_error='1' where quality_id='".$da_ta['quality_id']."'";
		$db->exec($query);
	}
	$qc_count = $db->queryOne("select count(media_id) from bc_media_quality where media_id = '$media_id' and no_error != '1'");
	$query = "update bc_media_quality_info set error_count = '$qc_count' , is_checked = 'Y', review_comment = '$comment', last_modify_date = '$now' where content_id = '$content_id' ";
	$db->exec($query);
	$cmt = $db->queryOne("select review_comment from bc_media_quality_info where content_id = $content_id");

	$msg = _text('MN00011');
	echo json_encode(array(
		'success' => true,
		'msg' => $msg,
		'comment' => $cmt,
		'query' => $query
	));
} else if ($action == 'edit_check') {
	$qc_count = 0;
	
	foreach($grid_data as $da_ta)
	{
		$da_ta = json_decode($da_ta, true);
		$media_id = $da_ta['media_id'];
		$no_error = $da_ta['no_error'];
		$query = "update bc_media_quality set no_error='$no_error' where quality_id='".$da_ta['quality_id']."'";
		$db->exec($query);
		if($no_error == '0') {
		    $qc_count = $qc_count + 1;
		}
	}
	
	$query = "update bc_media_quality_info set error_count = $qc_count , is_checked = 'Y', review_comment = '$comment', last_modify_date = '$now' where content_id = $content_id ";
	$db->exec($query);
	$cmt = $db->queryOne("select review_comment from bc_media_quality_info where content_id = $content_id");
	$msg = _text('MN00011');
	echo json_encode(array(
		'success' => true,
		'msg' => $msg,
		'comment' => $cmt,
		'query' => $query
	));
	
} else if ($action == 'get_cmt') {
    $cmt = $db->queryOne("select review_comment from bc_media_quality_info where content_id = $content_id");
    
    echo json_encode(array(
		'success' => true,
		'comment' => $cmt,
		'query' => $query
    ));
} else if ($action == 'no_error') {
    $query = "update bc_media_quality
        set no_error='1'
        where media_id in (select media_id from bc_media where content_id=".$content_id.")";
    $db->exec($query);
    $msg = _text('MN00011');
	echo json_encode(array(
		'success' => true,
		'msg' => $msg,
		'query' => $query
	));
}else {
	echo json_encode(array(
		'success' => false,
		'msg' => 'undefined action',
		'query' => $query
	));
}
?>