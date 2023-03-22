<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

//$types = $mdb->queryAll("select meta_table_id as type, name from meta_table");

//$fileName=iconv('utf-8','euc-kr','사용자정의등록현황');
//header(	"Content-type: application/vnd.ms-excel" );
//header(	"Content-Disposition: attachment; filename={$fileName}.xls" );
//header(	"Content-Description: Gamza	Excel Data"	);
//header(	"Content-charset=utf-8" );
//$excelTable="
//<table border=1 cellpadding=1 cellspacing=1>
//	<tr height='25' align='center'>
//		<td bgcolor='#FFFF99'>사용자 정의</td>
//		<td bgcolor='#FFFF99'>등록 횟수</td>
//	</tr>";
$mode = $_GET['mode'];
try
{
	$array =  array();

	$types = $mdb->queryAll("select ud_content_id as type, ud_content_title as name from bc_ud_content ");

	$k=0;
	foreach($types as $type)
	{
		if($mode == 'cg')
		{
			if( !in_array( $type['type'], $CG_LIST  ) )
			{
				continue;
			}

			$data = $mdb->queryOne("select count(ud_content_id)
										from bc_content
										where is_deleted='N'
										and ud_content_id = '{$type['type']}'");

			$name[] = $type['name'];
			$count[] = $data;
			$k++;
		}
		else
		{
			$data = $mdb->queryOne("select count(ud_content_id)
										from bc_content
										where is_deleted='N'
										and ud_content_id = '{$type['type']}'");

			$name[] = $type['name'];
			$count[] = $data;
			$k++;
		}
	}

	for($i=0; $i<$k; $i++){
		array_push($array, array('사용자정의 콘텐츠'=>$name[$i], '등록 횟수'=>$count[$i]));

	}

//
//	$array = $db->queryAll("select c.ud_content_title \"사용자정의 콘텐츠\", count(L.log_id) \"등록 횟수\"
//		from bc_log L, view_content C
//		where L.content_id = C.content_id
//		and L.action = 'regist'
//    		 group by c.ud_content_title");



//foreach($types as $type){
////	$count = $mdb->queryOne("
////	select count(meta_table_id)
////	from content
////	where meta_table_id = '{$type['type']}'");
////	if($count < 0){
////		$count=0;
////	}
//	$excelTable.="<tr height='25' align='center' bgcolor='#FFFFFF'><td>{$type['type']}</td><td>{$type['count']}</td></tr>";
//}
//$excelTable.="</table>";
//echo $excelTable;

echo createExcelFile('사용자정의등록현황', $array);




}
catch (Exception $e)
{
	echo '구문 오류: '.$e->getMessage();
}
?>