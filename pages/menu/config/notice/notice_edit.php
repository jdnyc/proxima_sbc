<?php
/*필드
	fields: [
		{name: 'id'},
		{name: 'title'},
		{name: 'created_time', type: 'date', dateFormat: 'YmdHis'}
		{name: 'content'}
	]
*/
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');

//$user_id = $_SESSION['user']['user_id'];

$id = $_POST['id'];
$title = $_POST['title'];
$es_title = $db->escape($title);
$content = $_POST['content'];
$es_content = $db->escape($content);
$created_time = date('YmdHis');
try
{//공지사항 내용 수정	
	$update = $db->exec("
		update bc_notice
		set notice_title= '$es_title',
			notice_content='$es_content' ,
			created_date='$created_time'
		where notice_id = '$id'
	");

	echo "{success: true,msg:'수정 성공'}";
}
catch (Exception $e)
{
	echo "{failure: true,msg:'수정 실패'}";
}

?>