<?php
//header('application/x-json; charset=UTF-8');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/db.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$content_id 	= $_POST['content_id'];
$meta_field_id 	= $_POST['usr_meta_field_id'];

$type 	= $_POST['type'];
try
{
	if($_POST['action'] == 'save'){

		$values = json_decode($_POST['json_value'], true );

		if(!$values) throw new Exception('디코딩 오류');

		foreach($values as $row)
		{
			foreach($row as $key => $val)
			{
				if( empty($val) )  throw new Exception('값이 비어있습니다.');
			}
		}

		$field_check = $db->queryRow("select * from BC_USR_META_VALUE where CONTENT_ID='$content_id' and USR_META_FIELD_ID='$meta_field_id'");
		$value = $db->escape( json_encode($values) );
		if( empty($field_check) ){
			$content_info = $db->queryRow("select * from bc_content where content_id='$content_id'");
			//신규 인서트
			$r = $db->exec("insert into BC_USR_META_VALUE ( CONTENT_ID,UD_CONTENT_ID,USR_META_FIELD_ID,USR_META_VALUE) values ('$content_id','{$content_info[ud_content_id]}','$meta_field_id','$value') ");
		}else{
			//업데이트
			$r = $db->exec("update BC_USR_META_VALUE set USR_META_VALUE='$value' where CONTENT_ID='$content_id' and USR_META_FIELD_ID='$meta_field_id'");
		}

		echo json_encode(array(
			'success' => true,
			'msg' => '저장완료하였습니다'
		));

	}else{
		if($type == 'json' ){
			$query = "select * from bc_usr_meta_value where content_id = '$content_id' and usr_meta_field_id='$meta_field_id' ";
			$meta_data = $db->queryRow($query);
			$value = $meta_data['usr_meta_value'];
			//if( empty($value) ) $value = '{}';

			echo '{"success":true, "data": '.$value.' }';
		}else{
			printArrayMulti($content_id);
		}
	}

}
catch (Exception $e)
{
	echo '{"success":false, "msg": "'.$db->last_query.'"}';
}

function paaarintArrayMulti($content_id)
{
	global $db;
	$query = "select * from bc_meta_multi_xml where content_id = '$content_id' order by show_order";
	$meta_data = $db->queryAll($query);

	$data = array();
	for ($i=0; $i<count($meta_data); $i++)
	{

		$xml = simplexml_load_string( $meta_data[$i]['xml_value']);

		$xml->addChild('meta_value_id', $meta_data[$i]['meta_multi_xml_id']);


		foreach($xml as $k => $v)
		{
			$data[$i][$k] =htmlspecialchars_decode($v);
		}
	}

	echo json_encode(array(
		'success' => true,
		'data' => $data
	));
}
?>