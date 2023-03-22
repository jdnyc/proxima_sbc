<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$s_date = $_POST['start_date'];
$e_date = $_POST['end_date'];

$task_kind = array(
	'success' => true,
	'task_kind' => array()
);
$job = array(
 '10' => '카탈로깅',
 '20' => '트랜스코딩',
 '30' => 'PFR',
 '40' => '아비드 트랜스코더',
 '60' => '트랜스퍼FS',
 '70' => '오디오트랜스코딩',
 '80' => '트랜스퍼FTP',
);

$tasks = $mdb->queryAll("select type from task group by type");

if(empty($s_date)){

	foreach($tasks as $task){
		$success = $mdb->queryOne("select count(id) from task where type = {$task['type']} and status = 'complete'");
		$fail = $mdb->queryOne("select count(id) from task where type = {$task['type']} and status = 'error'");
		$total = $success + $fail;
		if($job[$task['type']]){
			array_push($task_kind['task_kind'], array('task'=>$job[$task['type']], 'success'=>$success, 'fail'=>$fail, 'total'=>$total));
		}
	}
}else{
	foreach($tasks as $task){
		$success = $mdb->queryOne("select count(id) from task where type = {$task['type']} and status = 'complete' and complete_datetime between $s_date and $e_date");
		$fail = $mdb->queryOne("select count(id) from task where type = {$task['type']} and status = 'error' and start_datetime between $s_date and $e_date");
		$total = $success + $fail;
		if($job[$task['type']]){
			array_push($task_kind['task_kind'], array('task'=>$job[$task['type']], 'success'=>$success, 'fail'=>$fail, 'total'=>$total));
		}
	}
}
//print_r($task_kind);
echo json_encode(
	$task_kind
);


//작업 성공 실패 합계
?>

