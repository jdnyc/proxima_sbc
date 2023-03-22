<?php
//header('application/x-json; charset=UTF-8');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/db.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$ingest_id 	= $_POST['content_id'];
$meta_field_id 	= $_POST['meta_field_id'];

try
{
	ingestPrintArrayMulti($ingest_id);
}
catch (Exception $e)
{
	echo '{"success":false, "msg": "'.$db->last_query.'"}';
}


function ingestPrintArrayMulti($ingest_id)
{
	global $db;
	$query = "select * from ingest_meta_multi where ingest_id = '$ingest_id' order by sort";
	$meta_data = $db->queryAll($query);
	$checkNum = $db->queryOne("select count(*) from ( $query ) cnt");
	$symbol = '[';
	for ($i=0; $i<$checkNum; $i++)
	{
		/*
		작성자: 박정근
		작성일: 20110314
		내용: 개행문자 처리
		*/
		$value = str_replace("\n", "\\n", $meta_data[$i]['value']);

		$values = substr($value, 0, -1);
		$values .=', "meta_multi_id" : "'.$meta_data[$i]['meta_multi_id'].'" }';

		$symbol .= $values;
		if(($i+1)<$checkNum)
		{
			$symbol .= ',';
		}
	}
	$symbol .= ']';

	$result = '{"success":true,"data":'.$symbol.'}';

	echo $result;

}
?>