<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
session_start();
$year = $_GET['year'];
$user = $_GET['user'];
if($user == 'null'){
	$user = "";
}else{
	$user = "and user_id= '$user' ";
}

$fileName		= iconv('utf-8', 'euc-kr', '기간별로그인횟수');
header(	"Content-type: application/vnd.ms-excel" );
header(	"Content-Disposition: attachment; filename={$fileName}.xls" );
header(	"Content-Description: Gamza	Excel Data"	);
header(	"Content-charset=euc-kr" );

$excelTable="
<table border=1 cellpadding=1 cellspacing=1 bgcolor='#000000'>
	<tr height='25' align='center' bgcolor='#FFFFFF'>
		<td bgcolor='#FFFFBB'>".iconv('utf-8', 'euc-kr', '월별')."</td>
		<td bgcolor='#FFFFBB'>".iconv('utf-8', 'euc-kr', '로그인 횟수')."</td>
	</tr>
";

try
{
	for ( $i = 1; $i < 13; $i++ )
	{
		if($i<10){
			$month = "0".$i;
		}else{
			$month = $i;
		}
//		$month = str_pad($i, 2, "0", STR_PAD_LEFT);
		$query = "select
						count(log_id)
					from
						bc_log
					where
						action='login'
					$user
					and
						created_date between ".$year.$month."00000000 and ".$year.$month."31240000";
		$each_month = $mdb->queryOne($query);
		if ( MDB2::isError($each_month) ) throw new Exception($each_month->getMessage());

		$count = $each_month;
		$name = $i.'월';

		$excelTable .= "<tr height='25' align='center' bgcolor='#FFFFFF'><td>".iconv('utf-8', 'euc-kr', $name)."</td><td>".iconv('utf-8', 'euc-kr', $count)."</td></tr>";
	}
	$excelTable .= "</table>";

	echo $excelTable;
}
catch (Exception $e)
{
	echo '쿼리 오류: '.$e->getMessage();
}
?>