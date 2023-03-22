<?php
try
{
	$_select = array();
	$_from = array();
	$_where = array();
	$_order = array();


	$status = '2'; //콘텐츠 상태
	$is_deleted = 'N'; //삭제여부 - 존재

	array_push($_select , " c.* " );

	array_push($_from , " view_bc_content c " );

	array_push($_where , " c.status in( $status, 0) " );
	array_push($_where , " c.is_deleted = '$is_deleted' " );

//	if ( $filter_type == 'category' )
//	{

	if( $filter_type == 'topic_root' ) {
		array_push($_where , " c.parent_content_id is not null " );
	}
	if( $filter_type == 'topic_content_id' ) {
		array_push($_where , " c.parent_content_id = '".$_POST['topic_content_id']."' " );
	}
	if( $filter_type == 'topic_category' ) {
		array_push($_where , " c.parent_content_id in (select content_id from bc_content
			where category_id='".$_POST['topic_category']."') " );
	}

	if($_POST['archive_combo'] > 1) {
		/*
		'1'],//전체 		'2'],//온라인		'3'],//아카이브
		*/
		$archive_select = " ,ac.status as archive_status  ";
		$archive_from = " left outer join (
		  SELECT A.CONTENT_ID, A.TASK_ID, B.STATUS
		  FROM (
				SELECT	M.CONTENT_ID, MAX(T.TASK_ID) AS TASK_ID
				FROM	BC_TASK T
						,BC_ARCHIVE_REQUEST SA
						,BC_MEDIA M
				WHERE	SA.TASK_ID = T.TASK_ID
				AND		T.TYPE = '110'
				AND		SA.MEDIA_ID = M.MEDIA_ID
				AND		SA.REQUEST_TYPE = 'ARCHIVE'
				AND		M.CONTENT_ID IN (SELECT CONTENT_ID FROM BC_CONTENT)
				GROUP BY M.CONTENT_ID
				) A
				LEFT OUTER JOIN (
					SELECT	TASK_ID, STATUS
					FROM	BC_TASK
					WHERE	TYPE	= '110'
				) B ON(A.TASK_ID=B.TASK_ID)
		) ac on (ac.content_id=c.content_id) ";
		if($_POST['archive_combo'] == '2') {
			array_push($_where , " ac.status IS NULL " );
		} else if($_POST['archive_combo'] == '3') {
			array_push($_where , " ac.status IN ('REQUEST', 'APPROVE', 'PROCESSING', COMPLETE') " );
		}

		array_push($_select , " c.* ".$archive_select );
		array_push($_from , " view_content c ".$archive_from );
	}

	//카테고리 패스있을때
	//CM 유형일 경우 카테고리 구분 없이 검색

	if( !empty( $_POST['filter_value'] ) && ( $ud_content_id != UD_CM ) )
	{
		//그룹정보
		$groups_array = getGroups( $_SESSION['user']['user_id'] );
		//PHP5.4에서 지원안됨. $target_category_id = array_pop( explode('/', $_POST['filter_value'] ) );
		$post_filter_value_arr = explode('/', $_POST['filter_value'] );
		$target_category_id = array_pop( $post_filter_value_arr );
		if( empty($groups_array) ) $groups_array = array();

		if( $_SESSION['user']['is_admin']  == 'Y' ){
			//관리자 일때
			$_t_where = " c.CATEGORY_FULL_PATH like '{$_POST['filter_value']}%' ";

			if(  $_POST['category_type'] == 'inner' ){
				$_t_where = " c.CATEGORY_ID ='$target_category_id' ";
			}
		}else{
			//아닐때
			if( ( $_POST['filter_value'] == '/0' ) && ( !in_array( REVIEW_GROUP , $groups_array ) ) && !in_array($q_meta_table_id, $CG_LIST) )
			{
				$_subwhere = array();
				$user_id = $_SESSION['user']['user_id'];
				$lists = $db->queryAll("select category_id from user_mapping where user_id='$user_id'");

				if( !empty($lists) )
				{
					foreach($lists as $list)
					{
						if(  $_POST['category_type'] == 'inner' ){
							$_subwhere [] = " c.CATEGORY_ID ='{$list['category_id']}' ";
						}else{
							$_subwhere [] = " c.CATEGORY_FULL_PATH like '/0/{$list['category_id']}%' " ;
						}
					}
					$_t_where = " ( ".join(' or ' ,$_subwhere)." ) " ;
				}
				else
				{//이런건 없을테지만..
					$_t_where = " ( 1!=1 ) " ;
				}
			}
			else
			{
				$_t_where = " c.CATEGORY_FULL_PATH like '{$_POST['filter_value']}%' ";
				if(  $_POST['category_type'] == 'inner' ){
					$_t_where = " c.CATEGORY_ID ='$target_category_id' ";
				}
			}
		}

		if(!empty($_t_where)){
			array_push($_where, $_t_where);
		}
	}

	//유형별 건수 위해 쿼리 분기 2012-11-09 이성용
	$total_where_array = $_where;
	$total_from_array = $_from;

	array_push($_where , " c.ud_content_id = '$ud_content_id' " );

	if( $order_field != 'title' && $order_field != 'content_id'  && $order_field != 'created_date'  && $order_field != 'category_title' )
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


	//로우 메타데이터에서 컬럼 메타데이터로 변경 2014-06-19 이성용
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

	if (!empty($_select)) {
		$select = " select ".join(' , ', $_select);
	}

	if (!empty($_from)) {
		$from = " from ".join(' , ', $_from);
	}

	if (!empty($_where)) {
		$where = " where ".join(' and ', $_where);
	}

	if (!empty($_order)) {
		$order = " order by ".join(' , ', $_order);
	}

	$query = $select.$from.$where;

	// echo $query;exit;

	$total = $db->queryOne("select count(*) from (".$query.") cnt");

	$db->setLimit($limit, $start);
	$content_list = $db->queryAll($query.$order);
//echo $query;exit;
	$contents = fetchMetadata($content_list);

	$total_where =  " where ".join(' and ', $total_where_array);
	$total_from = " from ".join(' , ', $total_from_array);

	$tableInfo = MetaDataClass::getTableInfo('usr', $ud_content_id );

	$t_s = array();
	foreach( $ud_content_info as $table )
	{
		array_push($t_s, 'MAX(DECODE(ud_content_id, '.$table[ud_content_id].', count( ud_content_id))) "'.$table[ud_content_id].'"');
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
catch(Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage() . '(' . $db->last_query . ')'
	));
	exit;
}
?>