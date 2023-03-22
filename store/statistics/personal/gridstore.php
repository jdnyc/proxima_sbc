<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
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

try
{
	for($i=1;$i<13;$i++)
	{
		$month = str_pad($i, 2, "0", STR_PAD_LEFT);
		$query = "
		select count(log_id)
		from bc_log
		where action='login' ".
		$add_q."
		and created_date between ".$year.$month."00000000
		and ".$year.$month."31240000";
		$each_month = $mdb->queryOne($query);
		if(empty($each_month))
		{
			$val[$i] = 0;
		}
		else
		{
			$val[$i] = $each_month;
		}
	}
}
catch (Exception $e)
{
	echo '오류 : '.$e->getMessage();
}
	$data = array(
		'success' => true,
		'data' => array(
					  array(
						  'jan' => $val[1],
						  'feb' => $val[2],
						  'mar' => $val[3],
						  'apr' => $val[4],
						  'may' => $val[5],
						  'jun' => $val[6],
						  'jul' => $val[7],
						  'aug' => $val[8],
						  'seb' => $val[9],
						  'oct' => $val[10],
						  'nov' => $val[11],
						  'dec' => $val[12],
						  'user_id' => $user
						   )
						)
					);
	echo json_encode(
		$data
	);

?>