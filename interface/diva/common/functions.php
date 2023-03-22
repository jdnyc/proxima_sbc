<?php
function get_update_state_code($curr_state) {
	global $update_state_map;

	$result = $update_state_map[$curr_state];
	if (empty($result)) throw new Exception('업데이트 할 상태값이 존재하지 않습니다.');

	return $result;
}

function get_state_code_to_name($state_code) {
	global $action_name_map;

	$result = $action_name_map[$state_code];
	if (empty($result)) throw new Exception('상태 이름이 존재하지 않습니다.');

	return $result;
}

function get_state_name_to_code($state_name) 
{
	global $state_code_map;

	$state_code = $state_code_map[$state_name];
	if ($state_code !== 0 && empty($state_code)) throw new Exception('상태 코드가 존재하지 않습니다.');

	return $state_code;
}

function checkRestorePath($action, $path){

	if (DIVA_RESTORE == $action)
	{
		$result = DIVA_RESTORE_PATH;
	}
	else
	{
		$result = str_replace('\\', '/', $path);
	}

	return $result;
}

function printSuccess(){
	$xml = new SimpleXMLElement("<Response />");
	$result = $xml->addChild("Result");
		$result->addAttribute("success", "true");

	echo $xml->asXML();
}

function printFailure($msg, $code, $query){
	$xml = new SimpleXMLElement("<Response />");
	$result = $xml->addChild("Result");
		$result->addAttribute("success", "false");
		$result->addAttribute("msg", $msg);
		$result->addAttribute("code", $code);
		$result->addAttribute("query", $query);

	echo $xml->asXML();
}

///// workflow에 따른 task작업 function /////NPS db에 등록하기위해 db connect 변경
function insert_task_queryNps($content_id, $source, $target, $cur_time, $channel)
{

	global $dbNps;
	$channel_q = "select task_workflow_id from task_workflow where register = '$channel'";
	$get_register = $dbNps->queryOne($channel_q);
	if(empty($get_register)) throw new Exception('task_workflow에 등록되지 않은 인제스트 채널입니다.');

	$get_jobs = $dbNps->queryRow("select * from task_workflow_define where task_workflow_id = ".$get_register." order by job_priority asc");

	$insert_job = $dbNps->queryRow("select * from task_define where task_define_id = '{$get_jobs['task_define_id']}'");

			$target_path = $target;
			//$get_info = $dbNps->queryRow("select * from storage where name='transfer'");
			//if(empty($get_info)) throw new Exception('디비에서 transfer 값이 없습니다.');

			$proxy_mediaID = $dbNps->queryOne("select media_id from media where content_id=$content_id and type='original'");

			$query = "insert into task
			(media_id, type, status, priority, source, target, target_id, target_pw, parameter, creation_datetime, destination, task_workflow_id, job_priority, task_define_id)
			values
			('$proxy_mediaID', '{$insert_job['type']}', 'queue', 300, '$source', '$target_path', '{$get_info['login_id']}', '{$get_info['login_pw']}', '{$insert_job['parameter']}', '$cur_time', '$channel', '{$get_jobs['task_workflow_id']}', '{$get_jobs['job_priority']}', '{$get_jobs['task_define_id']}')";

	$result = $dbNps->exec($query);

	return;
}
?>