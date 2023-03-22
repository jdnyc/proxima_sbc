<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
try
{
if($_SESSION['user']['super_admin'] == 'Y'){
    $user_id = 'system';
}else{
    $user_id = $_SESSION['user']['user_id'];
}

$name = $_REQUEST['user_task_name'];
$register = $_REQUEST['register'];
$description = $_REQUEST['description'];
$activity = $_REQUEST['activity'];
$workflow_type = $_REQUEST['workflow_type'];
$task_workflow_id = $_REQUEST['task_workflow_id'];
$task_rule_id = $_REQUEST['task_rule_id'];
$job_priority = $_REQUEST['job_no'];
$workflow_rule_id = $_REQUEST['workflow_rule_id'];
$job_name = $_REQUEST['job_name'];

$bs_content_id =  $_REQUEST['bs_content_id'];

$storage_id = $_REQUEST['storage_id'];
$storage_path = $_REQUEST['storage_path'];
$storage_name = $_REQUEST['storage_name'];
$s_type = $_REQUEST['s_type'];
$s_id = $_REQUEST['s_id'];
$s_pw = $_REQUEST['s_pw'];
$description = $_REQUEST['description'];
$preset_type = $_REQUEST['preset_type'];

$source_storage_id = $_REQUEST['source_storage_id'];
$target_storage_id = $_REQUEST['target_storage_id'];
$storage_id = $_REQUEST['storage_id'];
$type = $_REQUEST['type'];
$path = $_REQUEST['path'];

$login_id = $_REQUEST['login_id'];
$login_pw = $_REQUEST['login_pw'];

$task_rule_id = $_REQUEST['task_rule_id'];
$condition = $_REQUEST['condition'];
if ($activity == 'on') {
    $activity = '1';
} else {
    $activity = '0';
}

$icon_url = $_POST['icon_url'];

switch($_REQUEST['action']) {
    case 'add_workflow':
        if (is_null($_POST['content_status']) || !is_numeric($_POST['content_status'])) {
            $content_status = '';
        } else {
            $content_status = $_POST['content_status'];
        }

        // PRESET 워크플로우가 아닐 경우 워크플로우 명 자동 변경 로직 추가
        // GWR : Gemiso Workflow Register
        // _GWR_ 로 워크플로우 명을 확인하여 워크플로우가 추가될때마다 문자값을 계속 증가
        // A->Z->AA->ZZ 식으로 계속 증가 가능
        if($workflow_type != 'p' && $workflow_type != 'i' && $workflow_type != 's') {
            $registered_name = $db->queryOne("
                                    SELECT	MAX(REGISTER)
                                    FROM	BC_TASK_WORKFLOW
                                    WHERE	REGISTER LIKE '".$register."_GWR_%'
                                ");

            $registered_name_array = explode("_GWR_", $registered_name);
            if(empty($registered_name_array[1])) {
                $register = $register.'_GWR_A';
            } else {
                $register = $register.'_GWR_'.++$registered_name_array[1];
            }
        }

        //if not Preset workflow, then get bs_content_id from Preset
        if($workflow_type != 'p') {
            $preset_info = $db->queryRow("SELECT * FROM BC_TASK_WORKFLOW
                WHERE TASK_WORKFLOW_ID=".$task_workflow_id);
            $bs_content_id = $preset_info['bs_content_id'];
        }

        //if Preset, check 'Content Type' and 'Task Channel'. For prevent dupli.
        if($workflow_type == 'p' || $workflow_type == 'i') {
            $is_workflow_exist = $db->queryRow("
                SELECT	*
                FROM	BC_TASK_WORKFLOW
                WHERE	REGISTER='".$register."'
                AND     type = '".$workflow_type."'
                AND		BS_CONTENT_ID=".$bs_content_id);
            if(!empty($is_workflow_exist)) {
                echo json_encode(array(
                    'success' => false,
                    'msg' => _text('MSG02087')//이미 존재하는 워크플로우 유형 입니다.
                ));
                exit;
            }
        }

        add_workflow($name, $register, $description, $activity, $content_status, $workflow_type, $icon_url, $task_workflow_id, $preset_type, $bs_content_id, $user_id);
    break;

    case 'edit_workflow':
        if (is_null($_POST['content_status']) || !is_numeric($_POST['content_status'])) {
            $content_status = '';
        } else {
            $content_status = $_POST['content_status'];
        }

        $workflow_info = $db->queryRow("SELECT * FROM BC_TASK_WORKFLOW
                WHERE TASK_WORKFLOW_ID=".$task_workflow_id);

        //if not Preset workflow, then get bs_content_id from original workflow
        //means, not change bs_content_id
        if($workflow_info['type'] != 'p') {
            $bs_content_id = $workflow_info['bs_content_id'];
        }

        edit_workflow($name, $register, $description, $activity, $task_workflow_id , $content_status, $preset_type, $icon_url, $bs_content_id);
    break;

    case 'delete_workflow':
        delete_workflow($task_workflow_id);
    break;
//작업흐름에 작업추가
    case 'add_task_rule':

        add_task_rule($task_workflow_id, $task_rule_id, $_POST['task_rule_parent_id'], $_POST['job_priority'], $condition, $_POST);
    break;

    case 'edit_workflow_rule_sort':

        $params = json_decode( $_POST['params'] , true );

        edit_workflow_rule_sort( $params );
    break;

    case 'edit_workflow_rule':

        if (is_null($_POST['content_status']) || ! is_numeric($_POST['content_status'])) {
            $content_status = '';
        } else {
            $content_status = $_POST['content_status'];
        }

        edit_workflow_rule($workflow_rule_id, $task_rule_id, $content_status, $source_storage_id, $target_storage_id);
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
        //$storage = json_decode($_POST['storage']);
        //$task_type = json_decode($_POST['task']); 2011.12.14 김형기 제거
        $task_rule = json_decode($_POST['task']); //2011.12.14 김형기 추가

        //add_module($data, $storage, $task_type);
        add_module($data, $storage, $task_rule);
    break;

    case 'edit_module':

        $module_info_id = $_POST['module_info_id'];
        $data = json_decode($_POST['data']);
        //$storage = json_decode($_POST['storage']);
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
        add_storge($storage_path, $storage_name, $s_type, $s_id, $s_pw, $description);
    break;

    case 'edit_storage':
        $name = $_REQUEST['name'];
        edit_storage($storage_id, $type, $path, $name, $login_id, $login_pw, $description);
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
            'msg' => _text('MSG01022')
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
    $bc_task_type_id = $db->queryOne("SELECT MAX(TASK_TYPE_ID) FROM BC_TASK_TYPE")+1;
    $query = "INSERT INTO BC_TASK_TYPE VALUES ('$bc_task_type_id','$task_type','$task_name','$bc_task_type_id')";

    $r = $db->exec($query);

    if($r == 0)
    {
        echo json_encode(array(
            'success' => false,
            'msg' => _text('MSG01023')
        ));
        exit;
    }
    echo json_encode(array(
        'success' => true
        ));
}

function edit_task_type($task_type_id , $task_type, $task_name){
    global $db;
    $query = "
                UPDATE	BC_TASK_TYPE
                SET		TYPE = '$task_type',
                        NAME = '$task_name'
                WHERE	TASK_TYPE_ID = $task_type_id
            ";
    $r = $db->exec($query);
    if($r == 0)
    {
        echo json_encode(array(
            'success' => false,
            'msg' => _text('MSG00043')
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
    $query = "
                DELETE	FROM BC_TASK_TYPE
                WHERE	TASK_TYPE_ID = $task_type_id
            ";
    $r = $db->exec($query);
    if($r == 0)
    {
        echo json_encode(array(
            'success' => false,
            'msg' => _text('MSG00018')
        ));
        exit;
    }
    else {
        echo json_encode(array(
            'success' => true,
            'msg' => _text('MSG00017')
        ));
    }
}

function add_code($name) {
    global $db;

    //$code = $db->queryOne("SELECT MAX(TO_NUMBER(CODE)) FROM BC_CODE WHERE CODE_TYPE_ID=1");
//	$code = $db->queryOne("SELECT MAX(CAST(CODE AS INT)) FROM BC_CODE WHERE CODE_TYPE_ID=1");
//	$code = intval($code) << 1;

    //2017-05-02 SSLee. code check login change. Due to limit of maximum digit value.
    $code_type_id = 1; //content_grant
    $code = find_code_in_bc_code($code_type_id);

    $id = getSequence('seq_bc_code_id');
    $db->exec("
                INSERT INTO BC_CODE
                    (ID, CODE, NAME, CODE_TYPE_ID, REF1)
                VALUES
                    ($id, '$code', '$name', $code_type_id, 'CONTEXT_GRANT')
            ");

//	$id = $db->queryOne("
//				SELECT	ID
//				FROM	BC_CODE
//				WHERE	CODE = '$code'
//				AND		NAME = '$name'
//				AND		CODE_TYPE_ID = 1
//				AND		REF1 = 'CONTEXT_GRANT'
//			");

    return $id;
}

function find_code_in_bc_code($code_type_id, $min=0) {
    global $db;
    //BC_CODE grant maximum is 31. If code value is over 2^31, that overflow int type.
    //min code is 1024, for reduce recursive.
    $total = $db->queryOne("select count(*) from bc_code where code_type_id=".$code_type_id);
    if($total == 31) throw new Exception("Proxima maximum code count is 31");
    $code = $db->queryOne("SELECT MIN(CAST(CODE AS INT)) FROM BC_CODE WHERE CODE_TYPE_ID=".$code_type_id." AND CAST(CODE AS INT) >= '".$min."'");
    $code = intval($code) << 1;
    $is_exists = $db->queryOne("SELECT CODE FROM BC_CODE WHERE CODE='".$code."'");
    if(!empty($is_exists)) {
        $code = find_code_in_bc_code($code_type_id, $code);
    }
    return $code;
}

function delete_code($id) {
    global $db;

    $db->exec("DELETE BC_CODE WHERE ID = $id");
}

function add_workflow($name, $register, $description, $activity, $content_status, $workflow_type, $icon_url, $task_workflow_id = null, $preset_type, $bs_content_id, $creator) {
    global $db;

    $workflow_id = getNextSequence('task_workflow_id');
    $insert_data = array(
            'TASK_WORKFLOW_ID' => $workflow_id,
            'USER_TASK_NAME'	=>	$name,
            'REGISTER'			=>	$register,
            'DESCRIPTION'		=>	$description,
            'ACTIVITY'			=>	$activity,
            'TYPE'				=>	$workflow_type,
            'ICON_URL'			=>	$icon_url,
            'PRESET_TYPE'		=>	$preset_type,
            'BS_CONTENT_ID'		=>	$bs_content_id,
            'CREATOR'			=> $creator
    );

    _debug("register_sequence", print_r($insert_data,true));


    if( $content_status != '' ){
        $insert_data['CONTENT_STATUS'] = $content_status;
    }
    _debug("register_sequence","====== = = = = =");
    $query_insert = $db->insert('BC_TASK_WORKFLOW', $insert_data, 'exec');

    ////임시
    //if($content_status == '') $content_status = 'null';
    //$add_query = "
                    //INSERT INTO BC_TASK_WORKFLOW
                        //(USER_TASK_NAME, REGISTER, DESCRIPTION, ACTIVITY ,CONTENT_STATUS, TYPE, ICON_URL, PRESET_TYPE)
                    //VALUES
                        //('$name', '$register', '$description', '$activity' , $content_status, '$workflow_type', '$icon_url', '$preset_type')
                //";

    //$add_query = "
                    //INSERT INTO BC_TASK_WORKFLOW
                        //(USER_TASK_NAME, REGISTER, DESCRIPTION, ACTIVITY ,CONTENT_STATUS, TYPE, ICON_URL, PRESET_TYPE)
                    //VALUES
                        //('$name', '$register', '$description', '$activity' , $content_status, '$workflow_type', '$icon_url', '$preset_type')
                //";
    //$r = $db->exec($add_query);

    if ($workflow_type != 'p') {
        //$workflow_id = $db->queryOne("SELECT MAX(TASK_WORKFLOW_ID) FROM BC_TASK_WORKFLOW");

        $task_rule_parant_id[0][1] = 0;
        $workflow_rule_parent_id[0][1] = 0;
        $task_rule_array = array();
        $tasks = $db->queryAll("
                        SELECT	*
                        FROM	BC_TASK_WORKFLOW_RULE
                        WHERE	TASK_WORKFLOW_ID = $task_workflow_id
                        ORDER BY JOB_PRIORITY, TASK_RULE_PARANT_ID
                ");
/*
make array [parent_id][priority].
there's two array.
array1 for task_rule_id
array2 for workflow_rule_id
*/
        foreach ($tasks as $task) {
            $task_rule_id = $db->queryOne("SELECT MAX(TASK_RULE_ID) + 1 FROM BC_TASK_RULE");
            $db->exec("
                    INSERT	INTO BC_TASK_RULE
                        (TASK_RULE_ID, JOB_NAME, PARAMETER, SOURCE_OPT, SOURCE_PATH,
                            TARGET_OPT, TARGET_PATH, TASK_TYPE_ID)
                    SELECT	$task_rule_id, JOB_NAME || '_".$name."' , PARAMETER, SOURCE_OPT, SOURCE_PATH,
                            TARGET_OPT, TARGET_PATH, TASK_TYPE_ID
                    FROM	BC_TASK_RULE
                    WHERE	TASK_RULE_ID = {$task['task_rule_id']}");
//this, make array1 for next childs.
            $task_rule_parant_id[$task['task_rule_id']][$task['job_priority'] + 1] = $task_rule_id;
            
            $v_task_rule_parent_id = $task_rule_parant_id[$task['task_rule_parant_id']][$task['job_priority']];
            if(empty($v_task_rule_parent_id)) {
                $v_task_rule_parent_id = 0;
            }
            $v_workflow_rule_parent_id = $workflow_rule_parent_id[$task['workflow_rule_parent_id']][$task['job_priority']];
            if(empty($v_workflow_rule_parent_id)) {
                $v_workflow_rule_parent_id = 'null';
            }
//this query, get id
            $db->exec("
                    INSERT	INTO BC_TASK_WORKFLOW_RULE
                        (TASK_WORKFLOW_ID, TASK_RULE_ID, TASK_RULE_PARANT_ID,
                            WORKFLOW_RULE_PARENT_ID, SOURCE_PATH_ID, TARGET_PATH_ID,
                            JOB_PRIORITY, CONDITION, CONTENT_STATUS)
                    SELECT	$workflow_id, $task_rule_id, ". $v_task_rule_parent_id . ",	".
                                $v_workflow_rule_parent_id . ", SOURCE_PATH_ID, TARGET_PATH_ID,
                                JOB_PRIORITY, CONDITION, CONTENT_STATUS
                    FROM	BC_TASK_WORKFLOW_RULE
                    WHERE	TASK_WORKFLOW_ID = {$task['task_workflow_id']}
                    AND		WORKFLOW_RULE_ID = {$task['workflow_rule_id']}
            ");

            $workflow_rule_id = $db->queryOne("
                                    SELECT	WORKFLOW_RULE_ID
                                    FROM	BC_TASK_WORKFLOW_RULE
                                    WHERE	TASK_WORKFLOW_ID = $workflow_id
                                    AND		TASK_RULE_ID = $task_rule_id
                                ");
//this, make array2 for next childs.
            $workflow_rule_parent_id[$task['workflow_rule_id']][$task['job_priority'] + 1] = $workflow_rule_id;
            array_push($task_rule_array, $task_rule_id);

        }

        /*
        // CJO의 경우 콘텍스트 메뉴별로 권한이 아닌 커스텀 권한이라 주석처리 - 2017.12.29 Alex
        if ($workflow_type == 'c') {
            $code_id = add_code($name);
            $db->exec("
                INSERT INTO CONTEXT_MENU
                    (CODE_ID, WORKFLOW_ID)
                VALUES
                    ($code_id, $workflow_id)
            ");
        }
        */
        add_task_rule_to_module($task_rule_array);
    }

    echo json_encode(array(
        'success' => true
    ));
}

function edit_workflow($name, $register, $description, $activity, $task_workflow_id, $content_status, $preset_type, $icon_url,$bs_content_id){
    global $db;

    $ori_info = $db->queryRow("SELECT * FROM BC_TASK_WORKFLOW WHERE TASK_WORKFLOW_ID = $task_workflow_id");

//	Remove. workflow dupli check
//	if($ori_info['register'] != $register)
//	{
//		$check_query = "SELECT COUNT(REGISTER) FROM BC_TASK_WORKFLOW WHERE REGISTER = '$register'";
//		$r = $db->queryOne($check_query);
//		if($r != 0)
//		{
//			echo json_encode(array(
//				'success' => false,
//				'msg' => _text('MSG01025')
//			));
//			exit;
//		}
//	}

    //Old Update method
//	$edit_query = "
//			UPDATE	BC_TASK_WORKFLOW
//			SET		USER_TASK_NAME = '$name',
//					REGISTER = '$register',
//					DESCRIPTION = '$description',
//					ACTIVITY = '$activity' ,
//					CONTENT_STATUS = '$content_status',
//					PRESET_TYPE = '$preset_type',
//					ICON_URL = '".$icon_url."',
//					BS_CONTENT_ID=".$bs_content_id."
//			WHERE	TASK_WORKFLOW_ID = $task_workflow_id";
//	$r = $db->exec($edit_query);

    //New Update method for check number type
    $update_data = array(
        'USER_TASK_NAME' => $name,
        'REGISTER' => $register,
        'DESCRIPTION' => $description,
        'ACTIVITY' => $activity,
        'CONTENT_STATUS' => $content_status,
        'PRESET_TYPE' => $preset_type,
        'ICON_URL' => $icon_url,
        'BS_CONTENT_ID' => $bs_content_id
    );
    $db->update('BC_TASK_WORKFLOW', $update_data, " TASK_WORKFLOW_ID = $task_workflow_id ");

    /*
    // CJO의 경우 콘텍스트 메뉴별로 권한이 아닌 커스텀 권한이라 주석처리 - 2017.12.29 Alex
    $code_query = "
            UPDATE	BC_CODE
            SET		NAME='$name'
            WHERE	ID = (	SELECT	CODE_ID
                            FROM	CONTEXT_MENU
                            WHERE	WORKFLOW_ID=$task_workflow_id )
    ";
    $r = $db->exec($code_query);
    */
    $btr_query = "
            UPDATE	BC_TASK_RULE
            SET		JOB_NAME = REPLACE(JOB_NAME,'_".$ori_info['user_task_name']."','_".$name."')
            WHERE	TASK_RULE_ID IN (
                        SELECT	TASK_RULE_ID
                        FROM	BC_TASK_WORKFLOW_RULE
                        WHERE	TASK_WORKFLOW_ID=$task_workflow_id
                    )
    ";
    $r = $db->exec($btr_query);

    echo json_encode(array(
        'success' => true
    ));
}

function delete_workflow($task_workflow_id){
    global $db;

    $workflow = $db->queryRow("SELECT * FROM BC_TASK_WORKFLOW WHERE TASK_WORKFLOW_ID = $task_workflow_id");
    $workflow_name = $workflow['user_task_name'];

    $db->exec("
                DELETE	FROM BC_TASK_RULE
                WHERE	JOB_NAME LIKE '%".$workflow_name."'
                AND   TASK_RULE_ID IN	(
                                            SELECT	TASK_RULE_ID
                                            FROM	BC_TASK_WORKFLOW_RULE
                                            WHERE	TASK_WORKFLOW_ID = ".$task_workflow_id."
                                        )
            ");
    //Agent에 맵핑된 작업도 삭제
    $db->exec("
            DELETE	FROM BC_TASK_AVAILABLE
            WHERE	TASK_RULE_ID IN (
                                        SELECT TASK_RULE_ID FROM BC_TASK_RULE          
                                        WHERE	JOB_NAME LIKE '%".$workflow_name."'
                                                AND   TASK_RULE_ID IN (
                                                                            SELECT	TASK_RULE_ID
                                                                            FROM	BC_TASK_WORKFLOW_RULE
                                                                            WHERE	TASK_WORKFLOW_ID = ".$task_workflow_id."
                                                                        )
                                    )
    ");
    $db->exec("DELETE FROM BC_TASK_WORKFLOW_RULE WHERE TASK_WORKFLOW_ID = $task_workflow_id");
    $db->exec("DELETE FROM BC_TASK_WORKFLOW WHERE TASK_WORKFLOW_ID = $task_workflow_id");


    /*
    // CJO의 경우 콘텍스트 메뉴별로 권한이 아닌 커스텀 권한이라 주석처리 - 2017.12.29 Alex
    if ($workflow['type'] == 'c') {
        $grant_value = $db->queryOne("
                            SELECT	B.CODE
                            FROM	CONTEXT_MENU A,
                                    BC_CODE B
                            WHERE	A.CODE_ID = B.ID
                            AND		A.WORKFLOW_ID = {$workflow['task_workflow_id']}
                        ");
        $grant_access = $db->queryAll("
                            SELECT	*
                            FROM	BC_GRANT
                            WHERE	GRANT_TYPE = 'content_grant'
                        ");
        // 이미 적용된 권한 확인 후 삭제
        foreach($grant_access as $grant){
            $group_grant		= $grant['group_grant'];
            $ud_content_id		= $grant['ud_content_id'];
            $member_group_id	= $grant['member_group_id'];
            if($group_grant & $grant_value) {
                $new_grant_value = (int)$group_grant - (int)$grant_value;
                if($new_grant_value > 0) {
                    $db->exec("
                        UPDATE	BC_GRANT
                        SET		GROUP_GRANT = $new_grant_value
                        WHERE	UD_CONTENT_ID = $ud_content_id
                        AND		MEMBER_GROUP_ID = $member_group_id
                        AND		GRANT_TYPE	= 'content_grant'
                    ");
                }
            }
        }
        $db->exec("DELETE FROM BC_CODE WHERE ID=(SELECT CODE_ID FROM CONTEXT_MENU WHERE WORKFLOW_ID = {$workflow['task_workflow_id']})");
        $db->exec("DELETE FROM CONTEXT_MENU WHERE WORKFLOW_ID = {$workflow['task_workflow_id']}");
    }
    */

    echo json_encode(array(
        'success' => true,
        'msg' => _text('MSG00017')
    ));
}

function add_task_rule($task_workflow_id, $task_rule_id, $task_rule_parent_id, $job_priority, $condition, $POST){
    global $db;

    $condition = empty($condition) ? 'null' : $condition;


    $workflow_rule_parent_id = empty($POST['workflow_rule_parent_id']) ? '0' : $POST['workflow_rule_parent_id'];

    $source_path_id = $POST['s_name'];
    $target_path_id = $POST['t_name'];
    $storage_group = $POST['storage_group'];
    $content_status = $POST['content_status'];

    if($task_rule_parent_id ==  $task_rule_id){
        echo json_encode(array(
            'success' => false,
            'msg' => _text('MSG02058')
        ));
        exit;
    }

    $insert_data = array(
            'TASK_WORKFLOW_ID'	=>	 $task_workflow_id,
            'TASK_RULE_ID'			=>	 $task_rule_id,
            'TASK_RULE_PARANT_ID'	=>	 $task_rule_parent_id,
            'JOB_PRIORITY'				=>	 $job_priority,
            'CONDITION'					=>	 $condition,
            'WORKFLOW_RULE_PARENT_ID'	=>	 $workflow_rule_parent_id,
            'SOURCE_PATH_ID'					=>	 $source_path_id,
            'TARGET_PATH_ID'					=>	 $target_path_id,
            'STORAGE_GROUP'					=>	 $storage_group,
            'CONTENT_STATUS'					=>	 $content_status
    );

    $db->insert('BC_TASK_WORKFLOW_RULE', $insert_data);


    //$add_query = "
        //INSERT	INTO BC_TASK_WORKFLOW_RULE
            //(TASK_WORKFLOW_ID, TASK_RULE_ID, TASK_RULE_PARANT_ID, JOB_PRIORITY , CONDITION, WORKFLOW_RULE_PARENT_ID, SOURCE_PATH_ID,TARGET_PATH_ID, STORAGE_GROUP , CONTENT_STATUS )
        //VALUES
            //($task_workflow_id, $task_rule_id, $task_rule_parent_id, $job_priority, '$condition' , $workflow_rule_parent_id, $source_path_id, $target_path_id, $storage_group, $content_status)
    //";


    //$r = $db->exec($add_query);

    echo json_encode(array(
        'success' => true,
        'msg' => _text('MSG00094')
    ));
}

function add_task_rule_to_module($task_rule_array) {
    global $db;

    $modules = $db->queryAll("
                    SELECT	*
                    FROM	BC_MODULE_INFO
                    WHERE	ACTIVE = '1'
                ");

    foreach($modules as $module) {
        $module_info_id = $module['module_info_id'];

        foreach($task_rule_array as $task_rule) {
            $task_rule_id = $task_rule;
            $is_exists = $db->queryRow("SELECT * FROM BC_TASK_AVAILABLE
                WHERE MODULE_INFO_ID=".$module_info_id." AND TASK_RULE_ID=".$task_rule_id);
            if(empty($is_exists)) {
                $query = "
                            INSERT INTO BC_TASK_AVAILABLE
                                (MODULE_INFO_ID, TASK_RULE_ID)
                            VALUES
                                ($module_info_id, $task_rule_id)
                        ";
                $r=$db->exec($query);
            }
        }
    }

    return true;

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


    $total_job_priority = $db->queryOne("
                            SELECT	MAX(JOB_PRIORITY)
                            FROM	BC_TASK_WORKFLOW_RULE
                            WHERE	TASK_WORKFLOW_ID = $task_workflow_id
                        ");

    $edit_query = "
            UPDATE	BC_TASK_WORKFLOW_RULE
            SET		TASK_RULE_PARANT_ID = $task_rule_parant_id,
                    JOB_PRIORITY = $job_priority
            WHERE	WORKFLOW_RULE_ID = $workflow_rule_id
    ";
    $r = $db->exec($edit_query);

    update_child_workflow( $task_workflow_id , $task_rule_id , $job_priority );

    echo json_encode(array(
        'success' => true,
        'msg' => _text('MSG00087')
    ));

}

function update_child_workflow( $task_workflow_id , $task_rule_id , $job_priority )
{
    global $db;

    $next_job_priority = $job_priority  + 1;

    $task_rule_parant_id = $task_rule_id ;

    $leaf_info = $db->queryAll("
                    SELECT	*
                    FROM	BC_TASK_WORKFLOW_RULE
                    WHERE	TASK_RULE_PARANT_ID = $task_rule_parant_id
                ");

    if( count($leaf_info) > 0 )
    {
        foreach($leaf_info as $leaf)
        {
            $workflow_rule_id = $leaf['workflow_rule_id'];

            $query = "
                UPDATE	BC_TASK_WORKFLOW_RULE
                SET		JOB_PRIORITY = $next_job_priority
                WHERE	WORKFLOW_RULE_ID = $workflow_rule_id
            ";
            $r = $db->exec($query);

            update_child_workflow($task_workflow_id , $leaf['task_rule_id'] , $next_job_priority );
        }
    }
    else
    {
        return true;
    }
}

function edit_workflow_rule($workflow_rule_id, $task_rule_id, $content_status, $source_storage_id, $target_storage_id) {
    global $db;

    $condition = empty($condition) ? 'NULL' : $condition;
    $set_str = '';
    if(!empty($source_storage_id)) {
        $set_str = $set_str.', SOURCE_PATH_ID = '.$source_storage_id;
    }

    if(!empty($target_storage_id)) {
        $set_str = $set_str.', TARGET_PATH_ID = '.$target_storage_id;
    }
    if($content_status == '') $content_status = 'null';
    $query = "
                UPDATE	BC_TASK_WORKFLOW_RULE
                SET		CONTENT_STATUS = $content_status
                        ".$set_str."
                WHERE	WORKFLOW_RULE_ID = '$workflow_rule_id'";
    $db->exec($query);

    $query = "
                UPDATE	BC_TASK_RULE
                SET		SOURCE_PATH = $source_storage_id,
                        TARGET_PATH = $target_storage_id
                WHERE	TASK_RULE_ID = $task_rule_id
            ";
    $db->exec($query);

    echo json_encode(array(
        'success' => true,
        'msg' => _text('MSG00087')
    ));
}

function del_task_rule($workflow_rule_id){
    global $db;

    $delete_query = "
        DELETE	FROM BC_TASK_WORKFLOW_RULE
        WHERE	WORKFLOW_RULE_ID = $workflow_rule_id
    ";
    $r = $db->exec($delete_query);

    echo json_encode(array(
        'success' => true,
        'msg' => _text('MSG00040')
    ));
}

//######################  (BC_TASK_RULE 테이블 추가, 수정, 삭제) #######################//

function add_rule($job_name, $parameter, $s_path, $t_path, $task_rule_id, $task_type_id, $source_opt, $target_opt){ //2011.12.14 김형기 수정
    global $db;

    $task_rule_id = $db->queryOne("SELECT MAX(TASK_RULE_ID) FROM BC_TASK_RULE")+1;

    $add_query = "
        INSERT	INTO BC_TASK_RULE
            (TASK_RULE_ID, JOB_NAME, TASK_TYPE_ID, PARAMETER, SOURCE_PATH, TARGET_PATH, SOURCE_OPT, TARGET_OPT)
        VALUES
            ('$task_rule_id', '$job_name', '$task_type_id', '$parameter', '$s_path', '$t_path', '$source_opt', '$target_opt')
    ";

    $r = $db->exec($add_query);

    echo json_encode(array(
        'success' => true,
        'msg' => _text('MSG02059')
    ));
}

function edit_rule($job_name, $parameter, $s_path, $t_path, $task_rule_id, $task_type_id, $source_opt, $target_opt){
    global $db;

    $edit_query = "
        UPDATE	BC_TASK_RULE
        SET		JOB_NAME = '$job_name',
                PARAMETER = '$parameter',
                SOURCE_PATH = '$s_path',
                TARGET_PATH = '$t_path',
                TASK_TYPE_ID = $task_type_id,
                SOURCE_OPT = '$source_opt',
                TARGET_OPT = '$target_opt'
        WHERE	TASK_RULE_ID = $task_rule_id
    ";
    $r = $db->exec($edit_query);

    echo json_encode(array(
        'success' => true,
        //'msg'=> $edit_query
        'msg' => _text('MSG02060')
    ));
}

function delete_rule($task_rule_id){
    global $db;

    $delete_query = "
        DELETE	BC_TASK_RULE
        WHERE	TASK_RULE_ID IN ({$task_rule_id})
    ";
    $r = $db->exec($delete_query);

    echo json_encode(array(
        'success' => true,
        'msg' => _text('MSG02061')
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

    $module_info_id = $db->queryOne("SELECT MAX(MODULE_INFO_ID) FROM BC_MODULE_INFO")+1;

    $query = "
        INSERT	INTO BC_MODULE_INFO
            (NAME, ACTIVE, MAIN_IP, SUB_IP, DESCRIPTION, MODULE_INFO_ID)
        VALUES
            ('$m_name', '$active', '$main_ip', '$sub_ip', '$description', $module_info_id)
    ";
    $r = $db->exec($query);

    //foreach($storage as $v)
    //{
    //	$query = "insert into bc_path_available (module_info_id, available_storage) values ('$module_info_id', '$v')";
    //	$r = $db->exec($query);
    //}

    foreach($task_rule as $v)
    {
        $query = "
            INSERT	INTO BC_TASK_AVAILABLE
                (MODULE_INFO_ID, TASK_RULE_ID)
            VALUES
                ($module_info_id, $v)";
        $r = $db->exec($query);
    }

    echo json_encode(array(
        'success' => true,
        'msg' => _text('MSG02062')
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

    $edit_query = "
        UPDATE	BC_MODULE_INFO
        SET		NAME = '$m_name',
                ACTIVE = '$active',
                MAIN_IP = '$main_ip',
                SUB_IP = '$sub_ip',
                DESCRIPTION = '$description'
        WHERE	MODULE_INFO_ID = $module_info_id
    ";
    $r = $db->exec($edit_query);
    // bc_TASK_AVAIABLE

    //path_available을 업데이트 하기전에 기존 데이터를 삭제
    $db->exec("DELETE BC_PATH_AVAILABLE WHERE MODULE_INFO_ID = $module_info_id");

    //foreach($storage as $v)
    //{
    //	$query = "insert into bc_path_available (module_info_id, available_storage) values ('$module_info_id', '$v')";
    //	$r = $db->exec($query);
    //}

    //path_available을 업데이트 하기전에 기존 데이터를 삭제
    $db->exec("DELETE BC_TASK_AVAILABLE WHERE MODULE_INFO_ID = $module_info_id");

    foreach($task_rule as $v)
    {
        $query = "INSERT INTO BC_TASK_AVAILABLE (MODULE_INFO_ID, TASK_RULE_ID) VALUES ($module_info_id, $v)";
        $r = $db->exec($query);
    }

    echo json_encode(array(
        'success' => true,
        'msg' => _text('MSG02063')
    ));
}

function delete_module($module_info_id){
    global $db;

    $delete_query = "DELETE BC_MODULE_INFO WHERE MODULE_INFO_ID = $module_info_id";
    $r = $db->exec($delete_query);
    $db->exec("DELETE BC_PATH_AVAILABLE WHERE MODULE_INFO_ID = $module_info_id");
    $db->exec("DELETE BC_TASK_AVAILABLE WHERE MODULE_INFO_ID = $module_info_id");

    echo json_encode(array(
        'success' => true,
        'msg' => _text('MSG00040')
    ));
}
//######################  (BC_STORAGE 테이블 추가, 수정, 삭제) #######################//

function add_storge($storage_path, $storage_name, $s_type, $s_id, $s_pw, $description){
    global $db;

    $storage_id = $db->queryOne("SELECT STORAGE_ID FROM BC_STORAGE ORDER BY STORAGE_ID DESC")+1;

    $add_query = "
        INSERT	INTO BC_STORAGE
            (STORAGE_ID, PATH, NAME, TYPE, LOGIN_ID, LOGIN_PW, DESCRIPTION)
        VALUES
            ('$storage_id', '$storage_path', '$storage_name', '$s_type', '$s_id', '$s_pw', '$description')
    ";
    $r = $db->exec($add_query);

    echo json_encode(array(
        'success' => true,
        'msg' => _text('MSG01024')
    ));
}

function edit_storage($storage_id, $type, $path, $name, $login_id, $login_pw, $description){
    global $db;

    $edit_query = "
            UPDATE	BC_STORAGE
            SET		TYPE = '$type',
                    PATH = '$path',
                    NAME = '$name',
                    LOGIN_ID = '$login_id',
                    LOGIN_PW = '$login_pw',
                    DESCRIPTION = '$description'
            WHERE	STORAGE_ID = $storage_id
    ";
    $r = $db->exec($edit_query);

    echo json_encode(array(
        'success' => true,
        'msg' => _text('MSG00087')
    ));
}

function delete_storage($storage_id){
    global $db;

    $delete_query = "DELETE BC_STORAGE WHERE STORAGE_ID = $storage_id";
    $r = $db->exec($delete_query);

    echo json_encode(array(
        'success' => true,
        'msg' => _text('MSG00017')//'삭제성공'
    ));
}
?>
