<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

use Proxima\core\Session;

Session::init();

function make_db_search_query($in_keyword, $in_ud_content_id) {
	global $db, $arr_sys_code;

	$tablename = MetaDataClass::getTableName('usr', $in_ud_content_id);
	$MetaFieldInfo = MetaDataClass::getMetaFieldInfo ('usr' , $in_ud_content_id);

	$arr_in_keyword = explode(' ', $in_keyword);

	$arr_search_query = array();
	foreach ($arr_in_keyword as $keyword) {
		if($keyword == "")
			continue;

		$keyword = strtoupper(trim($keyword));
		$keyword = str_replace("\\", "", $keyword);
		$keyword = str_replace("'", "''", $keyword);
		$keyword = str_replace("_", "\\_", $keyword);
		$keyword = str_replace("%", "\\%", $keyword);

		$orwhere = array();
		$query = '';
		if(count($MetaFieldInfo) > 1) {
			foreach ($MetaFieldInfo as $field ) {
				if (($field['usr_meta_field_type'] != 'container' ) && $field['is_search_reg'] == 1) {
					array_push($orwhere , " ( upper(".$field['usr_meta_field_code'].") like "."'%{$keyword}%' ESCAPE '\\' ) " );
				}
			}

			$where = join(' or ' , $orwhere );

			$query = " ( select usr_content_id as content_id from $tablename where $where ) ";
		}

		//$query = "( SELECT distinct content_id FROM (select * from bc_usr_meta_value where  usr_meta_field_id in (SELECT usr_meta_field_id FROM bc_usr_meta_field WHERE is_search_reg = '1') and UPPER(usr_meta_value) like '%{$keyword}%') ) ";

		$query_title = " ( select distinct content_id from bc_content where UPPER(title) like '%{$keyword}%' ESCAPE '\\') ";

		$query_marktitle = " ( select distinct content_id from bc_mark where UPPER(title) like '%{$keyword}%' or UPPER(comments) like '%{$keyword}%' ESCAPE '\\' ) ";

		//$query_preview = "( select distinct content_id from view_preview where UPPER(content) like '%{$keyword}%' )";
		$arr_tmp = array($query_title, $query_marktitle);

		if(!empty($query)) {
			array_push($arr_tmp, $query);
		}


		$query = implode(' UNION ', $arr_tmp);

		array_push($arr_search_query, "(".$query.")");
	}
	
	 /*사용자화 for SMC*/
	if( $arr_sys_code['smc_yn']['use_yn']=='Y'){
		if($_SESSION['user']['is_admin'] != 'Y'){
			array_push($arr_search_query, "( select usr_content_id as content_id from $tablename where USR_ISOPEN='공개' )");
		}
	}

	$search_query = implode(' INTERSECT ', $arr_search_query);

	if (empty($search_query)) {
       // $search_query = "select content_id from view_bc_content";
		$search_query = "";
    }
	$count_query = "SELECT count(*) FROM ({$search_query}) mv_count";

	return array('search_query' => $search_query, 'count_query' => $count_query);
}

function fetchMetadataForNew($content_list, $contents)
{
	global $db;

	$user_id = $_SESSION['user']['user_id'];

	$metadatas = array();
	foreach($content_list as $con)
	{
		$metadatas[$con['content_id']] = $con;
	}

	$contents = getCheckNewInfoTotal($user_id, $metadatas, $contents);

	return $contents;
}

// 쿼리 변경 2011-5-15 by 이성용
function fetchMetadata($content_list, $qtips = array()) {
	global $db, $arr_sys_code;
	$content_ids = array();

	if (empty($content_list)) {
		return array();
	}

	foreach ($content_list as $list) {
		array_push($content_ids, $list['content_id']);
	}

	$ud_content_id = $content_list[0]['ud_content_id'];
	$bs_content_id = $content_list[0]['bs_content_id'];

	$fields = $db->queryAll("
				SELECT *
				FROM bc_usr_meta_field
				WHERE ud_content_id='$ud_content_id'
				ORDER BY SHOW_ORDER
			");
	$contents = join(',', $content_ids);

	$_s = array();

    $user_id = $_SESSION['user']['user_id'];

    //즐겨찾기 사용안함
    $favorites = array();
    /*$favorites_contents = $db->queryAll("SELECT CONTENT_ID FROM BC_FAVORITE WHERE USER_ID = '$user_id' order by show_order");
    foreach ($favorites_contents as $key => $val) {
        array_push($favorites, $val['content_id']);
    }
    */
	$qc_lists = array();
	if( $arr_sys_code['interwork_qc']['use_yn'] == 'Y' ){
		// $qc_list = $db->queryAll('select content_id from bc_media_quality_info where content_id in ('.$contents.')');
		// foreach ($qc_list as $key => $val) {
		// 	array_push($qc_lists, $val['content_id']);
		// }
	}

//	foreach ($fields as $field)
//	{
//		array_push($_s, 'MAX(DECODE(T1.USR_META_FIELD_ID, '.$field['usr_meta_field_id'].', USR_META_VALUE)) "f'.$field['usr_meta_field_id'].'"');
//	}
//
//	$query = "
//		SELECT
//		MAX(CONTENT_ID) content_id,
//		".join(', ', $_s)."
//		FROM
//		(
//		SELECT ROWNUM RN, V.USR_META_VALUE, V.USR_META_FIELD_ID, V.CONTENT_ID
//		FROM BC_USR_META_VALUE V
//		WHERE V.CONTENT_ID in ( ".$contents."  )
//
//		ORDER BY V.CONTENT_ID DESC
//		) T1
//		GROUP BY CONTENT_ID
//		";
////	echo $query;
//	$metadatas = $db->queryAll($query);

    $metadatas = $content_list;
    
    $metadatas = getContentStatusMapping($content_ids, $metadatas);
    $metadatas = getContentUsrMapping($content_ids, $metadatas);
    

	// 쿼리 IN 으로 조인하므로 정렬.
	$metadatas = getSorting($content_ids, $metadatas);

    // 기본 콘텐츠 정보 추가
	$metadatas = getContentMapping($content_list, $metadatas);

    // 시스템 메타 정보 추가
	// $metadatas = getSysMetaMapping($content_ids, $metadatas);

	$metadatas = getSysMetaMapping($content_ids, $metadatas, $bs_content_id);

    // 섬네일 / 리스트보기 필드 정보 추가
	$metadatas = getMainFieldMapping($fields, $metadatas);

	// 시사실 플레이어용 meta 작성
	//$metadatas = getMetaInfo($ud_content_id, $fields, $metadatas);

    // 미디어 패스 정보 추가
	$metadatas = getMediaMapping( $content_ids, $metadatas );

	// 스토리지 루트 패스 정보 추가
	$metadatas = getStorageMapping($ud_content_id, $metadatas);

    // 경로 및 재생길이 때문에 getStorageMapping / getSysMetaMapping 필요
	$metadatas = getQtipMapping($content_ids, $metadatas, $qtips);

	if($arr_sys_code['interwork_sns']['use_yn'] == 'Y') {
		$metadatas = snsMapping($content_ids, $metadatas);
	}

	//$metadatas = IsArchiveMapping($content_ids, $metadatas); // Archive info mapping

	// loudness mapping
	if($arr_sys_code['interwork_loudness']['use_yn'] == 'Y') {
		$metadatas = IsLoudnessMapping($content_ids, $metadatas);
    }
    
    //평균정보 매핑
    $metadatas = getLoudnessInfo($content_ids, $metadatas);

	/*CJO 사전제작일 경우 QC정보 매핑하도록 추가 - 2018.03.20 Alex*/
	if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\ContentAction')) {		
		//if($ud_content_id == PRE_PRODUCE) {
			$metadatas = \ProximaCustom\core\ContentAction::getQulictyCheckStatus($content_ids, $metadatas);
		//}
		$metadatas = \ProximaCustom\core\ContentAction::getLoudnessStatus($content_ids, $metadatas);
	}

	// 각 미디어의 작업정보 추가
	$metadatas = getTaskMapping($content_ids,$metadatas);
	
	//$metadatas = getCheckNewInfo($content_ids, $user_id, $metadatas); //New 아이콘 붙일 여부 판단할 read_date구함

	// if( $arr_sys_code['interwork_qc']['use_yn'] == 'Y' ){
	// 	$metadatas = QCstatusMapping($content_ids, $metadatas);
	// }

	// 섬네일 아이콘 정보 추가 상태정보 필요
	$metadatas = getIconMapping($content_list, $metadatas, $favorites, $qc_lists, $content_ids);

	// 그룹 콘텐츠 자식 콘텐츠 가져오기
	// $metadatas = getChildContents($metadatas);
	// if($bs_content_id == IMAGE){
	// 	$metadatas = getThumbImageForGroup($metadatas);
	// }
	// if($bs_content_id == MOVIE){
	// 	$metadatas = getThumbImageForGroupMovie($metadatas);
	// }
	// if($bs_content_id == SEQUENCE){
	// 	$metadatas = getThumbImageForGroupMovie($metadatas);
	// }
	// // Premiere 시퀀스 관련
	// if($arr_sys_code['premiere_plugin_use_yn']['use_yn'] == 'Y') {
	// 	$metadatas = getPremierContents($content_ids ,$metadatas);
	// }

	return $metadatas;
}

function getTaskMapping($content_ids,$metadatas){

	global $db;
	global $arr_sys_code;

	if( $arr_sys_code['interwork_flashnet']['use_yn'] == 'Y' || $arr_sys_code['interwork_oda_ods_l']['use_yn'] == 'Y' || $arr_sys_code['interwork_oda_ods_d']['use_yn'] == 'Y'){
		//archive status check with out REJECT, FAILED. g.c.Shin 2016.06.27
		$oda_archive = "
			LEFT JOIN BC_ARCHIVE_REQUEST AR
			ON(AR.CONTENT_ID = M.CONTENT_ID AND AR.REQUEST_TYPE = 'ARCHIVE' AND AR.STATUS IN ('REQUEST', 'APPROVE', 'PROCESSING', 'COMPLETE'))
		";
		$oda_archive_status = " , AR.STATUS AS STATUS_ARCHIVE_ODA, AR.TAPE_ID AS ARCHIVE_TAPE_ID ";
		$is_archive_use = true;
	}

	$task_info = $db->queryAll("
		SELECT	M.CONTENT_ID , M.MEDIA_ID , M.MEDIA_TYPE,M.STATUS M_STATUS, T.TYPE , T.STATUS ".$oda_archive_status."
		FROM	BC_TASK T,
				BC_MEDIA M ".$oda_archive."
		WHERE	T.MEDIA_ID=M.MEDIA_ID
		AND		M.CONTENT_ID IN ( ".join(',', $content_ids)." )
		ORDER BY T.TASK_ID DESC
	");

	foreach( $metadatas as $key => $data )
	{
		if( $arr_sys_code['interwork_flashnet']['use_yn'] == 'Y' || $arr_sys_code['interwork_oda_ods_l']['use_yn'] == 'Y' || $arr_sys_code['interwork_oda_ods_d']['use_yn'] == 'Y' ){
			$metadatas[$key]['archive_yn'] = 'N';
		}
		foreach($task_info as $task)
		{
			if( $data['content_id'] == $task['content_id'] )
			{
				$metadatas[$key]['archive_tape_id'] = $task['archive_tape_id'];

				if($is_archive_use){
					$archive_status = $db->queryRow("select content_id ,archive_status  from bc_content_status where content_id = ".$task['content_id']);
					if ($archive_status['archive_status']){
						$metadatas[$key]['archive_yn'] = $archive_status['archive_status'];
					}
				}

				switch( $task['media_type'] )
				{
					case 'original':
						$metadatas[$key]['ori_task_status'] = $task['status'];

						if( $task['status'] =='assigning' || $task['status'] == 'queue' || $task['status'] == 'processing' || $task['status'] == 'progressing' ){
							$metadatas[$key]['is_working'] = '1';
						}else{
							$metadatas[$key]['is_working'] = '0';
						}

					break;

					case 'proxy':
						$metadatas[$key]['proxy_task_status'] = $task['status'];
					break;

					case 'thumb':
						$metadatas[$key]['thumb_task_status'] = $task['status'];
					break;

					case 'attach':
						if( ( $task['status'] == 'complete' ) && is_numeric( $metadatas[$key]['attach_count'] ) )
						{
							$metadatas[$key]['attach_count'] += 1;
						}
						else if ( ( $task['status'] == 'complete' ) && !is_numeric( $metadatas[$key]['attach_count'] ) )
						{
							$metadatas[$key]['attach_count'] = 1;
						}

					break;
				}
			}
		}
	}

	return $metadatas;
}

function getCheckNewInfo($content_ids, $user_id, $metadatas){
	global $db;

	/*$arr_info = $db->queryAll("select * from check_new_by_user
		where content_id in (".implode($content_ids, ',').")
		  and user_id='".$user_id."'"); */
	$arr_info = $db->queryAll("

								SELECT 		CONTENT_ID,
											MAX(CREATED_DATE) as CREATED_DATE
								FROM 		BC_LOG
								WHERE 		CONTENT_ID IN (".implode($content_ids, ',').")
								AND 		ACTION = 'read'
		 						AND 		USER_ID='".$user_id."'
		 						GROUP BY 	CONTENT_ID
		");

	foreach( $metadatas as $key => $data )
	{
		$metadatas[$key]['read_date'] = $data['created_date'];
		if( empty($metadatas[$key]['read_date']) ) {
			$metadatas[$key]['read_date'] = '99991231235959';
		}
		foreach($arr_info as $info)
		{
			if( $data['content_id'] == $info['content_id'] )
			{
				$metadatas[$key]['read_date'] = $info['created_date'];
			}
		}
	}

	return $metadatas;
}

function getCheckNewInfoTotal($user_id, $metadatas, $contents){
	global $db, $arr_sys_code;

	$today_obj = date('YmdHis');

	$arr_content_id = array();
	foreach( $metadatas as $key => $data )
	{
		$arr_content_id[] = $data['content_id'];
	}

	//$arr_cur_category_grant = checkGrantCategoryFullPathMulti($arr_content_id, $_SESSION['user']['groups']);
	$arr_ud_info = $db->queryAll("select * from bc_ud_content");
	$arr_ud_grant = array();
	foreach($arr_ud_info as $ud)
	{
		$arr_ud_grant[$ud['ud_content_id']] = checkAllowUdContentGrant($user_id, $ud['ud_content_id'], GRANT_READ );
	}
	
	if($arr_sys_code['notice_new_content_count']['ref1'] != ''){
		$limited_date = $arr_sys_code['notice_new_content_count']['ref1'];

	}else{
		$limited_date = 7;
	}
	foreach( $metadatas as $key => $data )
	{
		$content_id = $data['content_id'];
		$ud_content_id = $data['ud_content_id'];

		$read_date = $data['read_date'];
		if($read_date == '') $read_date = $data['created_date'];
		if($read_date == '') $read_date = '99991231235959';

		$modi_date = date('YmdHis', strtotime($data['created_date']));
		$diff_created_date = date_diff_day($modi_date, $today_obj);
		if( 0 <= $diff_created_date && $diff_created_date < $limited_date )
		{
			if($read_date <= $modi_date)
			{
				$contents[$ud_content_id]['new_cnt']++;
				$contents[$ud_content_id]['new_content_ids'] .= $content_id.',';
			}
		}
	}

	return $contents;
}

function IsArchiveMapping($content_ids, $metadatas){
	global $db;
	global $arr_sys_code;


	if( $arr_sys_code['interwork_flashnet']['use_yn'] == 'Y' || $arr_sys_code['interwork_oda_ods_l']['use_yn'] == 'Y' || $arr_sys_code['interwork_oda_ods_d']['use_yn'] == 'Y' ){

		//$query = "select a.* from archive_list a, bc_content c where c.content_id=a.content_id and  c.content_id in ( ".join(',', $content_ids)." ) ";

		//정렬을 task_id 순으로 해야 마지막 정보가 foreach 돌면서 들어간다.
		$archive_info = $db->queryAll("
							SELECT	T.STATUS, M.CONTENT_ID, SA.IF_KEY1 AS SESSION_ID
							FROM	BC_TASK T, BC_ARCHIVE_REQUEST SA, BC_MEDIA M
							WHERE	SA.TASK_ID = T.TASK_ID
							AND		T.TYPE = '110'
							AND		SA.MEDIA_ID = M.MEDIA_ID
							AND		SA.REQUEST_TYPE = 'ARCHIVE'
							AND		M.CONTENT_ID IN ( ".join(',', $content_ids).")
							ORDER BY T.TASK_ID
						");

		//$archive_info = $db->queryAll($query);

		foreach( $metadatas as $key => $data )
		{
			foreach($archive_info as $archive)
			{
				if( $data['content_id'] == $archive['content_id'] )
				{
					$metadatas[$key]['archive_session_id'] = trim($archive['session_id']);
					$metadatas[$key]['archive_status'] = trim($archive['status']);
				}
			}
		}
	}

	//No need, because, refer BC_CONTENT_STATUS table ARCHIVE_YN column.
//	else if( $arr_sys_code['interwork_oda_ods_l']['use_yn'] == 'Y' ){
//		foreach( $metadatas as $key => $data ){
//			$metadatas[$key]['archive_status'] = $metadatas[$key]['status_archive'];
//		}
//	}

	return $metadatas;
}

function IsLoudnessMapping($content_ids, $metadatas){
	global $db;

	$loudness_info = $db->queryAll("
						SELECT	L.*
						FROM	TB_LOUDNESS L
						WHERE	L.REQ_TYPE = 'M'
						ORDER BY L.REQ_DATETIME ASC
					");


	//	$archive_info = $db->queryAll($query);

	foreach( $metadatas as $key => $data )
	{
		foreach($loudness_info as $loudness)
		{
			if( $data['content_id'] == $loudness['content_id'] )
			{
				$metadatas[$key]['loudness_id'] = trim($loudness['loudness_id']);
				$metadatas[$key]['loudness_status'] = trim($loudness['measurement_state']);
			}
		}
	}

	return $metadatas;
}

function snsMapping($content_ids, $metadatas){
    global $db;

	if(DB_TYPE == 'oracle' ) {
		$query = "
			SELECT	*
			FROM	BC_SOCIAL_TRANSFER
			WHERE	CONTENT_ID IN ( ".join(',', $content_ids)." )
			AND		DELETED_DATE IS NULL
		";
	} else {
		$query = "
			SELECT	*
			FROM	BC_SOCIAL_TRANSFER
			WHERE	CONTENT_ID IN ( ".join(',', $content_ids)." )
			AND		DELETED_DATE=''
		";
	}
    
    $sns_info = $db->queryAll($query);

    foreach ($metadatas as $key => $data) {
        foreach ($sns_info as $sns) {
            if ($data['content_id'] == $sns['content_id']) {
				if($metadatas[$key]['sns_youtube_status'] == '' && $sns['social_type'] == 'YOUTUBE') {
					$metadatas[$key]['sns_youtube_status'] = $sns['status'];
	                $metadatas[$key]['sns_youtube_url'] = $sns['web_url1'];
				}
				if($metadatas[$key]['sns_facebook_status'] == '' && $sns['social_type'] == 'FACEBOOK') {
					$metadatas[$key]['sns_facebook_status'] = $sns['status'];
	                $metadatas[$key]['sns_facebook_url'] = $sns['web_url1'];
				}
				if($metadatas[$key]['sns_twitter_status'] == '' && $sns['social_type'] == 'TWITTER') {
					$metadatas[$key]['sns_twitter_status'] = $sns['status'];
	                $metadatas[$key]['sns_twitter_url'] = $sns['web_url1'];
				}
            }
        }
    }

    return $metadatas;
}



function getSorting($content_ids, $metadatas) {

	foreach ($metadatas as $idx => $data) {
		foreach ($content_ids as $key => $content_id) {
			if ($content_id == $data['content_id']) {
				$metadatas[$idx]['_sort'] = $key;
			}
		}
	}

	$_sort = array();
	foreach ($metadatas as $k=>$v) {
		$_sort[$k] = $v['_sort'];
	}

	array_multisort($_sort, SORT_ASC, SORT_NUMERIC, $metadatas);

	foreach ($metadatas as $idx => $data) {
		$metadatas[$idx]['fields'] = $data;
	}

	return $metadatas;
}

function getSysMetaMapping($content_ids, $metadatas, $bs_content_id) {
    global $db;
    
    $contentStatus = \Api\Models\ContentSysMeta::whereIn('sys_content_id', $content_ids )->get();
    foreach ($metadatas as $key => $data) {
        foreach ($contentStatus as $status) {
            if ($data['content_id'] == $status->sys_content_id ) {
                foreach($status->toArray() as $code => $val)
                {
                    $metadatas[$key][$code] = $val;
                }
            }
        }
    }
	return $metadatas;
}


function getStorageMapping($ud_content_id , $metadatas){

	global $db;

    $storage_info = $db->queryAll("SELECT * FROM BC_STORAGE ");

	if(empty($storage_info)) return $metadatas;

	foreach($metadatas as $idx => $data)
	{
		foreach($storage_info as $storage)
		{
            if( $storage['storage_id'] ==  $data['ori_storage_id'] ){
                $metadatas[$idx]['highres_mac_path'] = $storage['path_for_mac'];
                $metadatas[$idx]['highres_unix_path'] = $storage['path_for_unix'];
                $metadatas[$idx]['highres_path'] = $storage['path'];
                $metadatas[$idx]['highres_web_root'] = $storage['virtual_path'];
            }
            if( $storage['storage_id'] ==  $data['proxy_storage_id'] ){
                $metadatas[$idx]['lowres_mac_path'] = $storage['path_for_mac'];
                $metadatas[$idx]['lowres_unix_path'] = $storage['path_for_unix'];
                $metadatas[$idx]['lowres_root'] = $storage['path'];
                $metadatas[$idx]['lowres_web_root'] = $storage['virtual_path'];                
            }
            if( $storage['storage_id'] ==  $data['thumb_storage_id'] ){
                $metadatas[$idx]['thumb_web_root'] = $storage['virtual_path']; 
            }
			// switch($storage['us_type'])
			// {
			// 	case 'highres':
			// 		$metadatas[$idx]['highres_mac_path'] = $storage['path_for_mac'];
			// 		$metadatas[$idx]['highres_unix_path'] = $storage['path_for_unix'];
			// 		$metadatas[$idx]['highres_path'] = $storage['path'];
			// 		$metadatas[$idx]['highres_web_root'] = $storage['virtual_path'];
			// 	break;

			// 	case 'lowres':
			// 		$metadatas[$idx]['lowres_mac_path'] = $storage['path_for_mac'];
			// 		$metadatas[$idx]['lowres_unix_path'] = $storage['path_for_unix'];
			// 		$metadatas[$idx]['lowres_root'] = $storage['path'];
			// 		//$metadatas[$idx]['lowres_web_root'] = $storage['virtual_path'];
			// 		$metadatas[$idx]['lowres_web_root'] = 'http://' . STREAM_SERVER_IP . LOCAL_LOWRES_ROOT;
			// 	break;
			// }
		}
	}

	return $metadatas;
}

function getQtipMapping($content_ids, $metadatas , $qtips) {
	global $db, $logger;

	$thumb_total_array = array();

	if ( ! empty($qtips)) {
		foreach ($metadatas as $key => $value) {
			$metadatas[$key]['qtip'] = $qtips[$value['content_id']];
		}
	} else {
		if ($metadatas[0]['bs_content_id']  == IMAGE) {
			foreach ($metadatas as $idx => $data) {
				if ($data['bs_content_id'] == IMAGE) {

					if($data['is_group'] == 'I'){
						$str = "<div style='display: block;'><img  style='display:table-cell;		vertical-align:middle;		margin:auto;		width:auto;		height:auto;		max-width:400px;		max-height:400px;'  src='".$data['thumb_web_root']."/".$data['proxy_path']."'></div>";
					}else{
						$content_id = $data['content_id'];
						$thumb_id = $db->queryOne("SELECT THUMBNAIL_CONTENT_ID FROM BC_CONTENT WHERE CONTENT_ID = $content_id");
						if(!isset($thumb_id)){
							$str = "<div style='display: block;'><img  style='display:table-cell;		vertical-align:middle;		margin:auto;		width:auto;		height:auto;		max-width:400px;		max-height:400px;' src='".$data['thumb_web_root']."/".$data['proxy_path']."'></div>";
						}else{
							$query = "	SELECT	*
										FROM	BC_MEDIA M
												LEFT JOIN BC_CONTENT C ON M.CONTENT_ID = C.THUMBNAIL_CONTENT_ID
										WHERE	C.CONTENT_ID = $content_id
										AND		MEDIA_TYPE = 'proxy'";
							$thumb = $db->queryRow($query);
							$str = "<div style='display: block;'><img style='display:table-cell;		vertical-align:middle;		margin:auto;		width:auto;		height:auto;		max-width:400px;	max-height:400px;' src='".$data['thumb_web_root']."/".$thumb['path']."'></div>";
						}
					}
					
					$metadatas[$idx]['qtip'] = $str;
				}
			}
		} else if ($metadatas[0]['bs_content_id']  == DOCUMENT) {
			foreach ($metadatas as $idx => $data) {
				if ($data['bs_content_id'] == DOCUMENT) {
					$contents = $data['usr_document_nuber'];//2015-11-11 upload_other meta 변경
					if (empty($data['thumb'])) {
                        //MN00249 제목 MN01995 문서번호
						$str = "<div style='display: block; width:150px'><br/>"._text('MN00249')." : ".$data['title']."<br/>"._text('MN01995')." : $contents</div>";
					} else {
						$thumb_path = $data['lowres_web_root']."/".$data['thumb'];
						$str = "<div style='display: block;'><img  style=' #position: relative; overflow: hidden; #top: 80%; #left: 80%; display: table-cell; vertical-align: top; text-align: left; display: block;'  width=150px height=84px src=".$thumb_path."><br/>제목 : $title<br/>내용 : $contents</div>";
					}
					// $str = "<div style='display: block; width:200px'><br/>제목 : $title<br/>내용 : $contents</div>";
					$metadatas[$idx]['qtip'] = $str;
				}
			}
		} else {
			$thumb_info = $db->queryAll("select m.content_id , m.media_id , s.path, s.start_frame from bc_media m, bc_scene s where s.SHOW_ORDER < 20 and m.content_id in ( ".join(',', $content_ids)." ) and m.media_id=s.media_id ");

			if (empty($thumb_info)) {
                return $metadatas;
            }

			foreach ($thumb_info as $idx => $thumb) {
				foreach ($content_ids as $key => $content_id) {
					if ($content_id == $thumb['content_id']) {
						if ( ! array_key_exists((int)$content_id, $thumb_total_array)) {
							$thumb_total_array[$content_id] = array();
						}

						$thumb_total_array[$content_id][] = $thumb;
					}
				}
			}

			foreach ($metadatas as $idx => $data) {
				$title = str_replace('"', '\'', $data['title']);
				if ( ! array_key_exists((int)$data['content_id'], $thumb_total_array)) {
					$metadatas[$idx]['qtip'] = '';
				} else {
					$thumb_array = $thumb_total_array[$data['content_id']];
					$thumb_path = $metadatas[$idx]['thumb_web_root'];
					$thumb_count = count($thumb_array);

					if ($thumb_count > 0) {
						$str = "<div style='display: block; width:".CONFIG_THUMB_DIV_WIDTH."'>";
						$str .= $title;
						$str.="<div style='display: block; #position: relative; overflow: hidden;'>";
						$str.="<div style=' #position: absolute; #top: 50%; #left: 50%; display: table-cell; vertical-align: top; text-align: left; display: block;'>&nbsp;";

						if ($thumb_count <= 6) {
							//for ($j=0; $j < CONFIG_THUMB_PREVIEW_LIMIT; $j++) {
							for ($j=0; $j < $thumb_count; $j++) {
								// 카탈로깅 이미지가 없을 경우 no image 로 대체
								if (empty($thumb_array[$j]['path'])) {
									$img_url = '/images/noimage.jpg';
								} else {
									$img_url = $thumb_path."/".$thumb_array[$j]['path'];
								}

								$str .= "<div style='margin: 4px 4px 4px 4px; float:left;top:3px; left:0px;  right:5px display: table-cell; vertical-align: center; text-align: center; display: block;'><img width='".CONFIG_THUMB_IMG_WIDTH."'' src='".$img_url."'><br />".get_time_code($metadatas[$idx]['content_id'], $thumb_array[$j]['start_frame']).'</div>';
							}
						} else {
							$arr = randThumb($thumb_count);
							foreach ($arr as $name => $value) {
								$str .= "<div style='margin: 4px 4px 4px 4px; float:left;top:3px; left:0px;  right:5px display: table-cell; vertical-align: center; text-align: center; display: block;' ><img width='".CONFIG_THUMB_IMG_WIDTH."' src=".$thumb_path."/".$thumb_array[$value]['path']."><br>".get_time_code($metadatas[$idx]['content_id'], $thumb_array[$value]['start_frame'])."</div>";
							}
						}

						$created_date = date("Y-m-d H:i:s", strToTime($metadatas[$idx]['created_date']));
                        if ($metadatas[$idx]['bs_content_id'] == MOVIE) {
                            $duration = $metadatas[$idx]['sys_video_rt'];
                        } else if ($metadatas[$idx]['bs_content_id'] == SOUND) {
                            $duration = $metadatas[$idx]['sys_duration'];
                        } else {
                            $duration = '';
                        }

						$str.="</div></div>";
						$str.="	<div style='display: table; #position: relative; overflow: hidden;'>";
						$str.="<div style='#position: absolute; #top: 100%; display: table-cell; vertical-align: bottom;'>";
						$str.=" <div style=' #position: relative; #top: -100%'>";
						$str.= "Duration : $duration<br/>"._text('MN00102').": $created_date</div></div></div></div>";

						$metadatas[$idx]['qtip'] = $str;
					}
				}
			}
		}
	}

	// $logger->addInfo('thumbnail', $metadatas);

	return $metadatas;
}

function getContentMapping($content_list, $metadatas){
	
	$contentStatusMap = \Proxima\models\content\ContentStatus::all();
	foreach ($content_list as $idx => $content) {
		foreach ($content as $key => $value) {
			$metadatas[$idx][$key] = $value;

			// 등록자와 현재 세션정보 비교
			if ($key == 'reg_user_id') {
				if ($value == $_SESSION['user']['user_id']) {
					$metadatas[$idx]['is_mine'] = '1';
				} else {
					$metadatas[$idx]['is_mine'] = '0';
				}
			} else if($key == 'status') {				
				$contentStatus = $contentStatusMap[$value];
				if(!empty($contentStatus)) {				
					$metadatas[$idx]['content_status_color'] = $contentStatus->get('color');
					$metadatas[$idx]['content_status_icon'] = $contentStatus->get('icon');
				}
			}
		}
	}

	return $metadatas;
}

function getMediaMapping( $content_ids, $metadatas ){

	global $db, $arr_sys_code;

	$media_info = $db->queryAll("select m.* from bc_media m where m.content_id in ( ".join(',', $content_ids)." ) order by m.media_id");

	foreach( $metadatas as $key => $data )
	{
		foreach($media_info as $media)
		{
			if( $data['content_id'] == $media['content_id'] )
			{
                $metadatas[$key]['medias'] [] = $media;

				switch( $media['media_type'] )
				{
					case 'original':
						$ori_path_info = pathinfo($media['path']);
						$metadatas[$key]['ori_ext'] = $ori_path_info['extension'];
						if($metadatas[$key]['is_group'] == 'G') { //if Group content,
							$metadatas[$key]['ori_path'] = $ori_path_info['dirname'];
						} else {
							$metadatas[$key]['ori_path'] = $media['path'];
						}
						$metadatas[$key]['ori_size'] = formatByte($media['filesize']);
						$metadatas[$key]['ori_status'] = $media['status'];
                        $metadatas[$key]['ori_flag'] = trim($media['flag']);
                        $metadatas[$key]['ori_storage_id'] = trim($media['storage_id']);
					break;

					case 'proxy':
						if( $media['filesize'] > 0 ){
							$metadatas[$key]['proxy_path'] = $media['path'];
							$metadatas[$key]['proxy_status'] = $media['status'];
                        }
                        $metadatas[$key]['proxy_storage_id'] = trim($media['storage_id']);
					break;

					case 'thumb':
						if( $media['filesize'] > 0 ){
							$metadatas[$key]['thumb'] = str_replace('#', '%23', $media['path']);
							//if($media['content_id'] < 422771) $metadatas[$key]['thumb'] = '/2017/12/01/102061/102061/Thumbnail/thumb_102061.jpg';
							$metadatas[$key]['thumb_status'] = $media['status'];
						}else{
							//$metadatas[$key]['thumb'] = '/img/audio_thumb.png';
							$metadatas[$key]['thumb'] = null;
							$metadatas[$key]['thumb_status'] = $media['status'];
                        }
                        $metadatas[$key]['thumb_storage_id'] = trim($media['storage_id']);
					break;

					case 'album' :
						if( $media['filesize'] > 0 ){
							$metadatas[$key]['album'] = $media['path'];
							$metadatas[$key]['album_status'] = $media['status'];
						}else{
							//$metadatas[$key]['album'] = '/img/incoming.jpg';
							$metadatas[$key]['album'] = null;
							$metadatas[$key]['album_status'] = $media['status'];
                        }
                        $metadatas[$key]['album_storage_id'] = trim($media['storage_id']);
					break;
					/*
							Premiere Media type 이 존재할 경우 해당 path 를 넣어줌
					*/
					case $arr_sys_code['premiere_plugin_use_yn']['ref3']:
						  $metadatas[$key]['premiere_media_path'] = $media['path'];
					break;
				}
			}
		}
	}

	return $metadatas;
}

function getContentStatus($content_ids, $metadatas){
	$newMetadatas = $metadatas;
    $i = 0;
	foreach($metadatas as $metadata){
        $fields = [
            'scr_trnsmis_sttus',
            'scr_news_trnsmis_sttus',
            'scr_trnsmis_ty'
		];
		$contentStatus = \Api\Models\ContentStatus::where('content_id', $metadata['content_id'] )->select($fields)->first();
        $statusArray = $contentStatus->toArray();
		$newMetadatas[$i]['content_status'] = $statusArray;
        $i++;
	}
    return $newMetadatas;
}

/**
 * content_value 해상도 값으로 SD / HD 구분
 * @param $value
 * @return string
 */
function resolution_check($value) {
	$Rvalue = '';
	$value = trim($value);


	if ( ! is_null($value) ) {
		$tmp_array = explode(' ', $value);
		if (is_array($tmp_array)) {

			//첫번째 배열 받기 (ex : 1920x1080 , 720x480 )
			$value = $tmp_array[0];

			if ( ! empty($value)) {
                if (strstr($value, '*')) {
                    $value =str_replace('*','x',$value );
                }
                if (strstr($value, 'X')) {
                    $value =str_replace('X','x',$value );
                }
                $tmp_array = explode('x', $value);

				if (is_array($tmp_array)) {

					// 첫번째 배열 받기 (ex : 1920 , 720 )
					$value = $tmp_array[0];

					if (is_numeric($value)) {
						$value = (int)$value;

						if($value >= 3000){
							$Rvalue = 'UHD';
						}else if ($value >= 1000) {
							$Rvalue = 'HD';
						} else {
							$Rvalue = 'SD';
						}
					}
				}
			}
		}
	}

	return $Rvalue;
}

function QCstatusMapping($content_ids, $metadatas){
	global $db;

	//현재 검색건에 대해 에러가 아닌 QC정보가 있는지 검색.
	$query = "select m.content_id, q.*
		from bc_media_quality q, bc_media m
		where q.media_id=m.media_id
		  and m.content_id in (".join(',', $content_ids).")";
	$qc_list = $db->queryAll($query);
	//echo $query;exit;

	//metadatas에 있는 content_id가 위의 qc_list에 포함되면
	//qc_info = N 로 만들어서 QC가 이상이 있다고 표현
	foreach( $metadatas as $key => $data )
	{
		$content_id = $data['content_id'];
		$v = '';
		foreach($qc_list as $list)
		{
			if($list['content_id'] == $content_id)
			{
                if(empty(trim($list['no_error']))) {
                    $v = 'Y';
                } else {
                    $v = 'N';
                }
            }
		}

		$metadatas[$key]['qc_error_yn'] = $v;
	}

	return $metadatas;
}

function getLoudnessInfo($content_ids, $metadatas){
    global $db;
    $query = "SELECT *
    FROM TB_LOUDNESS 
    WHERE content_id in (".join(',', $content_ids).")";
    $loudnessInfo = $db->queryAll($query);
    if(empty($loudnessInfo)){
        return $metadatas;
    }
    foreach ($metadatas as $key => $data) {
        foreach($loudnessInfo as $list)
		{
            if ($data['content_id'] == $list['content_id']) {
                $metadatas[$key]['loudness_info'] = $list;
            }
        }
    }
    return $metadatas;
}

function getIconMapping($content_list, $metadatas, $favorites, $qc_lists, $content_ids) {

	global $db, $arr_sys_code;
	global $AUDIO_LIST;

	$is_cg_download_grant = false;
	$approval_content_yn = $arr_sys_code['approval_content_yn']['use_yn'];
	$notice_new_content_count = $arr_sys_code['notice_new_content_count']['use_yn'];
	
	if($arr_sys_code['notice_new_content_count']['ref1'] != ''){
		$limited_date = $arr_sys_code['notice_new_content_count']['ref1'];	
	}else{
		$limited_date = 7;
	}
	$mapping = array(
		4000306 => 'movie_sicon.jpg',
		4000305 => 'image_sicon.jpg',
		4000307 => 'text_sicon.jpg',
		4000308 => 'project_sicon.jpg',
		4000325 => 'sqc_sicon.jpg'
    );
    
    $isBroad = [
        'XDCAMHD',
        'mpeg2video (4:2:2)',
        'DVCPROHD',
        'dvvideo'
    ];
    
	foreach ($metadatas as $key => $val) {
		$table = array();
		$download_table = array();
		$metadatas[$key]['checked_qc'] = 0;
  
		// image가 아닐때
        if(!($val['bs_content_id'] == 518)){
					//영상일때 해상도 정보에서 HD / SD 구분 , 아이콘추가
            if (! empty($val['sys_display_size'])) {
                $resolution_text = $val['sys_display_size'];
                $resolution = resolution_check($resolution_text);
               
                $videoCodec = $val['sys_video_codec'];
                                
                if($resolution == 'UHD') {
					array_push($table, '
						<td >
							<span class="fa-stack " title="UHD" style="position:relative;padding-right:12px;">
								<i class="fa fa-square fa-stack-1x" style="font-size:17px;"></i>
								<i class="fa fa-square fa-stack-1x" style="font-size:17px;left:8px;"></i>
								<strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:4px;font-size:10px;font-weight:bold;">UHD</strong>
							</span>
						</td>
					');
				}else if (in_array($videoCodec, $isBroad)) {                

                    if ($resolution == 'HD') {
                        array_push($table, '
                            <td >
                                <span class="fa-stack " title="HD" style="position:relative;padding-right:5px;">
                                    <i class="fa fa-square fa-stack-1x" style="font-size:17px;"></i>
                                    <i class="fa fa-square fa-stack-1x" style="font-size:17px;left:3px;"></i>
                                    <strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:2px;font-size:10px;font-weight:bold;">HD</strong>
                                </span>
                            </td>
                        ');
                    } elseif ($resolution == 'SD') {
                        array_push($table, '
                            <td >
                                <span class="fa-stack " title="SD" style="position:relative;padding-right:5px;">
                                    <i class="fa fa-square fa-stack-1x" style="font-size:17px;"></i>
                                    <i class="fa fa-square fa-stack-1x" style="font-size:17px;left:3px;"></i>
                                    <strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:2px;font-size:10px;font-weight:bold;">SD</strong>
                                </span>
                            </td>
                        ');
                    }
                }else if( strtolower($val['ori_ext']) == 'mp4'){
                    array_push($table, '
                        <td >
                            <span class="fa-stack " title="MP4" style="position:relative;padding-right:5px;">
                                <i class="fa fa-square fa-stack-1x" style="font-size:17px;"></i>
                                <i class="fa fa-square fa-stack-1x" style="font-size:17px;left:3px;"></i>
                                <strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:2px;font-size:8px;font-weight:bold;">MP4</strong>
                            </span>
                        </td>
                        <td ></td>
                    ');
                }else{
                    array_push($table, '
                        <td >
                            <span class="fa-stack " title="ETC" style="position:relative;padding-right:5px;">
                                <i class="fa fa-square fa-stack-1x" style="font-size:17px;"></i>
                                <i class="fa fa-square fa-stack-1x" style="font-size:17px;left:3px;"></i>
                                <strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:2px;font-size:8px;font-weight:bold;">ETC</strong>
                            </span>
                        </td>
                        <td ></td>
                    ');
                }

			}else{
                array_push($table, '
                    <td >
                        <span class="fa-stack " title="ETC" style="position:relative;padding-right:5px;">
                            <i class="fa fa-square fa-stack-1x" style="font-size:17px;"></i>
                            <i class="fa fa-square fa-stack-1x" style="font-size:17px;left:3px;"></i>
                            <strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:2px;font-size:8px;font-weight:bold;">ETC</strong>
                        </span>
                    </td>
                    <td ></td>
                ');
            }
		}

		// 오리지널영상 전송완료및 스토리지에 존재
		// 원본영상 존재여부를 기존 status에서 Flag로 기준값을 변경 - 2018.1.5 Alex
        // 삭제완료 및 삭제요청된 항목은 아이콘을 원본이 존재하지 않는것으로 표기 - 2018.1.5 Alex
        if( !in_array($val['ori_flag'], array(DEL_MEDIA_CONTENT_REQUEST_FLAG, DEL_MEDIA_COMPLETE_FLAG))  && $val['ori_size'] > 0 ) {
			//array_push($table, '<td ><span align="center" style="position:relative;top:1px;left:2px;"><i class="fa fa-download" style="font-size:13px;"></i></span>&nbsp;</td>');

            
            if( $val['ori_storage_id']  == \Api\Types\StorageIdMap::MAIN || $val['ori_storage_id'] == \Api\Types\StorageIdMap::NEAR ){
                    //메인스토리지 일때만 온라인 영상 표시
                    array_push($table, '
                    <td style=\ "position:relative;\">
                    <span class="icon fa-stack "  title="온라인 영상" style="position:relative;">
                            <i class="fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"></i>
                            <strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:0px;font-size:8px;font-weight:bold;">O</strong>
                        </span>
                    </td>
                ');
            }else{
                //원본이 있지만 메인이 아니면 아카이브 표시
                array_push($table, '
                    <td style=\ "position:relative;\">
                    <span class="icon fa-stack "  title="아카이브 영상" style="position:relative;">
                            <i class="fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"></i>
                            <strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:0px;font-size:8px;font-weight:bold;">A</strong>
                        </span>
                    </td>
                ');
            }
                        
            //리스토어 영상
            if( $val['restore_at'] == 1 ){
                //삭제 남은 일자 표기
                $nowDt = \Carbon\Carbon::now();
                if( !empty($val['expired_date']) ){
                    $expiredDate = date("Ymd", strtotime($val['expired_date']));
                    $expiredDt = \Carbon\Carbon::createFromFormat('Ymd', $expiredDate);
                }
                
                if (!$expiredDt) {
                    //기본값
                    $expiredDt = $nowDt->addDays(14);
                }

                $expiredDay = $expiredDt->format("Y-m-d");

                $diffInDays = $nowDt->diffInDays($expiredDt);
                $deleteDay = $diffInDays;
                if ($deleteDay >= 0 ) {
                    if( $deleteDay < 10 ){
                        //10일미만 남은 일수 표시
                        if( $deleteDay == 0 ){
                            $restorePic = '<strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:0px;font-size:8px;color:red;font-weight:bold;">R'.'</strong>';  
                        }else if( $deleteDay == 1 ){
                            $restorePic = '<strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:0px;font-size:8px;color:yellow;font-weight:bold;">R'.$deleteDay.'</strong>';  
                        }else{                            
                            $restorePic = '<strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:0px;font-size:8px;font-weight:bold;">R'.$deleteDay.'</strong>';  
                        }
                    }else{
                        $restorePic = '<strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:0px;font-size:8px;font-weight:bold;">R</strong>';
                    }
                }else{
                    $restorePic = '<strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:0px;font-size:8px;font-color:red;font-weight:bold;">R</strong>';
                }
            
                array_push($table, '
                    <td style=\ "position:relative;\">
                    <span class="icon fa-stack "  title="리스토어 영상 [삭제:'.$expiredDay.']" style="position:relative;">
                            <i class="fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"></i>
                            '.$restorePic.'
                        </span>
                    </td>
                ');
                // if ($toDayTime < $exriedTime) {
                //     $deleteDay = ($toDayTime - $exriedTime) / 86400 ;
                //     array_push($table, '
                //     <td >
                //         <span class="fa-stack " title="'.$deleteDay.'" style="position:relative;padding-right:5px;">
                //             <i class="fa fa-square fa-stack-1x" style="font-size:17px;"></i>
                //             <i class="fa fa-square fa-stack-1x" style="font-size:17px;left:3px;"></i>
                //             <strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:2px;font-size:8px;font-weight:bold;">'.$deleteDay.'</strong>
                //         </span>
                //     </td>
                //     <td ></td>
                // ');
                // }
            }
		} else {
			//array_push($table, '<td ><img src="/led-icons/lr_sicon.jpg" alt="OFF" ext:qtip="오프라인" /></td>');
			//array_push($table, '<td><span class="fa-stack " title="offline" style="position:relative;"><i class="fa fa-square fa-stack-1x" style="font-size:17px;"  ></i><i class="fa fa-unlink fa-stack-1x fa-inverse" style="position:relative;top:0px;left:0px;font-size:10px;color:#c0c0c0#5a5a5a;"></i></span></td>');
        }

        if( !empty($val['archive_status']) ){
            if( $val['archive_status'] == 3){
                $archName = '[AD]';
            }else if( $val['archive_status'] == 2 ){
                $archName = '[D]';
            }
            else if( $val['archive_status'] == 1 ){
                $archName = '[A]';
            }
            array_push($table, '
                <td style=\ "position:relative;\">
                <span class="icon fa-stack "  title="아카이브 영상 '.$archName.'" style="position:relative;">
                        <i class="fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"></i>
                        <strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:0px;font-size:8px;font-weight:bold;">A</strong>
                    </span>
                </td>
			');
        }

		// 콘텐츠 상태에 따라 아이콘추가
		if( $val['status'] == '2') {
			//array_push($table, '<td><img src="/led-icons/accept2.png" alt="ACCEPT"  ext:qtip="승인" /></td>');
		}

		if( !in_array( $val['ud_content_id'], $AUDIO_LIST ) ) {
			if( $val['preview'] == 'queue') {
				array_push($table, '<td><img src="/led-icons/previewnote_sicon1.jpg" alt="PREVIEW"  ext:qtip="프리뷰 대기" /></td>');
			} else if( $val['preview'] == 'progressing') {
				array_push($table, '<td><img src="/led-icons/previewnote_sicon2.jpg" alt="PREVIEW"  ext:qtip="프리뷰 작업중" /></td>');
			} else if($val['preview'] == 'complete') {
				array_push($table, '<td><img src="/led-icons/previewnote_sicon3.jpg" alt="PREVIEW"  ext:qtip="프리뷰 완료" /></td>');
			}

			if( $val['review'] == 'queue') {
				array_push($table, '<td><img src="/led-icons/review_sicon1.jpg" alt="REVIEW"  ext:qtip="심의 대기" /></td>');
			}else if( $val['review'] == 'progressing') {
				array_push($table, '<td><img src="/led-icons/review_sicon3.jpg" alt="REVIEW"  ext:qtip="심의 진행중" /></td>');
			}else if( $val['review'] == 'accept') {
				array_push($table, '<td><img src="/led-icons/review_sicon4.jpg" alt="REVIEW"  ext:qtip="심의 승인" /></td>');
			} else if($val['review'] == 'refuse') {
				array_push($table, '<td><img src="/led-icons/review_sicon2.jpg" alt="REVIEW"  ext:qtip="심의 반려" /></td>');
			}

			if( $val['npstodmc'] == 'complete' ) {
				array_push($table, '<td><img src="/led-icons/forward_green.jpg"  alt="DMC" ext:qtip="DMC 전송완료" /></td>');
			} else if( $val['npstodmc'] == 'error' ) {
				array_push($table, '<td><img src="/led-icons/forward_red.jpg"  alt="DMC" ext:qtip="DMC 전송실패" /></td>');
			} else if( $val['npstodmc'] == 'progressing' ) {
				array_push($table, '<td><img src="/led-icons/forward_white.jpg"  alt="DMC" ext:qtip="DMC 전송중" /></td>');
			}
		}

		// 심의
		// if ($val['state'] & GRANT_REVIEW_ACCEPT) {
		// 	array_push($table, '<td><img src="/led-icons/review_sicon4.jpg" alt="REVIEW"  ext:qtip="심의 승인" /></td>');
		// } else if ($val['state'] & GRANT_REVIEW_REJECT) {
		// 	array_push($table, '<td><img src="/led-icons/review_sicon2.jpg" alt="REVIEW"  ext:qtip="심의 반려" /></td>');
		// } else if ($val['state'] & GRANT_REVIEW_REQUEST) {
		// 	array_push($table, '<td><img src="/led-icons/review_sicon1.jpg" alt="REVIEW"  ext:qtip="심의 반려" /></td>');
		// }

		// 승인 아이콘
		if( $val['status'] == '2') {
			array_push($table, '
				<td style="color:green">
					<span class="fa-stack " title="등록 승인"  style="position:relative;" >
						<i class="fa fa-square fa-stack-1x" style="font-size:18px;"  ></i>
						<i class="fa fa-check fa-stack-1x fa-inverse" style="position:relative;top:0px;left:0px;font-size:10px;"></i>
					</span>
				</td>
			');
		} else if( $val['status'] == '5') {
		// 반려 아이콘
			array_push($table, '
				<td style="color:red">
					<span class="fa-stack " title="등록 반려"  style="position:relative;" >
						<i class="fa fa-square fa-stack-1x" style="font-size:18px;"  ></i>
						<i class="fa fa-ban fa-stack-1x fa-inverse" style="position:relative;top:0px;left:0px;font-size:10px;"></i>
					</span>
				</td>
			');
		}


	//if($val['archive_status'] == 'complete')  //아카이브여부에 따라 아이콘추가
	if($val['archive_yn'] == 'Y')
	{
		//MN01056 아카이브
		//array_push($table, '<td><img src="/led-icons/database2.png" alt="AR"  ext:qtip="'._text('MN01056').'" /></td>');
		//array_push($table, '<td><img src="/led-icons/fn.png" alt="AR"  ext:qtip="High Resolution File on FlashNet Archive" /></td>');

		//array_push($table, '<td><img style="cursor:pointer" onclick="show_sgl_log('.$val['content_id'].');" src="/led-icons/archive_sicon1.png" alt="AR"  ext:qtip="High Resolution File on FlashNet Archive" /></td>');
		if( $arr_sys_code['interwork_flashnet']['use_yn'] == 'Y' ){
			array_push($table, '<td><span class="fa-stack " title="'._text('MN01056').'" onclick="show_sgl_log('.$val['content_id'].');" style="position:relative;"><i class="fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"  ></i><i class="fa fa-server fa-stack-1x fa-inverse" style="position:relative;top:0px;left:0px;font-size:10px;"></i></span></td>');
		} else {
			if( $arr_sys_code['interwork_oda_ods_d']['use_yn'] == 'Y') {
				$tape_id_text = "[Catrage ID : ".$val['archive_tape_id']."]";
			}
			array_push($table, '<td><span class="fa-stack " title="'._text('MN01056').$tape_id_text.'" style="position:relative;"><i class="fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"  ></i><i class="fa fa-server fa-stack-1x fa-inverse" style="position:relative;top:0px;left:0px;font-size:10px;"></i></span></td>');
		}
	}else if($val['archive_yn'] == 'P'){
		array_push($table, '<td><span class="fa-stack " title="'._text('MN02384').'" style="position:relative;"><i class="fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"  ></i><i class="fa fa-server fa-stack-1x fa-inverse" style="position:relative;top:0px;left:0px;font-size:10px;color:#5a5a5a;"></i></span></td>');
	}

	// if($val['loudness_status'] == 'P')  //loudness 여부에 따라 아이콘추가
	// {
	// 	//array_push($table, '<td><span class="fa-stack " title="'._text('MN02243').' '._text('MN02267').'" onclick="show_loudness_log('.$val['content_id'].');" style="position:relative;"><i class="fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"  ></i><i class="fa fa-file-audio-o fa-stack-1x fa-inverse" style="position:relative;top:0px;left:0px;font-size:10px;"></i></span></td>');
	// 	array_push($table, '<td><span class="fa-stack " title="'._text('MN02243').' '._text('MN02267').'" onclick="show_loudness_log('.$val['content_id'].');" style="position:relative;"><i class="fa fa-align-right fa-rotate-90 fa-stack-1x" style="position:relative;top:-1px;left:0px;font-size: 14px;"></i></span></td>');
	// } else if ($val['loudness_status'] == 'D') {
	// 	array_push($table, '<td><span class="fa-stack " title="'._text('MN02243').' '._text('MN02268').'" onclick="show_loudness_log('.$val['content_id'].');" style="position:relative;"><i class="fa fa-align-right fa-rotate-90 fa-stack-1x" style="position:relative;top:-1px;left:0px;font-size: 14px;color:#ff6600"></i></span></td>');
	// }
	// loudness 여부에 따라 아이콘 추가
	if($val['loudness'] == 'Y') {
		// array_push($table, '
		// 			<td >
		// 				<span class="fa-stack " title="Loudness" style="position:relative;padding-right:5px;">
		// 					<i class="fa fa-square fa-stack-1x" style="font-size:17px;"></i>
		// 					<i class="fa fa-square fa-stack-1x" style="font-size:17px;left:3px;"></i>
		// 					<strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:2px;font-size:10px;font-weight:bold;">L</strong>
		// 				</span>
		// 			</td>
        //         ');

        //INTEGRATE,UNIT
        if( !empty($val['loudness_info']) && is_numeric($val['loudness_info']['integrate']) ){
            $integrate = (float)$val['loudness_info']['integrate'];
            if( $integrate <= -22 &&  $integrate >= -26 ){
                array_push($table, '
                <td style="color:green">
                <span class="icon fa-stack "  title="라우드니스 정상 : '.$integrate.' LKFS" style="position:relative;">
                        <i class="fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"></i>
                        <strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:0px;font-size:8px;font-weight:bold;">L</strong>
                    </span>
                </td>
            ');
            }else{
                array_push($table, '
                <td style="color:red">
                <span class="icon fa-stack "  title="라우드니스 초과 : '.$integrate.' LKFS" style="position:relative;">
                        <i class="fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"></i>
                        <strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:0px;font-size:8px;font-weight:bold;">L</strong>
                    </span>
                </td>
            ');
            }
        }else{
            array_push($table, '
                <td style="color:red">
                <span class="icon fa-stack "  title="라우드니스 측정오류" style="position:relative;">
                        <i class="fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"></i>
                        <strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:0px;font-size:8px;font-weight:bold;">L</strong>
                    </span>
                </td>
            ');
        }
        
    //     array_push($table, '
    //     <td style="color:red">
    //         <span class="fa-stack " title="등록 반려"  style="position:relative;" >
    //             <i class="fa fa-square fa-stack-1x" style="font-size:18px;"  ></i>
    //             <i class="fa fa-ban fa-stack-1x fa-inverse" style="position:relative;top:0px;left:0px;font-size:10px;"></i>
    //         </span>
    //     </td>
    // ');
    }
    
	// 아카이브 여부에 따라 아이콘추가
	/*
        switch ($val['archive_status']) {
        case 1:
            array_push($table, '<td><img src="/led-icons/archive_sicon4.jpg" alt="AR"  ext:qtip="아카이브 승인대기" /></td>');
            break;

        case 2:
            array_push($table, '<td><img src="/led-icons/archive_sicon1.jpg" alt="AR"  ext:qtip="아카이브 전송중" /></td>');
            break;

        case 3:
            array_push($table, '<td><img src="/led-icons/archive_sicon3.jpg" alt="AR"  ext:qtip="아카이브 승인" /></td>');
            break;

        case 4:
            array_push($table, '<td><img src="/led-icons/archive_sicon2.jpg" alt="AR"  ext:qtip="아카이브 반려" /></td>');
            break;
        }

	*/

		// 그룹 아이템일 경우 아이콘 표기
		// if ($val['is_group'] == 'G' && $val['bs_content_id'] != SEQUENCE) {
		// 	//array_push($table, '<td><img src="/led-icons/folder-open.gif" alt="GR"  ext:qtip="그룹" /></td>');
		// 	array_push($table, '
		// 		<td>
		// 			<span class="fa-stack " title="Group"  style="position:relative;">
		// 				<i class="fa fa-folder fa-stack-1x" style="font-size:18px;"  ></i>
		// 				<strong class="fa fa-inverse  fa-stack-1x fa-text" style="position:relative;top:0px;font-size:9px;font-weight:bold;">G</strong>
		// 			</span>
		// 		</td>
		// 	');
		// }

		//관심컨텐츠에 등록된 영상일 경우 아이콘 표기
		// 20160309 CANPN remove favorite icon
		/*
		if( in_array($val['content_id'], $favorites)) {
				array_push($table, '<td><img src="/led-icons/star_1.png" alt="FV"  ext:qtip="관심콘텐츠" /></td>');
		}
		*/

		// //QC에 ERROR_COUNT가 1이상인 경우 아이콘표기
		// if (in_array($val['content_id'], $qc_lists)) {
		// 	$metadatas[$key]['checked_qc'] = 1;
		// 	array_push($table, '<td><img src="/led-icons/accept.png" alt="QC"  ext:qtip="퀄리티 확인됨" /></td>');
		// 	// array_push($table, '<td><i class="fa fa-check fa-lg" ext:qtip="퀄리티 확인됨"></i></td>');
		// }

		// //2016-05-19 QC관련 아이콘 추가
		// if ( !empty($val['q_status']) ) {//qc완료 전, qc 완료 후 error count 0, qc완료 후 error count존재, 사용자 확인
		// 	if($val['q_status'] != 'complete'){//qc 작업 완료 전
		// 		array_push($table, '
		// 			<td style="position:relative;">
		// 				<span class="fa-stack " title="'._text('MN02294').'('._text('MN00262').')" style="position:relative;padding-right:3px; ">
		// 					<i class="fa fa-square fa-stack-1x" style="font-size:17px;left:-2px;"></i>
		// 					<i class="fa fa-square fa-stack-1x" style="font-size:17px;left:2px;"></i>
		// 					<strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:0px;font-size:10px;font-weight:bold;color:#5a5a5a;">QC</strong>
		// 				</span>
		// 			</td>
		// 		');
		// 	}else if( $val['q_status'] == 'complete' ){//qc 작업 완료
		// 		if( $val['q_error_count'] > 0 ){//qc error 1개 이상
		// 			if($val['qc_error_yn'] == 'Y') {
		// 				array_push($table, '
		// 					<td style="position:relative;">
		// 						<span class="fa-stack " title="'._text('MN02294').'('._text('MN02346').')" style="position:relative;padding-right:3px;" onclick="show_qc_log('.$val['content_id'].');">
		// 							<i class="fa fa-square fa-stack-1x" style="font-size:17px;left:-2px;"></i>
		// 							<i class="fa fa-square fa-stack-1x" style="font-size:17px;left:2px;"></i>
		// 							<strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:0px;font-size:10px;font-weight:bold;color:red;">QC</strong>
		// 						</span>
		// 					</td>
		// 				');
		// 			} else {
		// 				//User confirm. Mark as not error.
		// 				array_push($table, '
		// 					<td style="position:relative;">
		// 						<span class="fa-stack " title="'._text('MN02294').'('._text('MN02347').')" style="position:relative;padding-right:3px;" onclick="show_qc_log('.$val['content_id'].');">
		// 							<i class="fa fa-square fa-stack-1x" style="font-size:17px;left:-2px;"></i>
		// 							<i class="fa fa-square fa-stack-1x" style="font-size:17px;left:2px;"></i>
		// 							<strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:0px;font-size:10px;font-weight:bold;color:green;">QC</strong>
		// 						</span>
		// 					</td>
		// 				');
		// 			}

		// 		}else{//qc error 없음
		// 			if( $val['q_is_cehcked'] == 'Y' ){//사용자 확인
		// 				array_push($table, '
		// 					<td style="position:relative;">
		// 						<span class="fa-stack " title="'._text('MN02294').'('._text('MN02347').')" style="position:relative;padding-right:3px; >
		// 							<i class="fa fa-square fa-stack-1x" style="font-size:17px;left:-2px;"></i>
		// 							<i class="fa fa-square fa-stack-1x" style="font-size:17px;left:2px;"></i>
		// 							<strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:0px;font-size:10px;font-weight:bold;color:green;">QC</strong>
		// 						</span>
		// 					</td>
		// 				');
		// 			}else{//사용자 미확인
		// 				array_push($table, '
		// 					<td style="position:relative;">
		// 						<span class="fa-stack " title="'._text('MN02294').'('._text('MN02178').')" style="position:relative;padding-right:3px;">
		// 							<i class="fa fa-square fa-stack-1x" style="font-size:17px;left:-2px;"></i>
		// 							<i class="fa fa-square fa-stack-1x" style="font-size:17px;left:2px;"></i>
		// 							<strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:0px;font-size:10px;font-weight:bold;">QC</strong>
		// 						</span>
		// 					</td>
		// 				');
		// 			}
		// 		}
		// 	}
		// }

		//QC관련 ICON 추가 - 2018.03.20 Alex
		if($val['qc_cnfirm_at'] == '0') {
			array_push($table, '
							<td style="position:relative; color:red">
								<span class="fa-stack " title="'.'QC 오류'.'" style="position:relative;padding-right:3px;" onclick="show_qc_log('.$val['content_id'].');">
									<i class="fa fa-square fa-stack-1x" style="font-size:17px;left:-2px;"></i>
									<i class="fa fa-square fa-stack-1x" style="font-size:17px;left:2px;"></i>
									<strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:0px;font-size:10px;font-weight:bold;color:white;">QC</strong>
								</span>
							</td>
						');
		} else if($val['qc_cnfirm_at'] == '1') {
			array_push($table, '
							<td style="position:relative; color:green">
								<span class="fa-stack " title="'.'QC 정상'.'" style="position:relative;padding-right:3px;" onclick="show_qc_log('.$val['content_id'].');">
									<i class="fa fa-square fa-stack-1x" style="font-size:17px;left:-2px;"></i>
									<i class="fa fa-square fa-stack-1x" style="font-size:17px;left:2px;"></i>
									<strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:0px;font-size:10px;font-weight:bold;color:white;">QC</strong>
								</span>
							</td>
						');	
		}

		if(!empty($val['ori_path']) && $val['is_group'] == 'I') {
			$ext_array = explode('.',$val['ori_path']);
			$ext = strtoupper(array_pop($ext_array));
			$ext = '<td align="right" style="font-weight:bold; width:35px;">'.$ext.'</td>';
		}

		$today_obj = date('YmdHis');
		$modi_date = date('YmdHis', strtotime($val['created_date']));
		$diff_created_date = date_diff_day($modi_date, $today_obj);
		if( 0 <= $diff_created_date && $diff_created_date < $limited_date )
		{
			if($val['read_date'] <= $modi_date)
			{
				//array_push($table, '<td width="35px"><img src="/led-icons/new.png" /></td>');
				if($notice_new_content_count == 'Y'){
					array_push($table, '<td><span class="fa-stack " title="New"  style="position:relative;" ><i class="fa fa-certificate fa-stack-1x" style="font-size:17px;color:#ff6600;"  ></i><strong class="fa fa-inverse  fa-stack-1x fa-text" style="position:relative;font-size:10px;font-weight:bold;">N</strong></span></td>');
				}
			}
		}

		if($arr_sys_code['interwork_sns']['use_yn'] == 'Y') {
			if($metadatas[$key]['sns_youtube_status'] == 'SUCCESS') {
				if($metadatas[$key]['sns_youtube_url'] == '') {
					$metadatas[$key]['sns_youtube_url'] = 'https://www.youtube.com/';
				}
				array_push($table, '<td><span class="fa-stack " title="YouTube" onclick="show_url(\''.$metadatas[$key]['sns_youtube_url'].'\');" style="position:relative;"><i class="fa fa-youtube-play fa-stack-1x" style="font-size:15px;"  ></i></span></td>');
			}
			if($metadatas[$key]['sns_facebook_status'] == 'SUCCESS') {
				if($metadatas[$key]['sns_facebook_url'] == '') {
					$metadatas[$key]['sns_facebook_url'] = 'https://www.facebook.com/';
				}
				array_push($table, '<td><span class="fa-stack " title="Facebook" onclick="show_url(\''.$metadatas[$key]['sns_facebook_url'].'\');" style="position:relative;"><i class="fa fa-facebook-square fa-stack-1x" style="font-size:17px;"  ></i></span></td>');
			}
			if($metadatas[$key]['sns_twitter_status'] == 'SUCCESS') {
				if($metadatas[$key]['sns_twitter_url'] == '') {
					$metadatas[$key]['sns_twitter_url'] = 'https://twitter.com/';
				}
				array_push($table, '<td><span class="fa-stack " title="Twitter" onclick="show_url(\''.$metadatas[$key]['sns_twitter_url'].'\');" style="position:relative;"><i class="fa fa-twitter fa-stack-1x" style="font-size:17px;"  ></i></span></td>');
			}
		}

		if($approval_content_yn == 'Y'){
			if(!empty($val['approval_yn']) && $val['approval_yn'] == 'Y'){
				array_push($table, '<td><span class="fa-stack " title="Approved Content"  style="position:relative;" ><i class="fa fa-certificate fa-stack-1x" style="font-size:17px;color:#FFA500;"  ></i><strong class="fa fa-inverse  fa-stack-1x fa-text" style="position:relative;font-size:10px;font-weight:bold;"><i class="fa fa-check"></i></strong></span></td>');
			}else{
			}
		}

		// 사용금지 아이콘
		if($val['use_prhibt_at'] == 'Y') {
			array_push($table, '<td><span class="fa-stack " title="사용금지"  style="position:relative;" ><i class="fa fa-ban fa-stack-1x" style="font-size:17px;color:red;"  ></i></span></td>');
		}
		// get color tag
		$user_id = $_SESSION ['user'] ['user_id'] ;
		$query_tag_for_content = "
				SELECT	b.TAG_CATEGORY_TITLE,
						b.TAG_CATEGORY_COLOR
				FROM BC_TAG a
				LEFT JOIN BC_TAG_CATEGORY b
				ON a.TAG_CATEGORY_ID = b.TAG_CATEGORY_ID
				WHERE a.USER_ID = '$user_id'
				AND a.CONTENT_ID = ".$val['content_id']
				;
		$tag_category_data = $db->queryAll($query_tag_for_content);
		if(!empty($tag_category_data[0])){
			//array_push($table, '<td class ="content_tag"><i title="Tag" class="fa fa-star" style="position:relative;top:1px;font-size:16px; color:'.$tag_category_color.'"></i><p style="display:none;" class="content_id_text">'.$val['content_id'].'</p></td>');
			array_push($table, '
				<td>
					<span>
					<i title="'.$tag_category_data[0]['tag_category_title'].'" class="fa fa-circle test_tag_function" text_data="'.$val['content_id'].'" style="font-size:17px;color:'.$tag_category_data[0]['tag_category_color'].'"></i>
					</span>
				</td>
			');
		}else{
			array_push($table, '
				<td>
					<span>
					<i title="Tag" class="fa fa-circle-o test_tag_function" text_data="'.$val['content_id'].'" style="font-size:17px; color: #5a5a5a;"></i>
					</span>
				</td>');
		}
		$start_table = '<td align="left" ><table cellpadding="0" cellspacing="0" border="0"  ><tr>';
		$end_table ='</tr></table></td>';


		if(!empty($table)){
			// 확장자 표시
//			$metadatas[$key]['icons'] = $start_table.join('', $table).$end_table.$ext;
//			$metadatas[$key]['icons_grid'] = '<table cellpadding="0" cellspacing="0" border="0" style="padding-left:5px;">'.$start_table.join('', $table).$end_table.$ext.'</table>';
			$metadatas[$key]['icons'] = $start_table.join('', $table).$end_table;
			$metadatas[$key]['icons_grid'] = '<table cellpadding="0" cellspacing="0" border="0" style="padding-left:1px;">'.$start_table.join('', $table).$end_table.'</table>';
		}

		if(!empty($download_table)) {
			$metadatas[$key]['icons_download'] = join('', $download_table);
		}
	}

	return $metadatas;
}

function get_time_code($content_id, $startFrame)
{
	/* 쿼리 오류로 진행 안됨. 분석 필요 by 승수, 11/10/20
	$start_time_code = $db->queryOne("select cv.sys_meta_value from bc_sys_meta_value cv, bc_media m where m.content_id=cv.content_id and cv.sys_meta_field_id='6073034' and m.content_id=".$content_id);//시작타임코드 by 이성용

	if( !empty($start_time_code) )
	{
		$start_time_code_frame = timecode::getConvFrame($start_time_code);

		if($start_time_code_frame === false)
		{
			$start_time_code_frame = 0;
		}
	}
	else
	{
		$start_time_code_frame = 0;
	}
	*/

	$start_time_code_frame = 0;

	$total_sec = (int)( ($startFrame + $start_time_code_frame) / 30);

	$hour = (int)( $total_sec / 3600 );
	$min = (int)( ( $total_sec % 3600 ) / 60 );
	$sec = (int) ( ( $total_sec % 3600 ) % 60 );

	$time_code = str_pad($hour, 2, 0, STR_PAD_LEFT).':'.str_pad($min, 2, 0, STR_PAD_LEFT).':'.str_pad($sec, 2, 0, STR_PAD_LEFT);
	return $time_code;
}
/**
 * 11-11-07, 승수. 섬네일 갯수가 보여줄 갯수의 2배가 넘는 경우. 랜덤으로 보여주도록 하기 위해
 * ex) 마우스 오버시 섬네일 6장 보여줄 때 전체 섬네일은 30장이면 0~5번째에서 1장, 6~11번째에서 1장 이런식으로 랜덤하게 6장 뽑는다.
 * 새로고침 할 때마다 적용된다.
 * @param  [type] $CatTotal [description]
 * @return [type]           [description]
 */
function randThumb($CatTotal) {
	$ViewCount = CONFIG_THUMB_PREVIEW_LIMIT;
	$DivVal = $CatTotal / $ViewCount;

	srand((double)microtime()*1000000);

	$return = array();
	if ($CatTotal < $ViewCount*2) {
		for ($i = 0 ; $i < $ViewCount ; $i++) {
			$ret = round($DivVal*$i);
			if ($ret >= $CatTotal) {
				$return[$i] = $CatTotal-1;
			} else {
				$return[$i] = $ret;
			}
		}
	} else {
		for ($i = 0; $i < $ViewCount; $i++) {
			array_push($return, ((int)($i * $DivVal + rand(0, $DivVal-1))));
		}
	}
	return $return;
}

function getMainFieldMapping($fields, $metadatas) {
	global $db;
	global $CG_LIST;

	// 섬네일보기 리스트 수
	$thumb_field_count = 1;

	// 요약보기 리스트 수
	$summary_field_count = 4;

	$field_view_info = array();

	$code_info = $db->queryAll("select c.* from BC_CODE c, BC_CODE_TYPE ct where c.code_type_id=ct.id and ct.code='main_summary_field'");

	$thumb_field = array();
	$summary_field = array();

    array_push( $summary_field , array(
	    'usr_meta_field_id' => 'title',
	    'usr_meta_field_title' => _text('MN00249'),//title
	    'usr_meta_field_type' => 'content'
    ));

	foreach ($fields as $field) {
		foreach ($code_info as  $code) {

			// 코드 일치시
	        if ($code['code'] & $field['summary_field_cd']) {
				if ($code['code'] == '1') {//섬네일보기
					if (count($thumb_field) == $thumb_field_count) {
						continue;
					}
					array_push( $thumb_field , $field['usr_meta_field_code'] );
				} else if ($code['code'] == '2') {//요약보기
					if (count($summary_field) == $summary_field_count) {
						continue;
					}

					array_push( $summary_field , array(
						'usr_meta_field_code' => $field['usr_meta_field_code'],
						'usr_meta_field_id' => $field['usr_meta_field_id'],
						'usr_meta_field_title' => $field['usr_meta_field_title'],
						'usr_meta_field_type' => $field['usr_meta_field_type']
					));
				}
			}
		}
	}

	if (count($summary_field) < $summary_field_count) {
		foreach ($fields as $field) {
			if (count($summary_field) >= $summary_field_count) {
				continue;
			} else {
				if (in_array(array(
							'usr_meta_field_id' => $field['usr_meta_field_id'],
							'usr_meta_field_title' => $field['usr_meta_field_title']
						), $summary_field)) {
					continue;
				} else if ($field['usr_meta_field_type'] == 'container') {
					continue;
				} else {

					// if($field['is_show'] == 1){
						array_push( $summary_field, array(
							'usr_meta_field_id' => $field['usr_meta_field_id'],
							'usr_meta_field_title' => $field['usr_meta_field_title'],
							'usr_meta_field_type' => $field['usr_meta_field_type'],
							'usr_meta_field_line' => $field['num_line'],
							'usr_meta_field_code' => $field['usr_meta_field_code']//2015-11-11 upload_other 요약보기 위해 추가
						));
					// }
				}
			}
		}
	}

	foreach ($metadatas as $key => $meta) {
		if ( ! empty($thumb_field[0])) {
			$metadatas[$key]['thumb_field'] = $metadatas[$key][strtolower(array_pop($thumb_field))];

		} else {
			// 지정된 필드가 없다면 제목
			$metadatas[$key]['thumb_field'] = $metadatas[$key]['title'];
		}
		//2015-12-24 썸네일필드 지정 없는 버전은 제목으로....
		$metadatas[$key]['thumb_field'] = $metadatas[$key]['title'];

		if ( ! empty( $summary_field)) {
			$summary_value = array();
			foreach ($summary_field as $sf) {
				if ($sf['usr_meta_field_type'] == 'datefield') {
                    $meta_field_code =  $sf['usr_meta_field_code'];
					$val = $metadatas[$key][strtolower($meta_field_code)];

					if ( ! empty($val) && strtotime($val)) {
						$val = date('Y/m/d', strtotime($val));
					}
					array_push($summary_value ,  '<div class="user_meta_normal">'.$sf['usr_meta_field_title'].': '.$val.'</div>');
				} else if ($sf['usr_meta_field_id'] == 'title') {
                    $val = $metadatas[$key]['title'];
                    array_push($summary_value, '<div class="user_meta_ellipsis_single_line">'.$sf['usr_meta_field_title'].': '.$val.'</div>');
                } else {
                    $meta_field_code =  $sf['usr_meta_field_code'];
					$val = $metadatas[$key][strtolower($meta_field_code)];
					if($sf['usr_meta_field_type'] == 'textfield' || $sf['usr_meta_field_type'] == 'textarea'){
						if($sf['usr_meta_field_line'] <= 1){
							array_push($summary_value ,  '<div class="user_meta_ellipsis_single_line" line="'.$sf['usr_meta_field_line'].'">'.$sf['usr_meta_field_title'].': '.$val.'</div>');
						}else{
							$height = 13*intval($sf['usr_meta_field_line']);
							array_push($summary_value ,  '<div class="user_meta_ellipsis_multiple_line" style="height:'.$height.'px">'.$sf['usr_meta_field_title'].': '.$val.'</div>');
						}
						
					}else{
						array_push($summary_value ,  '<div class="user_meta_ellipsis_single_line">'.$sf['usr_meta_field_title'].': '.$val.'</div>');
					}
					
				}
			}

			$metadatas[$key]['summary_field'] = join('<p style="margin-bottom:5px;"></p>' , $summary_value);
		}
	}

	return $metadatas;
}

function getMetaInfo($ud_content_id, $fields, $metadatas)
{
    global $db;

    $usr_metas = $db->queryAll("select * from bc_usr_meta_value where ud_content_id = $ud_content_id");

    $field_info = array();

    foreach($fields as $field)
    {
            foreach($usr_metas as  $metafield )
            {
                    if( $metafield['usr_meta_field_id'] == $field['usr_meta_field_id'] ) //코드 일치시
                    {
                        if($field['usr_meta_field_type'] != 'container') {
                            array_push( $field_info , array(
                                                    $field['usr_meta_field_title'] => $metafield['usr_meta_value']
                            ));
                        }
                    }
            }
    }

    foreach($metadatas as $key => $meta)
    {
            if( !empty($field_info) )
            {
                    $metadatas[$key]['usr_meta_info'] = $field_info;
            }
    }


    return $metadatas;

}

function getChildContents($metadata_list) {
	foreach ($metadata_list as $key => $content) {
		if ($content['is_group'] == 'G') {
			$metadata_list[$key]['children'] = MetaDataClass::getChildContents($content['content_id']);
		}
	}

	return $metadata_list;
}


/**
	프리미어 관련 정보 가져오기
*/
function getPremierContents($content_ids, $metadata_list) {

	global $db;

	//$log_path = $_SERVER['DOCUMENT_ROOT'].'/log/register_sequence_'.date('Ymd').'.log';
	$premiere_content_q = "
			SELECT 		content_id,seq_id
			FROM 		TB_PREMIERE_SEQINFO
			WHERE 		CONTENT_ID IN (".implode($content_ids, ',').")
	";

	$arr_info = $db->queryAll($premiere_content_q);
//file_put_contents($log_path, date("Y-m-d H:i:s\t").print_r($arr_info,true)."\n\n", FILE_APPEND);
	foreach($metadata_list as $key => $data ){

		foreach($arr_info as $info)	{

			if( $data['content_id'] == $info['content_id'] ){
				
				$metadata_list[$key]['seq_id'] = $info['seq_id'];

			}
		}
	}


	return $metadata_list;
}

function getThumbImageForGroup($metadata_list) {
	global $db;

	foreach ($metadata_list as $key => $content) {
		if ($content['is_group'] == 'G') {
			$content_id = $content['content_id'];
			$query = "SELECT THUMBNAIL_CONTENT_ID FROM BC_CONTENT WHERE CONTENT_ID = $content_id";
			$thumb_id = $db->queryOne($query);
			if(!isset($thumb_id)){
				$thumb_info = $db->queryAll("SELECT * FROM BC_MEDIA WHERE CONTENT_ID = $content_id AND MEDIA_TYPE IN ('thumb', 'proxy')");
				foreach ($thumb_info as $info) {
					switch ($info['media_type']) {
						case 'thumb':
							$metadata_list[$key]['thumb_group_path'] = $info['path'];
							break;
						
						case 'proxy':
							$metadata_list[$key]['proxy_group_path'] = $info['path'];
							break;
					}
				}
			}else{
				$thumb_info = $db->queryAll("SELECT * FROM BC_MEDIA WHERE CONTENT_ID = $thumb_id AND MEDIA_TYPE IN ('thumb', 'proxy')");
				foreach ($thumb_info as $info) {
					switch ($info['media_type']) {
						case 'thumb':
							$metadata_list[$key]['thumb_group_path'] = $info['path'];
							break;
						
						case 'proxy':
							$metadata_list[$key]['proxy_group_path'] = $info['path'];
							break;
					}
				}
			}
			
		}
	}
	return $metadata_list;
}

function getThumbImageForGroupMovie($metadata_list) {
	global $db;

	foreach ($metadata_list as $key => $content) {
		if ($content['is_group'] == 'G') {
			$content_id = $content['content_id'];
			$query = "SELECT THUMBNAIL_CONTENT_ID FROM BC_CONTENT WHERE CONTENT_ID = $content_id";
			$thumb_id = $db->queryOne($query);
			if(!isset($thumb_id)){
				$thumb_info = $db->queryAll("SELECT * FROM BC_MEDIA WHERE CONTENT_ID = $content_id AND MEDIA_TYPE = 'thumb'");
				foreach ($thumb_info as $info) {
					switch ($info['media_type']) {
						case 'thumb':
							$metadata_list[$key]['thumb_group_path'] = $info['path'];
							break;
					}
				}
			}else{
				$thumb_info = $db->queryAll("SELECT * FROM BC_MEDIA WHERE CONTENT_ID = $thumb_id AND MEDIA_TYPE = 'thumb'");
				foreach ($thumb_info as $info) {
					switch ($info['media_type']) {
						case 'thumb':
							$metadata_list[$key]['thumb_group_path'] = $info['path'];
							break;
					}
				}
			}
			
		}
	}
	return $metadata_list;
}

function getContentStatusMapping($content_ids, $metadatas)
{

    $contentStatus = \Api\Models\ContentStatus::whereIn('content_id', $content_ids )->get();
    foreach ($metadatas as $key => $data) {
        foreach ($contentStatus as $status) {
            if ($data['content_id'] == $status->content_id ) {
             
                $metadatas[$key] = $status->toArray();
            }
        }
    }
    return $metadatas;
}


function getContentUsrMapping($content_ids, $metadatas)
{

    $contentStatus = \Api\Models\ContentUsrMeta::whereIn('usr_content_id', $content_ids )->get();
    foreach ($metadatas as $key => $data) {
        foreach ($contentStatus as $status) {
            if ($data['content_id'] == $status->usr_content_id ) {
             
                $metadatas[$key]['usr_meta'] = $status->toArray();
            }
        }
    }
    return $metadatas;
}
?>
