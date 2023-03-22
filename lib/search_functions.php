<?php

function getNPSContentListsFromDB($assetTypeCode, $s_type, $contentType, $sdate, $edate, $keyword, $curPage, $rowcount, $user_id){
	global $db;

	if($assetTypeCode == MEDIA_BIT) {
		// 영삼만 조회되도록
		$ud_query = "AND	C.BS_CONTENT_ID = 506
					AND		C.IS_GROUP = 'I'";
	} else if($assetTypeCode == 'cm') {
		$ud_query = "
					AND		C.CATEGORY_ID = 5394640
				";
	}

	if($assetTypeCode != 'cm') {
		if(empty($contentType)) {
			/*$ud_content_list = $db->queryAll("
							SELECT	*
							FROM	BC_UD_GROUP
							WHERE	UD_GROUP_CODE = $assetTypeCode
						");
			*/
			$ud_content_list = $db->queryAll("
								SELECT	*
								FROM	BC_UD_CONTENT
								ORDER BY SHOW_ORDER
							");
			$ud_contents_arr = array();
//			$allowUDCOntents = array(1, 2, 3, 4, 5, 6, 7, 8);

			foreach($ud_content_list as $list) {
				/*if(in_array($list['ud_content_id'], $allowUDCOntents)) {
					array_push($ud_contents_arr, $list['ud_content_id']);
                }*/
                if( $list['ud_content_id'] == 9 ){
                    array_push($ud_contents_arr, $list['ud_content_id']);
                }
			}
			$ud_contents = join(',', $ud_contents_arr);
		} else {
			$ud_contents = $contentType;
		}
	} else if($assetTypeCode == 'cm') {
		$ud_contents = '4000290';//광고 ud_content_id
	}

	if(!empty($keyword)) {
		$keyword = strtoupper($keyword);
		$keyword_query = " AND UPPER(C.TITLE) LIKE '%$keyword%' ";
	}

	if(!empty($sdate) && !empty($edate)) {
		$date_query = " AND		(C.CREATED_DATE >= '$sdate' AND C.CREATED_DATE <= '$edate')";
	}


	$query = "
		SELECT	(SELECT BS_CONTENT_TITLE FROM BC_BS_CONTENT WHERE BS_CONTENT_ID = C.BS_CONTENT_ID) AS CONTENTTYPE, C.BS_CONTENT_ID,
                C.CONTENT_ID AS OBJECTID, C.TITLE, C.IS_GROUP, 
                C.GROUP_COUNT, 
                OT.PLAYOUT_ID AS PLAYOUTID,
                OM.PATH AS ORI_PATH, 
                PM.PATH AS PROXY_PATH,
                 TM.PATH AS THUMB_PATH, 
                 om.storage_id as ori_storage_id, 
                pm.storage_id as proxy_storage_id,
                 tm.storage_id as thumb_storage_id, 
                 SM.SYS_VIDEO_RT AS DURATION
		FROM	BC_CONTENT C
				LEFT OUTER JOIN BC_SYSMETA_MOVIE SM ON SM.SYS_CONTENT_ID = C.CONTENT_ID
				LEFT OUTER JOIN TB_ORD_TRANSMISSION_ID OT ON OT.CONTENT_ID = C.CONTENT_ID
				LEFT OUTER JOIN BC_MEDIA OM ON OM.CONTENT_ID = C.CONTENT_ID AND OM.MEDIA_TYPE = 'original'
				LEFT OUTER JOIN BC_MEDIA PM ON PM.CONTENT_ID = C.CONTENT_ID AND PM.MEDIA_TYPE = 'proxy'
				LEFT OUTER JOIN BC_MEDIA TM ON TM.CONTENT_ID = C.CONTENT_ID AND TM.MEDIA_TYPE = 'thumb'
		WHERE	C.UD_CONTENT_ID IN ($ud_contents)
		AND		C.IS_DELETED = 'N'
		AND		C.STATUS >= '0'
		AND		( C.BS_CONTENT_ID = ".SEQUENCE." OR C.IS_GROUP = 'I' )
	".$date_query.$ud_query.$keyword_query.$condition_query;

	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/zodiac_getContentLists_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] query ===> '.$query."\r\n", FILE_APPEND);
	$total = $db->queryOne("SELECT COUNT(A.OBJECTID) FROM ($query) A");

	$db->setLimit($rowcount, ($curPage -1) * $rowcount);

    $content_list = $db->queryAll($query." ORDER BY C.CREATED_DATE DESC");
    
    $storageLists = $db->queryAll("select * from bc_storage");
    $storageMap = [];
    foreach($storageLists as $storage){
        $storageMap[$storage['storage_id']] = $storage['virtual_path'];
    }

    foreach($content_list as $key => $content )
    {
        $content_list[$key]['ori_mid_path'] = $storageMap[$content['ori_storage_id']];
        $content_list[$key]['proxy_mid_path'] = $storageMap[$content['proxy_storage_id']];
        $content_list[$key]['thumb_mid_path'] = $storageMap[$content['thumb_storage_id']];
    }

	return array(
		'total' => $total,
		'content_list' => $content_list
	);

}

function getNPSPublishContentListsFromDB($assetType, $category_id, $sdate, $edate, $keyword, $curPage, $rowcount, $user_id) {
	global $db;

	if($assetType == 'video') {
		// 영삼만 조회되도록
		$ud_query = "
					AND		C.UD_CONTENT_ID = 4000287
				";
		$usrmetatable = MetaDataClass::getTableName('usr', 4000287);
	} else if($assetType == 'graphic') {
		$ud_query = "
					AND		C.UD_CONTENT_ID = 4000293
				";
		$usrmetatable = MetaDataClass::getTableName('usr', 4000293);
	}

	if(!empty($category_id)) {
		$pgm_query = "
					AND		C.CATEGORY_FULL_PATH LIKE '/0/$category_id%'
				";
	} else {
		$pgm_query = "
					AND		C.CATEGORY_FULL_PATH LIKE '/0%'
				";
	}

	if(!empty($keyword)) {
		$keyword = strtoupper($keyword);
		$keyword_query = " AND UPPER(C.TITLE) LIKE '%$keyword%' ";
	}

	if(!empty($sdate) && !empty($edate)) {
		$date_query = " AND		(C.CREATED_DATE >= '$sdate' AND C.CREATED_DATE <= '$edate')";
	}


	$query = "
		SELECT	(SELECT BS_CONTENT_TITLE FROM BC_BS_CONTENT WHERE BS_CONTENT_ID = C.BS_CONTENT_ID) AS CONTENTTYPE, C.BS_CONTENT_ID,
				C.CONTENT_ID AS OBJECTID, C.TITLE, C.IS_GROUP, C.GROUP_COUNT, OT.PLAYOUT_ID AS PLAYOUTID,
				OM.PATH AS ORI_PATH, PM.PATH AS PROXY_PATH, TM.PATH AS THUMB_PATH, SM.SYS_VIDEO_RT AS DURATION,
				(SELECT USER_NM FROM BC_MEMBER WHERE USER_ID = C.REG_USER_ID) AS REG_USER_NM, UM.USR_PGM_NM AS PGM_NM, C.CREATED_DATE
		FROM	BC_CONTENT C
				LEFT OUTER JOIN ".$usrmetatable." UM ON UM.USR_CONTENT_ID = C.CONTENT_ID
				LEFT OUTER JOIN BC_SYSMETA_MOVIE SM ON SM.SYS_CONTENT_ID = C.CONTENT_ID
				LEFT OUTER JOIN TB_ORD_TRANSMISSION_ID OT ON OT.CONTENT_ID = C.CONTENT_ID
				LEFT OUTER JOIN BC_MEDIA OM ON OM.CONTENT_ID = C.CONTENT_ID AND OM.MEDIA_TYPE = 'original'
				LEFT OUTER JOIN BC_MEDIA PM ON PM.CONTENT_ID = C.CONTENT_ID AND PM.MEDIA_TYPE = 'proxy'
				LEFT OUTER JOIN BC_MEDIA TM ON TM.CONTENT_ID = C.CONTENT_ID AND TM.MEDIA_TYPE = 'thumb'
		WHERE	C.IS_DELETED = 'N'
		AND		C.IS_GROUP = 'I'
		AND		C.STATUS >= 0
	".$ud_query.$pgm_query.$date_query.$keyword_query;

	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/zodiac_getPublishContentLists_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] query ===> '.$query."\r\n", FILE_APPEND);
	$total = $db->queryOne("SELECT COUNT(A.OBJECTID) FROM ($query) A");

	$db->setLimit($rowcount, ($curPage -1) * $rowcount);

	$content_list = $db->queryAll($query." ORDER BY C.CREATED_DATE DESC");

	return array(
			'total' => $total,
			'content_list' => $content_list
	);
}
?>