<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$status 		= INGEST_READY;
$start_date		= trim($_POST['start_date']);
$end_date		= trim($_POST['end_date']);
$meta_table_id	= $_POST['meta_table_id'];

$total = $db->queryOne("
		select
			count(*)
		from
			content
		where
			meta_table_id='$meta_table_id'
		and
			LAST_MODIFIED_TIME is not null
		and LAST_MODIFIED_TIME between $start_date and $end_date
		order by created_time desc
		");


echo json_encode(array(
	'total' => $total
));
?>