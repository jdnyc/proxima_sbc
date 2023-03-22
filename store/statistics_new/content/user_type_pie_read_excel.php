<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');

$types = $mdb->queryAll("select ud_content_id as type, ud_content_title as name from bc_ud_content");

$fileName = iconv('utf-8', 'euc-kr', '사용자정의조회현황');
header(	"Content-type: application/vnd.ms-excel" );	
header(	"Content-Disposition: attachment; filename={$fileName}.xls" );	
header(	"Content-Description: Gamza	Excel Data"	); 
header(	"Content-charset=euc-kr" );	
$excelTable="
<table border=0 cellpadding=1 cellspacing=1 bgcolor='#000000'>
	<tr height='25' align='center' bgcolor='#FFFFFF'>
		<td>사용자 정의</td>
		<td>조회 횟수</td>
	</tr>";
try
{
	foreach($types as $type){
		$count = $mdb->queryOne("
		select count(l.log_id) 
		from bc_log l, bc_content c 
		where l.content_id = c.content_id 
		and c.ud_content_id = '{$type['type']}' 
		and l.action = 'read'");	
		
		$excelTable.="<tr height='25' align='center' bgcolor='#FFFFFF'><td>{$type['name']}</td><td>{$count}</td></tr>";
	}
	$excelTable.="</table>";

	echo $excelTable;
}
catch (Exception $e)
{
	echo '쿼리 오류: '.$e->getMessage();
}
?>