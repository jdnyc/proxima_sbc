<?php
try
{
	$_where = array();

	if($content_type == MOVIE)
	{
		$content_type = "ct.bs_content_id='".$content_type."' and ";
		$status = "and c.status ='-5'";
	}
	else if ($content_type == 'all' || empty($content_type) )
	{
		$status = "and c.status ='-5'";
		$content_type = '';
	}
	else
	{
		$content_type = "ct.bs_content_id='".$content_type."' and ";
	}

	$query = "
		select
			*
		from 
			bc_content c
		where 
		$content_type 		
		c.is_deleted='N' 
		and c.reg_user_id!='watchfolder' 
		$status" ;


	if ($filter_type == 'category')
	{
		array_push($_where, "c.category_full_path like '".$filter_value."%'");
	}

	if (!empty($_where))
	{
		$query .= " and ".join(' and ', $_where);
	}

	//echo $query;exit;

	$total = $db->queryOne("select count(*) from (".$query.") cnt");
	//echo $db->last_query;

	if ($order_field == 'content_id' || $order_field == 'title') {
		$query .=  " order by c.$order_field $order_dir ";
	} else {
		$query .=  " order by title $order_dir ";
	}

	/*
	2010-11-03
	박정근
	디비 공통적으로 사용할수 있도록 limit 절 변경
	$db->setLimit(가져올수, 시작줄)
	*/

	$db->setLimit($limit, $start);
	$content_list = $db->queryAll($query);

//	$contents = fetchMetadata($content_list);
	echo json_encode(array(
		'success' => true,
		'total' => $total,
		'results' => $content_list
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