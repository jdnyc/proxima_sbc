<?php
set_time_limit(0);
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
session_start();

$mappingsource = array(
	'NB1' => 'Ana-Beta10분',
	'NB2' => 'Ana-Beta20분',
	'NB3' => 'Ana-Beta30분',
	'NB6' => 'Ana-Beta60분',
	'NB9' => 'Ana-Beta90분',
	'ND1' => 'Digi-Betacam10분',
	'ND3' => 'Digi-Betacam30분',
	'ND4' => 'Digi-Betacam30분',
	'ND6' => 'Digi-Betacam60분',
	'ND9' => 'Digi-Betacam90분',
	'ND0' => 'Digi-Betacam120분',
	'NA4' => 'HD40분',
	'NA6' => 'HD60분',
	'NA9' => 'HD90분',
	'NA0' => 'HD120분'
);


$time = date('YmdHis');
$fileName		= iconv('utf-8', 'euc-kr', '인제스트작업통계'.'_'.$time);

header(	"Content-type: application/vnd.ms-excel" );
header(	"Content-Disposition: attachment; filename={$fileName}.xls" );
header(	"Content-Description: Gamza	Excel Data"	);
header(	"Content-charset=euc-kr" );

$excelTable="<table border=1 cellpadding=1 cellspacing=1 bgcolor='#000000'>"; //테이블 선언

$excelHearder1 = "<tr height='25' align='center' bgcolor='#FFFFFF'>";//기본 헤더 상위 테잎종류 만들기

$excelHearder1 .= "<td width='400' bgcolor='#FFFFBB'></td>";
$excelHearder1 .= "<td colspan='5' bgcolor='#FFFFBB'>AnaBeta</td>";
$excelHearder1 .= "<td colspan='5' bgcolor='#FFFFBB'>DigiBetacam</td>";
$excelHearder1 .= "<td colspan='4' bgcolor='#FFFFBB'>HD</td>";
$excelHearder1 .= "<td width='150' bgcolor='#FFFFBB'></td>";
$excelHearder1 .= "</tr>";


$excelHearder2 = "<tr height='25' align='center' bgcolor='#FFFFFF'>";//기본 헤더 테잎길이 만들기

$excelHearder2 .= "<td width='400' bgcolor='#FFFFBB'>검색 목록</td>";
$excelHearder2 .= "<td bgcolor='#FFFFBB'>10분</td><td bgcolor='#FFFFBB'>20분</td><td bgcolor='#FFFFBB'>30분</td><td bgcolor='#FFFFBB'>60분</td><td bgcolor='#FFFFBB'>90분</td>";
$excelHearder2 .= "<td bgcolor='#FFFFBB'>10분</td><td bgcolor='#FFFFBB'>30분</td><td bgcolor='#FFFFBB'>60분</td><td bgcolor='#FFFFBB'>90분</td><td bgcolor='#FFFFBB'>120분</td>";
$excelHearder2 .= "<td bgcolor='#FFFFBB'>40분</td><td bgcolor='#FFFFBB'>60분</td><td bgcolor='#FFFFBB'>90분</td><td bgcolor='#FFFFBB'>120분</td>";
$excelHearder2 .= "<td width='150' bgcolor='#FFFFBB'>R/T 합계</td>";
$excelHearder2 .= "</tr>";
/////---------------------------------------------------------------//////

$status ='0';
$user_id = 'admin';

if( empty($_GET['start_date']) )
{
	$start_date =date('Ymd000000');
	$end_date = date('Ymd240000');
}
else
{
	$start_date = $_GET['start_date'];
	$end_date	= $_GET['end_date'];
}

$search = '프로그램명';

if( $_GET['search'] == 'broadymd' )
{
	$search = '프로그램명';
	$search_case = '방송일자';
}
else
{
	$search = $_GET['search'];
	$search_case = 'Tape NO';
}

if( $search == 'category' )
{
	$select_field =" c.content_id, value1.title value1, value2.value value2, value3.value value3, c.category_id";
	$where_field ="     (
     select ca.title, c.content_id from categories ca, content c where ca.id(+)=c.category_id
    ) value1,";
}
else if( $search == 'date' )
{
	$select_field =" c.content_id, SUBSTR(c.created_time, 0, 8) value1, value2.value value2, value3.value value3 ";
	$where_field ="(
	select
		content_id
	from
		content
	) value1,";
}
else
{
	$select_field =" c.content_id, value1.value value1, value2.value value2, value3.value value3 ";
	$where_field ="     (
      select
        mv.content_id content_id , mv.value value
      from
        meta_value mv,
        meta_field mf
      where
        mv.meta_field_id = mf.meta_field_id
      and mf.name = '$search'
    ) value1,";
}



try
{

  $query = " select
     $select_field
     from
     content c,
     $where_field
    (
     select
        mv.content_id as content_id , mv.value as value
      from
        meta_value mv,
        meta_field mf
      where
        mv.meta_field_id = mf.meta_field_id
      and mf.name = '$search_case'
     ) value2,
	 (
     select
        mv.content_id as content_id , mv.value as value
      from
        meta_value mv,
        meta_field mf
      where
        mv.meta_field_id = mf.meta_field_id
      and mf.name = 'RT'
     ) value3
    where
		c.status >= '$status'
    and value1.content_id=c.content_id
    and value2.content_id=c.content_id
	and value3.content_id=c.content_id
	and c.is_deleted = 0
	and c.user_id='$user_id'
	and c.created_time between $start_date and $end_date";

	$list = $db->queryAll( $query );

	if( $_GET['search'] == 'broadymd' )
	{
		$query = "
		select
			 distinct SUBSTR(mv.value, 0, 4) as value
		from
			meta_value mv,
			meta_field mf,
			content c
		where
			mv.meta_field_id = mf.meta_field_id
		and mf.name = '방송일자'
		and c.content_id = mv.content_id
		and c.status >= '$status'
		and c.user_id = '$user_id'
		and c.is_deleted = 0
		and c.created_time between $start_date and $end_date
		order by value asc
		";//검색 조건의 값들을 중복제거 해서 가져옴

		$ymdlist = $db->queryAll( $query );

		$data = array();
		$pronm_list = array();

		$total = array();
		$total['field'] = '합 계';

		foreach($ymdlist as $val)
		{
			$keyname = 'broadymd'.$val['value'];
			$total[$keyname] = 0;
		}

		foreach($list as $item)
		{
			$pronm = $item['value1'];  //프로그램명
			$ymd = $item['value2'];  //방송일자

			$year = substr($ymd, 0, 4); //방송일자에서 년만 뽑기
			$yearname = 'broadymd'.$year;

			if( !in_array($pronm , $pronm_list ) ) //배열에 없을때
			{
				array_push($pronm_list, $pronm);//배열에 없을때 프로그램명 추가

				$k = array_search($pronm, $pronm_list); //배열의 키값 받기

				$data[$k]['field'] = $pronm;

				foreach($ymdlist as $val)
				{
					$keyname = 'broadymd'.$val['value'];
					$data[$k][$keyname] = 0;
				}

				$data[$k][$yearname]++; //카운트
				$total[$yearname]++; //카운트
			}
			else
			{
				$k = array_search($pronm, $pronm_list);

				$data[$k][$yearname]++; //카운트
				$total[$yearname]++; //카운트
			}
		}
		array_push($data, $total );
	}
	else
	{
		$data = array();
		$pronm_list = array();

		$total = array();
		$total['field'] = '합 계';
		foreach($mappingsource as $key => $val)
		{
			$total[$val] = 0;
		}
		$total['R/T 합계'] = 0;

		foreach($list as $item)
		{
			if( $search == 'category' ) //카테고리일때
			{
				$pronm = $item['category_id'];
			}
			else
			{
				$pronm = $item['value1']; //조건 필드 프로그램명/등록일자
			}

			$tapeno = trim($item['value2']);  //Tape NO
			$rt = trim($item['value3']);  //RT

			$rttime = rtTime( $rt ); //00:00:00 sec로 변경

			$tapeno = substr($tapeno, 0, 3); //Tape NO에서 앞에 3자리만 뽑기
			$tapename = $mappingsource[$tapeno]; //key값 매핑

			if( !in_array( $pronm , $pronm_list ) ) //배열에 없을때
			{
				array_push($pronm_list, $pronm);//배열에 없을때 추가

				$k = array_search($pronm, $pronm_list); //배열의 키값 받기

				$data[$k]['field'] = $pronm;

				foreach($mappingsource as $key => $val)
				{
					$data[$k][$val] = 0;
				}

				$data[$k]['R/T 합계'] = $rttime;
				$total['R/T 합계'] += $rttime;

				$data[$k][$tapename]++; //카운트
				$total[$tapename]++;
			}
			else
			{
				$k = array_search($pronm, $pronm_list);

				$data[$k][$tapename]++; //카운트
				$data[$k]['R/T 합계'] += $rttime;
				$total[$tapename]++;
				$total['R/T 합계'] += $rttime;
			}
		}

		if( $search == 'category' ) //카테고리일때
		{
			foreach($data as $k=>$v)
			{
				if(empty($v['field']) || $v['field'] == 0)
				{
					$data[$k]['field'] = 'DAS';
				}
				else
				{
					$data[$k]['field'] = substr(getCategoryFullPathTitle($v['field']),4 );
				}
			}
		}

		array_push($data, $total );
	}
	////////////////////테이블 합치기 //////////////
	$excelBody = tableset($data);

	if( $_GET['search'] == 'broadymd' )
	{
		$excelTable .= tableheader($ymdlist);
	}
	else
	{
		$excelTable .= $excelHearder1;
		$excelTable .= $excelHearder2;
	}
	$excelTable .= $excelBody;
	$excelTable .= "</table>";
	echo $excelTable;

}
catch (Exception $e)
{
	echo $e->getMessage();
}

function getCategoryFullPathTitle($id)
{
	global $db;

	$parent = $db->queryRow("select parent_id, category_title from bc_category where category_id=".$id);

	if ( $parent['parent_id'] !== 0 && !empty($parent['parent_id']) )
	{
		$self_id = getCategoryFullPathTitle( $parent['parent_id'] );
	}

	return $self_id.' -> '.$parent['title'];
}

function rtTime($value)
{

	$time= trim($value);
	if( strlen($value)== 5 )
	{
		$hour = substr($time,0,1);
		$min =	substr($time,1,2);
		$sec =  substr($time,3,2);
	//	echo $value.'<br />';
	//	echo $hour.' '.$min.' '.$sec.'<br />';
		$time = ($hour*3600) + ($min*60) + $sec;
	}
	else if( strlen($value)== 6 )
	{
		$hour = substr($time,0,2);
		$min =	substr($time,2,2);
		$sec =  substr($time,4,2);
	//	echo $value.'<br />';
	//	echo $hour.' '.$min.' '.$sec.'<br />';
		$time = ($hour*3600) + ($min*60) + $sec;
	}
	else if( strlen($value)== 8 )
	{
		$hour = substr($time,0,2);
		$min =	substr($time,3,2);
		$sec =  substr($time,6,2);
	//	echo $value.'<br />';
	//	echo $hour.' '.$min.' '.$sec.'<br />';
		$time = ($hour*3600) + ($min*60) + $sec;
	}
	else
	{
		$time=0;
	}

	return $time;
}

function rtsetTime($value)
{
	$time = $value;
	$hour = $time / 3600;
	$min =	($time%3600) / 60;
	$sec =  ($time%3600) % 60;
	settype($hour,'int');
	settype($min,'int');
	settype($sec,'int');
	$hour = str_pad($hour, 2, "0", STR_PAD_LEFT);
	$min = str_pad($min, 2, "0", STR_PAD_LEFT);
 	$sec = str_pad($sec, 2, "0", STR_PAD_LEFT);
	$time = $hour.':'.$min.':'.$sec.':00';
	return $time;
}

function tableheader($array)
{
	$data = "";
	$tr_start = "<tr height='25' align='center' bgcolor='#FFFFFF'>";
	$tr_end = "</tr>";
	$colspan = count($array);

	$data .=$tr_start;
	$data .= "<td width='400' bgcolor='#FFFFBB'></td>";
	$data .= "<td colspan='$colspan' bgcolor='#FFFFBB'>방송연도</td>";
	$data .=$tr_end;


	$data .=$tr_start;
	$data .= "<td width='400' bgcolor='#FFFFBB'>검색 목록</td>";
	foreach($array as $list)
	{
		$data .= "<td bgcolor='#FFFFBB'>".$list['value']."년</td>";
	}
	$data .=$tr_end;

	return $data;
}

function tableset($array)
{
	$data = "";
	$tr_start = "<tr height='25' align='center' bgcolor='#FFFFFF'>";
	$tr_end = "</tr>";

	foreach($array as $list)
	{
		$data .= $tr_start;

		foreach($list as $k => $v)
		{
			if($k == 'R/T 합계')
			{
				$data .= "<td >".rtsetTime($v)."</td>";
			}
			else
			{
				$data .= "<td >".$v."</td>";
			}
		}

		$data .= $tr_end;
	}

	return $data;
}
?>