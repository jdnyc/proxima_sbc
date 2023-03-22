<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib.php');
require_once $_SERVER['DOCUMENT_ROOT'].'/libs/functions.php';

$content_id = '19401472';

delete_archive($content_id);

function delete_archive($content_id)
{
	global $db;
	// 니어라인의 미디어 아이디 필요.
	$media_id = $db->queryOne("select media_id from media where content_id = '$content_id' and type = 'nearline'");
	$arch = $db->queryRow("select
							a.media_id, a.archive_id, m.path
						from
							archive a, media m
						where
							a.media_id=".$media_id."
						and
							a.media_id=m.media_id");

	if ( empty($arch) )
	{
		return false;
	}

	$task_id = getNextTaskSequence();
	$archive_id = $arch['archive_id'];
	$type = 'delete';
	$status = 'queue';
	$priority = 300;
	$creation_datetime = date('YmdHis');
	$restore_channel = 'delete_archive';

	$q = "insert into task (id, media_id, type, status, priority, destination, creation_datetime) values ($task_id, $media_id, '$type', '$status', '$priority', '$restore_channel', '$creation_datetime')";
	$r = $db->exec($q);

	$q = "insert into archive (media_id, archive_id, task_id) values ('$media_id', '$archive_id', '$task_id')";
	$r = $db->exec($q);
}
?>