<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');

$types = $mdb->queryAll("select ud_content_id as type, ud_content_title as name from bc_ud_content ");

$fileName=iconv('utf-8','euc-kr','사용자정의등록현황');
header(	"Content-type: application/vnd.ms-excel" );	
header(	"Content-Disposition: attachment; filename={$fileName}.xls" );	
header(	"Content-Description: Gamza	Excel Data"	); 
header(	"Content-charset=euc-kr" );	
$excelTable="
<table border=0 cellpadding=1 cellspacing=1 bgcolor='#000000'>
	<tr height='25' align='center' bgcolor='#FFFFFF'>
		<td>사용자 정의</td>
		<td>등록 횟수</td>
	</tr>";
try
{
foreach($types as $type){
	$count = $mdb->queryOne("
	select count(ud_content_id) 
	from bc_content 
	where is_deleted='N' 
	and ud_content_id = '{$type['type']}'");
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
	echo '구문 오류: '.$e->getMessage();
}
?>