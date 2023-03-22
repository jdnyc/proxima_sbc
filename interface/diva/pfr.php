<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/config.php');
//require_once('common/common.php');
//require_once('common/functions.php');


$media_id = 4006599;
$archive_id = 'test-arh-22';
$task_id = getNextTaskSequence();

$type = 'pfr_restore';

$source = '2010/12/12/23/0218/60120252.mov';//.$db->queryOne("select path from media where media_id=".$media_id);
$target = 'x:/Archive/pfr_test.mov';
$parameter = '00:00:02:00 00:00:10:00 ';

$status = 'queue';
$priority = '300';
//$creation_datetime = getCurrentDateTime();
$creation_datetime = date('YmdHis');


try
{
	$r = $db->exec("insert into archive (media_id, archive_id, task_id) values ('$media_id', '$archive_id', '$task_id')");

	$r = $db->exec("insert into task (media_id, id, type, source, target, parameter, status, priority, creation_datetime)
						values ($media_id, $task_id, '$type', '$source', '$target', '$parameter', '$status', '$priority', '$creation_datetime')");
}
catch (Exception $e)
{
	echo $e->getMessage();
	echo $db->last_query;
}


?>