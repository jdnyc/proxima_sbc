<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/libs/functions.php');
//require_once('common/common.php');
//require_once('common/functions.php');


$media_id = 4006599; //
$source = $db->queryOne("select path from media where media_id=".$media_id);


$task_id = getNextTaskSequence();

$archive_id = buildDASArchiveID();
$type = 'archive';
$status = 'queue';
$priority = '300';
$creation_datetime = date('YmdHis');

try
{

	// 아카이브 타입 추가
//	$r = $db->exec("insert into media (content_id, media_id, task_id) values ('$media_id', '$archive_id', '$task_id')");

	$r = $db->exec("insert into archive (media_id, archive_id, task_id) values ('$media_id', '$archive_id', '$task_id')");

	$r = $db->exec("insert into task (media_id, id, type, source, status, priority, creation_datetime)
						values ($media_id, $task_id, '$type', '$source', '$status', '$priority', '$creation_datetime')");
}
catch (Exception $e)
{
	echo $e->getMessage();
	echo $db->last_query;
}


?>