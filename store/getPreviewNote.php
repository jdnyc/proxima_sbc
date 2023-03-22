<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');


try
{
	$content_id = $_POST['content_id'];
	$query = "
	select
		pm.NOTE_ID,
		pm.CONTENT_ID,
		pn.TALENT,
		pn.SHOTGU,
		pn.SHOTLO,
		pn.SHOTDATE,
		pn.SEASON,
		pn.SHOTMET,
		pn.SHOTOR,
		pn.SOURCE,
		pm.REGISTDATE, 
		pn.SORT,
		pn.TYPE,
		pn.START_TC,
		pn.CONTENT,
		pn.VIGO
	from
		preview_main pm,
		preview_note pn ,
		nps_work_list n,
		( select  max(REGISTDATE) registdate, content_id from preview_main group by  content_id ) maxc
	where
		pm.note_id=pn.note_id
	and
		pm.content_id=maxc.content_id
	and
		pm.registdate=maxc.registdate
	and
		pm.content_id=n.content_id
	and 
		n.work_type='preview'
	and 
		pm.content_id='$content_id'
	";

	$order = " order by pm.note_id desc, pn.sort ";
	
	$result = $db->queryAll($query.$order);

	echo json_encode(array(
		'success' => true,
		'data' => $result
	));
	
}
catch (Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}