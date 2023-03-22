<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');


$user_type = array(
	'success' => true,
	'user_type' => array()
);

$types = $mdb->queryAll("select meta_table_id as type, name from meta_table");
foreach($types as $type){
	$regist = $mdb->queryOne("select count(meta_table_id) from content where meta_table_id = '{$type['type']}'");
	$read = $mdb->queryOne("select count(L.id) from log L, content C where link_table_id = C.content_id and C.meta_table_id = '{$type['type']}' and L.action = 'read'");
	$down = $mdb->queryOne("select count(L.id) from log L, content C where link_table_id = C.content_id and C.meta_table_id = '{$type['type']}' and L.action = 'download'");
	
	array_push($user_type['user_type'], array('type'=>$type['name'], 'regist'=>$regist, 'read' =>$read, 'download'=>$down));
}

echo json_encode(
	$user_type
);

//print_r($user_type);
//타입 / 등록 / 조회 / 다운로드
//movie   1       2       3

?>

