<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
//
//$types = $mdb->queryAll("select ud_content_id as type, ud_content_title name from bc_ud_content");
//
//$fileName = iconv('utf-8', 'euc-kr', '사용자정의조회현황');
//header(	"Content-type: application/vnd.ms-excel" );
//header(	"Content-Disposition: attachment; filename={$fileName}.xls" );
//header(	"Content-Description: Gamza	Excel Data"	);
//header(	"Content-charset=utf-8" );
//$excelTable="
//<table border=1 cellpadding=1 cellspacing=1>
//	<tr height='25' align='center'>
//		<td bgcolor='#FFFF99'>사용자 정의</td>
//		<td bgcolor='#FFFF99'>조회 횟수</td>
//	</tr>";


//	foreach($types as $type){
//		$count = $mdb->queryOne("
//		select count(L.log_id)
//		from bc_log L, bc_content C
//		where L.content_id = C.content_id
//		and C.ud_content_id = '{$type['type']}'
//		and L.action = 'read'");
//
//		$excelTable.="<tr height='25' align='center' bgcolor='#FFFFFF'><td>{$type['name']}</td><td>{$count}</td></tr>";
//	}
//	$excelTable.="</table>";
//
//	echo $excelTable;

$types = $mdb->queryAll("select ud_content_id as type, ud_content_title as name from bc_ud_content");
$mode = $_GET['mode'];
$k=0;
try
{
	$array =  array();

	foreach($types as $type)
	{
		if($mode == 'cg'){
			if( !in_array( $type['type'], $CG_LIST  ) )
				{
					continue;
				}

			$data = $mdb->queryOne("
				select count(l.log_id)
				from bc_log l, bc_content c
				where l.content_id = c.content_id
				and c.ud_content_id = '{$type['type']}'
				and l.action = 'read'");

			$name[] = $type['name'];
			$count[] = $data;
			$k++;
		}
		else{
			$data = $mdb->queryOne("
				select count(l.log_id)
				from bc_log l, bc_content c
				where l.content_id = c.content_id
				and c.ud_content_id = '{$type['type']}'
				and l.action = 'read'");

			$name[] = $type['name'];
			$count[] = $data;
			$k++;
		}
	}
	for($i=0; $i<$k; $i++)
	{
		array_push($array, array('사용자정의 콘텐츠'=>$name[$i], '조회 횟수'=>$count[$i]));
	}
//	$array = $db->queryAll("select c.ud_content_title \"사용자정의 콘텐츠\", count(L.log_id) \"횟수 \"
//		from bc_log L, view_content C
//		where L.content_id = C.content_id
//		and L.action = 'read'
//    		 group by c.ud_content_title");

	echo createExcelFile('사용자정의조회현황', $array);
}
catch (Exception $e)
{
	echo '쿼리 오류: '.$e->getMessage();
}
?>