<?php
require_once '../lib/config.php';
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
session_start();

$user_id = $_SESSION['user']['user_id'];
$position = $_POST['position'];
$filter = $_POST['filter'];
$startDate = $_POST['start_date'];
$endDate = $_POST['end_date'];
$searchValue = $_POST['searchValue'];
$start = empty($_POST['start']) ? 0 : $_POST['start'];
$limit = empty($_POST['limit']) ? 15 : $_POST['limit'];

switch ($position) {
	case 'all':
		$in_workflow = array("'transfer_to_maincontrol'","'transfer_to_maincontrol_xdcam'","'transfer_to_maincontrol_archive'", "'transmission_zodiac'","'transmission_zodiac_xdcam'",
		"'transmission_zodiac_archive'","'transmission_zodiac_ab'","'transmission_zodiac_news'","'transmission_zodiac_news_archive'","'transmission_zodiac_ab_archive'","'transmission_zodiac_news_xdcam'","'transmission_zodiac_ab_xdcam'");
    break;
	case 'sub':
		$in_workflow = array("'transmission_zodiac'","'transmission_zodiac_xdcam'","'transmission_zodiac_archive'","'transmission_zodiac_ab'",
		"'transmission_zodiac_news'","'transmission_zodiac_news_archive'","'transmission_zodiac_ab_archive'","'transmission_zodiac_news_xdcam'","'transmission_zodiac_ab_xdcam'");
    break;

	case 'main':
	default:
		$in_workflow = array("'transfer_to_maincontrol'","'transfer_to_maincontrol_xdcam'","'transfer_to_maincontrol_archive'");
    break;
}

$query = "
	SELECT /*+ INDEX(A BC_TASK_CREATION_DATETIME_IDX) */
			A.TASK_ID,
			A.SOURCE,
			A.TARGET,
			CASE A.STATUS
				WHEN 'complete' THEN '완료'
				WHEN 'queue' THEN '대기'
				WHEN 'processing' THEN '진행중'
                WHEN 'error' THEN '오류'
                WHEN 'cancel' THEN '취소'
                WHEN 'canceled' THEN '취소'
                WHEN 'assigning' THEN '진행중'
                WHEN 'pending' THEN '처리대기중'
				ELSE A.STATUS
			END STATUS,
			A.PROGRESS,
            A.TASK_USER_ID,
            A.CREATION_DATETIME,
			A.START_DATETIME,
			A.COMPLETE_DATETIME,
            B.USER_ID QC_USER_ID,	
            a.src_media_id,	
			c.TITLE,		
            s.SYS_VIDEO_RT,
            r.JOB_NAME as rule_name,
			A.ASSIGN_IP,
			mi.NAME
      FROM BC_TASK A 
      join BC_TASK_RULE r on a.task_rule_id=r.task_rule_id
      left outer join bc_content c on a.src_content_id=c.content_id 
      left outer join  BC_MEDIA_QUALITY_INFO B on a.src_content_id=b.content_id
      left outer join BC_SYSMETA_MOVIE s on a.src_content_id=s.sys_content_id
	  left outer join BC_MODULE_INFO mi on mi.MAIN_IP=A.ASSIGN_IP
	 WHERE a.destination IN (" . join(", ", $in_workflow) . ")
     ";

    if(!is_null($startDate) || !is_null($endDate)){
        $startDate = date("YmdHis", strtotime($startDate));
        $endDate = date("YmdHis", strtotime($endDate));
        
        $query .= "AND creation_datetime BETWEEN $startDate AND $endDate";
    };
if ($filter == 2) {
    $query .= " AND TASK_USER_ID = '$user_id'";
}



$total = $db->queryOne("select count(*) from ($query) cnt");
$query .= " ORDER BY TASK_ID DESC";



$db->setLimit($limit, $start);

$tasks = $db->queryAll($query);

foreach($tasks as $key => $task){
    if (!empty($tasks[$key]['qc_user_id'])) {
        $tasks[$key]['qc_user_name'] = $db->queryOne("select user_nm from bc_member where user_id='".$tasks[$key]['qc_user_id']."'");
    }
    $tasks[$key]['task_user_name'] =  $db->queryOne("select user_nm from bc_member where user_id='".$tasks[$key]['task_user_id']."'");
    $tasks[$key]['filesize'] =  $db->queryOne("SELECT max(FILESIZE) FROM BC_MEDIA WHERE media_id='".$tasks[$key]['src_media_id']."'");
    $tasks[$key]['to_second'] = $db->queryOne("select ROUND(((TO_DATE(A.COMPLETE_DATETIME,'YYYYMMDDHH24MISS')-TO_DATE(A.START_DATETIME,'YYYYMMDDHH24MISS'))*24*60*60),3) AS TO_SECOND from bc_task a where task_id='".$tasks[$key]['task_id']."'");

	$toSecond = (int)$tasks[$key]['to_second'] ;
	$filesize = (int)$tasks[$key]['filesize'] ;
	if(($toSecond == "") || ($filesize == "")){
		// $tasks[$key]['transfer_speed'] = "0.00 MB/s";
		$tasks[$key]['transfer_speed'] = "";
	}else{
		$transferSpeed=$filesize/$toSecond;
		$transferSpeed = formatBytes($transferSpeed);
	   	$tasks[$key]['transfer_speed'] = $transferSpeed."/s";
    }
    
}

echo json_encode(array(
	'success' => true,
	'total' => $total,
	'data' => $tasks
));
