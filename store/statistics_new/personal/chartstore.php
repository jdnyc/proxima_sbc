
<?php
	require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
	require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');
	$year = $_POST['year'];
	if(empty($year))
	{
		$year = date('Y');
	}

	$user = $_POST['user'];
	if($user)
	{
		$add_q = "and user_id = '$user'";
	}
	else
	{
		$add_q = '';
	}

	$data = array(
		'success' => true,
		'data' => array()
	);

	$mon_text_array = array(
		'jan',
	  'feb',
	  'mar',
	  'apr',
	  'may',
	  'jun',
	  'jul',
	  'aug',
	  'seb',
	  'oct',
	  'nov',
	  'dec'
	);


try
{
	for($i = 1; $i < 13; $i++){
		$month = str_pad($i, 2, "0", STR_PAD_LEFT);
		$query = "
		select count(log_id)
		from bc_log
		where action='login' ".
		$add_q."
		and created_date between ".$year.$month."00000000
		and ".$year.$month."31240000";
		$each_month = $mdb->queryOne($query);
		if(empty($each_month) || $each_month == 0)
		{
			$each_month = 0;
		}
		array_push($data['data'], array('name'=>$mon_text_array[$i-1] , 'visit'=>$each_month));
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

