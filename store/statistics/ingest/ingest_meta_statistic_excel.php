<?php
set_time_limit(0);
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
session_start();
$start_date = $_GET['start_date'];
$end_date = $_GET['end_date'];
$status = INGEST_READY;
$meta_table_id = $_GET['meta_table_id'];
$start = $_GET['start'];
$limit = $_GET['limit'];

$mapping_status = array(
	0	=> "<font color=red>등록대기</font>",
	1	=> "<font color=green>완료</font>",
	-1	=> '오류',
	-3	=> "<font color=red>대기</font>"
);

$time = date('YmdHis');
$fileName		= iconv('utf-8', 'euc-kr', '인제스트 메타데이터 통계'.'_'.$time);

header(	"Content-type: application/vnd.ms-excel" );
header(	"Content-Disposition: attachment; filename={$fileName}.xls" );
header(	"Content-Description: Gamza	Excel Data"	);
header(	"Content-charset=euc-kr" );

$excelTable="<table border=1 cellpadding=1 cellspacing=1 bgcolor='#000000'>"; //테이블 선언

try
{
	$excelTable_field =	"<tr height='25' align='center' bgcolor='#FFFFFF'>"; //첫 네임 필드 만들기
	$excelTable_field .="<td>Tape NO</td>";
	$excelTable_field .="<td>재생길이</td>";
	$excelTable_field .="<td>상태</td>";

	$excelTable_value_total = "";
///////////////tc정보 필드만들기///////////////
	$tc_default = "select DEFAULT_VALUE from meta_field where name like '%TC정보%' and depth =1 and meta_table_id='$meta_table_id'";
	$tc_default_value = $mdb->queryOne($tc_default);

	$default = getListViewColumns($tc_default_value);
	foreach($default as $k =>$v)
	{
		$excelTable_field .="<td>".$v."</td>";
	}
///////////////////////////////////////////////
	$content_query = "
		select
			*
		from
			content
		where
			meta_table_id='$meta_table_id'
		and is_deleted != '1'
		and LAST_MODIFIED_TIME is not null
		and LAST_MODIFIED_TIME between $start_date and $end_date
			order by created_time desc
		";

//	$db->setLimit($limit,$start);
	$content = $db->queryAll($content_query);

	foreach($content as $k => $list)
	{
		$content_id			= $list['content_id'];
		$content_type_id	= $list['content_type_id'];
		$meta_table_id		= $list['meta_table_id'];
		$title				= $list['title'];

		$runningtime = $db->queryOne("select value from content_value where content_id ='$content_id' and content_field_id='507'");

/////////////////////////////////////////////////////////////////////////////////////


		$meta_field_list=$db->queryAll("
			select
				f.name, v.meta_field_id, v.value
			from
				meta_value v,
				meta_field f
			where
				f.meta_field_id=v.meta_field_id(+)
			and
				f.meta_table_id='$meta_table_id'
			and
				content_id='$content_id'
			order by sort
			");

		$excelTable_value = "<tr height='25' align='center' bgcolor='#FFFFFF'>";

		foreach($meta_field_list as $meta_field) //콘테이너 및 하위필드 모두 만들기
		{
			if( $meta_field['name']== 'Tape NO')
			{
				$excelTable_value .='<td>'.$meta_field['value'].'</td>';
				$excelTable_value .='<td>'.$runningtime.'</td>';
			}
		}

		$excelTable_value .="<td>".$mapping_status[$list['status']]."</td>";

		foreach($default as $kd =>$v)
		{
			$excelTable_value .="<td></td>";
		}

		foreach($meta_field_list as $meta_field) //콘테이너 및 하위필드 모두 만들기
		{
			if( $k == 0 )/////첫 리스트의 필드이름 만들기
			{
				$excelTable_field .='<td>'.$meta_field['name'].'</td>';
			}

			if($meta_field['name']=='방송일자' || $meta_field['name']=='수상일자' || $meta_field['name']=='입수일자' || $meta_field['name']=='인코딩 작업일자' || $meta_field['name']=='메타데이터 작업일자')
			{

				if( !empty( $meta_field['value'] ) )
				{
					$date = strtotime($meta_field['value']);
					$excelTable_value .= '<td>'.date('Y-m-d', $date).'</td>';
				}
				else
				{
					$excelTable_value .= '<td>'. $meta_field['value'].'</td>';
				}
			}
			else if($meta_field['name']=='TC정보')
			{
				$excelTable_value .= '<td></td>';
			}
			else if($meta_field['name']=='메타데이터 작업자')
			{
				$name = $db->queryOne("select name from member where user_id= '{$meta_field['value']}'");
				if ( !empty($name) )
				{
					$excelTable_value .= '<td>'.$name.'</td>';
				}
				else
				{
					$excelTable_value .= '<td>'.$meta_field['value'].'</td>';
				}
			}
			else
			{
				$excelTable_value .= '<td>'.$meta_field['value'].'</td>';
			}
		}

		$excelTable_value .="</tr>";
		$excelTable_value_total .= $excelTable_value;

/////////////////////////////////////tc list///////////////////////////////////////////
		$tc_sub_qeury = "(select meta_field_id from meta_field where name like '%TC정보%' and depth =1 and meta_table_id='$meta_table_id')";
		$tc_info = "select value from meta_multi where content_id='$content_id' and meta_field_id=$tc_sub_qeury order by sort";
		$tc_list = $mdb->queryAll($tc_info);
//////////////////////TC정보 만들기


		if(count($tc_list) > 0)
		{
			for( $i=0; $i<count($tc_list) ; $i++ )
			{
				$list = json_decode($tc_list[$i]['value'], true);
				if(!empty($list))
				{
					$excelTable_tc="";
					$excelTable_tc .= "<tr height='25' align='center' bgcolor='#FFFFFF'>";
					$excelTable_tc .="<td></td>";
					$excelTable_tc .="<td></td>";
					$excelTable_tc .="<td></td>";

					//print_r($list);exit;

					foreach($list as $key=>$value)
					{
						$excelTable_tc .= "<td>".$value."</td>";
					}
					$count = count($meta_field_list);

					for($j=0;$j<$count;$j++)
					{
						$excelTable_tc .= "<td></td>";
					}
					$excelTable_tc .="</tr>";
					$excelTable_value_total .= $excelTable_tc;
				}
			}
		}
	}
	$excelTable .= $excelTable_field.'</tr>';
	$excelTable .= $excelTable_value_total;
	$excelTable .= "</table>";

	echo $excelTable;
}
catch (Exception $e)
{
	echo '쿼리 오류: '.$e->getMessage();
}

function escape($v)
{
	$v = str_replace("'", "\'", $v);
	$v = str_replace('"', '\"', $v);
	$v = str_replace("\r", '', $v);
	$v = str_replace("\n", '\\n', $v);

	return $v;
}
function getListViewColumns($columns)
{
	$asciiA = 65;
	$columns = explode(';', $columns);
	foreach ($columns as $v)
	{
		$result[] = $v;
	}
	return $result;
}
?>