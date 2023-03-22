<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');

$types = $mdb->queryAll("select bs_content_id as type, bs_content_title as name from bc_bs_content");
$fileName=iconv('utf-8','euc-kr','콘텐츠타입별등록현황');
header(	"Content-type: application/vnd.ms-excel" );	
header(	"Content-Disposition: attachment; filename={$fileName}.xls" );	
header(	"Content-Description: Gamza	Excel Data"	); 
header(	"Content-charset=euc-kr" );	

$excelTable="
<table border=0 cellpadding=1 cellspacing=1 bgcolor='#000000'>
	<tr height='25' align='center' bgcolor='#FFFFFF'>
		<td>콘텐츠 타입</td>
		<td>등록 횟수</td>
	</tr>";
try
{
	foreach($types as $type){
		$regist = $mdb->queryOne("
		select count(content_id) 
		from bc_content 
		where bs_content_id = '{$type['type']}'");
		$del_count = $mdb->queryOne("
		select count(log_id) 
		from bc_log 
		where bs_content_id = '{$type['type']}'");
		$count=$regist-$del_count;
		if($count < 0){
			$count=0;
		}
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