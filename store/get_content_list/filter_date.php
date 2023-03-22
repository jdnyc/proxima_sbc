<?php
session_start();
$group = $_SESSION['user']['group'];

try
{
	$filter_name = $_POST['filter_name'];
	$filter_value = str_replace('/', '', $_POST['filter_value']);

	$_select = array();
	$_from = array();
	$_where = array();
	$_order = array();

	$status = '0'; //콘텐츠 상태
	$is_deleted = 'N'; //삭제여부 - 존재
	array_push($_select , " c.* " );
	array_push($_from , " view_content c " );
	array_push($_where , " c.ud_content_id = '$ud_content_id' " );
	array_push($_where , " c.status > $status " );
	array_push($_where , " c.is_deleted = '$is_deleted' " );

	array_push($_where , " c.$filter_name like '".$filter_value."%' " );


	if( $order_field != 'title' && $order_field != 'content_id' && $order_field != 'created_date' )
	{ //메타데이터 정렬일때 필드 추가

		array_push($_select , " m.usr_meta_value sortvalue " );
		array_push($_from , " (select * from bc_usr_meta_value m where m.ud_content_id='$ud_content_id' and m.usr_meta_field_id='".substr($order_field , 1)."' ) m " );
		array_push($_where , " c.content_id=m.content_id " );
		array_push($_order , " m.usr_meta_value $order_dir " );
	}
	else
	{
		array_push($_order , " c.$order_field $order_dir " );
	}

		//유형별 건수 위해 쿼리 분기 2012-11-09 이성용
	$total_where_array = $_where;
	$total_from_array = $_from;

	if(!empty($_select))
	{
		$select = " select ".join(' , ', $_select);
	}

	if(!empty($_from))
	{
		$from = " from ".join(' , ', $_from);
	}

	if (!empty($_where))
	{
		$where = " where ".join(' and ', $_where);
	}

	if(!empty($_order))
	{
		$order = " order by DECODE(c.status, 0, 0, 1) asc , ".join(' , ', $_order);
	}

	//echo $query;exit;

	$query = $select.$from.$where;

	$total = $db->queryOne("select count(*) from (".$query.") cnt");

	$db->setLimit($limit, $start);
	$content_list = $db->queryAll($query.$order);
	$contents = fetchMetadata($content_list);
	$total_where =  " where ".join(' and ', $total_where_array);
	$total_from = " from ".join(' , ', $total_from_array);

	$ud_content_list = array(
		4000282,
		4000284,
		4000345,
		4000346,
		4000365,
		4000385,
		4000305,
		4000306,
		4000325,
		4000308,
		4000307
	);
	$t_s = array();
	foreach( $ud_content_list as $t_ud_content_id )
	{
		array_push($t_s, 'MAX(DECODE(ud_content_id, '.$t_ud_content_id.', count( ud_content_id))) "'.$t_ud_content_id.'"');
	}
	$ud_total_list =  $db->queryRow(" select ".join(' , ',$t_s)." ".$total_from.$total_where."  group by ud_content_id ");
	echo json_encode(array(
		'success' => true,
		'total' => $total,
		'results' => $contents,
		'query'	=> $query.$order,
		'ud_total_list' =>$ud_total_list
	));
}
catch(Exception $e){
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage() . '(' . $db->last_query . ')'
	));
	exit;
}
?>