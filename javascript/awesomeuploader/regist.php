<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');


try
{
	$content_id = $_POST['content_id'];	
	$filename = $_POST['filename'];	
	$filesize = $_POST['filesize'];	
	$path = $_POST['path'];
	//베트남. 첨부파일시 부모 콘텐츠 ID로 판단해서 첨부한다.
	$content_id = $db->queryOne("select parent_content_id from bc_content where content_id='".$content_id."'");

	$media_info = addMedia($content_id , $filename, $filesize ,$path);

	echo json_encode(array(
		'success' => true,
		'msg' => $filename,
		'media_id' => $media_info['media_id'],
		'content_id' => $media_info['content_id']
	));
	
}
catch(Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage() 
	));
}




function addMedia($content_id , $filename, $filesize ,$path){
	global $db;

	$cur_time = date('YmdHis');
	$register = 'uploader';
	$type = 'attach';

	$content_info = $db->queryRow("select * from bc_content where content_id = '$content_id' ");

	$ori_info = $db->queryRow("select * from bc_media where media_type='original' and content_id='$content_id' ");

	//$ori_path_array = explode('/', $ori_info['path']);

	//$ori_filename = array_pop($ori_path_array);
	//$ori_path = implode('/', $ori_path_array);

	//$path = $db->escape($ori_path.'/Attach/'.$filename);
	$path = str_replace(ATTACH_ROOT.'\\','',$path);

	$path =str_replace("\\","/",$path);

	$storage_id = $ori_info['storage_id'];
	if($storage_id == '')
	{
		$storage_id = 0;
	}

	$query = "insert into bc_media (content_id, storage_id, media_type, path, filesize, created_date, reg_type) values ($content_id, '$storage_id', '$type', '$path', '$filesize', '$cur_time', '$register') ";

	//echo $query;

	$r = $db->exec($query);

	$media_id = $db->queryOne("select media_id from bc_media
								where media_type='$type'
								  and created_date='$cur_time'
								  and content_id='$content_id'
								order by media_id desc");

	//$channel = $register;

	//insert_task_query($content_id, $filename, $path, $cur_time, $channel, $m_info['media_id'] );

	return array(
		'media_id' => $media_id,
		'content_id' => $content_id
	);
}

?>