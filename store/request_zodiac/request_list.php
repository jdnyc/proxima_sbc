<?PHP
//sbs_ops@192.168.1.72:/Proxima_OPS/javascript/ext.ux/Ariel.Nps.editArticle.php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/FcpXML.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');
fn_checkAuthPermission($_SESSION);
$user_id	= $_SESSION['user']['user_id'];
$values = json_decode($_POST['values'], true);

try{
	switch($_POST['action']){
		case 'dept'://부서정보(combo)
			getDept();
		break;
		case 'ud_content'://콘텐츠 유형
			getUdContent($_POST);
		break;
		case 'hour'://종료시각 입력시 시
			getHour();
		break;
		case 'minute'://종료시각 입력시 분
			getMinute();
		break;
		case 'list'://의뢰 목록
			getListArticle($_POST, $user_id);
		break;
		case 'list_article'://의뢰목록 기사 상세보기 기사 목록
			getListDetailArticle($_POST, $user_id);
		break;
		case 'list_edl'://의뢰 목록 기사 상세보기 edl 목록
			getListEdl($_POST);
		break;
		case 'list_video'://의뢰 목록 기사 상세보기 콘텐츠(비디오/그래픽) 목록
			getListVideo($_POST);
		break;
		case 'list_file'://의뢰 목록 기사 상세보기 첨부파일 목록
			getListFile($_POST);
		break;
		case 'update_status'://의뢰 목록 할당 상태의 목록 상태 변경
			updateStatus($_POST);
		break;
		case 'set_user'://의뢰 목록 기사 상세보기 편집자 할당
			setUser($_POST);
		break;
		case 'insert_time'://의뢰 목록 종료시간 입력
			insertTime($_POST);
		break;
		case 'delete_list'://의뢰 목록 삭제
			deleteRequest($_POST);
		break;
		case 'list_tr'://송출 목록
			getListTransmission($_POST);
		break;
		case 'update_keep_status'://송출 목록 영구보관 요청/취소
			updateKeepStatus($_POST);
		break;
		case 'delete_content_tr'://송출 삭제
			deleteContentTr($_POST, $user_id);
		break;
		case 'update_request':
			$query = updateRequest($values, $_POST['editor'], $user_id);
		break;
		case 'create_request':
			$query = createRequest($values, $user_id,$_POST['type_content']);
		break;
		case 'get_total_new_count':
			getTotalNewRequestCount($_POST, $user_id);
			break;
	}

}catch (Exception $e){
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}
function getDept(){

	global $db;

	$query = "
		SELECT	MEMBER_GROUP_ID AS VALUE, MEMBER_GROUP_NAME AS NAME
		FROM		BC_MEMBER_GROUP
	";
	$groups = $db->queryAll($query);
	$data = array();
	$data_all['name'] = _text('MN00008');//'전체'
	$data_all['value'] = '0';
	array_push($data, $data_all);
	foreach($groups as $group){
		array_push($data, $group);
	}
	echo json_encode(array(
		'success' => true,
		'data' => $data,
		'query' => $query
	));
}

function getHour(){
	$hours = array();
	$hour = array();
	for($i = 1;$i<=24;$i++){
		$hour['name'] = $i;
		$hour['value'] = str_pad($i, 2, 0, STR_PAD_LEFT);
		array_push($hours, $hour);
	}

	echo json_encode(array(
		'success' => true,
		'data' => $hours
	));
}

function getMinute(){
	$hours = array();
	$hour = array();
	for($i = 1;$i<=59;$i++){
		$hour['name'] = $i;
		$hour['value'] = str_pad($i, 2, 0, STR_PAD_LEFT);
		array_push($hours, $hour);
	}
	echo json_encode(array(
		'success' => true,
		'data' => $hours
	));
}

function getUdContent($post){

	global $db;

	switch($post['tab_id']){
		case 'tab_video':
			$ud_content_id = " WHERE BS_CONTENT_ID = 506 ";
		break;
		case 'tab_graphic':
			$ud_content_id = " WHERE BS_CONTENT_ID = 518 ";
		break;
		default:
			$ud_content_id = "";
		break;
	}
	$query = "
		SELECT	UD_CONTENT_ID AS VALUE, UD_CONTENT_TITLE AS NAME
		FROM		BC_UD_CONTENT
		".$ud_content_id."
	";
	$groups = $db->queryAll($query);
	$data = array();
	$data_all['name'] = _text('MN00008');//'전체'
	$data_all['value'] = '0';
	array_push($data, $data_all);
	foreach($groups as $group){
		array_push($data, $group);
	}
	echo json_encode(array(
		'success' => true,
		'data' => $data,
		'query' => $query
	));
}

function getListArticle($post, $user_id){

	global $db;

	$limit = !empty($post['limit']) ? $post['limit'] : 20;
	$start = !empty($post['start']) ? $post['start'] : 0;
	$start_date = !empty($post['start_date']) ? $post['start_date'] : date("Ymd", strtotime( $brodymd['value']."-1 month" )).'000000';
	$end_date = !empty($post['end_date']) ? $post['end_date'] : date("Ymd").'240000';
	$type = $post['type'];

	$where = array();
	array_push($where, " INPUT_DTM BETWEEN '".$start_date."' AND '".$end_date."' ");

	if($post['dept'] != 0 && !empty($post['dept']) && $post['dept'] != _text('MN00008')){//'전체'
		array_push($where, " DEPT_CD = '".$post['dept']."' ");
	}
	if($post['status'] != 'all' && !empty($post['status']) && $post['status'] != _text('MN00008')){//'전체'
		array_push($where, " ORD_STATUS = '".$post['status']."' ");
	}
	if($post['ord_id'] != '' && !empty($post['ord_id']) ){
		array_push($where, " ord_id = '".$post['ord_id']."' ");
	}
	
	if($post['search_text'] != '' && !empty($post['search_text']) ){
		array_push($where, " (TITLE LIKE '%".$post['search_text']."%' OR ORD_CTT LIKE '%".$post['search_text']."%') ");
	}
	array_push($where, " ORD_META_CD = '".$type."' ");

	$graphicRequestTypeCodeSetIdQuery = "SELECT ID FROM DD_CODE_SET WHERE CODE_SET_CODE = 'GRAPHIC_REQEST_TY'";
	$graphicRequestTypeCodeSetId = $db->queryOne($graphicRequestTypeCodeSetIdQuery);
    
	$query = "
			SELECT		CASE
					      WHEN A.ORD_WORK_ID = '$user_id' THEN
					        CASE WHEN 1 -
					          (
					          SELECT	COUNT(LOG_ID) AS LOG_ID_C
					          FROM	BC_LOG
					          WHERE	ACTION		= 'read_request'
					          AND		ZODIAC_ID	= A.ORD_ID
					          AND		USER_ID		= A.ORD_WORK_ID
					        ) = 1 THEN 1 ELSE 0 END
					      	ELSE 0
				    	END AS READ_FLAG
						,A.INPUT_DTM
						,A.ORD_CTT
						,A.TITLE
						,A.GRAPHIC_REQEST_TY
						,(
							SELECT code_itm_nm
							FROM DD_CODE_ITEM
							WHERE CODE_SET_ID = '$graphicRequestTypeCodeSetId'
							AND CODE_ITM_CODE = A.GRAPHIC_REQEST_TY
						) GRAPHIC_REQEST_TY_LN
						,A.ORD_ID
						,A.EXPT_ORD_END_DTM
						,A.ORD_WORK_ID
						,A.INPUTR_ID
						,A.DEPT_CD
						,A.ARTCL_TITL
						,A.ARTCL_ID
						,A.RD_ID
						,A.RD_SEQ
						--,FUNC_GET_USER_NAME(A.ORD_WORK_ID) AS ORD_WORK_NAME -- Request User
						--,FUNC_GET_USER_NAME(A.INPUTR_ID) AS INPUTR_NAME -- Assigned Editorok
						,M.USER_NM AS ORD_WORK_NAME
						,MN.USER_NM AS INPUTR_NAME
						,G.MEMBER_GROUP_NAME AS DEPT_NAME
						,CASE A.ORD_META_CD
							WHEN 'video' THEN  'Video'
							WHEN 'graphic' THEN  'Graphic'
							ELSE NULL
						END AS ORD_META_CD
						,CASE A.ORD_STATUS
							WHEN 'ready' THEN 'Queued'--대기
							WHEN 'working' THEN 'Assigned'--할당
							WHEN 'complete' THEN 'Completed'--완료
							WHEN 'cancel' THEN 'Cancel'--취소
							ELSE NULL
						END AS ORD_STATUS
				FROM	TB_ORD A
						LEFT OUTER JOIN BC_MEMBER_GROUP G ON(TO_CHAR(G.MEMBER_GROUP_ID, '') = A.DEPT_CD)
						LEFT OUTER JOIN BC_MEMBER M
							ON	M.USER_ID = A.ORD_WORK_ID
						LEFT OUTER JOIN BC_MEMBER MN
							ON MN.USER_ID = A.INPUTR_ID
			WHERE	".join(" AND ", $where)."
			";
	$order = " ORDER BY INPUT_DTM DESC ";
	$total_query = "SELECT COUNT(*) FROM (".$query.") CNT ";
	$total = $db->queryOne($total_query);
	$db->setLimit($limit, $start);

	$result = $db->queryAll($query.$order);

	echo json_encode(array(
		'success' => true,
		'total' => $total,
		'data' => $result,
		'query' => $query.$order
	));
}

function getListDetailArticle($post, $user_id){
	global $db;

	$query = "
		SELECT	INPUT_DTM, ORD_CTT, TITLE, ORD_ID, EXPT_ORD_END_DTM, ORD_WORK_ID, INPUTR_ID, DEPT_CD, ARTCL_TITL,
					(SELECT M.USER_NM FROM BC_MEMBER M WHERE M.USER_ID = ORD_WORK_ID) AS ORD_WORK_NAME,
					(SELECT B.USER_NM FROM BC_MEMBER B WHERE B.USER_ID = INPUTR_ID) AS INPUTR_NAME,
					(SELECT G.MEMBER_GROUP_NAME FROM BC_MEMBER_GROUP G WHERE G.MEMBER_GROUP_ID = DEPT_CD) AS DEPT_NAME,
					CASE ORD_META_CD
						WHEN 'video' THEN  '"._text('MN02087')."'
						WHEN 'graphic' THEN  '"._text('MN02088')."'
					ELSE NULL
					END AS ORD_META_CD,
					CASE ORD_STATUS
						WHEN 'ready' THEN '"._text('MN00160')."'--대기중
						WHEN 'working' THEN '"._text('MN02178')."'--할당
						WHEN 'complete' THEN '"._text('MN01117')."'--완료
						WHEN 'cancel' THEN '".'취소'."'--완료
					ELSE NULL
					END AS ORD_STATUS
		FROM		TB_ORD
		WHERE	ORD_ID = '".$post['ord_id']."'
	";

	$total = $db->queryOne("SELECT COUNT(*) FROM (".$query.") cnt");

	$result = $db->queryAll($query);

	$description = '';
	$request_id = $post['ord_id'];
	insertLogRequest('read_request', $user_id, $request_id, $description);

	echo json_encode(array(
		'success' => true,
		'total' => $total,
		'data' => $result,
		'query' => $query.$order
	));
}

function getListEdl($post){
	global $db;

	$query = "
		SELECT	E.*, E.VIDEO_ID AS CONTENT_ID
		FROM		TB_ORD_EDL E
		WHERE	E.ORD_ID = '".$post['ord_id']."'
	";

	$total = $db->queryOne("SELECT COUNT(*) FROM (".$query.") cnt");

	$result = $db->queryAll($query);

	echo json_encode(array(
		'success' => true,
		'total' => $total,
		'data' => $result,
		'query' => $query.$order
	));
}

function getListVideo($post){
	global $db;

	if($post['type'] == _text('MN02087') ){
		$table = 'TB_ORD_VIDEO';
		$field = 'VIDEO_ID';
	}else{
		$table = 'TB_ORD_GRPHC';
		$field = 'GRPHC_ID';
	}

	$query = "
		SELECT	O.".$field."  AS CONTENT_ID, O.ORD_ID, C.TITLE
		FROM		".$table." O, BC_CONTENT C
		WHERE	O.".$field." = C.CONTENT_ID
			AND	O.ORD_ID = '".$post['ord_id']."'
	";

	$result = $db->queryAll($query);

	echo json_encode(array(
		'success' => true,
		'total' => $total,
		'data' => $result,
		'query' => $query.$order
	));
}

function getListFile($post){
	global $db;

	$query = "
		SELECT	*
		FROM		TB_ORD_FILE
		WHERE	ORD_ID = '".$post['ord_id']."'
	";

	$result = $db->queryAll($query);

	echo json_encode(array(
		'success' => true,
		'total' => $total,
		'data' => $result,
		'query' => $query.$order
	));
}

function updateStatus($post){
	global $db;

	$query = "
		UPDATE	TB_ORD SET
		ORD_STATUS = 'complete'
		WHERE	ORD_ID = '".$post['ord_id']."'
	";
	$result = $db->exec($query);

	echo json_encode(array(
		'success' => true,
		//'data' => $result,
		'query' => $query
	));
}

function setUser($post){
	global $db;
	$query = "
		UPDATE	TB_ORD SET
					ORD_STATUS = 'working',
					UPDTR_ID = '".$post['user_id']."',
					UPDT_DTM = '".date("YmdHis")."',
					ORD_WORK_ID = '".$post['worker_id']."'
		WHERE	ORD_ID = '".$post['ord_id']."'

	";

	$resutl = $db->exec($query);

	//편집자 할당시 EDL 파일 스토리지에 생성
	//createEDL($post['ord_id']);

	echo json_encode(array(
		'success' => true,
		'data' => $result,
		'query' => $query
	));
}

function insertTime($post){
	global $db;
	$values = json_decode($post['values'], true);
	$day = date("Ymd", strtotime($values['day']));
	$hour = $values['hour'] == 'value' ? '00' : str_pad($values['hour'], 2, "0", STR_PAD_LEFT);
	$minute = $values['minute'] == 'value' ? '00' : str_pad($values['minute'], 2, "0", STR_PAD_LEFT);

	$query = "
		UPDATE	TB_ORD	SET
			EXPT_ORD_END_DTM = '".$day.$hour.$minute."00'
		WHERE	ORD_ID = '".$post['ord_id']."'
	";

	$resutl = $db->exec($query);

	echo json_encode(array(
		'success' => true,
		'data' => $result,
		'query' => $query
	));
}

function createEDL($ord_id){
	//EDL 저해상도 스토리지에 생성
	$fcp = new FcpXML();
	$xml = $fcp->TB_ORD_EDL_parser( $ord_id );
	$fcp->_PrintFile( ATTACH_ROOT.'/EDL/'.$ord_id.'.xml' ,$xml );
}

function deleteRequest($post){
	global $db;

	$query = "
		DELETE	TB_ORD
		WHERE	ORD_ID = '".$post['ord_id']."'
	";
	$result =  $db->exec($query);

	echo json_encode(array(
		'success' => true,
		'data' => $result,
		'query' => $query
	));
}

function getListTransmission($post){
	global $db;

	$query = "
		SELECT	O.ORD_TR_ID, O.CREATE_TIME AS ORD_CREATE_TIME, O.DELETE_TIME AS ORD_DELETE_TIME,
					O.KEEP_YN AS ORD_KEEP_YN, O.TASK_ID AS ORD_TASK_ID, O.CONTENT_ID AS ORD_CONTENT_ID,
					O.CREATE_USER AS TASK_USER_ID, O.REQUEST_TIME AS TASK_CREATION_TIME, O.COMPLETE_TIME AS TASK_COMPLETE_TIME,
					O.TR_STATUS ,
					C.TITLE AS CONTENT_TITLE, C.BS_CONTENT_ID , C.BS_CONTENT_ID AS CONTENT_UD,
					CASE C.BS_CONTENT_ID
						WHEN 506 THEN '"._text('MN02087')."'
						WHEN 518 THEN '"._text('MN02088')."'
						ELSE ''
					END AS CONTENT_UD_NAME,
					(SELECT	 USER_NM FROM BC_MEMBER WHERE USER_ID = O.CREATE_USER) AS MEMBER_NAME
		FROM		TB_ORD_TRANSMISSION O, BC_CONTENT C
		WHERE	O.CONTENT_ID = C.CONTENT_ID
	";

	$start = empty($post['start']) ? 0 : $post['start'];
	$limit = empty($post['limit']) ? 50 : $post['limit'];
	$start_date = empty($post['start_date']) ? 0 : $post['start_date'];
	$end_date = empty($post['end_date']) ? date("YmdHis") : $post['end_date'];

	$array_where = array();
	if($post['tr_date'] == 'all' || empty($post['tr_date'])){
	}else{
		array_push($array_where, "A.".strtoupper($post['tr_date'])." between '".$start_date."' and '".$end_date."' ");
	}

	foreach($post as $field=>$value){
		if( in_array( $field, array('action','start_date','end_date','tr_date', 'start', 'limit') )  || $value == 'all'  || empty($value) ){
			continue;
		}else if( $field == 'content_title' ){
			array_push($array_where, " A.".strtoupper($field)." like '%".$value."%' ");
		}else{
			array_push($array_where, " A.".strtoupper($field)." = '".$value."' ");
		}
	}

	if( count($array_where) > 0 ){
		$where = " WHERE ".join(' AND	', $array_where);
	}

	$query_search = "
		SELECT	*
		FROM		(".$query.") A
		".$where."
	";

	$order = " ORDER BY	 ORD_CREATE_TIME DESC ";

	$total = $db->queryOne("SELECT COUNT(*) FROM (".$query_search.")CNT");

	$db->setLimit($limit, $start);
	$result = $db->queryAll($query_search.$order);

	echo json_encode(array(
		'success' => true,
		'total' => $total,
		'data' => $result,
		'query' => $query_search.$order
	));
}

function updateKeepStatus($post){
	global $db;

	if($post['status'] == 'N'){
		$update_yn = 'Y';
	}else{
		$update_yn = 'N';
	}

	$ord_tr_ids =  json_decode($post['ord_tr_id']);

	$query = "
		UPDATE	TB_ORD_TRANSMISSION	SET
		KEEP_YN = '".$update_yn."',
		UPDATE_USER = '".$post['user_id']."',
		UPDATE_TIME = '".date("YmdHis")."'
		WHERE	ORD_TR_ID IN('".join('\',\'', json_decode($post['ord_tr_id'], true))."')
	";

	$db->exec($query);

	echo json_encode(array(
		'success' => true,
		'status' => $result,
		'query' => $query
	));
}

function deleteContentTr($post, $user_id){
	global $db;

	$query = "
		SELECT	T.TARGET, O.ORD_TR_ID, O.CONTENT_ID
		FROM		BC_TASK T, TB_ORD_TRANSMISSION O
		WHERE	T.TASK_ID = O.TASK_ID
			AND	O.ORD_TR_ID IN('".join('\',\'', json_decode($post['ord_tr_id'], true) )."')
			AND	O.KEEP_YN = 'N'
			AND	O.DELETE_TIME IS NULL
			AND	O.DELETE_USER IS NULL
	";
	$result = $db->queryAll($query);

	foreach( $result as $info ){
		$task = new TaskManager($db);
		$task_id = $task->insert_task_query_outside_data($info['content_id'], 'delete_content_zodiac', '1',  $user_id, '', $info['target']);

		$query_update = "
			UPDATE	TB_ORD_TRANSMISSION	SET
			DELETE_TASK_ID = '".$task_id."',
			DELETE_TIME = '".date("YmdHis")."',
			DELETE_USER = '".$user_id."'
			WHERE	ORD_TR_ID = '".$info['ord_tr_id']."'
		";
		$result_update = $db->exec($query_update);
	}

	echo json_encode(array(
		'success' => true,
		//'status_update' => $result_update,
		'query' => $query,
		'query_update' => $query_update
	));


	//$task = new TaskManager($db);
	//$task_id = $task->start_task_workflow($content_id, 'delete_content_zodiac', $user_id);
}

function createRequest($values, $user_id, $type_content){
	global $db;

	$title = $db->escape($values['title']);
	$detail = $db->escape($values['detail']);

	if(_text('MN02088') == $type_content){
		$ord_meta_cd = 'graphic';
	}else{
		$ord_meta_cd = 'video';
	}

	//SEQ_ORD_REQUEST
	$seq_ord_request = getSequence('SEQ_ORD_REQUEST');
	$ord_id = date('Ymd').'POR'.str_pad($seq_ord_request, 5, '0', STR_PAD_LEFT);

	if( empty($values['editor_id']) ){
		$status = 'ready';
		$editor = '';
		$dep_cd = '';
	}else{
		$status = 'working';
		$editor = $values['editor_id'];
		$dep_cd = $db->queryOne("
			SELECT	G.MEMBER_GROUP_ID
			FROM		BC_MEMBER_GROUP_MEMBER G, BC_MEMBER M
			WHERE	G.MEMBER_ID = M.MEMBER_ID
				AND	M.USER_ID = '".$values['editor_id']."' ");
    }
    
    
    $request = new \Api\Models\Request();
    $request->ord_id        = $ord_id;
    $request->ord_ctt       = $values['detail'];
    $request->input_dtm     = date('YmdHis');
    $request->inputr_id     = $user_id;
    $request->ord_status    = $status;
    $request->title         = $values['title'];
    $request->ord_work_id   = $editor;
    $request->dept_cd       = $dep_cd;
    $request->ord_meta_cd   = $ord_meta_cd;
    $request->save();

	// $query = "
	// 	INSERT	INTO	TB_ORD
	// 	(ORD_ID, ORD_CTT, INPUT_DTM, INPUTR_ID, ORD_STATUS, TITLE, ORD_WORK_ID, DEPT_CD, ORD_META_CD)
	// 	VALUES
	// 	('".$ord_id."','".$detail."','".date('YmdHis')."','".$user_id."','".$status."','".$title."','".$editor."','".$dep_cd."', '".$ord_meta_cd."')
	// ";

	// $db->exec($query);

	echo json_encode(array(
		'success'		=>	 true,
		'query'			=>	 $query
	));
}

function updateRequest($values, $editor, $user_id){
    global $db;
    
    
    $ordId = $values['ord_id'];

    $query = \Api\Models\Request::query();
    $request = $query->find($ordId);
    if(!$request){
        echo json_encode(array(
            'success'   =>	 false,
            'msg' => 'not found request'
        ));
        return;
    }

	// $title = $db->escape($values['title']);
	// $detail = $db->escape($values['detail']);

	if( empty($editor) ){
	}else{
		$dep_cd = $db->queryOne("
			SELECT	G.MEMBER_GROUP_ID
			FROM		BC_MEMBER_GROUP_MEMBER G, BC_MEMBER M
			WHERE	G.MEMBER_ID = M.MEMBER_ID
				AND	M.USER_ID = '".$editor."' ");
		// $edit_editor = "
		// 	ORD_STATUS = 'working',
		// 	ORD_WORK_ID = '".$editor."',
		// 	DEPT_CD = '".$dep_cd."',
		// ";

		// if( strstr($values['ord_id'], 'POR') ){
		// }else{
		// 	//편집자 할당시 EDL 파일 스토리지에 생성
		// 	//createEDL($post['ord_id']);
        // }
        
        $request->ord_status = 'working';
        $request->ord_work_id = $editor;
        $request->dep_cd = $dep_cd;
	}

	// $query = "
	// 	UPDATE	TB_ORD	SET
	// 	TITLE = '".$db->escape($values['title'])."',
	// 	ORD_CTT = '".$db->escape($values['detail'])."',
	// 	".$edit_editor."
	// 	UPDTR_ID = '".$user_id."',
	// 	UPDT_DTM = '".date("YmdHis")."'
	// 	WHERE	ORD_ID = '".$values['ord_id']."'
    // ";   
	// $db->exec($query);

    $request->title = $values['title'];
    $request->ord_ctt = $values['detail'];
    $request->updtr_id = $user_id;
    $request->updt_dtm = date("YmdHis");
    $request->save();
   
	echo json_encode(array(
		'success'		=>	 true
	));
}

function getTotalNewRequestCount($post, $user_id){
	global $db;

	$query_total_new_request_count = "
			SELECT COUNT(*) AS CNT
			FROM (
			      SELECT		CASE
			                      WHEN A.ORD_WORK_ID = '$user_id' THEN
			                        CASE WHEN 1 -
			                          (
			                          SELECT	COUNT(LOG_ID) AS LOG_ID_C
			                          FROM	BC_LOG
			                          WHERE		ACTION		= 'read_request'
			                          AND		ZODIAC_ID	= A.ORD_ID
			                          AND		USER_ID		= A.ORD_WORK_ID
			                        ) = 1 THEN 1 ELSE 0 END
			                        ELSE 0
			                    END AS READ_FLAG
			      FROM TB_ORD A
			) J
			WHERE J.READ_FLAG = 1
			";

	$query_total_new_video_count = "
			SELECT COUNT(*) AS CNT
			FROM (
			      SELECT		CASE
			                      WHEN A.ORD_WORK_ID = '$user_id' THEN
			                        CASE WHEN 1 -
			                          (
			                          SELECT	COUNT(LOG_ID) AS LOG_ID_C
			                          FROM	BC_LOG
			                          WHERE		ACTION		= 'read_request'
			                          AND		ZODIAC_ID	= A.ORD_ID
			                          AND		USER_ID		= A.ORD_WORK_ID
			                        ) = 1 THEN 1 ELSE 0 END
			                        ELSE 0
			                    END AS READ_FLAG
			      FROM TB_ORD A
			      WHERE ORD_META_CD = 'video'
			) J
			WHERE J.READ_FLAG = 1
			";

	$query_total_new_graphic_count = "
			SELECT COUNT(*) AS CNT
			FROM (
			      SELECT		CASE
			                      WHEN A.ORD_WORK_ID = '$user_id' THEN
			                        CASE WHEN 1 -
			                          (
			                          SELECT	COUNT(LOG_ID) AS LOG_ID_C
			                          FROM	BC_LOG
			                          WHERE		ACTION		= 'read_request'
			                          AND		ZODIAC_ID	= A.ORD_ID
			                          AND		USER_ID		= A.ORD_WORK_ID
			                        ) = 1 THEN 1 ELSE 0 END
			                        ELSE 0
			                    END AS READ_FLAG
			      FROM TB_ORD A
			      WHERE ORD_META_CD = 'graphic'
			) J
			WHERE J.READ_FLAG = 1
			";

	echo json_encode(array(
			'success' => true,
			'total_new_request_count' => $db->queryOne($query_total_new_request_count),
			'total_new_video_count' => $db->queryOne($query_total_new_video_count),
			'total_new_graphic_count' => $db->queryOne($query_total_new_graphic_count)
	));
}

?>