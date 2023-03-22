<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');


$types = $mdb->queryAll("select content_type_id as type, name from content_type");

	unset($t);
	unset($d);
	$k=0;
try
{
	foreach($types as $type){
		$down = $mdb->queryOne("select count(L.id) from log L, content C where link_table_id = C.content_id and C.meta_table_id = '{$type['type']}' and L.action = 'download'");
		$t[] = $type['name'];	//name
		$d[] =$down;	//count
		$k++;
	}
}
catch (Exception $e)
{
	echo '오류: '.$e->getMessage();
}
	$data = array('success'=>true,
						'data'=>array()		
				);				
	for($i=0;$i<$k;$i++)
	{
		array_push($data['data'],array('content'=>$t[$i],'down'=>$d[$i]));
	}
	echo json_encode($data);
?>