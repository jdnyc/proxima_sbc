<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/interface.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/soap/nusoap.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/Zodiac.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/store/request_zodiac/functions.php');
fn_checkAuthPermission($_SESSION);
try
{
	$user_id	= $_SESSION['user']['user_id'];
	$channel	= 'transmission_zodiac';
	$test_user_id = $user_id;

	$action = $_POST['action'];
	if(!empty($_POST['type_content'])){
		switch(strtolower($_POST['type_content'])){
			case 506:
			case 'tab_video':
			case 'video':
				$media_cd = '001';
			break;
			case 518:
			case 'tab_graphic':
			case 'graphic':
				$media_cd = '002';
			break;
		}
	}

	switch($action){
		//일반기사 관련
		case 'list_article'://일반기사 목록
			$resultData = requestListArticle(SOAP_ZODIAC_ARTICLE, $_POST,$test_user_id);
		break;
		case 'list_detail'://일반기사 매칭 목록
			$resultData = requestListMatchingArticle(SOAP_ZODIAC_ARTICLE, $_POST,$test_user_id, $media_cd);
		break;
		case 'matching'://일반기사 매칭
			$resultData = matchingArticle(SOAP_ZODIAC_ARTICLE, $_POST,$test_user_id, $media_cd, $user_id, $channel);
		break;
		case 'show_detail'://일반기사 상세보기
			$resultData = showDetalArticle(SOAP_ZODIAC_ARTICLE, $_POST,$test_user_id);
		break;
		case 'unmatching_article'://일반기사 매칭 해제
			$resultData = unmatchingArticle(SOAP_ZODIAC_ARTICLE, $_POST,$test_user_id, $media_cd);
		break;
		//큐시트 관련
		case 'list_program'://큐시트 프로그램 목록
			$resultData = requestListProgram(SOAP_ZODIAC_RUNDOWN, $_POST,$test_user_id);
		break;
		case 'list_rundown'://큐시트 목록
			$resultData = requestListRundown(SOAP_ZODIAC_RUNDOWN, $_POST,$test_user_id);
		break;
		case 'list_rundown_maching'://큐시트 매칭 목록
			$resultData = requestListMachingRundown(SOAP_ZODIAC_RUNDOWN, $_POST,$test_user_id, $media_cd);
		break;
		case 'matching_q'://큐시트 매칭
			$resultData = matchingRundown(SOAP_ZODIAC_RUNDOWN, $_POST,$test_user_id, $media_cd, $user_id, $channel);
		break;
		case 'show_detail_rundown'://큐시트 상세보기
			$resultData = showDetailRundown(SOAP_ZODIAC_RUNDOWN, $_POST,$test_user_id);
		break;
		case 'unmatching_rundown'://큐시트 매칭 해제
			$resultData = unmatchingRundown(SOAP_ZODIAC_RUNDOWN, $_POST,$test_user_id, $media_cd);
		break;
		case 'update_order':
			$resultData = updateOrder(SOAP_ZODIAC_ARTICLE, $_POST,$test_user_id);
		break;
		case 'caption':
			$resultData = getCaption(SOAP_ZODIAC_ARTICLE, $_POST,$test_user_id);
		break;
	}
	echo json_encode($resultData);
}
catch(Exception $e)
{
echo 'err:'.$e->getMessage();

}

/*
function requestListArticle($wsdl, $post,$test_user_id){
	$search = json_decode($post['search'], true);
	$artcl_titl = empty($search['artcl_titl']) ? '' : $search['artcl_titl'];
	$artcl_frm_cd = empty($search['artcl_frm_cd']) || $search['artcl_frm_cd'] == 'value' ? '' : $search['artcl_frm_cd'];
	$issu_id = empty($search['issu_id']) || $search['issu_id'] == 'value' ? '' : $search['issu_id'];
	$apprv_div_cd = empty($search['apprv_div_cd']) || $search['apprv_div_cd'] == 'value' || $search['apprv_div_cd'] == 'all' ? '' : $search['apprv_div_cd'];


	$start_date = empty($search['start_date']) ? date("Y-m-d", strtotime(date("Ymd")."-3 month")) : $search['start_date'];
	$end_date = empty($search['end_date']) ? date("Y-m-d") : $search['end_date'];
	$start = empty($post['start']) ? 0 : $post['start'];
	$limit = empty($post['limit']) ? 20 : $post['limit'];
	$page = ($start+1)/$limit;
	if($page == 0 ){
		$curPage = 1;
	}else{
		$curPage = ceil($page);
	}
	$request = array(
		'orderXml' => '',
		'srcXml' => $artcl_titl,
		'frnoti_yn' => 'N',
		'frm_cd' => $artcl_frm_cd,
		'fld_cd' => '',
		'dept_cd' => '',
		'artcl_type' => $issu_id,
		'issu_id' => '',
		'issu_seq' => '',
		'issu_dt' => '',
		'sns_yn' => 'N',
		'apr_cd' => $apprv_div_cd,
		'artcl_target_gubun' => '',
		'ch_div_cd' => '001',
		'sch_div_cd' => '001',
		'del_yn' => 'N',
		'sdate' => $start_date,
		'edate' => $end_date,
		'rpt_pln_id' => '',
		'curPage' => $curPage,
		'rowcount' => $limit,
		'usr_id' => $test_user_id,
		'token' => '',
		'usr_ip' => '',
		'format' => 'json',
		'lang' => 'KOR',
		'os_type' => 'CS'
	);

	$return = InterfaceClass::client($wsdl, 'getSelectArticleExtend' , $request);
	$r_data = json_decode($return['return'], true);
	if(!is_array($r_data['data']['record'])){
		$r_data['data']['record'] = array();
	}else if($r_data['data']['totalcount'] == 1){
		$r_data['data']['record'] = array($r_data['data']['record']);
	}

	$result = array(
		'success' => $r_data['result']['success'],
		'total' => $r_data['data']['totalcount'],
		'data' => $r_data['data']['record']
	);

	return $result;
}

function requestListMatchingArticle($wsdl, $post,$test_user_id, $media_cd){
	$request = array(
		'artcl_id' => $post['artcl_id'],
		'media_cd' => $media_cd,
		'ch_div_cd' => '001',
		'usr_id' => $test_user_id,
		'token' => '',
		'usr_ip' => '',
		'format' => 'json',
		'lang' => 'KOR',
		'os_type' => 'CMS'
	);

	$return = InterfaceClass::client($wsdl, 'getSelectArticleMediaListExt' , $request);
	$r_data = json_decode($return['return'], true);

	if(!is_array($r_data['data']['record'])){
		$r_data['data']['record'] = array();
	}else if($r_data['data']['totalcount'] == 1){
		$r_data['data']['record'] = array($r_data['data']['record']);
	}

	$_data = array();
	foreach( $r_data['data']['record'] as $data ){
		if(is_array($data['playout_time'])){
			$data['playout_time'] = '';
		}else{
			$data['playout_time'] = str_pad($data['playout_time'], 6, '0', STR_PAD_LEFT);
		}
		array_push($_data, $data);
	}
	$result = array(
		'success' => $r_data['result']['success'],
		'total' => $r_data['data']['totalcount'],
		'data' => $_data
	);

	return $result;
}

function matchingArticle($wsdl, $post,$test_user_id, $media_cd, $user_id, $channel){
	global $db;
	//송출 아이디 생성
	$ord_tr_id = createTransmissionId();
	$content_id = $post['content_id'];

	//송출 목록 생성 TB_ORD_TRANSMISSION
	$isnert_data = array(
		'ord_tr_id'		=>	 $ord_tr_id,
		'content_id'		=>	 $content_id,
		'create_time'	=>	 date('YmdHis'),
		'create_user'	=>	 $user_id
	);

	$query_insert = $db->insert('TB_ORD_TRANSMISSION', $isnert_data);
	//$query_insert = "
		//INSERT	INTO	TB_ORD_TRANSMISSION
		//(ORD_TR_ID, CONTENT_ID, CREATE_TIME, CREATE_USER)
		//VALUES
		//('".$ord_tr_id."', ".$content_id."','".date('YmdHis')."', '".$user_id."')
	//";

	if( $query_insert )
	{
		$title = $db->queryOne("
			SELECT	COUNT(MEDIA_ID)
			FROM		BC_MEDIA
			WHERE	CONTENT_ID = '".$content_id."'
				AND	MEDIA_TYPE = 'original'
				AND	STATUS != '1'
				AND	FILESIZE > 0
		");

		if(empty($title)) {
			// 미디어가 없을 경우 false return
			$result =  array(
				'success' => false,
				'msg' => '요청하신 콘텐츠의 원본파일이 없습니다.'
			);
		} else {
			// 미디어가 있을 경우에는 전송요청
			$task = new TaskManager($db);
			$task_id = $task->start_task_workflow($content_id, $channel, $user_id);

			if($task_id){
				//송출테이블에 TASK_ID 업데이트
				$query_update = "
					UPDATE	TB_ORD_TRANSMISSION	SET
					TASK_ID = ".$task_id."
					WHERE	ORD_TR_ID = '".$ord_tr_id."'
				";

				if( $db->exec($query_update) ){
					$request = array(
						'artcl_id' =>  $post['artcl_id'],
						'media_id' => $content_id,
						'playout_id' => $ord_tr_id,
						'media_cd' => $media_cd,//영상 001 그래픽 002
						'media_nm' => $post['title'],//영상 제목
						'duration' => $post['duration'],
						'format' => 'json',
						'lang' => 'KOR',
						'usr_id' => $test_user_id
					);

					$return = InterfaceClass::client($wsdl, 'putUpdateArticleMatchExt' , $request);
					$data_count = showDetalArticle(SOAP_ZODIAC_ARTICLE, $post,$test_user_id);
					$r_data = json_decode($return['return'], true);

					$result = array(
						'success' => $r_data['result']['success'],
						'total' => $r_data['data']['totalcount'],
						'data' => $r_data['data']['record'],
						'data_count' => $data_count['data']
					);
				}else{
					$result =  array(
						'success' => false,
						'msg' => '작업 등록id 업데이트 실패'
					);
				}



			}else{
				$result =  array(
					'success' => false,
					'msg' => '파일전송 실패'
				);
			}
		}
	}else{
		$result =  array(
			'success' => false,
			'msg' => '송출 목록 생성 실패'
		);
	}

	return $result;
}

function makeField($data){

	$name_field = array(
		'artcl_id' => '기사아이디',
		'ch_div_nm' => '',
		'artcl_frm_nm' => '기사형식명',
		'artcl_fld_nm' => '기사분야명',
		'artcl_titl' => '기사제목',
		'artcl_ctt' => '기사내용',
		'dept_nm' => '부서명',
		'artcl_reqd_sec' => '',
		'artcl_div_nm' => '',
		'issu_seq' => '',
		'apprv_div_nm' => '승인구분명',
		'artcl_ord' => '',
		'brdc_cnt' => '',
		'org_artcl_id' => '',
		'urg_yn' => '긴급여부',
		'internet_only_yn' => '',
		'frnoti_yn' => '',
		'embg_yn' => '엠바고여부',
		'sns_yn' => '',
		'top_yn' => '',
		'del_yn' => '삭제여부',
		'os_type' => '',
		'inputr_id' => '입력자아이디',
		'input_dtm' => '입력일시',
		'inputr_nm' => '입력자명',
		'updtr_id' => '수정자아이디',
		'updt_dtm' => '수정일시',
		'updtr_nm' => '수정자명',
		'grphc_count' => '그래픽매칭갯수',
		'video_count' => '영상매칭갯수',
		'cvcount' => '',
		'cgcount' => '',
		'attc_file_count' => '',
		'ord_gt_count' => '',
		'ord_gc_count' => '',
		'ord_vt_count' => '',
		'ord_vc_count' => '',
		//큐시트 관련
		'rd_id' => '큐시트Id',
		'rd_seq' => '큐시트시퀀스',
		'ch_div_nm' => '채널명',
		'artcl_id' => '기사ID',
		'artcl_frm_nm' => '기사형식',
		'artcl_fld_nm' => '기사분야',
		'artcl_titl' => '기사제목',
		'artcl_ctt' => '기사내용',
		'rptr_id' => '',
		'rptr_nm' => '기자명',
		'dept_nm' => '부서명',
		'apprv_div_nm' => '승인구분',
		'apprv_dtm' => '승인일시',
		'apprvr_nm' => '승인자명',
		'inputr_nm' => '입력자',
		'input_dtm' => '입력일시',
		'updtr_nm' => '수정자',
		'updt_dtm' => '수정일시'
	);

	$ex = array('artcl_id','issu_seq','artcl_ord','brdc_cnt','org_artcl_id','os_type');
	$field_form = array();
	foreach($data as $field=>$d){
		if( is_array($d) || strstr($field, '_cd') || in_array($field, $ex) || empty($name_field[$field]) )  {
			continue;
		}else if($field == 'artcl_ctt'){
			//$value = addslashes($d);
			//$value = str_replace("\r", '', str_replace("\n", '\\n', $value));
			//array_push($field_form, "{xtype:'textarea', readOnly: true, fieldLabel:'".$name_field[$field]."', value:'".$value."', height : 400,listeners: {afterrender:function(self){self.getEl().setStyle({fontSize:'13px'})}} }");
		}else{
			//$value = addslashes($d);
			//$value = str_replace("\r", '', str_replace("\n", '\\n', $value));
			//array_push($field_form, "{xtype:'textfield', readOnly: true, fieldLabel:'".$name_field[$field]."', value:'".$value."'}");
		}
	}

	$fields = join(',', $field_form);

	return $fields;
}

function showDetalArticle($wsdl, $post,$test_user_id){
	global $db;

	$request = array(
		'artcl_id' => $post['artcl_id'],
		'edit_yn' => '',
		'ch_div_cd' => '001',
		'usr_id' => $test_user_id,
		'token' => '',
		'usr_ip' => '',
		'format' => 'json',
		'lang' => 'KOR',
		'os_type' => 'CS'
	);

	$return = InterfaceClass::client($wsdl, 'getSelectArticleInfo' , $request);
	$r_data = json_decode($return['return'], true);
	if(!is_array($r_data['data']['record'])){
		$r_data['data']['record'] = array();
	}

	//$data = makeField($r_data['data']['record']);

	$result = array(
		'success' => $r_data['result']['success'],
		'total' => $r_data['data']['totalcount'],
		'data' => $r_data['data']['record']
	);

	return $result;
}

function unmatchingArticle($wsdl, $post,$test_user_id, $media_cd){
	$request = array(
		'artcl_id' => $post['artcl_id'],
		'media_id' => $post['content_id'],
		'plyout_id' => $post['plyout_id'],
		'media_cd' => $media_cd,//영상 001 그래픽 002
		'usr_id' => $test_user_id,
		'lang' => 'KOR',
		'format' => 'json'
	);

	$return = InterfaceClass::client($wsdl, 'UpdateArticleUnMatch' , $request);
	$r_data = json_decode($return['return'], true);
	$data_count = showDetalArticle(SOAP_ZODIAC_ARTICLE, $post,$test_user_id);
	$result = array(
		'success' => $r_data['result']['success'],
		'total' => $r_data['data']['totalcount'],
		'data' => $r_data['data']['record'],
		'data_count' => $data_count['data']
	);

	return $result;
}

function requestListProgram($wsdl, $post,$test_user_id){
	$search = json_decode($post['search'], true);
	$start_date = empty($search['broad_ymd']) ? date("Y-m-d 00:00:00") : date("Y-m-d 00:00:00", strtotime($search['broad_ymd']));
	$end_date = empty($search['broad_ymd']) ? date('Y-m-d 23:59:59') : date("Y-m-d 23:59:59", strtotime($search['broad_ymd']));
	$start = empty($post['start']) ? 0 : $post['start'];
	$limit = empty($post['limit']) ? 20 : $post['limit'];
	$page = ($start+1)/$limit;
	if($page == 0 ){
		$curPage = 1;
	}else{
		$curPage = ceil($page);
	}

	$prognm = empty($search['pgm_nm']) ? '' : $search['pgm_nm'];

	$request = array(
		'pgm_nm' => $prognm,
		'sdate' => $start_date,
		'edate' => $end_date,
		'ch_div_cd' => '001',
		'curpage' => $curPage,
		'rowcount' => $limit,
		'usr_id' => $test_user_id,
		'lang' => 'KOR',
		'format' => 'json'
	);

	//print_r($request);exit;

	$return = InterfaceClass::client($wsdl, 'getSelectRundownExt' , $request);
	$r_data = json_decode($return['return'], true);
	if(!is_array($r_data['data']['record'])){
		$r_data['data']['record'] = array();
	}else if($r_data['data']['totalcount'] == 1){
		$r_data['data']['record'] = array($r_data['data']['record']);
	}

	$result = array(
		'success' => $r_data['result']['success'],
		'total' => $r_data['data']['totalcount'],
		'data' => $r_data['data']['record']
	);

	return $result;
}

function requestListRundown($wsdl, $post,$test_user_id){
	$start = empty($post['start']) ? 0 : $post['start'];
	$limit = empty($post['limit']) ? 20 : $post['limit'];
	$page = ($start+1)/$limit;
	if($page == 0 ){
		$curPage = 1;
	}else{
		$curPage = ceil($page);
	}
	$request = array(
		'rd_id' => $post['search'],
		'usr_id' => $test_user_id,
		'lang' => 'KOR',
		'format' => 'json'
	);


	$return = InterfaceClass::client($wsdl, 'getSelectRundownDtlExt' , $request);
	$r_data = json_decode($return['return'], true);
	if(!is_array($r_data['data']['record'])){
		$r_data['data']['record'] = array();
	}else if($r_data['data']['totalcount'] == 1){
		$r_data['data']['record'] = array($r_data['data']['record']);
	}

	$result = array(
		'success' => $r_data['result']['success'],
		'total' => $r_data['data']['totalcount'],
		'data' => $r_data['data']['record']
	);

	return $result;
}

function requestListMachingRundown($wsdl, $post,$test_user_id, $media_cd){
	$start = empty($post['start']) ? 0 : $post['start'];
	$limit = empty($post['limit']) ? 20 : $post['limit'];
	$page = ($start+1)/$limit;
	if($page == 0 ){
		$curPage = 1;
	}else{
		$curPage = ceil($page);
	}
	$request = array(
		'rd_id' => $post['artcl_id'],
		'rd_seq' => $post['rd_seq'],
		'media_cd' => $media_cd,
		'usr_id' => $test_user_id,
		'lang' => 'KOR',
		'format' => 'json'
	);

	$return = InterfaceClass::client($wsdl, 'getSelectRundownDtlMatch' , $request);
	$r_data = json_decode($return['return'], true);
	if(!is_array($r_data['data']['record'])){
		$r_data['data']['record'] = array();
	}else if($r_data['data']['totalcount'] == 1){
		$r_data['data']['record'] = array($r_data['data']['record']);
	}

	$_data = array();
	foreach( $r_data['data']['record'] as $data ){
		if(is_array($data['playout_time'])){
			$data['playout_time'] = '';
		}else{
			$data['playout_time'] = str_pad($data['playout_time'], 6, '0', STR_PAD_LEFT);
		}
		array_push($_data, $data);
	}
	$result = array(
		'success' => $r_data['result']['success'],
		'total' => $r_data['data']['totalcount'],
		'data' => $_data
	);

	return $result;
}

function matchingRundown($wsdl, $post,$test_user_id, $media_cd, $user_id, $channel){
	global $db;
	//송출 아이디 생성
	$ord_tr_id = createTransmissionId();
	$content_id = $post['content_id'];

	//송출 목록 생성 TB_ORD_TRANSMISSION
	$query_insert = "
		INSERT	INTO	TB_ORD_TRANSMISSION
		(ORD_TR_ID, CONTENT_ID, CREATE_TIME, CREATE_USER)
		VALUES
		('".$ord_tr_id."','".$content_id."','".date('YmdHis')."', '".$user_id."')
	";

	if( $db->exec($query_insert) ){
		$title = $db->queryOne("
			SELECT	COUNT(MEDIA_ID)
			FROM		BC_MEDIA
			WHERE	CONTENT_ID = '".$content_id."'
				AND	MEDIA_TYPE = 'original'
				AND	STATUS != '1'
				AND	FILESIZE > 0
		");

		if(empty($title)) {
			// 미디어가 없을 경우 false return
			$result =  array(
				'success' => false,
				'msg' => '요청하신 콘텐츠의 원본파일이 없습니다.'
			);
		} else {
			// 미디어가 있을 경우에는 전송요청
			$task = new TaskManager($db);
			$task_id = $task->start_task_workflow($content_id, $channel, $user_id);

			if($task_id){
				//송출테이블에 TASK_ID 업데이트
				$query_update = "
					UPDATE	TB_ORD_TRANSMISSION	SET
					TASK_ID = '".$task_id."'
					WHERE	ORD_TR_ID = '".$ord_tr_id."'
				";

				if( $db->exec($query_update) ){
					$request = array(
						'rd_id' =>  $post['artcl_id'],
						'rd_seq' =>  $post['rd_seq'],
						'media_id' => $content_id,
						'playout_id' => $ord_tr_id,
						'media_cd' => $media_cd,//영상 001 그래픽 002
						'human_id' => '',
						'media_nm' => $post['title'],//영상 제목
						'duration' => $post['duration'],
						'usr_id' => $test_user_id,
						'lang' => 'KOR',
						'format' => 'json'
					);

					$return = InterfaceClass::client($wsdl, 'putUpdateRundownMatch' , $request);
					$data_count = showDetailRundown(SOAP_ZODIAC_RUNDOWN, $post,$test_user_id);
					$r_data = json_decode($return['return'], true);
					$result = array(
						'success' => $r_data['result']['success'],
						'total' => $r_data['data']['totalcount'],
						'data' => $r_data['data']['record'],
						'data_count' => $data_count['data']
					);
				}else{
					$result =  array(
						'success' => false,
						'msg' => '작업 등록id 업데이트 실패'
					);
				}



			}else{
				$result =  array(
					'success' => false,
					'msg' => '파일전송 실패'
				);
			}
		}
	}else{
		$result =  array(
			'success' => false,
			'msg' => '송출 목록 생성 실패'
		);
	}

	return $result;
}

function showDetailRundown($wsdl, $post,$test_user_id){
	$request = array(
		'rd_id' => $post['artcl_id'],
		'rd_seq' => $post['rd_seq'],
		'ch_div_cd' => '001',
		'usr_id' => $test_user_id,
		'token' => '',
		'usr_ip' => '',
		'format' => 'json',
		'lang' => 'KOR',
		'os_type' => 'CMS'
	);

	$return = InterfaceClass::client($wsdl, 'getSelectRundownDtlArticleWithCap' , $request);
	$r_data = json_decode($return['return'], true);
	if(!is_array($r_data['data']['record'])){
		$r_data['data']['record'] = array();
	}
	$result = array(
		'success' => $r_data['result']['success'],
		'total' => $r_data['data']['totalcount'],
		'data' => $r_data['data']['record']
	);

	return $result;
}

function unmatchingRundown($wsdl, $post,$test_user_id, $media_cd){
	$request = array(
		'rd_id' => $post['artcl_id'],
		'rd_seq' => $post['rd_seq'],
		'media_id' => $post['content_id'],
		'plyout_id' => $post['plyout_id'],
		'media_cd' => $media_cd,//영상 001 그래픽 002
		'usr_id' => $test_user_id,
		'lang' => 'KOR',
		'format' => 'json'
	);

	$return = InterfaceClass::client($wsdl, 'putUpdateRundownUnMatch' , $request);
	$r_data = json_decode($return['return'], true);
	$result = array(
		'success' => $r_data['result']['success'],
		'total' => $r_data['data']['totalcount'],
		'data' => $r_data['data']['record']
	);

	return $result;
}

function createTransmissionId(){
	global $db;

	$seq = getSequence('SEQ_TB_ORD_TRANSMISSION');
	$ord_tr_id = date('Ymd').'TR'.str_pad($seq, 5, '0', STR_PAD_LEFT);

	return $ord_tr_id;
}
*/
?>