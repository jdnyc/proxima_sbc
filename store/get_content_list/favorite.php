<?php
try
{
	$_select = array();
	$_from = array();
	$_where = array();
	$_order = array();


	$status = '2'; //콘텐츠 상태
	$is_deleted = 'N'; //삭제여부 - 존재

        $favorite_category_id = $_POST['favorite_category_id'];
        $content_type = $_POST['content_type'];

    $bs_content_id = $db->queryOne("select bs_content_id from bc_ud_content where ud_content_id='$ud_content_id'");

	array_push($_select , " c.* " );

	array_push($_from , " view_bc_content c " );
        array_push($_from , " bc_favorite bf " );

	array_push($_where , " c.status = '".$status."' " );
	array_push($_where , " c.is_deleted = '$is_deleted' " );
        array_push($_where , " bf.content_id = c.content_id " );

        array_push($_where , " bf.user_id = '$user_id' " );
        array_push($_where , " bf.content_type = '$content_type'" );
        if($favorite_category_id != 0) {
            array_push($_from , " bc_favorite_category bfc " );
            array_push($_where , " bfc.favorite_category_id = '$favorite_category_id' " );
            array_push($_where , " bf.favorite_category_id = bfc.favorite_category_id " );
        }

        array_push($_order, " bf.show_order desc");

	//유형별 건수 위해 쿼리 분기 2012-11-09 이성용
	$total_where_array = $_where;
	$total_from_array = $_from;

	array_push($_where , " c.ud_content_id = '$ud_content_id' " );

	$renQuery = MetaDataClass::createMetaQuery('usr' , $ud_content_id , array(
		'select' => $_select,
		'from' => $_from,
		'where' => $_where,
		'order' => $_order
	) );

	$_select = $renQuery[select];
	$_from = $renQuery[from];
	$_where = $renQuery[where];
	$_order = $renQuery[order];

	$renQuery = MetaDataClass::createMetaQuery('sys' , $bs_content_id , array(
		'select' => $_select,
		'from' => $_from,
		'where' => $_where,
		'order' => $_order
	) );

	$_select = $renQuery[select];
	$_from = $renQuery[from];
	$_where = $renQuery[where];
	$_order = $renQuery[order];

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
		//$order = " order by  DECODE(c.status, 0, 0, 1) asc , ".join(' , ', $_order);
		$order = " order by c.status asc , ".join(' , ', $_order);
	}



	$query = $select.$from.$where;

	$total = $db->queryOne("select count(*) from (".$query.") cnt");

	$db->setLimit($limit, $start);

	$content_list = $db->queryAll($query.$order);

	$contents = fetchMetadata($content_list);

	$total_where =  " where ".join(' and ', $total_where_array);
	$total_from = " from ".join(' , ', $total_from_array);

	$tableInfo = MetaDataClass::getTableInfo('usr', $ud_content_id );

	$t_s = array();
	foreach( $ud_content_info as $table )
	{
		array_push($t_s, 'MAX(DECODE(ud_content_id, '.$table[ud_content_id].', count( ud_content_id))) "t_'.$table[ud_content_id].'"');
	}
	$ud_total_list = array();
	$query_ud_total_info = " select ".join(' , ',$t_s)." ".$total_from.$total_where."  group by ud_content_id ";
	$ud_total_info =  $db->queryRow($query_ud_total_info);
	
	foreach ($ud_total_info as $f => $v) {
		$f_id = explode('_', $f);
		$ud_total_list[$f_id[1]] = $v;
	}

	echo json_encode(array(
			'success' => true,
			'total' => $total,
			'results' => $contents,
			'query'	=> $query.$order,
			'ud_content_id' =>  $ud_content_id,
			'ud_total_list' =>$ud_total_list
	));
}
catch(Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage() . '(' . $db->last_query . ')'
	));
	exit;
}
?>