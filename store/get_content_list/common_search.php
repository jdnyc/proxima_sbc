<?php

use Proxima\models\content\Category;

try {
	$user_id = $_SESSION['user']['user_id'];
	$_select = array();
	$_from = array();
	$_where = array();
	$_total_where = array();
	$_sort = array();
	$search_q = $_POST['search_q'];
    //$search_q = preg_replace("/[#\&\+\-%@=\/\\\:;,\.'\"\^`~\_|\!\?\*$#<>()\[\]\{\}\s]/i", "", $search_q);
    $ud_content_id = $_POST['ud_content_id'];
    if (empty($ud_content_id)) $ud_content_id = $_POST['meta_table_id'];
    if (empty($ud_content_id)) {
        $p = json_decode($_POST['params']);
        $ud_content_id = $p->meta_table_id;
	}

	if(!is_null($_POST['search_tbar'])){
        $searchTbar = json_decode($_POST['search_tbar']);
	}

	$mapping_category = getCategoryFullPath($db->queryOne("
								SELECT	CATEGORY_ID
								FROM	BC_CATEGORY_MAPPING
								WHERE	UD_CONTENT_ID = $ud_content_id
						"));
	$show_content_in_subcat = $db->queryOne("
		SELECT show_content_subcat_yn
		FROM bc_member_option
		WHERE member_id = (
			SELECT  member_id
			FROM        bc_member
			WHERE   user_id =  '".$user_id."'
		)
    ");
    $show_content_in_subcat = '1';//2017-11-16 이승수, 하위 카테고리 보여주도록 고정.

    $filters = json_decode($_REQUEST['filters'], true);

    /* 기본검색은 -3(콘텐츠 입수 이전 상태)은 제외하고 검색 2017.12.21 Alex */	
    // 필터 에 따라 조건을 넣도록 변경 khk
    if(!empty($filters)) {
        
        if($filters['content_status'] !== null) {
            if(strstr($filters['content_status'], ',')) {
                $arr_filters_content_status = explode(',', $filters['content_status']);
                $arr_or_status = array();
                foreach($arr_filters_content_status as $sub_content_status) {
                    $arr_or_status[] = " c.status='".$sub_content_status."' ";
                }
                array_push($_where , '('.implode(' OR ', $arr_or_status).')');
                array_push($_total_where , '('.implode(' OR ', $arr_or_status).')');
            } else {
                array_push($_where , " c.status='".$filters['content_status']."' ");
                array_push($_total_where , " c.status='".$filters['content_status']."' ");
            }
        }
        if($filters['created_date'] !== null) {
            $fromDate = date('Ymd', strtotime("-{$filters['created_date']} day"));
            $today = date('Ymd');
            array_push($_where , " c.create_date between '{$fromDate}000000' and '{$today}240000' ");
            array_push($_total_where , " c.create_date between '{$fromDate}000000' and '{$today}240000' ");
        }
        if($filters['category_id'] !== null) {
            $_POST['filter_value'] = Category::getPath($filters['category_id']);
        }
        
        if($filters['usr_reviewtech'] !== null) {
            array_push($_where , " um.usr_reviewtech='".$filters['usr_reviewtech']."' ");
            array_push($_total_where , " um.usr_reviewtech='".$filters['usr_reviewtech']."' ");
        }
    } 

    //if ($filter_type == 'category') {
    //카테고리 패스있을때
    if ( ! empty( $_POST['filter_value'])) {
        $treegrid_check = explode('/', $_POST['filter_value']);
        if( !is_numeric($treegrid_check[1]) ) {
            //토픽(treegrid) 형식에서는 영문 노드명이 맨 앞에 붙어서 나오므로 제거.
            $_POST['filter_value'] = str_replace('/'.$treegrid_check[1], '', $_POST['filter_value']);
        }
		if($show_content_in_subcat == '1'){
			array_push($_where , " c.CATEGORY_FULL_PATH like '{$_POST['filter_value']}%' ");
			array_push($_total_where , " c.CATEGORY_FULL_PATH like '{$_POST['filter_value']}%' ");
		}else{
			array_push($_where , " c.CATEGORY_FULL_PATH = '{$_POST['filter_value']}' ");
			array_push($_total_where , " c.CATEGORY_FULL_PATH = '{$_POST['filter_value']}' ");
		}
		
	}else{
		if($show_content_in_subcat == '1'){
			array_push($_where , " c.CATEGORY_FULL_PATH like '".$mapping_category."%'");
			array_push($_total_where , " c.CATEGORY_FULL_PATH like '".$mapping_category."%'");
		}else{
			array_push($_where , " c.CATEGORY_FULL_PATH = '".$mapping_category."'");
			array_push($_total_where , " c.CATEGORY_FULL_PATH = '".$mapping_category."'");
		}
	}
    //}

	$video_codec_filter = json_decode($_POST['video_codec_filter']);
	$where_sql_video_codec_filter;
	if(!empty($video_codec_filter)){
		$sql = array();
		foreach($video_codec_filter as $value){
			$sql[] = 'SYS.sys_video_codec LIKE \'%'.$value.'%\'';
		}
		$where_sql_video_codec_filter = implode(" OR ", $sql);
	}

	$audio_codec_filter = json_decode($_POST['audio_codec_filter']);
	$where_sql_audio_codec_filter;
	if(!empty($audio_codec_filter)){
		$sql = array();
		foreach($audio_codec_filter as $value){
			$sql[] = 'SYS.sys_audio_codec LIKE \'%'.$value.'%\'';
		}
		$where_sql_audio_codec_filter = implode(" OR ", $sql);
	}

	$resolution_filter = json_decode($_POST['resolution_filter']);
	$where_sql_resolution_filter;
	if(!empty($resolution_filter)){
		$sql = array();
		foreach($resolution_filter as $value){
			$sql[] = 'SYS.sys_display_size LIKE \'%'.$value.'%\'';
		}
		$where_sql_resolution_filter = implode(" OR ", $sql);
	}

	$frame_rate_filter = json_decode($_POST['frame_rate_filter']);
	$where_sql_frame_rate_filter;
	if(!empty($frame_rate_filter)){
		$sql = array();
		foreach($frame_rate_filter as $value){
			$sql[] = 'SYS.sys_frame_rate LIKE \'%'.$value.'%\'';
		}
		$where_sql_frame_rate_filter = implode(" OR ", $sql);
	}

	$group_content_filter = json_decode($_POST['group_content_filter']);
	$where_sql_group_content_filter;
	if(!empty($group_content_filter)){
		$sql = array();
		foreach($group_content_filter as $value){
			$sql[] = 'c.is_group LIKE \'%'.$value.'%\'';
		}
		$where_sql_group_content_filter = implode(" OR ", $sql);
	}
	/*
	* tags_content_filter: filters for content tagged or not tagged
	* tags_content_filter: 1: all, 2: tagged, 3: not tagged
	*/
	$tags_content_filter = $_POST['tags_content_filter'];
	if(isset($tags_content_filter)){
		if($tags_content_filter == 1){

		}else if($tags_content_filter == 2){
			$where_tags_content_filter = "  c.content_id IN (
											SELECT	CONTENT_ID
											FROM	BC_TAG
											WHERE	USER_ID = '$user_id'
									) ";
			array_push($_where, $where_tags_content_filter);
			array_push($_total_where, $where_tags_content_filter);
		}else if($tags_content_filter == 3){
			$where_tags_content_filter = "  c.content_id NOT IN (
											SELECT	CONTENT_ID
											FROM	BC_TAG
											WHERE	USER_ID = '$user_id'
									) ";
			array_push($_where, $where_tags_content_filter);
			array_push($_total_where, $where_tags_content_filter);
		}
	}
	$sns_share_filter = json_decode($_POST['sns_share_filter']);
	if(isset($sns_share_filter)){
		$arr_sns_share_filter = array();
		foreach($sns_share_filter as $value){
			array_push($arr_sns_share_filter, "'".$value."'");
		}

		$where_sns_share_filter = "  c.content_id IN (
											SELECT content_id
											FROM bc_social_transfer
											WHERE status = 'SUCCESS'
											AND social_type IN (".join(',',$arr_sns_share_filter).")
								) ";
		array_push($_where, $where_sns_share_filter);
		array_push($_total_where, $where_sns_share_filter);
	}

	$duration_filter = json_decode($_POST['duration_filter']);
	$where_sql_duration_filter;
	if(!empty($duration_filter)){
		$sql = array();
		foreach($duration_filter as $value){
			if($value == '3601'){
				$sql[] = 'SYS.sys_video_duration >'.$value;
			}else{
				$sql[] = 'SYS.sys_video_duration <'.$value;
			}
			
		}
		$where_sql_duration_filter = implode(" OR ", $sql);
	}

    //상세검색일때 추가되는 부분
    if ($action == 'a_search') {
        $p = json_decode($_POST['params']);
        $category_full_path = $_POST['category_full_path'];

        if ($ud_content_id != $p->meta_table_id) {

            //탭 전환시 상세검색 필터와 다르면 0개 검색되도록 이전 ud_content_id 유지
            array_push($_from , " (select content_id from bc_content where ud_content_id='".$p->meta_table_id."') tt ");
            array_push($_where , " tt.content_id = c.content_id ");
			array_push($_total_where , " tt.content_id = c.content_id ");
            //맨 아래에서 count값만 사용하고 나머지 콘텐츠는 없도록 처리
            $empty_content_flag = 'Y';
        }

        $ud_content_id = $p->meta_table_id;

        //메타데이터 정보
        $tablename = MetaDataClass::getTableName('usr',$ud_content_id);
        $fieldIdtoNameMap = MetaDataClass::getFieldIdtoNameMap('usr',$ud_content_id);
        $prefix = MetaDataClass::getVar('usr','field');

        $i = 1;

        foreach ($p->fields as $field) {
            $field_name='';
            if (is_numeric($field->meta_field_id)) {

                $field_name = $fieldIdtoNameMap[$field->meta_field_id];

                if (empty($field_name) ) continue;

                if ($field->type == 'datefield' )   {
                    if (empty($field->s_dt) ) {
                        array_push($_where , 't'.($i).'.content_id = c.content_id');
						array_push($_total_where , 't'.($i).'.content_id = c.content_id');
						array_push($_from , '(select distinct usr_content_id as content_id from '.$tablename.' where '.$field_name.' <= \''.$field->e_dt.'\') t'.$i);
						if(!empty($field->order_type)) $_sort[] = 't'.$i.' '.$field->order_type;
                    } else if ( empty($field->e_dt)) {
                        array_push($_where , 't'.($i).'.content_id = c.content_id');
						array_push($_total_where , 't'.($i).'.content_id = c.content_id');
						array_push($_from , '(select distinct usr_content_id as  content_id from '.$tablename.' where '.$field_name.' >= \''.$field->s_dt.'\') t'.$i);
						if(!empty($field->order_type)) $_sort[] = 't'.$i.' '.$field->order_type;
                    } else {
                        array_push($_where , 't'.($i).'.content_id = c.content_id');
						array_push($_total_where , 't'.($i).'.content_id = c.content_id');
						array_push($_from , '(select distinct usr_content_id as  content_id from '.$tablename.' where '.$field_name.' >= \''.$field->s_dt.'\' and '.$field_name.' <= \''.$field->e_dt.'\') t'.$i);
						if(!empty($field->order_type)) $_sort[] = 't'.$i.' '.$field->order_type;
                    }
                } else if ($field->type == 'listview') {
                    switch ($f->meta_field_id) {
                    case 4037607:
                        array_push($_where , 't'.($i).'.content_id = c.content_id');
						array_push($_total_where , 't'.($i).'.content_id = c.content_id');
						array_push($_from , '(select distinct content_id from meta_multi_xml where contains(val, \'%'.trim($db->escape($field->value)).'% INPATH (/columns/columnF)\') > 0) t'.$i);
						if(!empty($field->order_type)) $_sort[] = 't'.$i.' '.$field->order_type;
                        break;
                    case 11879136:
                        array_push($_where , 't'.($i).'.content_id = c.content_id');
						array_push($_total_where , 't'.($i).'.content_id = c.content_id');
						array_push($_from , '(select distinct content_id from meta_multi_xml where contains(val, \'%'.trim($db->escape($field->value)).'% INPATH (/columns/columnG)\') > 0) t'.$i);
						if(!empty($field->order_type)) $_sort[] = 't'.$i.' '.$field->order_type;
                        break;
                    }
                } else if($field->type == 'combo'){
                    array_push($_where , 't'.($i).'.content_id = c.content_id');
					array_push($_total_where , 't'.($i).'.content_id = c.content_id');
					array_push($_from , '(select distinct usr_content_id as  content_id from '.$tablename.' where lower('.$field_name.') = \''.strtolower(trim($db->escape($field->value))).'\') t'.$i);
					if(!empty($field->order_type)) $_sort[] = 't'.$i.' '.$field->order_type;
                } else {
                    array_push($_where , 't'.($i).'.content_id = c.content_id');
					array_push($_total_where , 't'.($i).'.content_id = c.content_id');
					array_push($_from , '(select distinct usr_content_id as  content_id from '.$tablename.' where lower('.$field_name.') like \'%'.strtolower(trim($db->escape($field->value))).'%\') t'.$i);
					if(!empty($field->order_type)) $_sort[] = 't'.$i.' '.$field->order_type;
                }
            } else {
                $a_field = (string)$field->meta_field_id;
                if ( in_array($a_field, array('created_date', 'expired_date')) ) {
                    if ( empty($field->s_dt) ) {
                        array_push($_where , 't'.($i).'.content_id = c.content_id');
						array_push($_total_where , 't'.($i).'.content_id = c.content_id');
						array_push($_from , '(select content_id from bc_content where ud_content_id='.$ud_content_id.' and '.$a_field.' <= \''.$f->e_dt.'\') t'.$i);
						if(!empty($field->order_type)) $_sort[] = 't'.$i.' '.$field->order_type;
                    } else if ( empty($field->e_dt) ) {
                        array_push($_where , 't'.($i).'.content_id = c.content_id');
						array_push($_total_where , 't'.($i).'.content_id = c.content_id');
						array_push($_from , '(select content_id from bc_content where ud_content_id='.$ud_content_id.' and '.$a_field.' >= \''.$field->s_dt.'\') t'.$i);
						if(!empty($field->order_type)) $_sort[] = 't'.$i.' '.$field->order_type;
                    } else {
                        array_push($_where , 't'.($i).'.content_id = c.content_id');
						array_push($_total_where , 't'.($i).'.content_id = c.content_id');
						array_push($_from , '(select content_id from bc_content where ud_content_id='.$ud_content_id.' and '.$a_field.' >= \''.$field->s_dt.'\' and '.$a_field.' <= \''.$field->e_dt.'\') t'.$i);
						if(!empty($field->order_type)) $_sort[] = 't'.$i.' '.$field->order_type;
                    }
                } else {
                    array_push($_where , 't'.($i).'.content_id = c.content_id');
					array_push($_total_where , 't'.($i).'.content_id = c.content_id');
					array_push($_from , '(select content_id from bc_content where ud_content_id='.$ud_content_id.' and lower(title) like \'%'.strtolower(trim($db->escape($field->value))).'%\') t'.$i);
					if(!empty($field->order_type)) $_sort[] = 't'.$i.' '.$field->order_type;
                }
			}
			
			$i++;
        }
    }

    $tag_category_id = json_decode($_POST['tag_category_id']);

    
	if(isset($tag_category_id)) {
		$query_where = "d.TAG_CATEGORY_ID IN ('".$tag_category_id."') AND d.USER_ID = '$user_id'";
        array_push($_where , $query_where);
        array_push($_total_where , $query_where );

		$tag_filter_from = " left outer join bc_tag d on (d.content_id=c.content_id) ";

    }
	if($_POST['archive_combo'] > 1) {

		$original_from = " LEFT JOIN (
					SELECT  COALESCE(BM.DELETE_DATE, NULL, '0') AS ORI_DELETED
							,BM.CONTENT_ID
					FROM	BC_MEDIA BM
					WHERE	BM.MEDIA_TYPE = 'original'
			 ) ORI
			ON ORI.CONTENT_ID     = C.CONTENT_ID ";
		array_push($_select,' ORI.ORI_DELETED'); //0 : 원본파일 있음 !0 : 삭제날짜가 있으므로 원본파일 삭제됨

    	/*
    	 '1'],//전체 		'2'],//온라인		'3'],//아카이브
    	 */
		$archive_select = " ,ac.status as archive_status  ";
		/*
		$archive_from = " left outer join (
		  SELECT A.CONTENT_ID, A.TASK_ID, B.STATUS
		  FROM (
				SELECT M.CONTENT_ID, MAX(T.TASK_ID) AS TASK_ID
				FROM BC_TASK T, SGL_ARCHIVE SA, BC_MEDIA M
				WHERE SA.TASK_ID = T.TASK_ID
				AND T.TYPE = '110'
				AND SA.MEDIA_ID = M.MEDIA_ID
				AND M.CONTENT_ID IN (SELECT CONTENT_ID FROM BC_CONTENT)
				GROUP BY M.CONTENT_ID
				) A
				LEFT OUTER JOIN (
				  SELECT TASK_ID, STATUS FROM BC_TASK WHERE TYPE='110'
				) B ON (A.TASK_ID=B.TASK_ID)
		) ac on (ac.content_id=c.content_id) ";
		*/

		if($_POST['archive_combo'] == '2') {//ONLINE
			//array_push($_where , " ac.status is null " );
			//array_push($_total_where , " ac.status is null " );
			//$where_archive_combo = "  ORI.ORI_DELETED = '0' ";
			$where_archive_combo = "  c.content_id NOT IN (
											SELECT	CONTENT_ID
											FROM	BC_ARCHIVE_REQUEST
											WHERE	REQUEST_TYPE = 'ARCHIVE'
											AND	STATUS = 'COMPLETE'
									) ";
			array_push($_where, $where_archive_combo);
			array_push($_total_where, $where_archive_combo);
		} else if($_POST['archive_combo'] == '3') {//ARCHIVE
			//array_push($_where , " ac.status='complete' " );
			//array_push($_total_where , " ac.status='complete' " );
			//$where_archive_combo = " ARCHIVE.STATUS = 'complete' ";
			$where_archive_combo = " c.content_id IN (
												SELECT	CONTENT_ID
												FROM	BC_ARCHIVE_REQUEST
												WHERE	REQUEST_TYPE = 'ARCHIVE'
												AND	STATUS = 'COMPLETE'
										) ";
			array_push($_where, $where_archive_combo);
			array_push($_total_where, $where_archive_combo);
		}


	}

	//join archive
	if( $arr_sys_code['interwork_flashnet']['use_yn'] == 'Y' || $arr_sys_code['interwork_oda_ods_l']['use_yn'] == 'Y' || $arr_sys_code['interwork_oda_ods_d']['use_yn'] == 'Y' ){
		$archive_from = " 
			LEFT OUTER JOIN (
				SELECT A.CONTENT_ID, A.TASK_ID, B.STATUS
				FROM (
					SELECT	M.CONTENT_ID, MAX(T.TASK_ID) AS TASK_ID
					FROM	BC_TASK T
							,BC_ARCHIVE_REQUEST SA
							,BC_MEDIA M
					WHERE	SA.TASK_ID		= T.TASK_ID
					AND		T.TYPE			= '110'
					AND		SA.MEDIA_ID		= M.MEDIA_ID
					AND		SA.REQUEST_TYPE	= 'ARCHIVE'
					AND		M.CONTENT_ID IN (SELECT CONTENT_ID FROM BC_CONTENT)
					GROUP BY M.CONTENT_ID
					) A
					LEFT OUTER JOIN (
					  SELECT TASK_ID, STATUS FROM BC_TASK WHERE TYPE='110'
					) B ON (A.TASK_ID=B.TASK_ID)
				) ARCHIVE
			ON ARCHIVE.content_id=c.content_id
		";
		array_push($_select,' ARCHIVE.STATUS AS STATUS_ARCHIVE ');
	}

	if(false && $arr_sys_code['interwork_qc']['use_yn'] == 'Y'){

		$qc_from = "			LEFT JOIN
				(
				SELECT  BT.TASK_ID, BT.STATUS AS Q_STATUS,
						BM.MEDIA_ID, BM.CONTENT_ID,
						BQ.IS_CHECKED, BQ.ERROR_COUNT
				FROM    BC_TASK BT,
						BC_MEDIA BM
						  LEFT JOIN BC_MEDIA_QUALITY_INFO BQ
						  ON  BM.CONTENT_ID = BQ.CONTENT_ID
				WHERE   BT.MEDIA_ID = BM.MEDIA_ID
				AND     BT.TYPE = '15'
				)QC
			ON c.CONTENT_ID = QC.CONTENT_ID	";
			array_push($_select,' QC.ERROR_COUNT AS Q_ERROR_COUNT, QC.Q_STATUS, QC.IS_CHECKED AS Q_IS_CEHCKED ');

	}


	array_push($_from , "
		view_bc_content c
	".$qc_from.$original_from.$archive_from.$tag_filter_from );

    //통합검색부분
    $total_from_array = $_from;
    
    //통합검색부분이 아닌, 자신의 탭만 검색할 용도로 쿼리 수정
    foreach($_from as $i => $sub_from) {
        if(strstr($sub_from, "view_bc_content c")) {
            $usr_meta_table = MetaDataClass::getTableName('usr', $ud_content_id);
            $tmp_bs_content_id = $db->queryOne("select bs_content_id from bc_ud_content where ud_content_id=".$ud_content_id);
            $sys_meta_table = MetaDataClass::getTableName('sys', $tmp_bs_content_id);

            $_from[$i] = str_replace('view_bc_content c',
            'view_bc_content c
            left outer join '.$usr_meta_table.' UM on C.CONTENT_ID=UM.USR_CONTENT_ID
            left outer join '.$sys_meta_table.' SYS on C.CONTENT_ID=SYS.SYS_CONTENT_ID', $sub_from);
        }
    }
    //print_r($_from);exit;
    

    $arr_query = make_db_search_query($search_q, $ud_content_id);
	//print_r($arr_query);
    $search_query = $arr_query['search_query'];
	//검색어가 없는경우 제외 2016-11-03 이성용
	if( !empty($search_query) ){
		array_push($_from , " ( $search_query ) v " );
		array_push($_where , "  c.content_id=v.content_id  " );
		array_push($_total_where , "  c.content_id=v.content_id  " );
	}

	// 콘텐츠 조건 검색
	// 날짜 오늘, 일주일, 한달
	if(!is_null($searchTbar) && !is_null($searchTbar->start_date) && !is_null($searchTbar->end_date) && !($searchTbar->start_date == "All")){

		array_push($_where , " c.created_date between '{$searchTbar->start_date}' and '{$searchTbar->end_date}' ");
		array_push($_total_where , " c.created_date between '{$searchTbar->start_date}' and '{$searchTbar->end_date}' ");
	}
	// 트리 연도별 검색
	if(!is_null($searchTbar) && !is_null($searchTbar->category_start_date)){
		array_push($_where , " c.created_date between '{$searchTbar->category_start_date}' and '{$searchTbar->category_end_date}' ");
		array_push($_total_where , " c.created_date between '{$searchTbar->category_start_date}' and '{$searchTbar->category_end_date}' ");

	}


	// 콘텐츠 상태 등록중, 승인
	if(!is_null($searchTbar) && !is_null($searchTbar->status) && !($searchTbar->status == "All")){
		array_push($_where, " c.status = ".$searchTbar->status);
		array_push($_total_where," c.status = ".$searchTbar->status);
	}
	// 심의 상태 요청, 승인, 반려
	if(!is_null($searchTbar) && !is_null($searchTbar->review_status) && !($searchTbar->review_status == "All")){
		array_push($_where, " c.review_status = ".$searchTbar->review_status);
		array_push($_total_where," c.review_status = ".$searchTbar->review_status);
	}
	// 아카이브 -> 3000 이면 아카이브 된것 3000이 아니면 아닌 것
	if(!is_null($searchTbar) && !is_null($searchTbar->archive_status) && !($searchTbar->archive_status == "All") && ($searchTbar->archiveStatusCombo == "3000")){
		array_push($_where, " c.archv_sttus = '".$searchTbar->archive_status."'");
		array_push($_total_where," c.archv_sttus = '".$searchTbar->archive_status."'");
	}
	if(!is_null($searchTbar) && !is_null($searchTbar->archiveStatusCombo) && !($searchTbar->archiveStatusCombo == "All") && !($searchTbar->archiveStatusCombo == "3000")){
		array_push($_where, " c.archv_sttus != '3000'");
		array_push($_total_where," c.archv_sttus != '3000'");
	}

    array_push($_select,' c.* ');
    array_push($_select,' um.* ');
    array_push($_select,' sys.* ');

    array_push($_where , "  c.is_deleted='N'  " );

	if($arr_sys_code['use_menu_accept']['use_yn'] == 'Y') {
		if(is_numeric($_REQUEST[content_status])){
			array_push($_where , "  c.status ='".$_REQUEST[content_status]."'  " );
			array_push($_total_where , "  c.status ='".$_REQUEST[content_status]."'  " );
		}else{
			if($_SESSION['user']['is_admin'] == 'Y'){
				// array_push($_where , "  c.status in('2', '0', '-3')  " );
				// array_push($_total_where , "  c.status in('2', '0', '-3')  " );
			}else{
				// array_push($_where , "  c.status ='2'  " );
				// array_push($_total_where , "  c.status ='2'  " );
			}
		}
	}else{
		// array_push($_where , "  c.status in('2', '0', '-3')  " );
		// array_push($_total_where , "  c.status in('2', '0', '-3')  " );
	}
    array_push($_where , "  c.is_group != 'C'  " );
	array_push($_total_where , "  c.is_deleted='N'  " );

	array_push($_total_where , "  c.is_group != 'C'  " );

	// 처음 표출되는 콘텐츠는 오늘 등록된 콘첸츠???
    // if(empty($_POST['filter_type']) && empty($_POST['search_tbar']) && empty($_POST['search_array'])){
	// 	$today = date('Ymd');		
	// 	array_push($_where , " c.created_date between '{$today}000000' and '{$today}240000' ");
	// 	array_push($_total_where , " c.created_date between '{$today}000000' and '{$today}240000' ");
	// }

    //유형별 건수 위해 쿼리 분기 2012-11-09 이성용
	$total_where_array = $_total_where;


    // fixme 리스토어 콘텐츠만 보기 임시
    if ($ud_content_id != UD_CONTENT_RESTORE) {
        array_push($_where, " c.ud_content_id='$ud_content_id' ");
    }
    $where = join(' and ' , $_where);
	
	if(!empty($where_sql_video_codec_filter)){
		array_push($_where , '('.$where_sql_video_codec_filter.')');
        array_push($_total_where , '('.$where_sql_video_codec_filter.')');
	}
	if(!empty($where_sql_audio_codec_filter)){
		array_push($_where , '('.$where_sql_audio_codec_filter.')');
        array_push($_total_where , '('.$where_sql_audio_codec_filter.')');
	}
	if(!empty($where_sql_resolution_filter)){
		array_push($_where , '('.$where_sql_resolution_filter.')');
        array_push($_total_where , '('.$where_sql_resolution_filter.')');
	}
	if(!empty($where_sql_frame_rate_filter)){
		array_push($_where , '('.$where_sql_frame_rate_filter.')');
        array_push($_total_where , '('.$where_sql_frame_rate_filter.')');
	}
	if(!empty($where_sql_group_content_filter)){
		array_push($_where , '('.$where_sql_group_content_filter.')');
        array_push($_total_where , '('.$where_sql_group_content_filter.')');
	}
	if(!empty($where_sql_duration_filter)){
		array_push($_where , '('.$where_sql_duration_filter.')');
        array_push($_total_where , '('.$where_sql_duration_filter.')');
    }
	
	
    // 2019-02-25 이승수, view_bc_content에 left outer join 으로 넣기 위해 위쪽으로 변경
    // //로우 메타데이터에서 컬럼 메타데이터로 변경 2014-06-19 이성용
    // $renQuery = MetaDataClass::createMetaQuery('usr' , $ud_content_id , array(
    //     'select' => $_select,
    //     'from' => $_from,
    //     'where' => $_where,
    //     'order' => $_order
    // ) );

    // $_select = $renQuery['select'];
    // $_from = $renQuery['from'];
    // $_where = $renQuery['where'];
    // $_order = $renQuery['order'];

    // $bs_content_id = $db->queryOne("select bs_content_id from bc_ud_content where ud_content_id='$ud_content_id'");
    // $renQuery = MetaDataClass::createMetaQuery('sys' , $bs_content_id , array(
    //     'select' => $_select,
    //     'from' => $_from,
    //     'where' => $_where,
    //     'order' => $_order
    // ));

    // $_select = $renQuery['select'];
    // $_from = $renQuery['from'];
    // $_where = $renQuery['where'];
    // $_order = $renQuery['order'];

    if ( ! empty($_select)) {
        $select = " select ".join(' , ', $_select);
    }

    if ( ! empty($_from)) {
        $from = " from ".join(' , ', $_from);
    }

    if ( ! empty($_where)) {
        $where = " where ".join(' and ', $_where);
    }

    if ( ! empty($_sort)) {
        //$order = " order by DECODE(c.status, 0, 0, 1) asc , ".join(' , ', $_order);
		$order = " order by  ".join(' , ', $_sort);
    } else {
        //$order = " order by DECODE(c.status, 0, 0, 1) asc , ".$order_field.' '.$order_dir;
		if(strstr($order_field, 'usr_')) {
			$order = " order by  um.".$order_field.' '.$order_dir;
		} else if(strstr($order_field, 'sys_')) {
			$order = " order by  sys.".$order_field.' '.$order_dir;
		} else {
			$order = " order by c.".$order_field.' '.$order_dir;
		}
    }

    $query = $select.$from.$where;

	// echo $query;exit;
    $total = $db->queryOne("select count(*) from ( $query ) cnt ");

	$query = $query.$order;


	if ($limit == 2) {
        die($total);
    }

    $tableInfo = MetaDataClass::getTableInfo('usr', $ud_content_id);

	//유형별 건수 위해 쿼리 분기 2012-11-09 이성용
    //$total_where =  " where ".join(' and ', $total_where_array);

    $t_s = array();
	$t_id = array();
    $ud_total_list = array();
    $j = 1;
    foreach ($ud_content_info as $table) {
        $temp_total_where_array = $total_where_array;
        $temp_total_where =  " where ".join(' and ', $temp_total_where_array);
		$tmp_total_from_array = $total_from_array;
		$arr_query = make_db_search_query($search_q, $table['ud_content_id']);
		$search_query = $arr_query['search_query'];
		if( !empty($search_query) ){
			array_push($tmp_total_from_array , " ( $search_query ) v " );
		}
		$total_from = " from ".join(' , ', $tmp_total_from_array);


		$usr_meta_table = MetaDataClass::getTableName('usr', $table['ud_content_id']);
		$tmp_bs_content_id = $db->queryOne("select bs_content_id from bc_ud_content where ud_content_id=".$table['ud_content_id']);
		$sys_meta_table = MetaDataClass::getTableName('sys', $tmp_bs_content_id);

		$tmp_map_category = getCategoryFullPath($db->queryOne("
								SELECT	CATEGORY_ID
								FROM	BC_CATEGORY_MAPPING
								WHERE	UD_CONTENT_ID = ".$table['ud_content_id']
							));

		$tmp_filter_value_query = '';

		// if(!empty($_POST['filter_value']) && $table['ud_content_id'] == $_POST['ud_content_id']){
		// 	$tmp_filter_value_query = "AND	C.CATEGORY_FULL_PATH LIKE '{$_POST['filter_value']}%' ";
		// }
        //array_push($t_s, 'MAX(DECODE(ud_content_id, '.$table['ud_content_id'].', count( ud_content_id))) "'.$table['ud_content_id'].'"');
		if( DB_TYPE == 'oracle' ){
			$query_decode = 'MAX(DECODE(ud_content_id, '.$table['ud_content_id'].', count( ud_content_id))) "t_'.$table['ud_content_id'].'" ';
		}else{
			$query_decode = '
				SUM(CASE
					WHEN UD_CONTENT_ID = '.$table['ud_content_id'].' THEN 1
					ELSE CAST(0 AS DOUBLE PRECISION)
				END
				) AS "t_'.$table['ud_content_id'].'"
			';
        }
        
        $temp_total_from = str_replace('view_bc_content c',
            'view_bc_content c
            left outer join '.$usr_meta_table.' UM on C.CONTENT_ID=UM.USR_CONTENT_ID
            left outer join '.$sys_meta_table.' SYS on C.CONTENT_ID=SYS.SYS_CONTENT_ID', $total_from);

		$tmp_query = '
					LEFT OUTER JOIN (
						SELECT '.$query_decode.'
						'.$temp_total_from.
						$temp_total_where
						.'AND	C.UD_CONTENT_ID = '.$table['ud_content_id']."
						AND		C.CATEGORY_FULL_PATH LIKE '".$tmp_map_category."%'".
						$tmp_filter_value_query."
						GROUP BY UD_CONTENT_ID) B".$j." ON (1=1)";
		array_push($t_id, '"t_'.$table['ud_content_id'].'"');
		array_push($t_s, $tmp_query);

        //$ud_total_list[$table['ud_content_id']] = '';
		$j++;
    }
	$t_id_str = join(' , ', $t_id);
	$t_from_str = join(' ', $t_s);
    //$ud_total_query = "select ".join(' , ',$t_s)." ".$total_from.$total_where."  group by ud_content_id ";
    $ud_total_query = '
				SELECT	'.$t_id_str.'
				FROM	BC_MEMBER A
						'.$t_from_str.'
                WHERE	A.USER_ID = '."'admin'";
//echo $ud_total_query;exit;
	$ud_total_info = $db->queryRow($ud_total_query);

    foreach ($ud_total_info as $f => $v) {
    	$f_id = explode('_', $f);
        $ud_total_list[$f_id[1]] = $v;
    }

	if($arr_sys_code['notice_new_content_count']['use_yn'] == 'Y'){
		$ud_new_total_list = fn_get_new_content_count($db,$search_q, $ud_content_id,$user_id, $ud_content_info);
		$total_new_content_count = 0;
		if ($ud_new_total_list != null){
			foreach ($ud_new_total_list as $key => $value) {
				if ($value['new_cnt'] != null){
					$total_new_content_count += $value['new_cnt'];
				}
			}
		}
	}

    if ($total <= 0) {
        die(json_encode(array(
            'success' => true,
            'total' => $total,
            'results' => array(),
            '1q' => $query,
            'ud_q' => $ud_total_query,
            'ud_total_list' =>$ud_total_list,
			'ud_new_total_list' => $ud_new_total_list,
            'total_new_content_count' => $total_new_content_count
        )));
    }
	
    $db->setLimit($limit, $start);
    $content_list = $db->queryAll( $query );

    if($empty_content_flag == 'Y') {
        $contents = array();
    } else {
        $contents = fetchMetadata($content_list, $qtips);
    }

    // print_r($contents);exit;
 
    die(json_encode(array(
        'success' => true,
        'total' => $total,
        'results' => $contents,
        '1q' => $query,
        'ud_q' => $ud_total_query,
        'ud_total_list' =>$ud_total_list,
		'ud_new_total_list' => $ud_new_total_list,
		'total_new_content_count' => $total_new_content_count
    )));
} catch(Exception $e) {
    die(json_encode(array(
        'success' => false,
		'msg' => $e->getMessage()
    )));
}

function fn_get_new_content_count($db,$search_q, $ud_content_id,$user_id, $ud_content_info){
	$_where = array();
	//	$search_q = preg_replace("/[#\&\+\-%@=\/\\\:;,\.'\"\^`~\_|\!\?\*$#<>()\[\]\{\}\s]/i", "", $search_q);
	$status_filter = 1;

	$_where [] = " CAST(c.status AS DOUBLE PRECISION) >= 2  ";
	switch($status_filter)
	{
		case 1:
		break;
		case 2:
			$_where [] = " c.manager_status like '%accept%' ";
		break;
		case 3:
			$_where [] = " c.manager_status like '%decline%' ";
		break;
		case 4:
			$_where [] = " c.manager_status like '%regist%' ";
		break;
		default:
	}


	$_where [] = " c.is_deleted='N' ";
    // $_where [] = " CAST(c.status AS DOUBLE PRECISION) in(2, 0)  ";
    $_where [] = "c.is_group != 'C'";

	/* if(!empty( $_POST['filter_value'] ))
	{
		$_where [] = " c.CATEGORY_FULL_PATH like '{$_POST['filter_value']}%' ";
	} */

	$where = join(' and ' , $_where);

	$arr_query = make_db_search_query($search_q, $ud_content_id);

	$search_query = $arr_query['search_query'];

	///echo $search_query;
	//file_put_contents(LOG_PATH.'/A_search_query_'.date('Ymd').'.html', date("Y-m-d H:i:s\t")."-	search_query \r\n".$search_query."\r\n", FILE_APPEND);
	$count_query = $arr_query['count_query'];

	//	echo $search_query ;
	//	exit;
	if( !empty($search_query) ){
		$query = "select distinct ud_content_id, count(*) as cnt from
		view_bc_content c,( $search_query ) v where c.content_id=v.content_id ";
		$query .= ' and '.$where;
	}else{
		$query = "select distinct ud_content_id, count(*) as cnt from
		view_bc_content c ";
		$query .= ' where '.$where;
	}

	$query .= ' group by ud_content_id ';

	$count_list = $db->queryAll( $query );
	$contents = array();
	foreach($count_list as $con)
	{
		$contents[$con['ud_content_id']]['cnt'] = $con['cnt'];
	}
    $_total_content_tab_query = array();
    foreach ($ud_content_info as $table) {
        $usr_meta_table = MetaDataClass::getTableName('usr', $table['ud_content_id']);
        $tmp_bs_content_id = $db->queryOne("select bs_content_id from bc_ud_content where ud_content_id=".$table['ud_content_id']);
        $sys_meta_table = MetaDataClass::getTableName('sys', $tmp_bs_content_id); 
        //메타나 시스템메타가 없어도 검색결과에 포함되도록
        $tmp_query = 'SELECT  A.CONTENT_ID, 
                                    A.UD_CONTENT_ID, 
                                    A.CREATED_DATE, 
                                    A.REG_USER_ID, 
                                    A.STATUS,
                                    A.IS_DELETED,
                                    A.IS_GROUP
                            FROM    VIEW_BC_CONTENT A
                                left outer join '.$usr_meta_table.' UM on A.CONTENT_ID=UM.USR_CONTENT_ID
                                left outer join '.$sys_meta_table.' SYS on A.CONTENT_ID=SYS.SYS_CONTENT_ID';
        array_push($_total_content_tab_query, $tmp_query);
    }
    $total_content_tab_query = join(' UNION ALL ' , $_total_content_tab_query);
	$query2 = 
                "SELECT     U.READ_DATE,
                            C.CONTENT_ID,
                            C.UD_CONTENT_ID,
                            C.CREATED_DATE,
                            C.REG_USER_ID
                FROM        (".$total_content_tab_query.") C
                LEFT JOIN
                            (
                                SELECT      CONTENT_ID,
                                            MAX(CREATED_DATE) AS READ_DATE
                                FROM        BC_LOG
                                WHERE       USER_ID='".$user_id."'
                                AND         ACTION = 'read'
                                GROUP BY    CONTENT_ID
                            ) U
                ON  C.CONTENT_ID = U.CONTENT_ID WHERE ";
	$query2 .= $where;
    //file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sql_postgresql_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")." ::query2 :::"."\r\n".$query2."\r\n\n", FILE_APPEND);
	$content_list = $db->queryAll( $query2 );
	$contents = fetchMetadataForNew($content_list, $contents);
	return $contents;
}
?>