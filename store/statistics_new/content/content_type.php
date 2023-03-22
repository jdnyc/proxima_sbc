<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');


$content_type = array(
	'success' => true,
	'c_type' => array()
);

$types = $mdb->queryAll("select content_type_id as type, name from content_type");

foreach($types as $type){
	$regist = $mdb->queryOne("select count(content_id) from content where content_type_id = '{$type['type']}'");

	$read = $mdb->queryOne("select count(L.id) from log L, content C where link_table_id = C.content_id and C.content_type_id = '{$type['type']}' and L.action = 'read'");
	$down = $mdb->queryOne("select count(L.id) from log L, content C where link_table_id = C.content_id and C.content_type_id = '{$type['type']}' and L.action = 'download'");

	array_push($content_type['c_type'], array('type'=>$type['name'], 'regist'=>$regist, 'read' =>$read, 'download'=>$down));
	
}

echo json_encode(
	$content_type
);

//print_r($content_type);
//타입 / 등록 / 조회 / 다운로드
//movie   1       2       3

?>

