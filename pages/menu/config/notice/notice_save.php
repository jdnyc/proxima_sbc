<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');

//$user_id = $_SESSION['user']['user_id'];

$title = $_POST['title'];
$es_title =  $db->escape($title);
$content = $_POST['content'];
$es_content = $db->escape($content);
$created_time = date('YmdHis');
$id = getNextNoticeSequence();

try
{//새 공지사항 글 db insert	
	$query ="
		insert into bc_notice
		values( '$id',
			'$es_title' ,
			'$es_content' ,
			'$created_time') ";
	$insert = $db->exec($query);

	echo "{success: true, msg:'저장 성공'}";
}
catch (Exception $e)
{
	echo "{failure: true, msg:'저장 실패 : ".$e->getMessage()."'}";
}
?>