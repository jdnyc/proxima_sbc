<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');

$types = $mdb->queryAll("select content_type_id as type, name from content_type");

unset($name);
unset($count);
$k=0;
foreach($types as $type){
			$read = $mdb->queryOne("select count(L.id) from log L, content C where L.link_table_id = C.content_id and C.content_type_id = '{$type['type']}' and L.action = 'read'");
			
			$name[] = $type['name'];
			$count[]= $read;
			$k++;
}

$data = array(
		'success' => true,
		'data' => array()
			);

for($i=0;$i<$k;$i++)
{
		array_push($data['data'], array(
											'name' => $name[$i],
											'count' => $count[$i]
		));
}

echo json_encode(
	$data
);
?>