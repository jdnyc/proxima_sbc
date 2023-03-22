<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
fn_checkAuthPermission($_SESSION);

$workflow_type = $_REQUEST['workflow_type'];
$preset_type = $_REQUEST['preset_type'];

switch($_REQUEST['action']) {
	case 'get_icon':
		get_icon();
	break;
	case 'task_workflow':
		get_workflow_list($workflow_type);
	break;

	case 'task_workflow_type':
		get_preset_type_list($workflow_type, $preset_type);
	break;

	case 'task_rule':
		get_task_rule();
	break;

	case 'task_rule_list':
		task_rule_list();
	break;

	case 'get_available_task_list':
		get_available_task_list();
	break;

	case 'sm_task_type_list':
		get_available_task_list2();
	break;

	case 'get_bc_module_info':
		get_module_info();
	break;

	case 'module_info':
		get_module_info();
	break;

	case 'module_list':
		get_module_list();
	break;

	case 'task_type_list':
		task_type_list();
	break;

	case 'storage_list':
		get_storage_info();
	break;

	case 'get_available_task_list_by_module':
		get_available_task_list_by_module();
	break;

	case 'content_status_type_list':
		content_status_type_list();
	break;

	case 'storage_group':
		$list = getCodeInfo( 'storage_group' );
		$list[] = array('code'=> '3' , 'name'=> 'OD / AD' );
		$list[] = array('code'=> '' , 'name'=> 'All' );
		echo json_encode(array(
			'success' => true,
			'data' => $list
		));
	break;

	case 'get_ud_storage_group':
		$lists = get_ud_storage_group();
		echo json_encode($lists);
	break;

	case 'add_ud_storage_group':
		add_ud_storage_group();
	break;

	case 'edit_ud_storage_group':
		edit_ud_storage_group();
	break;

	case 'del_ud_storage_group':
		del_ud_storage_group();
	break;

	case 'add_set_storage_group':
		add_set_storage_group();
	break;

	case 'del_set_storage_group':
		del_set_storage_group();
	break;

	default:
		echo json_encode(array(
			'success' => false,
			'msg' => 'no more action'
		));
	break;
}

function get_icon(){
	global $db;

	$user_lang = $_SESSION['user']['lang'];

	if( $user_lang == 'en' ){
		$code_name = 'ename';
	}else if( $user_lang == 'etc' ){
		$code_name = 'other';
	}else{
		$code_name = 'name';
	}

	$query = "
		SELECT	C.ID AS CODE_ID, C.CODE, C.CODE_TYPE_ID, C.".$code_name."  AS CODE_NAME
		FROM		BC_CODE C, BC_CODE_TYPE T
		WHERE	C.CODE_TYPE_ID = T.ID
		AND		T.CODE = 'icons'
	";
	$icons = $db->queryAll($query);

	echo json_encode(array(
			'success' => true,
			'total' => count($icons),
			'data' => $icons
	));
}

function del_set_storage_group(){
	global $db;

	$ud_storage_group_ids = json_decode($_POST['ud_storage_group_ids'],true);
	if( empty($_POST['ud_storage_group_ids']) ) {
		die( json_encode(array(
			'success' => false,
			'msg' => 'empty data'
		)));
	}

	foreach($ud_storage_group_ids as $key => $val)
	{
		$ud_storage_group_id = $val['storage_group_id'];
		$source_storage_id = $val['source_storage_id'];
		$r = $db->exec("
				DELETE	FROM BC_UD_STORAGE_GROUP_MAP
				WHERE	STORAGE_GROUP_ID = $ud_storage_group_id
				AND		SOURCE_STORAGE_ID = $source_storage_id
			");
	}

	die( json_encode(array(
		'success' => true,
		'msg' => _text('MN00129')//'삭제 성공'//(그룹)
	)));
}

function add_set_storage_group(){
	global $db;
	$src_storage_id = $_POST['src_storage_id'];
	$trg_storage_id = $_POST['trg_storage_id'];
	$ud_storage_group_id = $_POST['ud_storage_group_id'];
	$check = $db->queryRow("SELECT * FROM BC_UD_STORAGE_GROUP_MAP WHERE STORAGE_GROUP_ID = $ud_storage_group_id AND SOURCE_STORAGE_ID = $src_storage_id");
	if( empty($_POST['src_storage_id']) || empty($_POST['trg_storage_id']) || empty($_POST['ud_storage_group_id']) || !empty($check) ) {
		die( json_encode(array(
			'success' => false,
			'msg' => 'empty data'
		)));
	}

	$r =$db->exec("
			INSERT	INTO BC_UD_STORAGE_GROUP_MAP
				(STORAGE_GROUP_ID , SOURCE_STORAGE_ID,  UD_STORAGE_ID)
			VALUES
				($ud_storage_group_id, $src_storage_id, $trg_storage_id)
		");

	die( json_encode(array(
		'success' => true,
		'msg' => _text('MSG01024')
	)));
}

function del_ud_storage_group(){
	global $db;

	$ud_storage_group_ids = json_decode($_POST['ud_storage_group_ids'],true);
	if( empty($_POST['ud_storage_group_ids']) ) {
		die( json_encode(array(
			'success' => false,
			'msg' => 'empty data'
		)));
	}
	foreach($ud_storage_group_ids as $ud_storage_group_id)
	{
		$r = $db->exec("DELETE FROM BC_UD_STORAGE_GROUP WHERE STORAGE_GROUP_ID = $ud_storage_group_id");
		$r = $db->exec("DELETE FROM BC_UD_STORAGE_GROUP_MAP WHERE STORAGE_GROUP_ID= $ud_storage_group_id ");
	}

	die( json_encode(array(
		'success' => true,
		'msg' => _text('MN00129')//'삭제 성공'//(그룹)
	)));
}

function edit_ud_storage_group(){
		global $db;
		$ud_storage_group_nm = $db->escape(trim($_POST['ud_storage_group_nm']));
		$ud_storage_group_id = $_POST['ud_storage_group_id'];

		if( empty($_POST['ud_storage_group_nm']) || empty($_POST['ud_storage_group_id']) ) {
			die( json_encode(array(
				'success' => false,
				'msg' => 'empty data'
			)));
		}

		$r = $db->exec("
				UPDATE	BC_UD_STORAGE_GROUP
				SET		STORAGE_GROUP_NM = '$ud_storage_group_nm'
				WHERE	STORAGE_GROUP_ID = $ud_storage_group_id
			");

		die( json_encode(array(
			'success' => true,
			'msg' => _text('MSG00087')
		)));
}
function add_ud_storage_group(){
		global $db;
		$ud_storage_group_nm = $db->escape(trim($_POST['ud_storage_group_nm']));

		if( empty($_POST['ud_storage_group_nm'])) {
			die( json_encode(array(
				'success' => false,
				'msg' => 'empty data'
			)));
		}

		$next_id = $db->queryOne("SELECT MAX(STORAGE_GROUP_ID)+1 FROM BC_UD_STORAGE_GROUP");

		$r = $db->exec("
				INSERT	INTO BC_UD_STORAGE_GROUP
					(STORAGE_GROUP_ID, STORAGE_GROUP_NM)
				VALUES
					($next_id,'$ud_storage_group_nm')
			");

		die( json_encode(array(
			'success' => true,
			'msg' => _text('MSG01024')
		)));
}

function get_ud_storage_group(){

	global $db;
	$lists = $db->queryAll("
				SELECT	G.STORAGE_GROUP_ID, G.STORAGE_GROUP_NM, G.STORAGE_GROUP_ID AS NO
				FROM	BC_UD_STORAGE_GROUP G
				WHERE	G.STORAGE_GROUP_ID > 0
				ORDER BY G.STORAGE_GROUP_ID
			");

	foreach($lists as $key => $val)
	{
		$lists[$key]['id'] = $val['storage_group_id'];
		$lists[$key]['icon'] = '/led-icons/folder.gif';

		$ch_lists = $db->queryAll("
			SELECT
				G.STORAGE_GROUP_ID, GM.SOURCE_STORAGE_ID NO,GM.SOURCE_STORAGE_ID,GM.UD_STORAGE_ID ,
				SRC_S.TYPE SRC_TYPE,SRC_S.PATH SRC_PATH, SRC_S.NAME STORAGE_GROUP_NM,TGT_S.TYPE UD_TYPE,TGT_S.PATH UD_PATH,
				TGT_S.NAME UD_NAME
			FROM
				BC_UD_STORAGE_GROUP G,BC_UD_STORAGE_GROUP_MAP GM,BC_STORAGE SRC_S,BC_STORAGE TGT_S
			WHERE G.STORAGE_GROUP_ID=GM.STORAGE_GROUP_ID(+)
				AND GM.SOURCE_STORAGE_ID=SRC_S.STORAGE_ID (+)
				AND GM.UD_STORAGE_ID=TGT_S.STORAGE_ID (+)
			AND GM.STORAGE_GROUP_ID = {$val['storage_group_id']}
			ORDER BY GM.SOURCE_STORAGE_ID
		");

		if( !empty($ch_lists) ){
			foreach($ch_lists as $chkey=> $child)
			{
				$ch_lists[$chkey]['id'] =  $child['storage_group_id'].'-'.$child['source_storage_id'];
				$ch_lists[$chkey]['icon'] = '/led-icons/folder.gif';
				//$lists[$chkey]['expanded'] = false;
				$ch_lists[$chkey]['leaf'] =  true;
				$ch_lists[$chkey]['no']='-';
			}
			$lists[$key]['expanded'] = true;
			$lists[$key]['children'] = $ch_lists;
			$lists[$key]['leaf'] = false;
		}else{
			$lists[$key]['expanded'] = false;
			$lists[$key]['leaf'] = true;
		}
	}
	return $lists;
}

function getCodeInfo( $code_type = null , $code = null )
{
	global $db;

	$where_array = array();

	$where_array [] = " c.code_type_id=ct.id ";

	if( !is_null($code_type) )
	{
		$where_array [] = " ct.code='$code_type' ";
	}

	if( !is_null($code) )
	{
		$where_array [] = " c.code='$code' ";
	}

	$order = " order by c.code asc";

	$where = ' where '.join(' and ', $where_array );

	$codelist = $db->queryAll("select c.code, c.name from bc_code c, bc_code_type ct ".$where.$order);

	return $codelist;
}

function getCodeInfoLang( $lang = null, $code_type = null , $code = null  , $codename = null)
{
	global $db;

	$where_array = array();

	$where_array [] = " c.code_type_id=ct.id ";

	if( !is_null($code_type) )
	{
		$where_array [] = " ct.code='$code_type' ";
	}

	if( !is_null($code) )
	{
		$where_array [] = " c.code='$code' ";
	}

	if( !is_null($codename) )
	{
		$where_array [] = " c.name='$codename' ";
	}

	 if( !is_null($lang) )
	{
		 if( $lang == 'en' ){
			 $select_name = " c.ename as name";
		 }else{
			 $select_name = " c.name ";
		 }
	}

	$order = " order by c.code asc";

	$where = ' where use_yn=\'Y\' and '.join(' and ', $where_array );

	$codelist = $db->queryAll("select c.code, ". $select_name." from bc_code c, bc_code_type ct ".$where.$order);

	return $codelist;
}

//2012.3.21 콘텐츠 상태 정보 리스트
function content_status_type_list(){
	global $db;
	//$code_type ='CONTENT_STATUS';
	//$query = "select c.name, c.code from bc_code c, bc_code_type ct where c.code_type_id=ct.id and ct.code='$code_type' order by name";

	//$get_info = $db->queryAll($query);

    $get_info = getCodeInfoLang($_SESSION['user']['lang'],'CONTENT_STATUS');

	echo json_encode(array(
		'success' => true,
		'data' => $get_info
	));
}



function get_available_task_list2(){
	global $db;
	$module_info_id = $_POST['module_info_id'];

	$query ="
			SELECT	A.MODULE_INFO_ID MODULE_INFO_IDX ,
					(SELECT NAME FROM BC_TASK_TYPE WHERE TASK_TYPE_ID = A.TASK_TYPE_ID) NAME,
					(SELECT TYPE FROM BC_TASK_TYPE WHERE TASK_TYPE_ID = A.TASK_TYPE_ID) TYPE
			FROM	BC_TASK_AVAILABLE A
			WHERE	MODULE_INFO_ID = $module_info_id
	";

	$get_info = $db->queryAll($query);

//print_r($get_info);
	echo json_encode(array(
		'success' => true,
		'data' => $get_info
	));
}

//2011.12.14 김형기 추가
function get_available_task_list_by_module(){
	global $db;
	$module_info_id = $_POST['module_info_id'];
	$query = "
			SELECT	TR.JOB_NAME, TT.TYPE, TT.NAME
			FROM	BC_TASK_AVAILABLE TA, BC_TASK_RULE TR, BC_TASK_TYPE TT
			WHERE	TA.TASK_RULE_ID = TR.TASK_RULE_ID
			AND		TR.TASK_TYPE_ID = TT.TASK_TYPE_ID
			AND		TA.MODULE_INFO_ID = $module_info_id
	";

	$get_info = $db->queryAll($query);

	echo json_encode(array(
		'success' => true,
		'data' => $get_info
	));
}


function get_available_task_list(){
  global $db;

  /* 2011.12.14 김형기 제거
  	$module_info_id = $_POST['module_info_id'];

  	$query ="select a.* ,
(select name from bc_task_type where task_type_id = a.task_type_id) name,
(select type from bc_task_type where task_type_id = a.task_type_id) type
from bc_task_available a
where module_info_id=$module_info_id";
  	*/
	//수정일 : 2011.12.17
	//작성자 : 김형기
	//내용 : 작업 타입과 이름 동시에 나오는 필드 추가
	$query ="SELECT TT.NAME, TT.TYPE, ('[' || TT.TYPE || '] ' || TT.NAME) AS TYPE_AND_NAME, TT.TASK_TYPE_ID FROM BC_TASK_TYPE TT ORDER BY TO_NUMBER(TT.TYPE)";

	$get_info = $db->queryAll($query);

//print_r($get_info);
	echo json_encode(array(
		'success' => true,
		'data' => $get_info
	));
}

function get_workflow_list($workflow_type) {
	global $db;

	$all = $db->queryAll("
		SELECT	A.TASK_WORKFLOW_ID
				,A.USER_TASK_NAME
				,A.REGISTER
				,A.DESCRIPTION
				,A.ACTIVITY
				,A.CONTENT_STATUS
				,A.TYPE
				,A.PRESET_TYPE
				,A.ICON_URL
				,A.BS_CONTENT_ID
				,A.CREATOR
				,CASE
					WHEN A.BS_CONTENT_ID=0
						THEN 'ALL'
						ELSE B.BS_CONTENT_TITLE
				END AS BS_CONTENT_TITLE
		FROM	BC_TASK_WORKFLOW A
				LEFT OUTER JOIN
				BC_BS_CONTENT B
				ON (A.BS_CONTENT_ID=B.BS_CONTENT_ID)
		WHERE	A.TYPE = '".$workflow_type."'
		ORDER BY A.TASK_WORKFLOW_ID ASC
	");

	$status_list = getCodeInfoLang($_SESSION['user']['lang'],'CONTENT_STATUS');

	foreach($all as $key => $val)
	{
		$all[$key]['content_status_nm'] = '';

		foreach($status_list as $list)
		{
			if($val['content_status'] == $list['code'])
			{
				$all[$key]['content_status_nm'] =  $list['name'];
			}
		}
	}

	echo json_encode(array(
		'success' => true,
		'total' => count($all),
		'data' => $all
	));
}

function get_preset_type_list($workflow_type, $preset_type) {
	global $db;

/* 	$query = "
			SELECT TASK_WORKFLOW_ID, USER_TASK_NAME, REGISTER, DESCRIPTION,
			ACTIVITY ,CONTENT_STATUS, TYPE, WORKFLOW_TYPE
			FROM BC_TASK_WORKFLOW
			WHERE TYPE = '$workflow_type'
			AND	WORKFLOW_TYPE = '$type'
			ORDER BY TASK_WORKFLOW_ID ASC
			";
	file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/proxima_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")."\r\n".$query."\r\n", FILE_APPEND);

    $all = $db->queryAll($query); */
    
	$all = $db->queryAll("
				SELECT	TASK_WORKFLOW_ID, USER_TASK_NAME, REGISTER, DESCRIPTION,
						ACTIVITY, CONTENT_STATUS, TYPE, PRESET_TYPE
				FROM	BC_TASK_WORKFLOW
				WHERE	TYPE = '$workflow_type'
				--AND		(PRESET_TYPE = '$preset_type'
				AND 	ACTIVITY = '1'
				ORDER BY TASK_WORKFLOW_ID ASC
			");

	$status_list = getCodeInfo('CONTENT_STATUS');

	$status_list = getCodeInfo('CONTENT_STATUS');
	foreach ($all as $key => $val) {
		$all[$key]['content_status_nm'] = '';
		foreach ($status_list as $list) {
			if ($val['content_status'] == $list['code']) {
				$all[$key]['content_status_nm'] =  $list['name'];
			}
		}
	}

	echo json_encode(array(
			'success' => true,
			'total' => count($all),
			'data' => $all
	));
}


function get_task_rule() {
	global $db;

	 $all = $db->queryAll("SELECT TASK_RULE_ID, JOB_NAME, SOURCE_PATH, TARGET_PATH FROM BC_TASK_RULE ORDER BY JOB_NAME ASC");

	echo json_encode(array(
		'success' => true,
		'data' => $all
	));
}

function task_rule_list() {
	global $db;
	//2011.12.14 이전
	/*
	$query = "select tr.*,(select name from bc_task_type where bc_task_type.type = tr.type) type_name,  (select path from bc_storage where storage_id = tr.source_path) src_path, (select name from bc_storage where storage_id = tr.source_path) s_name, (select name from bc_storage where storage_id = tr.target_path) t_name,
		  (select path from bc_storage where storage_id = tr.target_path) tar_path, (select name from bc_module_info where module_info_id=tr.module_info_id) m_name  from bc_task_rule tr order by task_rule_id asc";
	*/
	//수정일 : 2011.12.17
	//작성자 : 김형기
	//내용 : 작업유형 타입과 이름이 동시에 나오는 필드 추가
	$query = "
		SELECT	TR.*,
				TT.NAME TYPE_NAME, TT.TYPE TYPE, ('[' || TT.TYPE || '] ' || TT.NAME) AS TYPE_AND_NAME,
				(SELECT PATH FROM BC_STORAGE WHERE STORAGE_ID = TR.SOURCE_PATH) SRC_PATH,
				(SELECT NAME FROM BC_STORAGE WHERE STORAGE_ID = TR.SOURCE_PATH) S_NAME,
				(SELECT PATH FROM BC_STORAGE WHERE STORAGE_ID = TR.TARGET_PATH) TAR_PATH,
				(SELECT NAME FROM BC_STORAGE WHERE STORAGE_ID = TR.TARGET_PATH) T_NAME
		FROM	BC_TASK_RULE TR
				LEFT OUTER JOIN BC_TASK_TYPE TT
				ON TR.TASK_TYPE_ID = TT.TASK_TYPE_ID
		ORDER BY TASK_RULE_ID ASC
	";

	 $all = $db->queryAll("$query");

	echo json_encode(array(
		'success' => true,
		'data' => $all
	));
}

function task_type_list()
{
	global $db;

	$query = "SELECT * FROM BC_TASK_TYPE ORDER BY TASK_TYPE_ID ASC";
	$get_task_type_list = $db->queryAll($query);

	 echo json_encode(array(
		'success' => true,
		'data' => $get_task_type_list
	));
}

function get_module_list() {

	global $db;
	$query = "SELECT NAME,MODULE_INFO_ID,MODULE_ID FROM BC_MODULE_INFO WHERE ACTIVE = '1' ORDER BY MODULE_INFO_ID ASC";
	$get_module = $db->queryAll($query);

	$i = 0;
	foreach($get_module as $storage)
	{
		$query = "
			SELECT	(SELECT NAME FROM BC_STORAGE WHERE STORAGE_ID = PA.AVAILABLE_STORAGE) AS S_NAME ,
					(SELECT STORAGE_ID FROM BC_STORAGE WHERE STORAGE_ID = PA.AVAILABLE_STORAGE) AS S_NUM
			FROM	BC_PATH_AVAILABLE PA
			WHERE	MODULE_INFO_ID = {$storage['module_info_id']}
		";

		$get_info = $db->queryAll($query);

		$all_storage= '';
		if(!empty($get_info))
		{
			foreach($get_info as $info)
			{
				$all_storage .= $info['s_name'].', ';

				//$get_module[$i]['s_'.$info['s_num']] = $info['s_num'];
			}
			$all_storage = rtrim($all_storage, ', ');

		}
		$get_module[$i]['allow_storage'] = $all_storage;

		$i++;
	}


//print_r($get_module);
	echo json_encode(array(
		'success' => true,
		'data' => $get_module
	));
}

function get_module_info() {
	global $db;

	$query = "SELECT * FROM BC_MODULE_INFO ORDER BY MODULE_INFO_ID ASC";
	$get_module = $db->queryAll("$query");

	$i = 0;
	foreach($get_module as $storage)
	{
		$query = "
			SELECT	(SELECT NAME FROM BC_STORAGE WHERE STORAGE_ID = PA.AVAILABLE_STORAGE) AS S_NAME ,
					(SELECT STORAGE_ID FROM BC_STORAGE WHERE STORAGE_ID = PA.AVAILABLE_STORAGE) AS S_NUM
			FROM	BC_PATH_AVAILABLE PA
			WHERE	MODULE_INFO_ID = {$storage['module_info_id']}
		";

		$get_info = $db->queryAll($query);

		$all_storage= '';
		if(!empty($get_info))
		{
			foreach($get_info as $info)
			{
				$all_storage .= $info['s_name'].', ';

				//$get_module[$i]['s_'.$info['s_num']] = $info['s_num'];
			}
			$all_storage = rtrim($all_storage, ', ');

		}
		$get_module[$i]['allow_storage'] = $all_storage;

		$i++;
	}


//print_r($get_module);
	echo json_encode(array(
		'success' => true,
		'data' => $get_module
	));
}

function get_storage_info() {
	global $db;

	$query = "SELECT *  FROM BC_STORAGE ORDER BY STORAGE_ID ASC";

	 $all = $db->queryAll("$query");

	echo json_encode(array(
		'success' => true,
		'data' => $all
	));
}
?>
