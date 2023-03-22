<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$user_id = $_SESSION['user']['user_id'];
$created_time = date('YmdHis');

$tc_store = $_POST['k_tc_store'];
$tc_store_array = json_decode($tc_store);
$used_tc = 0;
if ($_POST['k_tc_store'] == '[]')
{
	$used_tc = 1;
}
$id = $_POST['k_content_id'];

try
{

	//	print_r($content_id);
	$content_type_id	= $_POST['k_content_type_id'];
	$meta_table_id		= $_POST['k_meta_table_id'];
	$title				= $_POST['c_title'];
	$title				= $db->escape($title);

	//콘텐츠 수정
	$content_update = $mdb->exec("update ingest set title='$title' where id='$id'");

	$is_listview = $db->queryAll("select meta_field_id from meta_field where type='listview'");

	foreach ( $_POST as $k=>$v )
	{
		if ( preg_match('/^k\_|^c\_/', $k) || strstr($k, 'ext') ) continue;

		if ( !empty($v) )
		{
			//$v = str_replace(array("\r", "\r\n", "\n"), '<br />', $v);
			//$v = nl2br($v);
		}

		$v = $db->escape($v);

		$meta_value_id_chk = $db->queryOne("select meta_value_id from ingest_metadata where meta_table_id='$meta_table_id' and meta_field_id='$k'");


	//	print_r($meta_value_id_chk);
		if ( $meta_value_id_chk )
		{
			if( $k==4037606 || $k==4037607 )
			{
				if($v=='' || empty($v) )
				{
					continue;
				}
				else
				{
					$update_meta = $mdb->exec("update ingest_metadata set meta_value='$v' where ingest_id='$id' and meta_table_id='$meta_table_id' and meta_field_id='$k'");
				}
			}
			else
			{
				$update_meta = $mdb->exec("update ingest_metadata set meta_value='$v' where ingest_id='$id' and meta_table_id='$meta_table_id' and meta_field_id='$k'");
			}
		}
		else
		{
			$meta_field_id = $k;
			$mdb->exec("insert into ingest_metadata (ingest_id, meta_table_id, meta_field_id,meta_value)values('$id', '$meta_table_id', '$meta_field_id', '$v')");
		}

	}


	$tc_del_query = "select * from ingest_tc_list where ingest_list_id='$id' ";//삭제 쿼리
	$minus = "minus select * from ingest_tc_list where id=";



	for($i=0; $i< count($tc_store_array);$i++)//기존 타임코드 업데이트
	{
		$tc_in= $tc_store_array[$i]->tc_in;
		$tc_out= $tc_store_array[$i]->tc_out;
		$old_id = $tc_store_array[$i]->id;
		if(!empty($old_id))
		{
			$tc_del_query .= $minus.$old_id." ";
		}
	}

	$tc_list = $db->queryAll($tc_del_query);

	foreach($tc_list as $tc)
	{
		$tc_id = $tc['id'];
		$delete_tc = $db->exec("
			delete
			from ingest_tc_list
			where id ='$tc_id'
		");
	}

	for($i=0; $i< count($tc_store_array);$i++)//새 타임코드 추가
	{
		$tc_in= $tc_store_array[$i]->tc_in;
		$tc_out= $tc_store_array[$i]->tc_out;
		$old_id = $tc_store_array[$i]->id;

		$tc_id = getNextIngestSequence();

		if(empty($old_id))
		{
			$query ="
				insert into ingest_tc_list(INGEST_LIST_ID, ID, TC_IN, TC_OUT)
				values( '$id',
					'$tc_id',
					'$tc_in',
					'$tc_out')";
			$insert = $db->exec($query);
		}
		else
		{
			$query ="
				update ingest_tc_list set tc_in='$tc_in', tc_out='$tc_out'
				where id='$old_id'";
			$insert = $db->exec($query);
		}
	}

//
//
//		$delete_tc = $db->exec("delete from ingest_tc_list where ingest_list_id ='$id'");
//
//		for ( $i=0; $i< count($tc_store_array); $i++ )
//		{
//			$tc_id = getNextIngestSequence();
//			$tc_in = $tc_store_array[$i]->tc_in;
//			$tc_out = $tc_store_array[$i]->tc_out;
//			$query ="insert into ingest_tc_list (INGEST_LIST_ID, ID, TC_IN, TC_OUT) values( '$id', '$tc_id' ,'$tc_in', '$tc_out')";
//		//	print_r($query);
//			$insert = $db->exec($query);
//		}
//

	echo "{success: true, msg:'수정 성공'}";
}
catch ( Exception $e )
{
	echo "{failure: true, msg:'수정 실패 : ".$e->getMessage().$db->last_query."'}";
}