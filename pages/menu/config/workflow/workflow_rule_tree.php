<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$user_id = $_SESSION['user']['user_id'];

$task_workflow_id = $_REQUEST['task_workflow_id'];

try
{
	$list = array();
	$root = array(
		'task_rule_id' => '0',
		'icon' => '/led-icons/application_put.png',
		'expanded' => true,
		'job_no' => '0',
		'job_name' => 'Workflow Start',
		'id' => '0',
		'workflow_rule_parent_id' => '0',
		'task_workflow_id' => $task_workflow_id,
		'storage_group' => '',
		'leaf' => false
	);
	$root['children'] =  get_full_node( $task_workflow_id, '0' );
	$list [] = $root ;
	echo json_encode($list);

}
catch (Exception $e)
{
	echo '오류 : '.$e->getMessage();
}

function escape($v)
{
	$v = str_replace("'", "\'", $v);
	$v = str_replace("\r", '', $v);
	$v = str_replace("\n", '\\n', $v);

	return $v;
}


function get_full_node($task_workflow_id, $id )
{
	global $db;

	$list = array();

	if( $id == 0 )
	{
		$_where = " and ( WORKFLOW_RULE_PARENT_ID = '$id' ) ";
		// or task_rule_parant_id is null
	}
	else
	{
		$_where = " and ( WORKFLOW_RULE_PARENT_ID = '$id' ) ";
	}



	$query = "select
			TASK_WORKFLOW_ID,
		TASK_RULE_ID,
		JOB_PRIORITY,
		WORKFLOW_RULE_ID,
		CONDITION,
		TASK_RULE_PARANT_ID,
		CONTENT_STATUS,
		SOURCE_PATH_ID,
		TARGET_PATH_ID,
		STORAGE_GROUP,
		WORKFLOW_RULE_PARENT_ID,
		(select path from bc_storage where storage_id = tr.source_path_id) src_path,
		(select path from bc_storage where storage_id = tr.target_path_id) tar_path
	from bc_task_workflow_rule tr where task_workflow_id = '$task_workflow_id' $_where order by job_priority asc";



	$datas = $db->queryAll($query);

	foreach($datas as $data)
	{
		$node = array();
		$q = $db->queryRow("
		SELECT	tr.job_name, tr.parameter,
					(SELECT PATH FROM bc_storage WHERE storage_id = CAST(tr.source_path AS DOUBLE PRECISION)) src_path,
					(SELECT PATH FROM bc_storage WHERE storage_id = CAST(tr.target_path AS DOUBLE PRECISION)) tar_path
		FROM		bc_task_rule tr
		WHERE	task_rule_id = {$data['task_rule_id']}");

		$node['job_name'] = $q['job_name'];
		$node['parameter'] = $q['parameter'];
		$node['src_path'] = $data['src_path'];
		$node['tar_path'] = $data['tar_path'];


		$node['job_no'] = (string)$data['job_priority'];
		$node['task_workflow_id']= $data['task_workflow_id'];
		$node['workflow_rule_id']= $data['workflow_rule_id'];
		$node['task_rule_id']= $data['task_rule_id'];
		$node['workflow_rule_parent_id']= $data['workflow_rule_parent_id'];
		$node['task_rule_parant_id']= $data['task_rule_parant_id'];
		$node['condition']= $data['condition'];
		$node['content_status']= $data['content_status'];

		$node['source_path_id']= $data['source_path_id'];
		$node['target_path_id']= $data['target_path_id'];
		$node['storage_group_id'] = $data['storage_group'];
		$node['storage_group'] = $data['storage_group'];

		if($data['storage_group'] == 3){
			$node['storage_group'] = 'OD / AD';
		}else if($data['storage_group'] == 1){
			$node['storage_group'] = 'OD';

		}else if($data['storage_group'] == 2){
			$node['storage_group'] = 'AD';
		}
		else{
			$node['storage_group'] = 'All';
		}



		$status_list = getCodeInfo('CONTENT_STATUS');



		foreach($status_list as $lists)
		{

			if($data['content_status'] == $lists['code'])
			{
				$node['content_status_nm'] = $lists['name'];
			}
		}

		$node['icon'] = '/led-icons/application_view_detail.png';


		$is_children = $db->queryOne("select count(*) from bc_task_workflow_rule where task_workflow_id = '$task_workflow_id' and workflow_rule_parent_id='{$node['workflow_rule_parent_id']}' ");
		//$is_children =0;
		if( $is_children > 0 )
		{
			$node['leaf'] = false;
			$node['expanded'] = true;
			$node['children'] = get_full_node($task_workflow_id, $data['workflow_rule_id'] );
		}
		else
		{
			$node['leaf'] = false;
			$node['expanded'] = false;
			$node['children'] = array();
		}

		$list [] = $node;
	}

	return $list ;
}

?>