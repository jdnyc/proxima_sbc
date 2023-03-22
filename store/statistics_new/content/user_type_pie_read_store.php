<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');


$types = $mdb->queryAll("select ud_content_id as type, ud_content_title as name from bc_ud_content ");

$k=0;
try
{
	foreach($types as $type)
	{
		$read = $mdb->queryOne("
			select count(l.log_id) 
			from bc_log l, bc_content c 
			where l.content_id = c.content_id 
			and c.ud_content_id = '{$type['type']}' 
			and l.action = 'read'");
		
		$content[] = $type['name'];
		$count[] = $read;
		$k++;	
	}
			
	$data = array(
		'success' => true,
		'data' => array()
	);

	for($i=0; $i<$k; $i++)
	{
		array_push($data['data'],array('content'=>$content[$i],'count'=>$count[$i]));
	}
	echo json_encode($data);
}
catch (Exception $e)
{
	echo '오류: '.$e->getMessage();
}
?>