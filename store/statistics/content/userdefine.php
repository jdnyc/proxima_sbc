<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');


$types = $mdb->queryAll("select meta_table_id as type, name from meta_table");

unset($userdefine);
unset($count);
$k=0;
foreach($types as $type){
	$user = $mdb->queryOne("select count(meta_table_id) from content where meta_table_id = '{$type['type']}'");
	
	$userdefine[] = $type['name'];
	$count[] = $user;
	$k++;
}

$data = array('success' => true,
					'data' => array()
			);

for($i=0;$i<$k;$i++)
{
	array_push($data['data'],array('userdefine'=>$userdefine[$i],'count'=>$count[$i]));
}

echo json_encode($data);
?>