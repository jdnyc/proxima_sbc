<?php
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
	0	=> "<font color=red>대기</font>",
	1	=> "<font color=green>완료</font>",
	2	=> "<font color=green>진행중</font>",
	-1	=> '오류',
	-3	=> "<font color=red>대기</font>"
);

$member_id=$db->queryOne("select member_id from member where user_id ='$user_id'");

$time = date('YmdHis');
$fileName		= iconv('utf-8', 'euc-kr', '인제스트요청리스트'.'_'.$time);

header(	"Content-type: application/vnd.ms-excel" );
header(	"Content-Disposition: attachment; filename={$fileName}.xls" );
header(	"Content-Description: Gamza	Excel Data"	);
header(	"Content-charset=euc-kr" );

$excelTable="<table border=1 cellpadding=1 cellspacing=1 bgcolor='#000000'>"; //테이블 선언

try
{
	$excelTable_field =	"<tr height='25' align='center' bgcolor='#FFFFFF'>"; //첫 네임 필드 만들기
	$excelTable_field .="<td>Tape NO</td>";
	$excelTable_field .="<td>TC_IN</td>";
	$excelTable_field .="<td>TC_OUT</td>";
	$excelTable_field .="<td>상태</td>";

	if( $meta_table_id==CLEAN )
	{
			///////////////tc정보 필드만들기///////////////

		$multi_field = $db->queryRow("select meta_field_id, default_value from meta_field where meta_table_id='".CLEAN."' and type='listview' and name='TC정보' ");//TC정보 meta_field_id

		$tc_default_value = $multi_field['default_value'];
		$multi_meta_field_id = $multi_field['meta_field_id'];

		$default = getListViewColumns($tc_default_value);

		foreach($default as $k =>$v)
		{
			$excelTable_field .="<td>".$v."</td>";
		}
	}

	$ingest_query = "
		select
			id, content_type_id, meta_table_id, title, status
		from
			ingest
		where
			meta_table_id='$meta_table_id' and
			created_time between $start_date and $end_date
			order by id desc
		";

	$db->setLimit($limit,$start);
	$ingest_list = $db->queryAll($ingest_query);

	if( empty( $ingest_list ) )
	{
		echo '정보가 없습니다.';
		exit;
	}

	foreach($ingest_list as $k => $list)
	{
		$ingest_id			= $list['id'];
		$content_type_id	= $list['content_type_id'];
		$meta_table_id		= $list['meta_table_id'];
		$title				= $list['title'];

/////////////////////////////////////////////////////////////////////////////////////


		$meta_field_list=$db->queryAll("
			select
				f.name,
				v.meta_value
			from
				meta_field f,
				ingest_metadata v
			where
				f.meta_field_id=v.meta_field_id(+)
			and	f.meta_table_id='$meta_table_id'
			and ingest_id='$ingest_id'
			order by sort");

		foreach($meta_field_list as $meta_field) //콘테이너 및 하위필드 모두 만들기
		{
			if($meta_field['name']=='Tape NO')
			{
				$tape_no_value = $meta_field['meta_value'];
			}
		}

		$excelTable_value = "<tr height='25' align='center' bgcolor='#FFFFFF'>";
		$excelTable_value .="<td>".$tape_no_value."</td>";//tape no
		$excelTable_value .="<td></td>";//tc_in
		$excelTable_value .="<td></td>";//tc_out
		$excelTable_value .="<td>".$mapping_status[$list['status']]."</td>";

		if( $meta_table_id==CLEAN )
		{
			foreach($default as $d)
			{
				$excelTable_value .="<td></td>";
			}
		}

		foreach($meta_field_list as $meta_fields) //콘테이너 및 하위필드 모두 만들기
		{
			if( $k == 0 )/////첫 리스트의 필드이름 만들기
			{
				$excelTable_field .='<td>'.$meta_fields['name'].'</td>';
			}

			$excelTable_value .= '<td>'.$meta_fields['meta_value'].'</td>';
		}

		$excelTable_value .="</tr>";
		$excelTable_value_total .= $excelTable_value;

/////////////////////////////////////tc list///////////////////////////////////////////
		$tc_query = "select * from ingest_tc_list where ingest_list_id = ".$list['id']." order by id desc ";
		$ingest_tc_list = $mdb->queryAll($tc_query);

		$tc_count = count($ingest_tc_list);

		if($tc_count > 0)
		{

			$excelTable_tc="";
			foreach($ingest_tc_list as $tc_list)
			{
				if($tc_list['status']=='1')
				{
					$status_count++;
				}
				$excelTable_tc .= "<tr height='25' align='center' bgcolor='#FFFFFF'>";
				$excelTable_tc .="<td></td>";
				$excelTable_tc .="<td>".$tc_list['tc_in']."</td>";
				$excelTable_tc .="<td>".$tc_list['tc_out']."</td>";
				$excelTable_tc .="<td>".$mapping_status[$tc_list['status']]."</td>";

				if( $meta_table_id==CLEAN )
				{
					foreach($default as $d)
					{
						$excelTable_tc .="<td></td>";
					}
				}

				$count = $db->queryOne("
						select
							count(f.name)
						from
							meta_field f,
							ingest_metadata v
						where
							f.meta_field_id=v.meta_field_id(+)
						and	f.meta_table_id='$meta_table_id'
						and ingest_id='$ingest_id'
						order by sort ");

				for($i=0;$i<$count;$i++)
				{
					$excelTable_tc .= "<td></td>";
				}
				$excelTable_tc .="</tr>";
				$excelTable_value_total .= $excelTable_tc;
			}
	/////////////////////////////////////////////////////////////////////////

		}
		if( $meta_table_id==CLEAN )
		{
			$ingest_multi_list =	$db->queryAll("select * from ingest_meta_multi where ingest_id='$ingest_id' and meta_field_id='$multi_meta_field_id' order by sort ");

			$excelTable_multi_list ='';

			if( !empty( $ingest_multi_list ) )
			{
				foreach($ingest_multi_list as $multi_list)
				{
					$multi_values = json_decode( $multi_list['value'], true );
					$excelTable_multi_list = "<tr height='25' align='center' bgcolor='#FFFFFF'>";

					$excelTable_multi_list .="<td></td>";
					$excelTable_multi_list .="<td></td>";
					$excelTable_multi_list .="<td></td>";
					$excelTable_multi_list .="<td></td>";

					foreach($multi_values as $value)
					{
						$excelTable_multi_list .="<td>".$value."</td>";
					}


					foreach($meta_field_list as $val)
					{
						$excelTable_multi_list .= "<td></td>";
					}

					$excelTable_multi_list .="</tr>";

					$excelTable_value_total .= $excelTable_multi_list;
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