<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$status ='0';
$user_id = 'admin';

if( empty($_POST['start_date']) )
{
	$start_date =date('Ymd000000');
	$end_date = date('Ymd240000');
}
else
{
	$start_date = $_POST['start_date'];
	$end_date	= $_POST['end_date'];
}

$search = '프로그램명';

if( $_POST['search'] == 'broadymd' )
{
	$search = '프로그램명';
	$search_case = '방송일자';
}
else
{
	$search = $_POST['search'];
	$search_case = 'Tape NO';
}

if( $search == 'category' )
{
	$select_field =" c.content_id, c.category_id value1, value2.value value2 ";
}
else if( $search == 'date' )
{
	$select_field =" c.content_id, SUBSTR(c.created_time, 0, 8) value1, value2.value value2 ";
}
else
{
	$select_field =" c.content_id, value1.value value1, value2.value value2 ";
}



try
{

  $query = " select
     $select_field
     from
     content c,
     (
      select
        mv.content_id content_id , mv.value value
      from
        meta_value mv,
        meta_field mf
      where
        mv.meta_field_id = mf.meta_field_id
      and mf.name = '$search'
    ) value1,
     (
     select
        mv.content_id as content_id , mv.value as value
      from
        meta_value mv,
        meta_field mf
      where
        mv.meta_field_id = mf.meta_field_id
      and mf.name = '$search_case'
     ) value2
     where
		c.status >= '$status'
    and value1.content_id=c.content_id
    and value2.content_id=c.content_id
	and c.user_id='$user_id'
	and c.created_time between $start_date and $end_date";

	$list = $db->queryAll( $query );

	if( $_POST['search'] == 'broadymd' )
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
		and c.status = '$status'
		and c.user_id = '$user_id'
		and c.is_deleted = 0
		and c.created_time between $start_date and $end_date
		order by value asc
		";//검색 조건의 값들을 중복제거 해서 가져옴

		$ymdlist = $db->queryAll( $query );

		$data = array();
		$pronm_list = array();

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
			}
			else
			{
				$k = array_search($pronm, $pronm_list);

				$data[$k][$yearname]++; //카운트
			}
		}
	}
	else
	{
		$mappingsource = array(
			'NB1' => 'AnaBeta10',
			'NB2' => 'AnaBeta20',
			'NB3' => 'AnaBeta30',
			'NB6' => 'AnaBeta60',
			'NB9' => 'AnaBeta90',
			'ND1' => 'DigiBetacam10',
			'ND3' => 'DigiBetacam30',
			'ND4' => 'DigiBetacam30',
			'ND6' => 'DigiBetacam60',
			'ND9' => 'DigiBetacam90',
			'ND0' => 'DigiBetacam120',
			'NA4' => 'HD40',
			'NA6' => 'HD60',
			'NA9' => 'HD90',
			'NA0' => 'HD120'
		);
/*
		if( $search == 'category' )
		{
			$select_field =" distinct c.category_id as value ";
			$where_field ="";
		}
		else if( $search == 'date' )
		{
			//$date_s = substr($start_date,0,8);
			$select_field =" distinct SUBSTR(c.created_time, 0, 8) as value ";
			$where_field ="";
		}
		else if( $_POST['search'] == 'broadymd' )
		{
			$select_field =" distinct SUBSTR(mv.value, 0, 4) as value ";
			$where_field =" and mf.name = '$search' ";
		}
		else
		{
			$select_field =" distinct mv.value as value ";
			$where_field =" and mf.name = '$search' ";
		}

		$query = "
		select
			 $select_field
		from
			meta_value mv,
			meta_field mf,
			content c
		where
			mv.meta_field_id = mf.meta_field_id
		$where_field
		and c.content_id = mv.content_id
		and c.status >= '$status'
		and c.user_id = '$user_id'
		and c.is_deleted = 0
		and c.created_time between $start_date and $end_date
		order by value asc
		";//검색 조건의 값들을 중복제거 해서 가져옴

		$ymdlist = $db->queryAll( $query );
*/
		$data = array();
		$pronm_list = array();
		foreach($list as $item)
		{
			//$pronm_list = array();
			$pronm = $item['value1']; //조건 필드 프로그램명/등록일자/카테고리
			$tapeno = trim($item['value2']);  //Tape NO

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

				$data[$k][$tapename]++; //카운트
			}
			else
			{
				$k = array_search($pronm, $pronm_list);
				$data[$k][$tapename]++; //카운트
			}
		}
	}

	echo json_encode(array(
		'rows'=>$data,
		'total'	=>$total
	));
}
catch (Exception $e)
{
	echo $e->getMessage();
}

?>
