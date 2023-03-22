<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
$user_id = $_SESSION['user']['user_id'];
$created_time =date('YmdHis');

$tc_store = $_POST['k_tc_store'];
$tc_store_array = json_decode($tc_store);
$used_tc=0;
if($_POST['k_tc_store']=='[]'){
	$used_tc=1;
}


//////////////////////////////////////////////////////////////////////////

try
{
	$ingest_id			= getNextIngestSequence();

	$content_type_id	= $_POST['k_content_type_id'];
	$meta_table_id		= $_POST['k_meta_table_id'];
	$title				= $_POST['c_title'];

	$category_id = '0';
	$category_full_path	= '/0';
	$title				= $db->escape($title);
	$ingest_status = INGEST_READY;

	//인제스트 등록
	$content_insert_q = "insert into ingest (id, category_id, category_full_path, content_type_id, meta_table_id, title, user_id, status, created_time) values ('$ingest_id', '$category_id', '$category_full_path','$content_type_id','$meta_table_id', '$title', '$user_id', '$ingest_status', '$created_time')";
	$content_insert = $mdb->exec($content_insert_q);

	$barcode_field_id=	$db->queryOne("select meta_field_id from meta_field where name like '%TAPE NO%' and meta_table_id='$meta_table_id'");

	foreach ( $_POST as $k=>$v )
	{
		if(preg_match('/^k\_|^c\_/', $k) || strstr($k, 'ext') ) continue;

		if($k == $barcode_field_id)
		{
			$v = strtoupper($v);
		}

		if( !empty($v) )
		{
			//$v = str_replace(array("\r", "\r\n", "\n"), '<br />', $v);
			$v = nl2br($v);
		}

		$v = $db->escape($v);

		$meta_value_insert_q ="insert into ingest_metadata (ingest_id, meta_table_id, meta_field_id, meta_value) values ('$ingest_id','$meta_table_id', '$k','$v')";
		$meta_value_insert = $mdb->exec($meta_value_insert_q);
	}


	if ($used_tc==0)
	{
		for ($i=0; $i<count($tc_store_array); $i++)
		{
			$tc_id = getNextIngestSequence();
			$tc_in= $tc_store_array[$i]->tc_in;
			$tc_out= $tc_store_array[$i]->tc_out;
			$query ="
				insert into ingest_tc_list(INGEST_LIST_ID, ID, TC_IN, TC_OUT)
				values( '$ingest_id',
					'$tc_id',
					'$tc_in',
					'$tc_out')";
			//		print_r($query);
			$insert = $db->exec($query);
		}

	}

	echo json_encode(array(
		'success' => true
	));
}
catch ( Exception $e )
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage().' '.$mdb->last_query
	));
}

?>