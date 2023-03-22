<?php 
require_once("ui_functions.php");
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");
$system_id = $_POST['id'];
$system_id = $_GET['id'];

//현재 실행중인 작업목록 테이블
$query1 = 'select get_date
		  from bc_system_process_used
		  where system_info_id='.$system_id.'
				order by get_date desc';
	
$stmt1 = $db->queryOne($query1);

$delayed = strtotime(date('YmdHis')) - strtotime($stmt1);
		
if($delayed < TIMECHECK && $delayed >= 0)
{
	echo '연결 양호';
}
else if($delayed > (TIMECHECK * 3))
{	//3번 체크해도 연결에 문제가 있을 경우
	echo '연결 불량';
}
else if($delayed < 0)
{
	echo '시간설정 오류: 서버 시간 설정을 다시 해 주세요.';
}
else
{
	echo '연결중..';
}	    

?>