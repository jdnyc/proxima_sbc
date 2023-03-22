<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');

$types = $mdb->queryAll("select content_type_id as type, name from content_type");
$fileName = iconv('utf-8', 'euc-kr', '콘텐츠타입별다운현황');
header(	"Content-type: application/vnd.ms-excel" );	
header(	"Content-Disposition: attachment; filename={$fileName}.xls" );	
header(	"Content-Description: Gamza	Excel Data"	); 
header(	"Content-charset=euc-kr" );	
$excelTable="
<table border=0 cellpadding=1 cellspacing=1 bgcolor='#000000'>
	<tr height='25' align='center' bgcolor='#FFFFFF'>
		<td>콘텐츠 타입</td>
		<td>다운 횟수</td>
	</tr>";
try
{
	foreach($types as $type){
		$count = $mdb->queryOne("
		select count(L.id) 
		from log L, content C 
		where link_table_id = C.content_id 
		and C.meta_table_id = '{$type['type']}' 
		and L.action = 'download'");			
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