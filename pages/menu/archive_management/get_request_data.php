<?php
set_time_limit(0);
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/lib.php');

$limit =		$_REQUEST['limit'];
$start =		$_REQUEST['start'];
$s_date =		$_REQUEST['req_start_date'];
$e_date =		$_REQUEST['req_end_date'];


$sort =		$_REQUEST['sort'];
$dir =		$_REQUEST['dir'];

$req_type = $_REQUEST['req_type'];
$req_status = $_REQUEST['req_status'];
$req_user = $_REQUEST['req_user'];

$groups = $_SESSION['user']['groups'];
$user_id = $_SESSION['user']['user_id'];

$search_field  = $_REQUEST['search_field'];
$search_value  = $db->escape(strtoupper($_REQUEST['search_value']));

$taskStatus =		$_REQUEST['task_status'];

$is_excel = $_REQUEST['is_excel'];

if (empty($start)) {
	$start = 0;
}
if (empty($limit)) {
	$limit = 50;
}

//엑셀용 맵핑 array
$mappingReqTypeFlag = array(
	'archive'		=>	'아카이브',
	'restore'		=>	'리스토어',
	'pfr_restore'	=>	'PFR 리스토어'
);

// $mappingReqStatusFlag = array(
// 	ARCHIVE_REQUEST	=> '요청',
// 	ARCHIVE_REJECT	=> '반려',
// 	ARCHIVE_APPROVE	=> '승인'
// );

$mappingReqStatusFlag = array(
	'ARCHIVE_REQUEST'	=> '요청',
	'ARCHIVE_REJECT'	=> '반려',
	'ARCHIVE_APPROVE'	=> '승인'
);

$mappingTaskStatusFlag = array(
	'queue'			=> '대기',
	'error'			=> '실패',
	'complete'		=> '완료',
	'processing'	=> '진행중',
	'scheduled'	=> '예약됨'
);

$ud_contents = $db->queryAll("
	SELECT	*
	FROM	BC_UD_CONTENT
	WHERE	BS_CONTENT_ID IN (506, 515, 518, 57057)
	ORDER BY SHOW_ORDER
");

$required_inputs = array();

foreach ($ud_contents as $ud_content) {
	$usr_meta_fields = $db->queryAll("
		SELECT	*
		FROM	BC_USR_META_FIELD
		WHERE	UD_CONTENT_ID = " . $ud_content['ud_content_id'] . "
		AND		IS_REQUIRED = '1'
		ORDER BY SHOW_ORDER
	");

	$required_inputs[$ud_content['ud_content_code']] = $usr_meta_fields;
}


//현재 유저의 content 테이블 불러오기
try {

	switch ($req_type) {
		case 'all':
			$req_type_query = "";
			break;
		default:
			//그 외 req_type 들
			$req_type_query = " AND TR.REQ_TYPE = '" . $req_type . "' ";
			break;
	}

	if ($req_user) {
		$req_user_query = " AND (TR.req_user_id = '" . $user_id . "' OR TR.appr_user_id = '".$user_id."') ";
	}

	switch ($req_status) {
		case 'all':
			$req_status_query = "";
			break;
		default:
			//그 외 arc_type 들
			$req_status_query = " AND TR.REQ_STATUS = '" . $req_status . "' ";
			break;
	}

	switch ($search_field) {
        case 'keyword':
            $search_query = "";
            if(!empty($search_value)){
                $search_query = " AND UPPER(C.TITLE) LIKE '%$search_value%'";
            }
			
			break;
		case 'req_user':
			$search_query = " AND TR.REQ_USER_ID = (SELECT USER_ID FROM BC_MEMBER WHERE UPPER(USER_NM) = '$search_value')";
			break;
		case 'appr_user':
			$search_query = " AND TR.APPR_USER_ID = (SELECT USER_ID FROM BC_MEMBER WHERE UPPER(USER_NM) = '$search_value')";
			break;
		case 'nps_content_id':
			$search_query = " AND TR.NPS_CONTENT_ID = $search_value";
			break;
		case 'req_comment':
			$search_query = " AND TR.REQ_COMMENT LIKE '%$search_value%'";
			break;
    }
    
    if($sort && $dir){
        $order = " ORDER BY TR.".$sort." ".$dir;
    }else{
        $order = " ORDER BY TR.REQ_TIME DESC ";
    }

    if( !empty($taskStatus) && $taskStatus !='all'){
        $taskStatusQuery = " AND T.status='$taskStatus'";
    }else{
        $taskStatusQuery="";
    }

	$req_date_query = "AND TR.REQ_TIME >= '" . $s_date . "' AND TR.REQ_TIME <= '" . $e_date . "'";

	$query = "
		SELECT	TR.*,
				(SELECT USER_NM FROM BC_MEMBER WHERE USER_ID = TR.REQ_USER_ID) AS REQ_USER_NM,
				(SELECT USER_NM FROM BC_MEMBER WHERE USER_ID = TR.APPR_USER_ID) AS APPR_USER_NM,
				C.TITLE, C.UD_CONTENT_ID,C.CONTENT_ID, UD.UD_CONTENT_TITLE, UD.UD_CONTENT_CODE,
				C.REG_USER_ID,
                T.STATUS,
                A.ARCHIVE_ID,
				BM.MEDIA_TYPE, BM.FILESIZE, BM.PATH
		FROM	BC_CONTENT C
				LEFT OUTER JOIN BC_UD_CONTENT UD ON UD.UD_CONTENT_ID = C.UD_CONTENT_ID
				LEFT OUTER JOIN BC_MEDIA BM ON BM.CONTENT_ID = C.CONTENT_ID AND BM.MEDIA_TYPE = 'original'
				,
				TB_REQUEST TR
                LEFT OUTER JOIN BC_TASK T ON T.TASK_ID = TR.TASK_ID	
                LEFT OUTER JOIN ARCHIVE A ON A.TASK_ID = TR.TASK_ID		
		WHERE	TR.DAS_CONTENT_ID = C.CONTENT_ID
		" . $req_date_query . $req_type_query . $taskStatusQuery . $req_status_query . $search_query . $req_user_query;
	//@file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/getRequestData_' . date('Ymd') . '.log', $_SERVER['REMOTE_ADDR'] . "\t[" . date('Y-m-d H:i:s') . '] query ===> ' . $query . "\r\n", FILE_APPEND);

	if ($is_excel == 'Y') {

		$results = $db->queryAll($query . $order );

		$results_excel = array();
		$num = 1;
		foreach ($results as $f => $result) {
			$excel_req_type = $mappingReqTypeFlag[trim($result['req_type'])];
			$excel_req_status = $mappingReqStatusFlag[trim($result['req_status'])];
			$excel_task_status = $mappingTaskStatusFlag[trim($result['status'])];

			if (!empty($result['appr_time'])) {
				$result['appr_time'] = date('Y-m-d H:i:s', strtotime($result['appr_time']));
			}

			$results_excel[$f]['순번'] = $num;
			$results_excel[$f]['의뢰구분'] = $excel_req_type;
			$results_excel[$f]['NPS ID'] = $result['nps_content_id'];
			$results_excel[$f]['의뢰상태'] = $excel_req_status;
			$results_excel[$f]['작업상태'] = $excel_task_status;
			$results_excel[$f]['소재명'] = $result['title'];
			$results_excel[$f]['의뢰자'] = $result['req_user_nm'];
			$results_excel[$f]['의뢰일시'] = date('Y-m-d H:i:s', strtotime($result['req_time']));
			$results_excel[$f]['요청사유'] = $result['req_comment'];
			$results_excel[$f]['승인자'] = $result['appr_user_nm'];
			$results_excel[$f]['승인일시'] = $result['appr_time'];
			$results_excel[$f]['승인내용'] = $result['appr_comment'];

			$num = $num + 1;
		}
		echo createExcel('아카이브_리스토어_요청관리', $results_excel);

		exit;
	}


	$total_query = "SELECT COUNT(*) FROM (" . $query . ") t1";
	$total = $db->queryOne($total_query);
  
	//요청일자로 정렬
	$db->setLimit($limit, $start);
	$results = $db->queryAll($query . $order );
    
	//$db->close();

	foreach ($results as $key => $result) {
		//아카이브 항목일 경우 필수 메타 입력여부 조회
		// if ($result['req_type'] == 'archive') {
		// 	$tablename = MetaDataClass::getTableName('usr', $result['ud_content_id']);
		// 	$empty_required = 'N';
		// 	$tmpUsrMetas = $db->queryRow(
		// 		"
		// 						SELECT	*
		// 						FROM	$tablename
		// 						WHERE	USR_CONTENT_ID = " . $result['das_content_id']
		// 	);

		// 	foreach ($required_inputs[$result['ud_content_code']] as $required) {
		// 		$tmpFieldName = strtolower('usr_' . $required['usr_meta_field_code']);
		// 		$tmpFieldValue = trim($tmpUsrMetas[$tmpFieldName]);

		// 		if (empty($tmpFieldValue)) {
		// 			$empty_required = 'Y';
		// 			break;
		// 		}
		// 	}

		// 	$results[$key]['required_input'] = $empty_required;
		// }

		// filesize 포맷 변경
		$results[$key]['filesize'] = formatBytes($results[$key]['filesize']);
	}

	$data = array(
		'success'		=> true,
		'data'			=> $results,
		'total_list'	=> $total,
		'query'			=> $query

	);

	echo json_encode($data);
} catch (Exception $e) {
	$data = array(
		'success'	=> false,
		'msg'		=> $e->getMessage(),
		'query' => $db->last_query
	);

	echo json_encode($data);
}
