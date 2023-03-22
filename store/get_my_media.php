<?php
/**
 * 2017-12-20 이승수
 * CJO에서 내 작업목록을 조회. PFR걸었던 항목을 다운로드 할 목적으로.
 */
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$limit = !empty($_POST['limit']) ? $_POST['limit'] : 20;
$start = !empty($_POST['start']) ? $_POST['start'] : 0;
$user_id = $_SESSION['user']['user_id'];
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];

$query = "
    SELECT  T.*
            ,C.TITLE
    FROM    (
            SELECT  *
            FROM    BC_TASK
            WHERE   CREATION_DATETIME BETWEEN '".$start_date."' AND '".$end_date."'
            AND     TASK_USER_ID='".$user_id."'
            AND     TYPE='30'
            ) T
            LEFT OUTER JOIN BC_CONTENT C ON T.SRC_CONTENT_ID=C.CONTENT_ID
    ";
$total = $db->queryOne("SELECT COUNT(*) FROM (".$query.") CNT");
$order = " ORDER BY T.TASK_ID DESC";
$db->setLimit($limit,$start);
$data = $db->queryAll($query.$order);

foreach($data as $idx => $sub_data) {
    if(!empty($sub_data['creation_datetime'])) {
        $data[$idx]['creation_datetime'] = date('Y-m-d H:i:s', strtotime($sub_data['creation_datetime']));
    }
    if(!empty($sub_data['complete_datetime'])) {
        $data[$idx]['complete_datetime'] = date('Y-m-d H:i:s', strtotime($sub_data['complete_datetime']));
    }
    // if(!empty($sub_data['target'])) {
    //     $target_path_info = pathinfo($sub_data['target']);
    //     $data[$idx]['file_ext'] = $target_path_info['extension'];
    // }
    if(!empty($sub_data['destination'])) {
        $task_file_type = '';
        switch($sub_data['destination']) {
            case 'pfr_high': $task_file_type='고해상도'; break;
            case 'pfr_low': $task_file_type='저해상도'; break;
            case 'pfr_archive': $task_file_type='저장영상'; break;
        }
        $data[$idx]['task_file_type'] = $task_file_type;
    }

    
    if(!empty($sub_data['parameter'])) {
        $data[$idx]['in_out'] = renderParameterPFR($sub_data['parameter']);
    }
}

echo json_encode(array(
	'success' => true,
	'total' => $total,
	'data' => $data,
	'query' => $query
));


function renderParameter($p, $task_type)
{
	$rendered = '';
	switch($task_type)
	{
		case '30':
			$rendered = renderParameterPFR($p);
		break;

		default:
			$rendered = $p;
		break;
	}

	return $rendered;
}

function renderParameterPFR($p)
{
	list($setIn, $setOut) = explode(' ', $p);

    $setIn = trim($setIn, '"');
    $setIn = frameToTimecode($setIn);
    $setOut = trim($setOut, '"');
	$setOut = frameToTimecode($setOut);

	return "<b>SetIn</b>: ".$setIn.", <b>SetOut</b>: ".$setOut;
}

?>