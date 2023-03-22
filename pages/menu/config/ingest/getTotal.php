<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$status 		= INGEST_READY;
$start_date		= trim($_POST['start_date']);
$end_date		= trim($_POST['end_date']);
$meta_table_id	= $_POST['meta_table_id'];
$search			= $_POST['search'];

$total = $db->queryOne("
	select
		count(*)
	from
		ingest
	where
		meta_table_id='$meta_table_id' and
		created_time between $start_date and $end_date
	");
if(!empty($search))
{
	$search = trim(strtoupper($search));
	$tape_list = $db->queryAll("select ingest_id from ingest_metadata where meta_value like '%$search%'");

    if(!empty($tape_list))
    {
    	$tape_list_item = array();

    	$search_query ="select
    		count(*)
    	from
    		ingest
    	where
    	";

    	foreach($tape_list as $tape_list_id)
    	{
    		array_push($tape_list_item,'id='.$tape_list_id['ingest_id']);
    	}

    	$search_query .= join(' or ', $tape_list_item);

    	$total = $db->queryOne($search_query);
    }

}

echo json_encode(array(
	'total' => $total
));
?>