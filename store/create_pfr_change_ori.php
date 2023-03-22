<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

try
{
	$user_id = $_SESSION['user']['user_id'];

	$content_id = $_POST['content_id'];
	$tc_start	= $_POST['start'];
	$tc_end		= $_POST['end'];

	addPFRMedia($content_id, $tc_start, $tc_end);

	echo '{"success":true}';
}
catch (Exception $e)
{
	echo $e->getMessage().' '.$db->last_query;
}

function addPFRMedia($content_id, $tc_start, $tc_end)
{
	global $db;

	$tc_start	*= 30; //프레임단위로 변경
	$tc_end		*= 30; //프레임단위로 변경

	$pfr_cnt		= $db->queryOne("select count(*)+1 from bc_media where content_id=$content_id and media_type like 'pfr%'");
	$ud_content_id	= $db->queryOne("select ud_content_id from bc_content where content_id=".$content_id);

	$cur_time = date('YmdHis');
	$register = $_SESSION['user']['user_id'];
	$storage_id = 0;
	$media_type = 'pfr_original';

	$orginal_media_path = $db->queryOne("select path from bc_media where media_type='original' and content_id=".$content_id);
	$pfr_media_path = substr($orginal_media_path, 0, strrpos($orginal_media_path, '.')).'_pfr_'.$pfr_cnt.substr($orginal_media_path, strrpos($orginal_media_path, '.'));

	$r = $db->exec("insert into bc_media 
						(content_id, storage_id, media_type, path, filesize, created_date, reg_type, vr_start, vr_end) 
					values 
						($content_id, $storage_id, '$media_type', '$pfr_media_path', '$filesize', '$cur_time', '$register', '$tc_start', '$tc_end')");

	//task 등록
	$channal ='PFR_CHANGE_ORI_'.$ud_content_id;

	//echo $channal;
	insert_task_query($content_id, $orginal_media_path, $pfr_media_path, $cur_time, $channal);

//	$db->exec("UPDATE BC_MEDIA SET STATUS='-1', media_type='original_delete' WHERE MEDIA_TYPE='original' AND CONTENT_ID=".$content_id);
//	$db->exec("UPDATE BC_MEDIA SET STATUS='-1', media_type='proxy_delete' WHERE MEDIA_TYPE='proxy' AND CONTENT_ID=".$content_id);

	return true;
}
?>