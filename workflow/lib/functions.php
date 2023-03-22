<?php

/**
 * 모듈이 할수 있는 롤에 대한 작업만 가져가도록 변경
 * @param $task_type
 * @param $module_info_id
 * @return bool
 */
function check_parent_job($task_type, $module_info_id) {
	global $db;

	$available_task_rules = $db->queryAll("select task_rule_id from bc_task_available where module_info_id = {$module_info_id}");
	if (empty($available_task_rules)){
		return false;
	}

	$arr_task_rule = array();
	foreach ($available_task_rules as $available_task_rule) {
		array_push($arr_task_rule, $available_task_rule['task_rule_id']);
	}
	$task_rules = implode(',', $arr_task_rule);

	if (empty($task_rules)) {
		return false;
	}

	if (is_array($task_type)) {
		$task_type = implode("', '", $task_type);
	}

	//0.1초부터 0.5초까지 랜덤 딜레이.
  usleep(rand(100000,500000));
  if(DB_TYPE == 'postgresql'){
		$assign_task = $db->queryRow("select * from BC_TASK where type in ('".$task_type."') and status='queue' and task_rule_id in ({$task_rules}) order by priority asc, creation_datetime, job_priority LIMIT 1");
	}else{		
		$assign_task = $db->queryRow("select * from BC_TASK where status='queue' and type in ('".$task_type."') and task_rule_id in ({$task_rules}) order by priority asc, creation_datetime, job_priority");
	}
	if (empty($assign_task['task_id'])) {
		return false;
	}

	return $assign_task;
}


function doRename( $rename, $task_id )
{
	global $db;

	$allow_type = array(
		'60',
		'20',
		'80',
		'31'
	);

	if( empty($rename) ) return true;

	//구분자 변경
	$rename_path = str_replace('\\', '/', $rename);

	$task_info = $db->queryRow("select t.media_id,wr.target_path_id, t.type,t.src_media_id, tr.* from bc_task t, bc_task_rule tr,bc_task_workflow_rule wr  where t.task_rule_id=tr.task_rule_id and t.workflow_rule_id=wr.workflow_rule_id and t.task_id = $task_id ");

	if( !empty($task_info['media_id']) && !empty($task_info['src_media_id']) && ($task_info['media_id'] != $task_info['src_media_id'] ) ){
		return true;
	}

	$task_info_extra = $db->queryRow("select * from BC_TASK_STORAGE  where task_id = $task_id ");
	$media_id = $task_info['media_id'];//작업 대상 미디어아이디
	$task_type = $task_info['type']; //작업 타입


	if( !in_array( $task_type, $allow_type) )  return true;

	if( !empty($task_info_extra['trg_storage_id']) ){
		$target_storage_id = $task_info_extra['trg_storage_id'];
	}
	else if( !empty($task_info['target_path_id']) ){
		$target_storage_id = $task_info['target_path_id'];
	}else{
		$target_storage_id = $task_info['target_path'];
	}
	//스토리지 루트 경로
	$target_storage_path = $db->queryOne("select path from bc_storage where storage_id=$target_storage_id");
	$target_storage_path = str_replace('\\', '/', $target_storage_path);

	if(empty($target_storage_path) || empty($media_id ) ) return true;

	//루트 패스와 구분자 트림
	$rename_trim_path = trim( str_replace($target_storage_path, '',  $rename_path) ,'/' );

	//패스 배열 생성
	$rename_path_array = explode('/' ,$rename_trim_path );

	//패스 배얼에서 파일명 추출
	$filename = array_pop($rename_path_array);

	$path = join('/', $rename_path_array);

	$filename_array = explode('.', $filename);

	//마지막 패스가 파일명이 아닐때 리턴
	if( count($filename_array) < 2 ) return true;

	$update_path = $db->escape($path.'/'.$filename);

	//미디어 테이블 업데이트.
	$q = "update bc_media set path='$update_path' where media_id=$media_id";
	$r = $db->exec($q);

	//다음작업이 있을수 있으므로 현재 task의 타겟 경로 업데이트.
	$q = "update bc_task set target='$update_path' where task_id=$task_id";
	$r = $db->exec($q);

	return true;

}


function taskAutoRetry($type)
{
	//진행상태 작업 자동 재시작 함수 2012-12-26 이성용
	global $db;

	$creation_date = date("YmdHis");

	$check_hour = 1;

	$lists = $db->queryAll("
		SELECT t.* 
		FROM BC_TASK T,
		( SELECT DISTINCT T.TASK_ID
			FROM BC_TASK T
					LEFT JOIN	BC_TASK_LOG TL
					ON			TL.TASK_ID = T.TASK_ID
			WHERE	t.status='processing' AND t.type='10'
			AND ( ( tl.task_log_id IS NULL  AND TO_DATE( t.creation_datetime, 'yyyymmddhh24miss' ) < CURRENT_DATE - ( 1 / 24) )
				OR ( tl.task_log_id IS NOT NULL  AND TO_DATE(tl.creation_date, 'yyyymmddhh24miss' ) < CURRENT_DATE - ( 1 / 24) )	)
		  AND t.task_id NOT IN ( SELECT DISTINCT t.task_id	FROM bc_task t, bc_task_log tl WHERE t.task_id=tl.task_id AND  tl.description LIKE '%retry%'  AND t.status='processing'  )
		    ) tl
		WHERE t.task_id=tl.task_id ORDER BY t.priority ASC, t.creation_datetime, t.job_priority
	");

	if(empty($lists)) return true;

	$allow_type = array(
		10,11,20,22,70 ,100 ,35 ,36 ,120, 30,80,60
	);


	foreach($lists as $list)
	{
		$task_type = $list['type'];

		//허용할 작업유형 체크
		if( !in_array( $task_type, $allow_type) )  continue;

		if( $task_type == 60 ){
			$parameter = trim($list['parameter']);
			$parameter_array =  explode(' ', $parameter);

			//60작업일시 파라미터 확인
			if( !empty($parameter_array) ){

				$return = false;
				foreach($parameter_array as $val)
				{
					$val = strtoupper( trim($val , '"') );
					//카피&삭제, 무브 작업은 제외
					if( $val == 'COPY_DELETE' || $val == 'MOVE' ){
						$return = true;
					}
				}

				if($return) continue;
			}
		}

		$description = 'Auto retry';
		$r = $db->exec("insert into bc_task_log (task_id, description, creation_date ) values ({$list['task_id']}, '$description', '$creation_date')");
		$r = $db->exec("update bc_task set status='queue' where task_id={$list['task_id']} and status='processing' ");
	}
}

function checkProcessingCount($task_code) {
	global $db;

	$query = "select count(*) from bc_task where status in ('processing', 'assigning') and type='".$task_code."'";

	return $db->queryOne($query);
}

function getTaskQueueCount($task_code) {
	global $db;

	$query = "select count(*) from bc_task where status in ('queue') and type='".$task_code."'";

	return $db->queryOne($query);
}

?>