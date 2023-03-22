<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');


try
{
	$k=0;
	$types = $mdb->queryAll("select bs_content_id as type, bs_content_title as name from bc_bs_content");

	foreach( $types as $type )
	{
		$regist = $mdb->queryOne("select count(*) from bc_content where bs_content_id='{$type['type']}' and is_deleted='N'");
		$del_count = $mdb->queryOne("select count(*) from bc_content where bs_content_id='{$type['type']}' and is_deleted='N'");

		$name[] = $type['name'];
		$count[] = $regist;
//		$count[] = $regist-$del_count;
//		if($count[$k] < 0)
//		{
//			$count[$k]=0;
//		}
		$k++;
	}
	$data = array(
		'success' => true,
		'data' => array()
	);
					
	for($i=0; $i<$k; $i++)
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