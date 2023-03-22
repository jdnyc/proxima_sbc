<?PHP
/**
 * id: 'task_store',
 * url: 'php/get_task.php',
 * totalProperty: 'total',
 * idProperty: 'task_id',
 * root: 'data',
 * fields: [
 *     {name: 'id'},
 *     {name: 'type'},
 *     {name: 'asset_title'},
 *     {name: 'progress'},
 *     {name: 'status'},
 *     {name: 'creation_datetime'},
 *     {name: 'start_datetime'},
 *     {name: 'complete_datetime}
 * ]
 * 작업채널 및 검색어 관련 기능 추가 - 2018.01.15 Alex
 */
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
fn_checkAuthPermission($_SESSION);
try
{
	$arrStatus = array(
		'처리중' => 'processing',
		'대기중' => 'queue',
		'지난작업 - 전체' => 'all',
		'지난작업 - 성공' => 'complete',
		'지난작업 - 실패' => 'error'
	);
	
	$mappint_status = array(
		'processing'	=>	'처리중',
		'assigning'		=>	'처리중',
		'queue'			=>	'대기중',
		'complete'		=>	'성공',
		'error'			=>	'실패',
		'canceled'		=>	'취소',
		'cancel'		=>	'취소',
		'retry'			=>	'재시도',
		'delete'		=>	'삭제'
	);
	
	$request_start = $_REQUEST['start'];
	$request_limit = $_REQUEST['limit'];
	$start = !empty($request_start) ? $request_start : 1;
	$limit = !empty($request_limit) ? $request_limit : 20;
	$limit = $limit + $start;
	$search = $_REQUEST;
	$task_status = $search['task_status'];
	$task_type = $search['taskType'];
	$start_date = $search['start_date'];
	$end_date = $search['end_date'];
    $title = strtolower($search['title']);
    $searchType = $search['search_type'];
	$workflow_channel = $_POST['workflow_channel'];

	if ($task_status == "''") {
		echo '{"success":"true", "total": 0, "data": []}';
		exit;
	}

    $task_query = "";
    if ($task_type == 'restore') {
		//리스토어 일때 pfr리스토어 포함 2011-04-12 by 이성용
		$task_query .= " 
            AND (A.TYPE = 'restore' OR A.TYPE = 'pfr_restore') 
        ";
	} else if ($task_type != 'all') {
		if($task_type == '80') {
			$task_query .= " 
                AND A.TYPE IN ('80', '81', '82') 
            ";
		} else {
            $task_query .= "
                 AND A.TYPE = '".$task_type."' 
            ";
		}
    }
    // 작업 상태
	if ( ! strstr($task_status, "''")) {
        //queue로 조회할때 pending 포함
        if( strstr($task_status, 'queue') ) {
            $task_status .= ",'pending'";
        }
		$task_query .= " 
            AND		A.STATUS IN (".$task_status.") 
        ";
    }
    // 작업흐름 구분
	if($workflow_channel != 'all' && !empty($workflow_channel)) {
        $task_query .= "
             AND		A.DESTINATION = '".$workflow_channel."' 
            ";
	}
    $task_query .= " 
        AND A.CREATION_DATETIME BETWEEN '".$start_date."' AND '".$end_date."' 
    ";


    // 검색어
	if( $title != '') {
        switch($searchType){
            case 'title':
                $other_query .= " 
                    AND		(LOWER(C.TITLE) LIKE '%".$title."%')
                ";
            break;
            case 'media_id':
                $other_query .= " 
                    AND		(UPPER(UM.MEDIA_ID) = UPPER('".$title."'))
                ";
            break;
            case 'content_id':
                $other_query .= " 
                    AND		(C.CONTENT_ID = '".$title."')
                ";
            break;
        }
	}
    $query = "
    SELECT  Z.*
    FROM    (
        SELECT  Z.*,
                (SELECT	NAME FROM	BC_TASK_TYPE WHERE	TYPE = Z.TYPE) AS T_NAME,
                (SELECT	USER_TASK_NAME FROM	BC_TASK_WORKFLOW WHERE	TASK_WORKFLOW_ID = Z.TASK_WORKFLOW_ID) AS USER_TASK_NAME,
                FUNC_GET_USER_NAME(Z.TASK_USER_ID) TASK_USER_NAME,
                (SELECT UD_CONTENT_TITLE FROM BC_UD_CONTENT WHERE UD_CONTENT_ID = Z.UD_CONTENT_ID) AS UD_CONTENT_TITLE
        FROM    (
                SELECT  A.*,
                        COUNT(*) OVER() AS TOTAL_ROWS,
                        ROW_NUMBER() OVER(ORDER BY A.TASK_ID DESC) AS RN,
                        B.FILESIZE,
                        C.CONTENT_ID,
                        C.TITLE,
                        C.REG_USER_ID,
                        C.UD_CONTENT_ID,
                        BT.JOB_NAME,
                        S.SYS_VIDEO_RT,
                        COALESCE(MI.NAME, A.ASSIGN_IP) AS ASSIGN_IP_NM,
                        UM.MEDIA_ID AS META_MEDIA_ID
                FROM    (
                        SELECT  
                                A.*
                        FROM    BC_TASK A 
                        WHERE   1 = 1
                            ".$task_query."
                        ) A
                        LEFT OUTER JOIN BC_MEDIA B ON A.MEDIA_ID = B.MEDIA_ID
                        LEFT OUTER JOIN BC_CONTENT C ON A.SRC_CONTENT_ID = C.CONTENT_ID
                        LEFT OUTER JOIN BC_TASK_RULE BT ON A.TASK_RULE_ID = BT.TASK_RULE_ID
                        LEFT OUTER JOIN BC_MODULE_INFO MI ON A.ASSIGN_IP = MI.MAIN_IP
                        LEFT OUTER JOIN BC_SYSMETA_MOVIE S ON A.SRC_CONTENT_ID = S.SYS_CONTENT_ID
                        LEFT OUTER JOIN BC_USRMETA_CONTENT UM ON A.SRC_CONTENT_ID = UM.USR_CONTENT_ID
                WHERE   1=1
                ".$other_query."
            ) Z
        WHERE 1 = 1
            and Z.RN >= ".$start." AND Z.RN < ".$limit."
            
        ) Z
    ";

	$tasks = $db->queryAll($query);

	$total = 0;

	if (count($tasks) > 0) {
		$total = $tasks[0]['total_rows'];
    }
    
    //pending 상태 추가
    foreach($tasks as $i => $task) {
        if($task['status'] == 'pending') {
            $tasks[$i]['status'] = 'queue';
        }
        if(!empty($task['filesize'])) {
            $tasks[$i]['filesize'] = formatByte($task['filesize']);
        }
        if($tasks[$i]['filesize'] == '0') $tasks[$i]['filesize'] = '';
    }
	
	echo json_encode(array(
		'success' => true,
		'total' => $total,
		'data' => $tasks,
		'query' => $query
	));

} catch (Exception $e) {
	switch($e->getCode()) {
		case ERROR_QUERY:
			$msg = $e->getMessage().'( '.$db->last_query. ' )';
		break;

		default:
			$msg = $e->getMessage();
		break;
	}

	die(json_encode(array(
		'success' => false,
		'msg' => $msg
	)));
}

function convertTimeExcel($date_time){
	$converted = substr($date_time,0,4).'-'.substr($date_time,4,2).'-'.substr($date_time,6,2).'<font style="color:white">.</font> '.substr($date_time,8,2).':'.substr($date_time,10,2).':'.substr($date_time,12,2);
	return $converted;
}

?>
