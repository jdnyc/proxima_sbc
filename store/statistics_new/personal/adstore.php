
<?php
	require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
try
{
	$select_user= $mdb->queryAll("select user_id, user_nm from bc_member");

	unset($ui);
	unset($un);
	$k=0;
	foreach($select_user as $choosen){		
		$ui[] =$choosen['user_id'];	//user_id
		$un[] =$choosen['user_nm'];	//name
		$k++;
	}
	$data = array('success'=>true,
						'data'=>array()		
				);				
	for($i=0;$i<$k;$i++)
	{
		array_push($data['data'],array('d'=>$un[$i].'('.$ui[$i].')','v'=>$ui[$i]));
	}
	echo json_encode($data);
}
catch (Exception $e)
{
	echo '오류 : '.$e->getMessage();
}
?>



