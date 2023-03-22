<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');


$types = $mdb->queryAll("select ud_content_id as type, ud_content_title as name from bc_ud_content ");

$k=0;
try
{
	foreach($types as $type)
	{
		if( !in_array( $type['type'], $CG_LIST  ) )
			{
				continue;
			}
		$user = $mdb->queryOne("select count(ud_content_id) 
										from bc_content 
										where is_deleted='N' 
										and ud_content_id = '{$type['type']}'");
			
			$name[] = $type['name'];
			$count[] = $user;
			$k++;	
	}

	$data = array(
		'success'	=> true,
		'data'		=> array()
	);

	for ($i=0; $i<$k; $i++)
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