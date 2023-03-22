<?php
session_start();
set_time_limit(0);
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
exit;
$start = $_POST['start'];
$limit = $_POST['limit'];

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
if(empty($_POST['start_date']))
{
	$start_date =date('Ymd000000');
	$end_date = date('Ymd240000');
}
else
{
	$start_date = $_POST['start_date'];
	$end_date	= $_POST['end_date'];
}
if( empty($_POST['search']) || $_POST['search'] == 'broadymd' )
{
	$search = '프로그램명';
}
else
{
	$search = $_POST['search'];
}
$datas = array();

try
{

	$status ='0';// 인제스트 완료후 등록대기상태
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
	else
	{
		$select_field =" distinct mv.value as value ";
		$where_field =" and mf.name = '$search' ";
	}

	$search_field_list = "
		select
			$select_field
		from
			meta_value mv,
			meta_field mf,
			content c
		where
			mv.meta_field_id = mf.meta_field_id "
		 .$where_field." and c.content_id = mv.content_id
		and c.status='$status'
		and c.user_id='admin'
		and c.created_time between $start_date and $end_date
		order by value asc
		";//검색 조건의 값들을 중복제거 해서 가져옴

	$total = $db->queryOne("select count(*) from ( $search_field_list ) cnt");

//	print_r($search_field_list);
//	$db->setLimit($limit, $start);
	$search_field_data = $db->queryAll($search_field_list);
//	print_r($search_field_data);

	$AnaBeta10_total = 0;
	$AnaBeta20_total = 0;		//'Ana-Beta20분'
	$AnaBeta30_total = 0;		//'Ana-Beta30분'
	$AnaBeta60_total = 0;		//'Ana-Beta60분'
	$AnaBeta90_total = 0;		//'Ana-Beta90분'
	$DigiBetacam10_total = 0; //'Digi-Betacam10분'
	$DigiBetacam30_total = 0; //'Digi-Betacam30분'
	$DigiBetacam60_total = 0; //'Digi-Betacam60분'
	$DigiBetacam90_total = 0; //'Digi-Betacam90분'
	$DigiBetacam120_total = 0; //'Digi-Betacam120분'
	$HD40_total = 0;			//'HD40분'
	$HD60_total = 0;			//'HD60분'
	$HD90_total = 0;			//'HD90분'
	$HD120_total = 0;			//'HD120분'
	$runtime_total = 0;			//'R/T 합계'

	$B2010total =0 ;

	$count_list_total = array();
	$count_list_total['field'] = '합계';

	foreach($search_field_data as $data)
	{
		$AnaBeta10 = 0;
		$AnaBeta20 = 0;		//'Ana-Beta20분'
		$AnaBeta30 = 0;		//'Ana-Beta30분'
		$AnaBeta60 = 0;		//'Ana-Beta60분'
		$AnaBeta90 = 0;		//'Ana-Beta90분'
		$DigiBetacam10 = 0; //'Digi-Betacam10분'
		$DigiBetacam30 = 0; //'Digi-Betacam30분'
		$DigiBetacam60 = 0; //'Digi-Betacam60분'
		$DigiBetacam90 = 0; //'Digi-Betacam90분'
		$DigiBetacam120 = 0; //'Digi-Betacam120분'
		$HD40 = 0;			//'HD40분'
		$HD60 = 0;			//'HD60분'
		$HD90 = 0;			//'HD90분'
		$HD120 = 0;			//'HD120분'

		$runtime = 0;			//'R/T 합계'


		if( $search == 'category' )
		{
			$field = $db->escape($data['value']);
			$where_field2 =" and c.category_id = '$field' ";
		}
		else if( $search == 'date' )
		{
			$field = $db->escape($data['value']);
			$where_field2 =" and c.created_time like '%".$field."%' ";
		}
		else
		{
			$field = $db->escape($data['value']);
			$where_field2 =" and mv.value = '$field' ";
		}

		$stat = "
		(select
			c.content_id
		from
			meta_field mf,
			meta_value mv,
			content c
		where
			mv.meta_field_id = mf.meta_field_id
		 $where_field2
		and c.content_id = mv.content_id
		and c.status='$status'
		and c.user_id='admin'
		and c.created_time between $start_date and $end_date)
		";// 검색 조건값에 해당하는 하나의 값의 콘텐츠 아이디를 구한뒤


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
			 and mf.name = '방송일자' and c.content_id = mv.content_id
			and c.status='$status'
			and c.user_id='admin'
			and c.created_time between $start_date and $end_date
			order by value asc
			";//검색 조건의 값들을 중복제거 해서 가져옴

			$columnlist = $db->queryAll( $query );


			$count_list = array();
			$count_list['field'] = $field;

			foreach($columnlist as $item)
			{
				if( !empty($item['value']) )
				{
					$count_q = "
					select
						count(mv.value)
					from
						content c,
						meta_field mf,
						meta_value mv
					where
						mf.meta_field_id = mv.meta_field_id
					and mv.content_id = c.content_id
					and mf.name='방송일자'
					and mv.value like '".$item['value']."%'
					and	c.content_id IN $stat
					order by c.created_time desc
					";

					$count = $db->queryOne($count_q);
					$keyname = 'broadymd'.$item['value'];
					$count_list[$keyname] = $count;
					$count_list_total[$keyname] += $count;
				}
			}
			array_push($datas, $count_list );

		}
		else
		{
			$value_list_q = "
			select
				c.content_id,
				c.category_id,
				mf.name,
				mv.value
			from
				content c,
				meta_field mf,
				meta_value mv
			where
				mf.meta_field_id = mv.meta_field_id
			and mv.content_id = c.content_id
			and ( mf.name='Tape NO' or mf.name='RT' )
			and	c.content_id IN $stat
			order by c.created_time desc
			";// 그 콘텐츠 아이디들 중에서의 테잎넘버,카테고리아이디,런타임을 가져옴

	//		print_r($value_list_q);

			$value_list = $db->queryAll($value_list_q);
	//		print_r($value_list);

			foreach($value_list as $value)
			{
				if( $value['name'] == 'Tape NO' )
				{
					if(!empty($value['value']))
					{
						$ingest_source = substr($value['value'], 0, 3);
						$ingest_source = $mappingsource[$ingest_source];

						switch ($ingest_source)
						{
							case 'AnaBeta10':
								$AnaBeta10++;
								$AnaBeta10_total++;
								break;
							case 'AnaBeta20':
								$AnaBeta20++;
								$AnaBeta20_total++;
								break;

							case 'AnaBeta30':
								$AnaBeta30++;
								$AnaBeta30_total++;
								break;

							case 'AnaBeta60':
								$AnaBeta60++;
								$AnaBeta60_total++;
								break;

							case 'AnaBeta90':
								$AnaBeta90++;
								$AnaBeta90_total++;
								break;

							case 'DigiBetacam10':
								$DigiBetacam10++;
								$DigiBetacam10_total++;
								break;

							case 'DigiBetacam30':
								$DigiBetacam30++;
								$DigiBetacam30_total++;
								break;

							case 'DigiBetacam60':
								$DigiBetacam60++;
								$DigiBetacam60_total++;
								break;

							case 'DigiBetacam90':
								$DigiBetacam90++;
								$DigiBetacam90_total++;
								break;

							case 'DigiBetacam120':
								$DigiBetacam120++;
								$DigiBetacam120_total++;
								break;

							case 'HD40':
								$HD40++;
								$HD40_total++;
								break;

							case 'HD60':
								$HD60++;
								$HD60_total++;
								break;

							case 'HD90':
								$HD90++;
								$HD90_total++;
								break;

							case 'HD120':
								$HD120++;
								$HD120_total++;
								break;
						}
					}
				}
				else if( $value['name'] == 'RT' )
				{
					$runtime += rtTime($value['value']); //런타임 값을 초시간으로 변경
					$runtime_total += rtTime($value['value']); //런타임 값을 초시간으로 변경
				}
			}

			if($search == 'date')
			{
				$field = date('Y-m-d', strtotime($field));
			}
			else if( $search == 'category' )
			{
				$category_data = $db->queryRow("select title,id from categories where id='$field'");
				if (!empty($field) )
				{
					if( $category_data['id'] == 0 )
					{
						$field ='DAS';
					}
					else
					{
						$field = $category_data['title'];
					}
				}
			}

			array_push($datas, array(
				'field' => $field,
				'Ana-Beta10분' => $AnaBeta10,
				'Ana-Beta20분' => $AnaBeta20,
				'Ana-Beta30분' => $AnaBeta30,
				'Ana-Beta60분' => $AnaBeta60,
				'Ana-Beta90분' => $AnaBeta90,
				'Digi-Betacam10분' => $DigiBetacam10,
				'Digi-Betacam30분' => $DigiBetacam30,
				'Digi-Betacam60분' => $DigiBetacam60,
				'Digi-Betacam90분' => $DigiBetacam90,
				'Digi-Betacam120분' => $DigiBetacam120,
				'HD40분' => $HD40,
				'HD60분' => $HD60,
				'HD90분' => $HD90,
				'HD120분' => $HD120,
				'R/T 합계' => $runtime
			));
		}

	}
	if( $_POST['search'] == 'broadymd' )
	{
		array_push($datas, $count_list_total);
	}
	else
	{

		array_push($datas, array(
			'field' => '합계',
			'Ana-Beta10분' => $AnaBeta10_total,
			'Ana-Beta20분' => $AnaBeta20_total,
			'Ana-Beta30분' => $AnaBeta30_total,
			'Ana-Beta60분' => $AnaBeta60_total,
			'Ana-Beta90분' => $AnaBeta90_total,
			'Digi-Betacam10분' => $DigiBetacam10_total,
			'Digi-Betacam30분' => $DigiBetacam30_total,
			'Digi-Betacam60분' => $DigiBetacam60_total,
			'Digi-Betacam90분' => $DigiBetacam90_total,
			'Digi-Betacam120분' => $DigiBetacam120_total,
			'HD40분' => $HD40_total,
			'HD60분' => $HD60_total,
			'HD90분' => $HD90_total,
			'HD120분' => $HD120_total,
			'R/T 합계' => $runtime_total
		));
	}

	echo json_encode(array(
		'rows'=>$datas,
		'total'	=>$total
	));
}
catch (Exception $e)
{
	echo $e->getMessage();
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
/*
ND(Digi-Beta)	NB(Ana-Beta)	NA~(HD)	테잎길이
ND 0~	120분	NB 1~	10분	NA 0~	120분
ND 1~	10분	NB 2~	20분	NA 4~	40분
ND 3~	30분	NB 3~	30분	NA 6~	60분
ND 4~	30분	NB 6~	60분	NA 9~	90분
ND 6~	60분	NB 9~	90분
ND 9~	90분	*/
?>