<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/config.php');
//소재만 갈수 있도록 수정함. 20110223 도훈 수정
//$metas= $dbDas->queryAll("select * from meta_table where content_type_id ='506' order by name asc");
$metas= $dbDas->queryAll("select * from meta_table where content_type_id ='506' and meta_table_id='81767' order by name asc");
$data = array(
	'success' => true,
	'data' => array()
);
foreach($metas as $meta)
{
	array_push(
		$data['data'],
		array(
			'content_type_id'		=> $meta['content_type_id']
			,'meta_table_id'		=> $meta['meta_table_id']
			,'name'					=> $meta['name']
		)
	);
}
echo json_encode(
	$data
);
?>
