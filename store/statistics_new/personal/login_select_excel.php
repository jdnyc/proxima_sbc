<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
session_start();
$year = $_GET['year'];
$user = $_GET['user'];
if($user)
{
	$add_q = "and user_id = '$user'";
}
else
{
	$add_q = '';
}
$fileName		= iconv('utf-8', 'euc-kr', '기간별로그인횟수');
header(	"Content-type: application/vnd.ms-excel" );	
header(	"Content-Disposition: attachment; filename={$fileName}.xls" );	
header(	"Content-Description: Gamza	Excel Data"	); 
header(	"Content-charset=euc-kr" );	

$excelTable="
<table border=0 cellpadding=1 cellspacing=1 bgcolor='#000000'>
	<tr height='25' align='center' bgcolor='#FFFFFF'>
		<td>월별</td>
		<td>로그인 횟수</td>
	</tr>
";

try
{
	for ( $i = 1; $i < 13; $i++ )
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
		if ( MDB2::isError($each_month) ) throw new Exception($each_month->getMessage());
	
		$count = $each_month;		
		$name = $i.'월';
		
		$excelTable .= "<tr height='25' align='center' bgcolor='#FFFFFF'><td>{$name}</td><td>{$count}</td></tr>";
	}
	$excelTable .= "</table>";

	echo $excelTable;
}
catch (Exception $e)
{
	echo '쿼리 오류: '.$e->getMessage();
}
?>