<?PHP
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
fn_checkAuthPermission($_SESSION);

try {

	$user_lang = $_SESSION['user']['lang'];
	if( $user_lang == 'en' ){
		$code_name = 'ENAME';
	}else if( $user_lang == 'other' ){
		$code_name = 'OTHER';
	}else{
		$code_name = 'NAME';
	}
	switch( $_POST['action'] ){
		case 'list_task':
			getListTask($code_name);
		break;
		case 'list_task_type':
			getListType();
		break;
		case 'list_task_status':
			getListStatus($code_name);
		break;
	}
} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'msg' => $e->getMessage()
    ));
}

function getListTask($code_name){
	global $db;

	$limit = ! empty($_POST['limit']) ? $_POST['limit'] : 19;
    $start = ! empty($_POST['start']) ? $_POST['start'] : 0;
    $search = json_decode($_POST['search'], true);
    $user_id = $_SESSION['user']['user_id'];

	$_where = array();
	array_push($_where, " C.CONTENT_ID = PR.SRC_CONTENT_ID ");
	array_push($_where, " C.CONTENT_ID = CO.SRC_CONTENT_ID ");
	array_push($_where, " TASK_INFO.TASK_STATUS = CODE_STATUS.CODE ");

	/*
		{"combo_monitoring_type":"All","start_date_monitoring":"2016-05-01T00:00:00","end_date_monitoring":"2016-05-31T00:00:00","combo_monitoring_task":"error","combo_monitoring_progress":"전체","value_search":""}
	*/

	foreach( $search as $field=>$value ){
		if( !empty($value) && !in_array($value, array('All', '전체')) ){
			switch($field){
				case 'combo_monitoring_type':
					array_push($_where, " TASK_INFO.TASK_USER_ID = '".$user_id."' ");
				break;
				case 'combo_monitoring_task':
					array_push($_where, " TASK_INFO.TASK_STATUS = '".$value."' ");
				break;
				case 'combo_monitoring_progress':
					array_push($_where, " TASK_INFO.TASK_WORKFLOW_ID = '".$value."' ");
				break;
				case 'value_search':
					array_push($_where, " C.TITLE LIKE '%".$value."%' ");
				break;
			}
		}
	}

	$start_date = empty($search['start_date_monitoring']) ? '0' : date("YmdHis", strtotime($search['start_date_monitoring']));
	$end_date = empty($search['end_date_monitoring']) ? date("Ymd").'240000' : date("YmdHis", strtotime($search['end_date_monitoring']));

	array_push($_where, " TASK_INFO.CREATION_DATETIME BETWEEN '".$start_date."' AND '".$end_date."' ");

	$query = "
		SELECT  C.CONTENT_ID, C.TITLE AS CONTENT_TITLE, C.REG_USER_ID,
				CODE_STATUS.".$code_name." AS TASK_STATUS_NAME,
				BM.USER_NM,
				PR.TOTAL_PROGRESS, PR.COUNT_TASK, CO.COUNT_COMPLETE,
				TASK_INFO.*
		FROM    BC_CONTENT C
				  LEFT JOIN
					(
						SELECT  TW.USER_TASK_NAME AS WORKFLOW_NAME, TR.JOB_NAME AS TASK_JOB_NAME,
								BT.SRC_CONTENT_ID, BT.TASK_ID, BT.TYPE, BT.PROGRESS, BT.STATUS AS TASK_STATUS, BT.START_DATETIME, BT.COMPLETE_DATETIME, BT.ROOT_TASK,
								BT.CREATION_DATETIME, BT.TASK_USER_ID, BT.TASK_WORKFLOW_ID, BT.WORKFLOW_RULE_ID
						FROM    BC_TASK BT,
								BC_TASK_WORKFLOW TW,
								BC_TASK_RULE TR,
								(
								  SELECT  SRC_CONTENT_ID, MAX(TASK_ID) AS LAST_TASK_ID
								  FROM    BC_TASK BT
								  GROUP BY SRC_CONTENT_ID
								) LAST_TASK
						WHERE   BT.TASK_ID = LAST_TASK.LAST_TASK_ID
						AND     BT.TASK_WORKFLOW_ID = TW.TASK_WORKFLOW_ID
						AND     BT.TASK_RULE_ID = TR.TASK_RULE_ID
					)TASK_INFO
				  ON C.CONTENT_ID = TASK_INFO.SRC_CONTENT_ID
				  LEFT JOIN BC_MEMBER BM
				  ON BM.USER_ID = TASK_INFO.TASK_USER_ID,
				(
					SELECT SUM(PROGRESS) AS TOTAL_PROGRESS, COUNT(TASK_ID) AS COUNT_TASK, SRC_CONTENT_ID
					FROM   BC_TASK 
					GROUP BY SRC_CONTENT_ID
				)PR,
				(
				   SELECT SRC_CONTENT_ID, COUNT(TASK_ID) AS COUNT_COMPLETE
				   FROM   BC_TASK
				   WHERE  STATUS = 'complete'
				   GROUP BY SRC_CONTENT_ID
				)CO,
				(
				  SELECT  CO.*
				  FROM    BC_CODE CO, BC_CODE_TYPE COT
				  WHERE   CO.CODE_TYPE_ID = COT.ID
				  AND     COT.CODE = 'TASK_STATUS'
				)CODE_STATUS
		WHERE   ".join(' AND ', $_where)."
		ORDER BY C.CONTENT_ID DESC 
	";

	$total = $db->queryOne("SELECT COUNT(*) FROM (".$query.")CNT ");
	$db->setLimit($limit, $start);
	$data = $db->queryAll($query);

	echo json_encode(array(
        'success' => true,
        'status' =>  0,
        'message' => "OK",
        'total' => $total,
		'query'	=>	$query,
        'data' => $data
    ));
}

function getListType(){
	global $db;

	$datas = array();
	$data['type'] = 'All';
	$data['name'] = _text('MN00008');
	array_push($datas, $data);

	$query = "
		SELECT	TASK_WORKFLOW_ID AS TYPE, USER_TASK_NAME AS NAME 
		FROM	BC_TASK_WORKFLOW 
		WHERE	TYPE IN('i', 's', 'c')
		ORDER BY USER_TASK_NAME
	";

	$query = "
		SELECT	BW.TASK_WORKFLOW_ID AS TYPE, BW.USER_TASK_NAME AS NAME 
		FROM	BC_TASK_WORKFLOW BW,
				( SELECT  DISTINCT(TASK_WORKFLOW_ID) AS TASK_WORKFLOW_ID
				FROM BC_TASK) DT
					WHERE	BW.TYPE IN('i', 's', 'c')
				AND BW.TASK_WORKFLOW_ID = DT.TASK_WORKFLOW_ID
				ORDER BY BW.USER_TASK_NAME
	";

	$list = $db->queryAll($query);
	echo json_encode(array(
        'success' => true,
        'status' =>  0,
        'message' => "OK",
        'total' => count($list),
		'query'	=>	$query,
        'data' => $datas + $list
    ));
}

function getListStatus($code_name){
	global $db;

	$datas = array();
	$data['type'] = 'All';
	$data['name'] = _text('MN00008');
	array_push($datas, $data);

	$query = "
		SELECT  BC.".$code_name."  AS NAME, BC.CODE AS STATUS
		FROM    BC_CODE BC, BC_CODE_TYPE BT
		WHERE   BC.CODE_TYPE_ID = BT.ID
		AND     BT.CODE = 'TASK_STATUS'
		ORDER   BY BC.ID	
	";

    $list = $db->queryAll($query);
    foreach($list as $i => $v) {
        if(strstr($v['name'], 'MN')) {
            $list[$i]['name'] = _text($v['name']);
        }
    }
	echo json_encode(array(
        'success' => true,
        'status' =>  0,
        'message' => "OK",
        'total' => count($list),
		'query'	=>	$query,
        'data' => $datas + $list
    ));
}
?>
