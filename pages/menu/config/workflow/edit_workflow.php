<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

try
{
$name = $_REQUEST['user_task_name'];
$register = $_REQUEST['register'];
$description = $_REQUEST['description'];
$activity = $_REQUEST['activity'];
$task_workflow_id = $_REQUEST['task_workflow_id'];
$task_rule_id = $_REQUEST['task_rule_id'];
$job_priority = $_REQUEST['job_no'];
$workflow_rule_id = $_REQUEST['workflow_rule_id'];
$job_name = $_REQUEST['job_name'];

$content_status =  $_REQUEST['content_status'];

$storage_id = $_REQUEST['storage_id'];
$storage_path = $_REQUEST['storage_path'];
$storage_path_win = $_REQUEST['storage_path_win'];
$storage_path_mac = $_REQUEST['storage_path_mac'];
$storage_path_unix = $_REQUEST['storage_path_unix'];
$storage_path_virtual = $_REQUEST['storage_path_virtual'];
$storage_name = $_REQUEST['storage_name'];
$s_type = $_REQUEST['s_type'];
$s_id = $_REQUEST['s_id'];
$s_pw = $_REQUEST['s_pw'];
$description = $_REQUEST['description'];

$storage_id = $_REQUEST['storage_id'];
$type = $_REQUEST['type'];
$path = $_REQUEST['path'];

$login_id = $_REQUEST['login_id'];
$login_pw = $_REQUEST['login_pw'];

$task_rule_id = $_REQUEST['task_rule_id'];
$condition = $_REQUEST['condition'];
if($activity == 'on')
{
	$activity = '1';
}
else
{
	$activity = '0';
}

switch($_REQUEST['action']) {
    case 'add_workflow':

		if( is_null($_POST['content_status']) || !is_numeric($_POST['content_status'])  )
		{
			$content_status = '';
		}
		else
		{
			$content_status = $_POST['content_status'];
		}

		add_workflow($name, $register, $description, $activity, $content_status );
	break;

    case 'edit_workflow':

		if( is_null($_POST['content_status']) || !is_numeric($_POST['content_status']) )
		{
			$content_status = '';
		}
		else
		{
			$content_status = $_POST['content_status'];
		}

		edit_workflow($name, $register, $description, $activity, $task_workflow_id , $content_status );
	break;

    case 'delete_workflow':
		delete_workflow($task_workflow_id);
	break;
//작업흐름에 작업추가
	case 'add_task_rule':

		add_task_rule($task_workflow_id, $task_rule_id, $_POST['task_rule_parant_id'], $_POST['job_priority'], $condition, $_POST);
	break;

	case 'edit_workflow_rule_sort':

		$params = json_decode( $_POST['params'] , true );

		edit_workflow_rule_sort( $params );
	break;

	case 'edit_workflow_rule':

		if( is_null($_POST['content_status']) || !is_numeric($_POST['content_status']) )
		{
			$content_status = '';
		}
		else
		{
			$content_status = $_POST['content_status'];
		}

		$task_rule_id = $db->queryOne("select task_rule_id from bc_task_rule where job_name = '$job_name'");
		edit_workflow_rule($task_rule_id, $job_priority, $workflow_rule_id, $condition, $content_status);
	break;

	case 'del_task_rule':
		del_task_rule($workflow_rule_id);
	break;

// 작업추가부분 (TASK_RULE 테이블)
	case 'add_rule':
		$job_name = $_REQUEST['user_task_name'];  //작업명칭
		$parameter = $_REQUEST['parameter']; //파라미터값
		$s_path = $_REQUEST['src_storage_id']; //소스 경로
		$t_path = $_REQUEST['trg_storage_id']; // 타겟 경로
		$task_type_id = $_REQUEST['type_and_name']; //작업 유형 아이디 2011.12.14 김형기 추가
		$source_opt = $_REQUEST['source_opt']; //소스 옵션 2011.12.15 김형기 추가
		$target_opt = $_REQUEST['target_opt']; //타겟 옵션 2011.12.15 김형기 추가
		add_rule($job_name, $parameter, $s_path, $t_path, $task_rule_id, $task_type_id, $source_opt, $target_opt);
	break;

	case 'edit_rule':
		$job_name = $_REQUEST['job_name']; // 작업명칭
		$parameter = $_REQUEST['parameter']; //파타미터값
		$t_path  = $_REQUEST['t_name']; // 타겟 경로
        $s_path = $_REQUEST['s_name']; // 소수 경로
		$task_rule_id = $_REQUEST['task_rule_id']; // task rule id
		$task_type_id = $_REQUEST['type_and_name']; //2011.12.14 김형기 추가
		$source_opt = $_REQUEST['source_opt']; //소스 옵션 2011.12.15 김형기 추가
		$target_opt = $_REQUEST['target_opt']; //타겟 옵션 2011.12.15 김형기 추가

		//edit_rule($job_name, $parameter, $s_path, $t_path, $module_info_id, $task_rule_id,$type); //2011.12.14 김형기 삭제
		edit_rule($job_name, $parameter, $s_path, $t_path, $task_rule_id,$task_type_id, $source_opt, $target_opt); //2011.12.14 김형기 추가
	break;

	case 'delete_rule':
		delete_rule($task_rule_id);
	break;

// 모듈 추가 부분 (MODULE_INFO)
//11.11.08 by 허광회 module_type 추가

	case 'add_module':

		$data = json_decode($_POST['data']);
		$storage = json_decode($_POST['storage']);
		//$task_type = json_decode($_POST['task']); 2011.12.14 김형기 제거
		$task_rule = json_decode($_POST['task']); //2011.12.14 김형기 추가

		//add_module($data, $storage, $task_type);
		add_module($data, $storage, $task_rule);
	break;

	case 'edit_module':

		$module_info_id = $_POST['module_info_id'];
		$data = json_decode($_POST['data']);
		$storage = json_decode($_POST['storage']);
		$task_type = json_decode($_POST['task']);
		$task_rule = json_decode($_POST['task_rule']); //2011.12.14 김형기 추가

		//edit_module($data, $storage, $task_type,$module_info_id);
		edit_module($data, $storage, $task_rule,$module_info_id);
	break;

	case 'del_module':
		$module_info_id = $_REQUEST['module_info_id'];
		delete_module($module_info_id);
	break;

//스토리지 설정 추가

	case 'add_storge':
		add_storge($storage_path, $storage_path_win, $storage_path_mac, $storage_path_unix, $storage_path_virtual, $storage_name, $s_type, $s_id, $s_pw, $description);
	break;

	case 'edit_storage':
		$name = $_REQUEST['name'];
		$path_win = $_REQUEST['path_for_win'];
		$path_mac = $_REQUEST['path_for_mac'];
		$path_unix = $_REQUEST['path_for_unix'];
		$path_virtual = $_REQUEST['virtual_path'];
		edit_storage($storage_id, $type, $path, $path_win, $path_mac, $path_unix, $path_virtual, $name, $login_id, $login_pw, $description);
	break;

	case 'delete_storage':
		delete_storage($storage_id);
	break;

//bc_task_type table  설정 추가 by 허광회
// 2011.12.04

	case 'add_task_type':
		$task_type=$_REQUEST['task_type'];
		$task_name=$_REQUEST['task_name'];
		add_task_type($task_type, $task_name);
		break;

	case 'edit_task_type':
		$task_type_id = $_REQUEST['task_type_id'];
		$task_type = $_REQUEST['type'];
		$task_name = $_REQUEST['name'];
		edit_task_type($task_type_id , $task_type, $task_name);
		break;

	case 'delete_task_type':
		$task_type_id = $_REQUEST['task_type_id'];
		delete_task_type($task_type_id);
		break;

    default:
        echo json_encode(array(
            'success' => false,
            'msg' => 'no more action 액션이 정의 되어있지 않습니다.'
        ));
    break;
}


}catch(Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}

function add_task_type($task_type, $task_name){
	global $db;
	$check_type_query = "
		SELECT	*
		FROM		BC_TASK_TYPE
		WHERE	TYPE = '".$task_type."'
	";

	$check_type = $db->queryAll($check_type_query);
	if( count($check_type) > 0 ){
		echo json_encode(array(
			'success' => false,
			'msg' => _text('MSG01023')//'등록 모듈 아이디 값은 중복이 될 수 없습니다.'
		));
		exit;
	}else{
		$bc_task_type_id = $db->queryOne("select max(task_type_id) from bc_task_type")+1;
		$query = "
			INSERT	INTO	BC_TASK_TYPE
				(TASK_TYPE_ID, TYPE, NAME)
			VALUES
				(".$bc_task_type_id.", '".$task_type."', '".$task_name."')
		";
		//$query = "insert into bc_task_type values('$bc_task_type_id','$task_type','$task_name')";

		$r = $db->exec($query);
		echo json_encode(array(
		'success' => true
		));
	}
}

function edit_task_type($task_type_id , $task_type, $task_name){
	global $db;
	$query = "update bc_task_type set type = '$task_type' , name = '$task_name' where task_type_id = '$task_type_id'";
	$r = $db->exec($query);
	if($r == 0)
	{
		echo json_encode(array(
			'success' => false,
			'msg' => 'TASK TYPE  업데이트시 오류가 발생 하였습니다.'
		));
		exit;
	}
	else {
	 echo json_encode(array(
        'success' => true
    ));
	}
}

function delete_task_type($task_type_id){
	global $db;
	$query = "delete from bc_task_type where task_type_id = '$task_type_id'";
	$r = $db->exec($query);
	if($r == 0)
	{
		echo json_encode(array(
			'success' => false,
			'msg' => 'TASK TYPE 삭제시 오류가 발생 하였습니다.'
		));
		exit;
	}
	else {
	 echo json_encode(array(
        'success' => true,
	 	'msg' => 'TASK TYPE 삭제가 완료되었습니다.'
    ));
	}
}

function add_code($name) {
	global $db;

	//$code = $db->queryOne("SELECT MAX(TO_NUMBER(CODE)) FROM BC_CODE WHERE CODE_TYPE_ID=1");
	$code = $db->queryOne("SELECT MAX(CAST(CODE AS INT)) FROM BC_CODE WHERE CODE_TYPE_ID=1");

	$code = intval($code) << 1;

	$db->exec("INSERT INTO BC_CODE
		                   (CODE, NAME, CODE_TYPE_ID)
		            values ('$code', '$name', 1)");

	$id = $db->queryOne("SELECT MAX(ID) FROM BC_CODE WHERE CODE_TYPE_ID=1");

	return $id;
}

function delete_code($id) {
	global $db;

	$db->exec("DELETE BC_CODE WHERE ID=$id");
}

function add_workflow($name, $register, $description, $activity, $content_status) {
	global $db;

	$check_query = "select count(register) from bc_task_workflow where register = '$register'";
	$r = $db->queryOne($check_query);
	if($r != 0)
	{
		echo json_encode(array(
			'success' => false,
			'msg' => '등록 채널 값은 중복이 될 수 없습니다.'
		));
		exit;
	}

	//2016-02-24 INSERT QUERY 수정
	$insert_data = array(
            'USER_TASK_NAME' => $name,
            'REGISTER' => $register,
            'DESCRIPTION' => $description,
            'ACTIVITY' => $activity,
            'CONTENT_STATUS' => $content_status
    );

	$r =  $db->insert('BC_TASK_WORKFLOW', $insert_data);

    echo json_encode(array(
        'success' => true
    ));
}

function edit_workflow($name, $register, $description, $activity, $task_workflow_id, $content_status){
	global $db;

	$ori_register = $db->queryOne("select register from bc_task_workflow where task_workflow_id = $task_workflow_id");
	if($ori_register != $register)
	{
		$check_query = "select count(register) from bc_task_workflow where register = '$register'";
		$r = $db->queryOne($check_query);
		if($r != 0)
		{
			echo json_encode(array(
				'success' => false,
				'msg' => '등록 채널 값은 중복이 될 수 없습니다.'
			));
			exit;
		}
	}

	$edit_query = "update bc_task_workflow set
							user_task_name = '$name',
							register = '$register',
							description = '$description',
							activity = '$activity' ,
							content_status = '$content_status'
						where
							task_workflow_id = $task_workflow_id";
	$r = $db->exec($edit_query);

    echo json_encode(array(
        'success' => true
    ));
}

function delete_workflow($task_workflow_id){
	global $db;

	$has_child = $db->queryOne("
					SELECT COUNT(TASK_WORKFLOW_ID)
					FROM BC_TASK_WORKFLOW_RULE
					WHERE TASK_WORKFLOW_ID = ".$task_workflow_id."
				");

	if($has_child > 0) {
		$delete_workflow_rule = $db->exec("
									DELETE FROM	BC_TASK_WORKFLOW_RULE
									WHERE TASK_WORKFLOW_ID = ".$task_workflow_id."
								");
	}

	$delete_query = "delete FROM bc_task_workflow where task_workflow_id = ".$task_workflow_id." ";
	$r = $db->exec($delete_query);

	echo json_encode(array(
        'success' => true,
		'msg' => _text('MSG02122')//Deleted.
    ));
}
//add_task_rule($task_workflow_id, $task_rule_id, $_POST['task_rule_parant_id'], $_POST['job_priority'], $condition);
function add_task_rule($task_workflow_id, $task_rule_id, $task_rule_parant_id, $job_priority, $condition, $POST){
	global $db;

	if($task_rule_parant_id ==  $task_rule_id){
		echo json_encode(array(
			'success' => false,
			'msg' => '같은 작업입니다.'
		));
		exit;
	}

	//2016-02-24 INSERT QUERY 수정
	$insert_data = array(
            'TASK_WORKFLOW_ID' => $task_workflow_id,
            'TASK_RULE_ID' => $task_rule_id,
            'TASK_RULE_PARANT_ID' => $task_rule_parant_id,
            'JOB_PRIORITY' => $job_priority,
            'CONDITION' => $condition,
            'WORKFLOW_RULE_PARENT_ID' => $POST['workflow_rule_parent_id'],
            'SOURCE_PATH_ID' => $POST['s_name'],
            'TARGET_PATH_ID' => $POST['t_name'],
            'STORAGE_GROUP' => $POST['storage_group'],
            'CONTENT_STATUS' => $POST['content_status']
    );

	$r =  $db->insert('BC_TASK_WORKFLOW_RULE', $insert_data);

	echo json_encode(array(
        'success' => true,
		'msg' => '작업이 추가 되었습니다.'
    ));
}

function edit_workflow_rule_sort( $params ){
	global $db;

	foreach($params as $param)
	{
		/*  [type] => node
		[job_priority] => 3
		[task_rule_id] => 20
		[task_rule_parant_id] => 19
		[task_workflow_id] => 67
		[workflow_rule_id] => 257 */
		if( $param['type'] == 'node' ) //대상 노드 정보
		{
			$task_rule_parant_id = $param['task_rule_parant_id'] ;
			$task_rule_id = $param['task_rule_id'] ;

			$condition = $param['condition'] ;
			$workflow_rule_id = $param['workflow_rule_id'] ;
		}
		else if( $param['type'] == 'newParent' ) //새 부모 노드
		{
			$task_rule_parant_id = $param['task_rule_id'] ;
			$job_priority = $param['job_priority'] + 1 ;

			$workflow_rule_parent_id =  $param['workflow_rule_id'] ;
		}
	}

	$condition = empty($condition) ? 'null' : $condition;


	$child_node = $db->queryOne("select count(*) from bc_task_workflow_rule where WORKFLOW_RULE_PARENT_ID='$workflow_rule_id' ");


	$workflow_rule_parent_id = empty($workflow_rule_parent_id) ? '0' :$workflow_rule_parent_id;

	$edit_query = "
		update bc_task_workflow_rule set
			task_rule_parant_id = ".$task_rule_parant_id.",
			job_priority = ".$job_priority.",
			WORKFLOW_RULE_PARENT_ID = ".$workflow_rule_parent_id."
		where workflow_rule_id = ".$workflow_rule_id." ";
	$r = $db->exec($edit_query);

	update_child_workflow( $task_workflow_id , $workflow_rule_id , $job_priority );

	echo json_encode(array(
		'success' => true,
		'msg' => '작업이 수정 되었습니다.'
	));

}

function update_child_workflow( $task_workflow_id , $workflow_rule_parent_id , $job_priority )
{
	global $db;

	$next_job_priority = $job_priority  + 1;

	$leaf_info = $db->queryAll("select * from bc_task_workflow_rule where WORKFLOW_RULE_PARENT_ID = ".$workflow_rule_parent_id." ");

	if( count($leaf_info) > 0 )
	{
		foreach($leaf_info as $leaf)
		{
			$workflow_rule_id = $leaf['workflow_rule_id'];

			$query = "update bc_task_workflow_rule set job_priority = '$next_job_priority' where workflow_rule_id = '$workflow_rule_id' ";
			$r = $db->exec($query);

			update_child_workflow($task_workflow_id , $workflow_rule_id , $next_job_priority );
		}
	}
	else
	{
		return true;
	}
}

function edit_workflow_rule($task_rule_id, $job_priority, $workflow_rule_id, $condition , $content_status){
	global $db;

	$condition = empty($condition) ? 'NULL' : $condition;

	$source_path_id = $_POST['s_name'];
	$target_path_id = $_POST['t_name'];
	$storage_group = $_POST['storage_group'];

	$edit_query = ".
		UPDATE BC_TASK_WORKFLOW_RULE SET
			TASK_RULE_ID = ".$task_rule_id.",
			SOURCE_PATH_ID = ".$source_path_id.",
			TARGET_PATH_ID = ".$target_path_id.",
			STORAGE_GROUP = ".$storage_group." ,
			CONTENT_STATUS = ".$content_status."
		WHERE WORKFLOW_RULE_ID = ".$workflow_rule_id."
	";

	$db->exec($edit_query);

	echo json_encode(array(
        'success' => true,
		'query' => $edit_query,
		'msg' => '작업이 수정 되었습니다.'
    ));
}

function del_task_rule($workflow_rule_id){
	global $db;

	$check = $db->queryOne("select count(*) from bc_task_workflow_rule where WORKFLOW_RULE_PARENT_ID = ".$workflow_rule_id." ");

	if($check > 0){
		echo json_encode(array(
			'success' => false,
			'msg' => '하위 작업을 삭제해주세요.'
		));
	}else{
	$delete_query = "delete from bc_task_workflow_rule where workflow_rule_id = ".$workflow_rule_id." ";
	$r = $db->exec($delete_query);

	echo json_encode(array(
        'success' => true,
		'msg' => '삭제 되었습니다.'
    ));
	}
}

//######################  (BC_TASK_RULE 테이블 추가, 수정, 삭제) #######################//

function add_rule($job_name, $parameter, $s_path, $t_path, $task_rule_id, $task_type_id, $source_opt, $target_opt){ //2011.12.14 김형기 수정
	global $db;

	$task_rule_id = $db->queryOne("select max(task_rule_id) from bc_task_rule")+1;

	if(empty($s_path)) {
		$s_path = 0;
	}

	if(empty($t_path)) {
		$t_path = 0;
	}

	$add_query = "insert into bc_task_rule (task_rule_id, job_name, task_type_id, parameter, source_path, target_path, source_opt, target_opt)
		values ($task_rule_id, '$job_name', $task_type_id, '$parameter', $s_path, $t_path, '$source_opt', '$target_opt')";

	$r = $db->exec($add_query);

	echo json_encode(array(
        'success' => true,
		'msg' => '작업이 추가 되었습니다.'
    ));
}

function edit_rule($job_name, $parameter, $s_path, $t_path, $task_rule_id, $task_type_id, $source_opt, $target_opt){
	global $db;

	$edit_arr = array(
		'JOB_NAME' => $job_name,
		'PARAMETER' => $parameter,
		'SOURCE_PATH' => $s_path,
		'TARGET_PATH' => $t_path,
		'TASK_TYPE_ID' => $task_type_id,
		'SOURCE_OPT' => $source_opt,
		'TARGET_OPT' => $target_opt
	);
	$r = $db->update("BC_TASK_RULE", $edit_arr, " TASK_RULE_ID = $task_rule_id ");

    echo json_encode(array(
        'success' => true,
		//'msg'=> $edit_query
		'msg' => '작업이 수정 되었습니다.'
    ));
}

function delete_rule($task_rule_id){
	global $db;

	$delete_query = "delete from bc_task_rule where task_rule_id = ".$task_rule_id." ";
	$r = $db->exec($delete_query);

    echo json_encode(array(
        'success' => true,
		'msg' => _text('MSG02122')//Deleted.
    ));
}

//######################  (BC_MODULE_INFO  추가, 수정, 삭제) #######################//
// 11.11.08 by 허광회 수정
// bc_task_available table 값 추가
// 11.12.05 by 허광회 수정
// edit
/*
function add_module($data, $storage, $task_type){

	global $db;

	$data_array = array();
	foreach( $data as $key => $v)
	{
			$data_array[$key] = $v;
	}

	$active = $data_array['activity'];

	if($active == 'on')
	{
		$active = 1;
	}
	else
	{
		$active = 0;
	}

	$m_name = $data_array['module_name'];
	$main_ip = $data_array['main_ip'];
	$sub_ip = $data_array['sub_ip'];
	$description = $data_array['description'];

	$module_info_id = $db->queryOne("select max(module_info_id) from bc_module_info")+1;

	$query = "insert into bc_module_info (module_id, name, active, main_ip, sub_ip, description,module_info_id)
		values ('$module_id', '$m_name', '$active', '$main_ip', '$sub_ip', '$description','$module_info_id')";
	$r = $db->exec($query);

	foreach($storage as $v)
	{
		$query = "insert into bc_path_available (module_info_id, available_storage) values ('$module_info_id', '$v')";
		$r = $db->exec($query);
	}

	foreach($task_type as $v)
	{
		$query = "insert into bc_task_available (module_info_id, task_type_id) values ('$module_info_id', '$v')";
		$r = $db->exec($query);
	}

    echo json_encode(array(
        'success' => true,
		'msg' => '모듈이 추가 되었습니다.'
    ));
}

function edit_module($data, $storage, $task_type,$module_info_id){
	global $db;

	$data_array = array();
	foreach( $data as $key => $v)
	{
			$data_array[$key] = $v;
	}

	$active = $data_array['activity'];

	if($active == 'on')
	{
		$active = 1;
	}
	else
	{
		$active = 0;
	}

	$m_name = $data_array['name'];
	$main_ip = $data_array['main_ip'];
	$sub_ip = $data_array['sub_ip'];
	$description = $data_array['description'];

	$edit_query = "update bc_module_info set name = '$m_name', active = '$active', main_ip = '$main_ip', sub_ip = '$sub_ip', description = '$description'  where module_info_id = '$module_info_id'";
	$r = $db->exec($edit_query);
	// bc_TASK_AVAIABLE

	//path_available을 업데이트 하기전에 기존 데이터를 삭제
	$db->exec("delete bc_path_available where module_info_id = $module_info_id");

	foreach($storage as $v)
	{
		$query = "insert into bc_path_available (module_info_id, available_storage) values ('$module_info_id', '$v')";
		$r = $db->exec($query);
	}

	//path_available을 업데이트 하기전에 기존 데이터를 삭제
	$db->exec("delete bc_task_available where module_info_id = $module_info_id");

	foreach($task_type as $v)
	{
		$query = "insert into bc_task_available (module_info_id, task_type_id) values ('$module_info_id', '$v')";
		$r = $db->exec($query);
	}

    echo json_encode(array(
        'success' => true,
		'msg' => '모듈이 수정 되었습니다.'
    ));
}
*/
//2011.12.14 김형기 추가
function add_module($data, $storage, $task_rule){

	global $db;

	$data_array = array();
	foreach( $data as $key => $v)
	{
			$data_array[$key] = $v;
	}

	$active = $data_array['activity'];

	if($active == 'on')
	{
		$active = 1;
	}
	else
	{
		$active = 0;
	}

	$m_name = $data_array['module_name'];
	$main_ip = $data_array['main_ip'];
	$sub_ip = $data_array['sub_ip'];
	$description = $data_array['description'];

	$module_info_id = $db->queryOne("select max(module_info_id) from bc_module_info")+1;

	$query = "insert into bc_module_info (name, active, main_ip, sub_ip, description,module_info_id)
		values ('$m_name', '$active', '$main_ip', '$sub_ip', '$description','$module_info_id')";
	$r = $db->exec($query);

	foreach($storage as $v)
	{
		$query = "insert into bc_path_available (module_info_id, available_storage) values ('$module_info_id', '$v')";
		$r = $db->exec($query);
	}

	foreach($task_rule as $v)
	{
		$query = "insert into bc_task_available (module_info_id, task_rule_id) values ('$module_info_id', '$v')";
		$r = $db->exec($query);
	}

    echo json_encode(array(
        'success' => true,
		'msg' => '모듈이 추가 되었습니다.'
    ));
}

function edit_module($data, $storage, $task_rule, $module_info_id){
	global $db;

	$data_array = array();
	foreach( $data as $key => $v)
	{
			$data_array[$key] = $v;
	}

	$active = $data_array['activity'];

	if($active == 'on')
	{
		$active = 1;
	}
	else
	{
		$active = 0;
	}

	$m_name = $data_array['name'];
	$main_ip = $data_array['main_ip'];
	$sub_ip = $data_array['sub_ip'];
	$description = $data_array['description'];

	$edit_query = "update bc_module_info set name = '$m_name', active = '$active', main_ip = '$main_ip', sub_ip = '$sub_ip', description = '$description'  where module_info_id = $module_info_id ";
	$r = $db->exec($edit_query);
	// bc_TASK_AVAIABLE

//2016-02-15 이승수. bc_path_available 사용 안하도록 변경
//	//path_available을 업데이트 하기전에 기존 데이터를 삭제
//	$db->exec("delete bc_path_available where module_info_id = $module_info_id");
//
//	foreach($storage as $v)
//	{
//		$query = "insert into bc_path_available (module_info_id, available_storage) values ('$module_info_id', '$v')";
//		$r = $db->exec($query);
//	}

	//path_available을 업데이트 하기전에 기존 데이터를 삭제
	$db->exec("delete from bc_task_available where module_info_id = $module_info_id");

	foreach($task_rule as $v)
	{
		$insert_data = array(
			'module_info_id' => $module_info_id,
			'task_rule_id' => $v
		);

		$query = $db->insert('BC_TASK_AVAILABLE', $insert_data);

		//$query = "insert into bc_task_available (module_info_id, task_rule_id) values ($module_info_id, $v)";
		//$r = $db->exec($query);
	}

    echo json_encode(array(
        'success' => true,
		'msg' => '모듈이 수정 되었습니다.'
    ));
}

function delete_module($module_info_id){
	global $db;

	$delete_query = "delete from	bc_module_info where module_info_id = $module_info_id";
	$r = $db->exec($delete_query);
	$db->exec("delete  from	bc_path_available where module_info_id = $module_info_id");
	$db->exec("delete  from	bc_task_available where module_info_id = $module_info_id");
    echo json_encode(array(
        'success' => true,
		'msg' => _text('MSG02122')//Deleted.
    ));
}
//######################  (BC_STORAGE 테이블 추가, 수정, 삭제) #######################//

function add_storge($storage_path, $storage_path_win, $storage_path_mac, $storage_path_unix, $storage_path_virtual, $storage_name, $s_type, $s_id, $s_pw, $description){
	global $db;
    $limit_session = $_REQUEST['limit_session'];
    if(empty($limit_session)) $limit_session = 0;
	$storage_id = $db->queryOne("select storage_id from bc_storage order by storage_id desc")+1;

	$add_query = "insert into bc_storage 
					(
						storage_id,
					 	path,
					 	path_for_win,
				 	 	path_for_mac,
				 	 	path_for_unix, 
				 	 	virtual_path, 
				 	 	name, 
				 	 	type, 
				 	 	login_id,
				 	 	login_pw, 
                          description,
                          limit_session
			 	 	) values (
			 	 		'$storage_id', 
			 	 		'$storage_path',
			 	 		'$storage_path_win',
			 	 		'$storage_path_mac',
			 	 		'$storage_path_unix',
			 	 		'$storage_path_virtual',
			 	 		'$storage_name', 
			 	 		'$s_type', 
			 	 		'$s_id', 
			 	 		'$s_pw', 
                          '$description',
                          $limit_session
		 	 		)";
	$r = $db->exec($add_query);

    echo json_encode(array(
        'success' => true,
		'msg' => '추가 되었습니다.'
    ));
}

function edit_storage($storage_id, $type, $path, $path_win, $path_mac, $path_unix, $path_virtual, $name, $login_id, $login_pw, $description){
	global $db;
	$write_limit = $_REQUEST['write_limit'];
    $read_limit = $_REQUEST['read_limit'];
    $limit_session = $_REQUEST['limit_session'];

    if(!is_numeric($limit_session)) {
        $limit_session = 0;
    }

	if( is_null($write_limit) ||  $write_limit <= 0 ){
		$write_limit = '-1';
	}
	if( is_null($read_limit) ||  $read_limit <= 0 ){
		$read_limit = '-1';
	}
	$edit_query = "	update bc_storage 
						set type = '$type', 
						path = '$path',
						path_for_win = '$path_win',
						path_for_mac = '$path_mac',
						path_for_unix = '$path_unix',
						virtual_path = '$path_virtual',
						name = '$name', 
						login_id = '$login_id', 
						login_pw = '$login_pw',
						write_limit= $write_limit , 
                        read_limit= $read_limit ,  
                        limit_session= $limit_session ,  
						description = '$description' 
					where storage_id = $storage_id";
	$r = $db->exec($edit_query);

    echo json_encode(array(
        'success' => true,
		'msg' => '작업이 수정 되었습니다.'
    ));
}

function delete_storage($storage_id){
	global $db;

	$delete_query = "delete from bc_storage where storage_id = '$storage_id'";
	$r = $db->exec($delete_query);

    echo json_encode(array(
        'success' => true,
		'msg' => _text('MSG00017')//'삭제성공'
    ));
}




?>