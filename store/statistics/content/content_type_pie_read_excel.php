<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');

$types = $mdb->queryAll("select bs_content_id as type, bs_content_title as name from bc_bs_content");
$time = date('YmdHis');
$fileName = iconv('utf-8', 'euc-kr', _text('MSG02153').'_'.$time);
header(	"Content-type: application/vnd.ms-excel" );
header(	"Content-Disposition: attachment; filename={$fileName}.xls" );
header(	"Content-Description: Gamza	Excel Data"	);
header(	"Content-charset=euc-kr" );
$excelTable="
<table border=1 cellpadding=1 cellspacing=1 bgcolor='#000000'>
	<tr height='25' align='center' bgcolor='#FFFFFF'>
		<td bgcolor='#FFFFBB'>".iconv('utf-8', 'euc-kr',_text('MN00276'))."</td>
		<td bgcolor='#FFFFBB'>".iconv('utf-8', 'euc-kr',_text('MN00251'))."</td>
	</tr>
";
try
{
	foreach($types as $type){
		$count = $mdb->queryOne("
		select count(l.log_id)
		from bc_log l, bc_content c
		where l.content_id = c.content_id
		and c.bs_content_id = '{$type['type']}'
		and l.action = 'read'");
		$excelTable.="<tr height='25' align='center' bgcolor='#FFFFFF'><td>".iconv('utf-8', 'euc-kr',$type['name'])."</td><td>".iconv('utf-8', 'euc-kr',$count)."</td></tr>";
	}
	$excelTable.="</table>";
	echo $excelTable;
}
catch (Exception $e)
{
	echo '쿼리 오류: '.$e->getMessage();
}
?>