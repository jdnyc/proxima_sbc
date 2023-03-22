<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
fn_checkAuthPermission($_SESSION);

$types = $mdb->queryAll("select bs_content_id as type, bs_content_title as name from bc_bs_content");

try
{
	foreach ($types as $type)
	{
		$read = $mdb->queryOne("
			select count(l.log_id) 
			from bc_log l, bc_content c 
			where l.content_id = c.content_id 
			and c.bs_content_id = '{$type['type']}' 
			and l.action = 'read'");
		
		$name[] = $type['name'];
		$count[]= $read;
	}

	$data = array(
		'success' => true,
		'data' => array()
	);

	for ($i=0; $i<count($name); $i++)
	{
		array_push($data['data'], array('name' => $name[$i], 'count' => $count[$i]));
	}

	echo json_encode($data);
}
catch (Exception $e)
{
	echo '오류: '.$e->getMessage();
}

?>