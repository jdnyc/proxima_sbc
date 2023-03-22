<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');


if($_POST['search_field'] == 'content_id')
{
	$content_id = $_POST['search_value'];
}
else if($_POST['search_field'] == 'tape_no')
{
	$tape_no = $_POST['search_value'];
	$content_id = $db->queryOne("select content_id from bc_meta_value where value = '$tape_no' order by content_id desc");
}
else
{
	$content_id = $_GET['content_id'];
}
//$task_info = $db->queryAll("select media_id from media where content_id = $content_id");

$data = array(
			'success' => true,
			'data' => array()
		);

$get_task_info = $db->queryAll("
    select  (select user_task_name from bc_task_workflow where task_workflow_id=t.task_workflow_id) user_task_name,
            (select name from bc_task_type where type=t.type ) type_name,
            t.*
    from    bc_task t
    where   t.SRC_CONTENT_ID = $content_id
	order by t.task_id asc");

if(empty($get_task_info)){
}
else
{
	$data = array(
		'success' => true,
		'data' => array()
	);

	foreach($get_task_info as $info)
	{

		array_push(
			$data['data'],
			array(
				'id'                        => $info['task_id']
                ,'type'                     => $info['type_name']
                ,'user_task_name'           => $info['user_task_name']
				,'status'                   => $info['status']
				,'creation_datetime'        => $info['creation_datetime']
                ,'progress'                 => $info['progress']
				,'start_datetime'           => $info['start_datetime']
				,'complete_datetime'        => $info['complete_datetime']
			)
		);
		$regist = $info['destination'];
		$job_priority = $info['job_priority'];
		$task_workflow_id = $info['task_workflow_id'];
	}
	if($job_priority)
	{
		$check_after = $db->queryOne("select job_priority from BC_TASK_WORKFLOW_RULE where job_priority= $job_priority+1 and task_workflow_id= $task_workflow_id");
		if(!empty($check_after))
		{
			//마지막 잡프리어리티보다 큰 작업들값 불러오기
			$job_kinds = $db->queryAll("select * from BC_TASK_WORKFLOW_RULE where job_priority > $job_priority and task_workflow_id = $task_workflow_id");
			foreach($job_kinds as $add_info)
			{
				$task_type = $db->queryOne("select tt.name from BC_TASK_RULE tr,BC_TASK_TYPE tt where tr.TASK_TYPE_ID=tt.TASK_TYPE_ID and tr.task_rule_id = '{$add_info['task_rule_id']}'");		
				array_push(
					$data['data'],
					array(
						//'id'				=> $info['id']
						'type'				=> $task_type
						,'status'			=> 'regiWait'
						//,'creation_datetime'=> $info['creation_datetime']
						//,'start_datetime'	=> $info['start_datetime']
						//,'complete_datetime'=> $info['complete_datetime']
					)
				);
			}
		}
	}

}
// 타입 검색
if(!empty($_POST['search_type'])){
	$searchType = $_POST['search_type'];
	$type = $db->queryOne("select name from BC_TASK_TYPE where TYPE = $searchType");

    foreach($data['data'] as $key => $res){
	
		if(!($res['type'] == $type)){
			unset($data['data'][$key]);
		
		};
	};
	$data['data'] = array_values($data['data']);
}

// 상태 검색
if(!empty($_POST['search_status'])){
	$searchStatus = $_POST['search_status'];
	// $type = $db->queryOne("select name from BC_TASK_TYPE where TYPE = $searchStatus");

    foreach($data['data'] as $key => $res){
    
		if(!($res['status'] == $searchStatus)){
			unset($data['data'][$key]);
		
		};
	};
	$data['data'] = array_values($data['data']);
}

echo json_encode(
	$data
);
?>
