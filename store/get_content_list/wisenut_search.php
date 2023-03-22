<?php
try
{
	$Search = new Search();
	$_select = array();
	$_from = array();
	$_where = array();
	$_order = array();
	$query_param = array();
	$search_q = $_POST['search_q'];
	$q_meta_table_id = $_POST['meta_table_id'];
	$order_field	= $_POST['sort'];
	$order_dir		= $_POST['dir'];
	$qtips = array();
	$is_HL = false;//하일라이트 여부
	$ud_total_list = array(); //탭별 카운트 배열
	$content_ids = array();//id 배열

	if( !empty($_REQUEST[params]) ){
		$is_avSearch = true;
	}else{
		$is_avSearch = false;
	}

	if( !empty($_POST['search_array']) && $search_array = json_decode($_POST['search_array'], true) ){
		$search_q = join(' ',  $search_array);
	}

	//NPS 검색 collection 명
	if( in_array(  $ud_content_id, $AUDIO_LIST ) ){
		$query_param['collection'] = 'nps_soundsrc';
	}else{
		$query_param['collection'] = 'product';
	}

	$query_param['query'] = '';
	$query_param['hl'] = 'on';
	$query_param['booleanQuery'] = 'E<STATUS:contains:2>';
		//카테고리 패스있을때
	if( !empty( $_POST['filter_value'] ) ){
		$category_id = array_pop(explode('/', $_POST['filter_value']));
		$category_full_path = getCategoryFullPath($category_id);
		$query_param['booleanQuery'] .= '^E<CATEGORY_FULL_PATH:contains:'.$category_full_path.'>';
	}

	//탭(콘텐츠 유형) 구분자 명 얻기 ex> ingest
	$ud_content_tableInfo = MetaDataClass::getTableInfo('usr',$ud_content_id);
	$ud_content_table = strtolower($ud_content_tableInfo[ud_content_code]);

	if( $is_avSearch ){
		//상세 검색
		$params_data = $Search->SearchParam($_REQUEST[params]);
		if( !empty($params_data['order']) ){
			$query_param['sortField'] = $ud_content_table.'^'.$params_data['order'];
		}
		$query_param['booleanQuery'] .= $params_data['query'];
	}else{
		//일반 통합 검색
		$query_param['query'] = $search_q;
		if( empty($query_param['query']) ){
			$query_param['sortField'] = $ud_content_table.'^'.'CONTENT_ID_SORT/DESC';
		}
	}

	//하일라이트 여부
	if( !empty($query_param['query']) || !empty($params_data['query']) ){
		$is_HL = true;
	}

	$arr_content_list_query = array();
	$tableinfo =  $db->queryAll("select * from bc_ud_content order by show_order");
	$ud_content_NametoIdMap = array();
	foreach($tableinfo as $table){
		$ud_content_NametoIdMap[strtolower($table[ud_content_code])] = $table[ud_content_id];
	}

	//검색엔진 검색 URL
	$receive_xml = $Search->SearchQuery($query_param, $start, $limit);
	//XML 을 json 으로 변환
	$receive_json = $Search->xmltojson($receive_xml);

	$collection_list =  $receive_json['Collection'] ;

	if(!is_array($collection_list) )throw new Exception('검색엔진 파싱 오류');

	$FieldNametoKorNameMap =  MetaDataClass::getFieldNametoKorNameMap('usr' , $ud_content_id);
	$FieldNametoKorNameMap[TITLE] = '제목';

	if( !empty($collection_list[Error])) throw new Exception($collection_list[Error][Value]);

	//단일 콜렉션일때
	if( !empty($collection_list[DocumentSet]) ){
		$collection_list = array($collection_list);
	}

	foreach($collection_list as $list ){

		if( !empty($list[Error][Value])) throw new Exception($list[Error][Value]);

		if( $list[Id] == $ud_content_table ){
			//메인
			if( !empty($list[DocumentSet]) && !empty($list[DocumentSet][Document]) && $list[DocumentSet][Count] > 0 ){

				//단일 데이터
				if(!empty($list[DocumentSet][Document][Field][DOCID])){
					$document_array = array( $list[DocumentSet][Document] );
				}else{
					$document_array = $list[DocumentSet][Document];
				}
				foreach( $document_array as $field_datas){
					$field_data = $field_datas[Field];
					$content_id = $field_data[DOCID];
					array_push($content_ids, $content_id);
					if($is_HL) $qtips[$content_id] = $Search->MapQtip( $field_data , $FieldNametoKorNameMap );
				}
			}
			$total =  (int)$list[DocumentSet][TotalCount];
		}
		if( empty($_REQUEST[params]) && !empty($list[DocumentSet]) && !empty($list[Id])  ){
			//다른 탭 검색 카운트 데이터
			$ud_total_list[$ud_content_NametoIdMap[$list[Id]]] = (int)$list[DocumentSet][TotalCount];
		}
	}

	if( empty($content_ids) ){
		die(json_encode(array(
			'success' => true,
			'total' => 0,
			'results' => array(),
			'ud_total_list' =>$ud_total_list,
			'search' =>$receive_json
		)));
	}

	$status = '2'; //콘텐츠 상태
	$is_deleted = 'N'; //삭제여부 - 존재
	array_push($_select , " c.* " );
	array_push($_from , " view_bc_content c " );
	array_push($_where , " c.status = $status " );
	array_push($_where , " c.is_deleted = '$is_deleted' " );

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

	$select = " select ".join(' , ', $_select);
	$from = " from ".join(' , ', $_from);
	if (!empty($_where)){
		$where = " where ".join(' and ', $_where);
	}
	if(!empty($_order)){
		$order = " order by ".join(' , ', $_order);
	}
	foreach($content_ids as $content_id){
		$query_list = $select.$from.$where." and c.content_id='$content_id' ";
		array_push($arr_content_list_query, $query_list );
	}
	$arr_content_list_query = implode(' union ', $arr_content_list_query);
	$Search->_log($arr_content_list_query);
	$content_list = $db->queryAll($arr_content_list_query);
	$contents = fetchMetadata($content_list, $qtips);


	die(json_encode(array(
		'success' => true,
		'total' => $total,
		'results' => $contents,
		'ud_content_id' => $ud_content_id,
		'ud_total_list' => $ud_total_list,
		'search' =>$receive_json
	)));
}
catch(Exception $e)
{
	die(json_encode(array(
		'success' => false,
		'search' =>$receive_json,
		'msg' => $e->getMessage()
	)));
}

?>