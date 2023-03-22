<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
fn_checkAuthPermission($_SESSION);
$user_id = $_SESSION['user']['user_id'];

switch($_REQUEST['action']) {
    case 'task_workflow':
		get_workflow_list();
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
		if( $user_id =='temp' || empty($user_id) ) throw new Exception(_text('MSG02041'));//'세션이 만료되어 로그인이 필요합니다.'
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
		$r = $db->exec("delete from BC_UD_STORAGE_GROUP_MAP where storage_group_id='$ud_storage_group_id' and  source_storage_id = '$source_storage_id' ");
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
	$check = $db->queryRow("select * from BC_UD_STORAGE_GROUP_MAP where STORAGE_GROUP_ID = '$ud_storage_group_id' and SOURCE_STORAGE_ID = '$src_storage_id' ");
	if( empty($_POST['src_storage_id']) || empty($_POST['trg_storage_id']) || empty($_POST['ud_storage_group_id']) || !empty($check) ) {
		die( json_encode(array(
			'success' => false,
			'msg' => 'empty data'
		)));
	}

	$r =$db->exec("insert into BC_UD_STORAGE_GROUP_MAP (STORAGE_GROUP_ID , SOURCE_STORAGE_ID,  UD_STORAGE_ID) values ('$ud_storage_group_id','$src_storage_id','$trg_storage_id') ");

	die( json_encode(array(
		'success' => true,
		'msg' => '설정 추가 성공'
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
		$r = $db->exec("delete from BC_UD_STORAGE_GROUP where storage_group_id='$ud_storage_group_id' ");
		$r = $db->exec("delete from BC_UD_STORAGE_GROUP_MAP where storage_group_id='$ud_storage_group_id' ");
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

		$r = $db->exec("update BC_UD_STORAGE_GROUP set STORAGE_GROUP_NM='$ud_storage_group_nm' where STORAGE_GROUP_ID='$ud_storage_group_id' ");

		die( json_encode(array(
			'success' => true,
			'msg' => '그룹 수정 성공'
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

		$next_id = $db->queryOne("select max(STORAGE_GROUP_ID)+1 from BC_UD_STORAGE_GROUP");

		$r = $db->exec("insert into BC_UD_STORAGE_GROUP ( STORAGE_GROUP_ID,STORAGE_GROUP_NM) values ('$next_id','$ud_storage_group_nm') ");

		die( json_encode(array(
			'success' => true,
			'msg' => '그룹 추가 성공'
		)));
}

function get_ud_storage_group(){

	global $db;
	$lists = $db->queryAll("select g.STORAGE_GROUP_ID,g.STORAGE_GROUP_NM,g.STORAGE_GROUP_ID no from BC_UD_STORAGE_GROUP g where g.storage_group_id > 0 order by g.storage_group_id");

	foreach($lists as $key => $val)
	{
		$lists[$key]['id'] = $val['storage_group_id'];
		$lists[$key]['icon'] = '/led-icons/folder.gif';

		$ch_lists = $db->queryAll("
		select
			g.STORAGE_GROUP_ID, gm.source_storage_id no,gm.source_storage_id,gm.ud_storage_id ,
			src_s.type src_type,src_s.path src_path, src_s.name STORAGE_GROUP_NM,tgt_s.type ud_type,tgt_s.path ud_path,
			tgt_s.name ud_name
		from
			BC_UD_STORAGE_GROUP g,BC_UD_STORAGE_GROUP_MAP gm,BC_STORAGE src_s,BC_STORAGE tgt_s
		where g.storage_group_id=gm.storage_group_id(+)
			and gm.source_storage_id=src_s.storage_id (+)
			and gm.ud_storage_id=tgt_s.storage_id (+)
			and gm.storage_group_id='{$val['storage_group_id']}'
		order by gm.source_storage_id");

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

    $where = ' where '.join(' and ', $where_array );

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

  	$query ="select a.module_info_id module_info_idx ,
(select name from bc_task_type where task_type_id = a.task_type_id) name,
(select type from bc_task_type where task_type_id = a.task_type_id) type
from bc_task_available a
where module_info_id=$module_info_id";

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
	$query = "select tr.job_name, tt.type, tt.name
			 from bc_task_available ta, bc_task_rule tr, bc_task_type tt
			 where ta.task_rule_id = tr.task_rule_id and tr.task_type_id = tt.task_type_id and ta.module_info_id = $module_info_id";

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
	//$query ="select tt.name, tt.type, ('[' || tt.type || '] ' || tt.name) as type_and_name, tt.task_type_id from bc_task_type tt order by to_number(tt.type)";
	$query = "
		SELECT	tt.name, tt.type, ('[' || tt.type || '] ' || tt.name) AS type_and_name, tt.task_type_id
		FROM		bc_task_type tt
		ORDER BY CAST(tt.type AS DOUBLE PRECISION)
	";

	$get_info = $db->queryAll($query);

//print_r($get_info);
    echo json_encode(array(
        'success' => true,
        'data' => $get_info
    ));
}

function get_workflow_list() {
    global $db;

    $all = $db->queryAll("select task_workflow_id, user_task_name, register, description, activity ,content_status  from bc_task_workflow order by register,user_task_name, task_workflow_id asc");

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

function get_task_rule() {
    global $db;

	 $all = $db->queryAll("select task_rule_id, job_name, source_path, target_path from bc_task_rule order by job_name asc");

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
	//$query = "select tr.*,
					 //tt.name type_name, tt.type type, ('[' || tt.type || '] ' || tt.name) as type_and_name,
					 //(select path from bc_storage where storage_id = tr.source_path) src_path,
					 //(select name from bc_storage where storage_id = tr.source_path) s_name,
					 //(select path from bc_storage where storage_id = tr.target_path) tar_path,
					 //(select name from bc_storage where storage_id = tr.target_path) t_name
			  //from bc_task_rule tr left outer join bc_task_type tt on tr.task_type_id = tt.task_type_id order by TO_NUMBER(tt.type) , tr.job_name, tr.task_rule_id asc";
	$query = "
		SELECT		TR.*,
						TT.NAME AS TYPE_NAME,
						TT.TYPE AS TYPE,
						('[' || TT.TYPE || ']' || TT.NAME) AS TYPE_AND_NAME,
						(
						SELECT	PATH
						FROM	BC_STORAGE
						WHERE	STORAGE_ID = CAST(TR.SOURCE_PATH AS DOUBLE PRECISION)
						) AS SRC_PATH,
						(
						SELECT	NAME
						FROM	BC_STORAGE
						WHERE	STORAGE_ID = CAST(TR.SOURCE_PATH AS DOUBLE PRECISION)
						) AS S_NAME,
						(
						SELECT	PATH
						FROM	BC_STORAGE
						WHERE	STORAGE_ID = CAST(TR.TARGET_PATH AS DOUBLE PRECISION)
						) AS TAR_PATH,
						(
						SELECT	NAME
						FROM	BC_STORAGE
						WHERE	STORAGE_ID = CAST(TR.TARGET_PATH AS DOUBLE PRECISION)
						) AS T_NAME
			FROM		BC_TASK_RULE TR
							LEFT JOIN BC_TASK_TYPE TT
							ON	TR.TASK_TYPE_ID = TT.TASK_TYPE_ID
			ORDER BY CAST(tt.type AS DOUBLE PRECISION) ASC

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

	$query = "select * from bc_task_type order by task_type_id asc";
	$get_task_type_list = $db->queryAll($query);

	 echo json_encode(array(
        'success' => true,
        'data' => $get_task_type_list
    ));
}

function get_module_list() {

	global $db;
	$query = "select name,module_info_id,module_id from bc_module_info where active='1' order by module_info_id asc";
	$get_module = $db->queryAll($query);

	$i = 0;
	foreach($get_module as $storage)
	{
		$query = "select
						(select name from bc_storage where storage_id = pa.available_storage) as s_name ,
						(select storage_id from bc_storage where storage_id = pa.available_storage) as s_num
					from
						bc_path_available pa
					where
						module_info_id = {$storage['module_info_id']}";

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

	$query = "select * from bc_module_info order by module_info_id asc";
	$get_module = $db->queryAll("$query");

	$i = 0;
	foreach($get_module as $storage)
	{
		$query = "select
						(select name from bc_storage where storage_id = pa.available_storage) as s_name ,
						(select storage_id from bc_storage where storage_id = pa.available_storage) as s_num
					from
						bc_path_available pa
					where
						module_info_id = {$storage['module_info_id']}";

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

	$query = "select * from bc_storage order by name asc";

	 $all = $db->queryAll("$query");

    echo json_encode(array(
        'success' => true,
        'data' => $all
    ));
}
?>