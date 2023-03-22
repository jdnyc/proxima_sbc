<?php
	require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
try
{
	$years = $db->queryAll("
	select substr(created_date, 1, 4) as year
	from bc_member group by substr(created_date, 1, 4)");
	unset($y);
	$k=0;
	foreach($years as $v){
		$y[] = $v['year'];
		$k++;
	}
	$data = array(
				'success' => true,
				'data' => array()
			);
	for($i=0; $i<$k;$i++)
	{
	//	array_push($data['data'],array('d'=>2010,'v'=>2010));
		array_push($data['data'], array('d'=>$y[$i],'v'=>$y[$i]));
	}
}
catch (Exception $e)
{
	echo '오류 : '.$e->getMessage();
}
	echo json_encode(
		$data
	);

?>