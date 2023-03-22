<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');


$types = $mdb->queryAll("select meta_table_id as type, name from meta_table");

unset($content);
unset($count);
$k=0;
foreach($types as $type){
	$read = $mdb->queryOne("select count(L.id) from log L, content C where link_table_id = C.content_id and C.meta_table_id = '{$type['type']}' and L.action = 'read'");
	
	$content[] = $type['name'];
	$count[] = $read;
	$k++;	
}

$data = array('success' => true,
					'data' => array()
			);

for($i=0;$i<$k;$i++)
{
	array_push($data['data'],array('content'=>$content[$i],'count'=>$count[$i]));
}

echo json_encode($data);
?>