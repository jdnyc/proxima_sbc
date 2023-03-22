<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
fn_checkAuthPermission($_SESSION);
try
{
	switch($_REQUEST['action'])
	{
		case 'bc_ud_content':
			get_meta_table();
		break;

		case 'table_field':
			get_custom_field($_POST['ud_content_id']);
			break;

		case 'table_list':
			get_table_list();
			break;

		case 'content_type_list':
			get_content_type_list();
		break;

		//2011.12.06 콘텐츠 자신 삭제일 불러오기
		case 'content_del_date_list':
			get_content_del_date_list();
		break;

		case 'contents_del_date_list':
			get_contents_del_date_list();
		break;

		case 'file_del_date_list':
			get_file_del_date_list();
		break;

		// 2010-11-08 추가 (컨테이너 추가 by CONOZ)
		case 'container_list':
			get_container_list($_POST['ud_content_id']);
		break;

		case 'ud_storage_list':
			ud_storage_list($_POST['ud_content_id'],$_POST['ud_type'] );
		break;

		// 사용자정의 콘텐츠별 ROOT 카테고리
		case 'ud_category_list':
			ud_category_list();
		break;

		// get root category and ud_content name
		case 'manage_category_list':
			manage_category_list();
		break;

		default:
			echo json_encode(array(
				'success' => false,
				'msg' => 'no more action 액션이 정의 되어있지 않습니다.'
			));
		break;
	}
}
catch (Exception $e)
{
	echo $db->last_query;
}

function ud_storage_list(){
	global $db;

	$storage = $db->queryAll("select storage_id , name tname, path from bc_storage " );

	foreach($storage as  $key => $val)
	{
		$storage[$key]['name'] = $val['tname'].'('.$val['path'].')';
	}

	echo json_encode(array(
		'success' => true,
		'data' => $storage
	));

}

function ud_category_list(){
	global $db;

	$category = $db->queryAll("SELECT CATEGORY_ID, CATEGORY_TITLE FROM BC_CATEGORY WHERE PARENT_ID = 0" );

	echo json_encode(array(
			'success' => true,
			'data' => $category
	));

}

function get_file_del_date_list()
{
	global $db;

	$query = "
		SELECT	C.ID, C.CODE, C.CODE_TYPE_ID, C.SORT, C.HIDDEN, C.REF1
					C.".get_code_name_field()." AS NAME
		FROM		BC_CODE C
		WHERE	CODE_TYPE_ID IN(
						SELECT	ID
						FROM		BC_CODE_TYPE CT
						WHERE	CT.CODE = 'FLDDDT'
					)
	";
	//$query = "select * from bc_code where code_type_id in (select id from bc_code_type ct where ct.code = 'FLDDDT')";
	$all = $db->queryAll($query);

	echo json_encode(array(
		'success' => true,
		'data' => $all
	));
}

//2011.12.06 콘텐츠 자신 삭제일 불러오기
function get_content_del_date_list()
{
	global $db;

	$query = "
		SELECT	C.ID, C.CODE, C.CODE_TYPE_ID, C.SORT, C.HIDDEN, C.REF1,
					C.".get_code_name_field()." AS NAME
		FROM		BC_CODE C
		WHERE	C.CODE_TYPE_ID IN(
						SELECT	ID
						FROM		BC_CODE_TYPE CT
						WHERE	CT.CODE = 'UCSDDT'
					)
	";

	//$query = "select * from bc_code where code_type_id  in (select id from bc_code_type ct where ct.code = 'UCSDDT')";
	$all = $db->queryAll($query);

	echo json_encode(array(
		'success' => true,
		'data' => $all
	));
}

function get_contents_del_date_list()
{
	global $db;

	$query = "select * from bc_code where code_type_id  in (select id from bc_code_type ct where ct.code = 'UCDDDT')";
	$all = $db->queryAll($query);

	echo json_encode(array(
		'success' => true,
		'data' => $all
	));
}

function get_meta_table()
{
	global $db;

	$query = "
		SELECT	BSC.BS_CONTENT_TITLE ORI_CONTENT_IDX, BSC.BS_CONTENT_ID, BSC.BS_CONTENT_TITLE, UDC.UD_CONTENT_ID,
				UDC.UD_CONTENT_TITLE, UDC.ALLOWED_EXTENSION, UDC.DESCRIPTION, UDC.SHOW_ORDER, UDC.EXPIRED_DATE AS CONTENT_EXPIRE_DATE,
				UDC.CON_EXPIRE_DATE AS CONTENTS_EXPIRE_DATE, UDC.UD_CONTENT_CODE AS UD_CONTENT_CODE, CM.CATEGORY_ID AS CATEGORY
				,(SELECT CATEGORY_TITLE FROM BC_CATEGORY WHERE CATEGORY_ID = CM.CATEGORY_ID) AS CATEGORY_NAME
				,(SELECT NAME FROM (SELECT * FROM BC_CODE WHERE CODE_TYPE_ID IN (SELECT ID FROM BC_CODE_TYPE WHERE CODE= 'UCSDDT')) Z WHERE Z.CODE = UDC.EXPIRED_DATE) E_DATE
				,(SELECT NAME FROM (SELECT * FROM BC_CODE WHERE CODE_TYPE_ID IN (SELECT ID FROM BC_CODE_TYPE WHERE CODE= 'UCDDDT')) Z WHERE Z.CODE = UDC.CON_EXPIRE_DATE) SE_DATE
		FROM	BC_BS_CONTENT BSC,
				BC_UD_CONTENT UDC
					LEFT JOIN BC_CATEGORY_MAPPING CM ON UDC.UD_CONTENT_ID = CM.UD_CONTENT_ID
		WHERE	BSC.BS_CONTENT_ID=UDC.BS_CONTENT_ID
		ORDER BY UDC.SHOW_ORDER
	";

	//$query = "SELECT BSC.BS_CONTENT_TITLE ORI_CONTENT_IDX, BSC.BS_CONTENT_ID, BSC.BS_CONTENT_TITLE, UDC.UD_CONTENT_ID,
						//UDC.UD_CONTENT_TITLE, UDC.ALLOWED_EXTENSION, UDC.DESCRIPTION, UDC.SHOW_ORDER, UDC.EXPIRED_DATE AS CONTENT_EXPIRE_DATE,
						//UDC.CON_EXPIRE_DATE AS CONTENTS_EXPIRE_DATE,
						//UDC.UD_CONTENT_CODE UD_CONTENT_CODE
			//,(SELECT NAME FROM (SELECT * FROM BC_CODE WHERE CODE_TYPE_ID IN (SELECT ID FROM BC_CODE_TYPE WHERE CODE= 'UCSDDT')) Z WHERE Z.CODE = UDC.EXPIRED_DATE) E_DATE
			//,(SELECT NAME FROM (SELECT * FROM BC_CODE WHERE CODE_TYPE_ID IN (SELECT ID FROM BC_CODE_TYPE WHERE CODE= 'UCDDDT')) Z WHERE Z.CODE = UDC.CON_EXPIRE_DATE) SE_DATE
			//FROM BC_BS_CONTENT BSC, BC_UD_CONTENT UDC, BC_CATEGORY_MAPPING CM
			//WHERE BSC.BS_CONTENT_ID=UDC.BS_CONTENT_ID
			//AND UDC.UD_CONTENT_ID=CM.UD_CONTENT_ID(+)
			//ORDER BY UDC.SHOW_ORDER";

	$all = $db->queryAll($query);

	for($i=0;$i<count($all) ; $i++)
	{
		$all[$i]['show_contents_expire_date'] = check_limit_code_to_date($all[$i]['contents_expire_date']);
		$ud_content_id = $all[$i]['ud_content_id'];
		$storage = $db->queryAll("select us_type, storage_id from BC_UD_CONTENT_STORAGE where ud_content_id ='$ud_content_id' ");

		foreach($storage as $st)
		{
			if( $st['us_type'] == 'highres' )
			{
				$highres = $st['storage_id'];
			}
			else if( $st['us_type'] == 'lowres' )
			{
				$lowres = $st['storage_id'];
			}
			else if( $st['us_type'] == 'upload' )
			{
				$upload = $st['storage_id'];
			}
		}

		$all[$i]['storage'] = array(
			'highres' => $highres,
			'lowres' => $lowres,
			'upload' => $upload
		);
	}

	echo json_encode(array(
		'success' => true,
		'total' => count($all),
		'data' => $all
	));
}

function get_custom_field($meta_table_id)
{
	global $db;

	// 2010-11-08 order by sort 수정 (컨테이너 추가 by CONOZ)
	//$all = $db->queryAll('select * from meta_field where meta_table_id = ' . $meta_table_id . ' order by sort');
//	$all = $db->queryAll('select * from meta_field where meta_table_id = ' . $meta_table_id . ' order by sort');

	$ud_content_id = $_POST['ud_content_id'];
	$container_id = $_POST['container_id'];

	//쿼리 변경 by 이성용 2011-05-23
	 //$all = $db->queryAll("select usrmf.*, cf.usr_meta_field_title as container_name
							//from bc_usr_meta_field usrmf,
								//(select usr_meta_field_title, usr_meta_field_id from bc_usr_meta_field where ud_content_id=$ud_content_id and usr_meta_field_type='container') cf
							//where usrmf.ud_content_id=$ud_content_id
							//--and usrmf.container_id(+)=cf.usr_meta_field_id
							//and usrmf.container_id=cf.usr_meta_field_id(+)
							//order by usrmf.show_order");
	$basic_container_id = $db->queryAll("
											SELECT 	USR_META_FIELD_ID
											FROM 	BC_USR_META_FIELD
											WHERE 	UD_CONTENT_ID = $ud_content_id
											ORDER BY USR_META_FIELD_ID ASC
												");
	$content_array = array();
	$container_name_array = array();
	if ($basic_container_id[0]['usr_meta_field_id'] == $container_id){

		array_push($container_name_array, _text('MN00249'));
		array_push($container_name_array, _text('MN00387'));
		array_push($container_name_array, _text('MN02149'));
		array_push($container_name_array, _text('MN02150'));
		array_push($container_name_array, _text('MN02217'));

		foreach ($container_name_array as $f) {
			$temp = array();
			$temp['ud_content_id'] = $ud_content_id;
			$temp['usr_meta_field_id'] = '';
			$temp['show_order'] = 0;
			$temp['usr_meta_field_title'] = $f;
			$temp['usr_meta_field_type'] = "textfield";
			$temp['is_required'] = 1;
			$temp['is_editable'] = 0;
			$temp['is_show'] = 1;
			if ($f ==  _text('MN00249')) {
				$temp['is_social'] = 1;
				$temp['num_line'] = 1;
			}else {
				$temp['is_social'] = 0;
				$temp['num_line'] = 0;
            }
            //HUIMAI, 제목이나 카테고리는 수정허용표시
            if ($f == _text('MN00249') || $f == _text('MN00387')) {
                $temp['is_editable'] = 1;
            }
			$temp['is_search_reg'] = 1;
			$temp['default_value'] = '';
			$temp['container_id'] = $ud_content_id;
			$temp['depth'] = 1;
			$temp['meta_group_type'] = '';
			$temp['summary_field_cd'] = 0;
			$temp['usr_meta_field_code'] = '';
			//$temp['container_name'] = _text('MN01089');
			$temp['container_name'] = '';
			$temp['is_default'] = 1;
			array_push($content_array, $temp);
		}

	}
	
	$all = $db->queryAll("
		SELECT A.*, B.USR_META_FIELD_TITLE AS CONTAINER_NAME
		FROM (
				SELECT *
				FROM BC_USR_META_FIELD
				WHERE CONTAINER_ID = $container_id
				AND UD_CONTENT_ID = $ud_content_id
				AND USR_META_FIELD_TYPE != 'container') A
		LEFT JOIN BC_USR_META_FIELD B
		ON B.USR_META_FIELD_ID = A.CONTAINER_ID
		ORDER BY A.SHOW_ORDER ASC
	 ");


	// 2010-11-08 추가 (컨테이너 추가 by CONOZ)
/*	$dataTmp=array();
	foreach($all as $key=>$val){
		$dataTmp[$key]['meta_table_id']=$val['meta_table_id'];
		$dataTmp[$key]['meta_field_id']=$val['meta_field_id'];
		$dataTmp[$key]['sort']=$val['sort'];
		$dataTmp[$key]['name']=$val['name'];
		$dataTmp[$key]['container_id']=$val['container_id'];
		$dataTmp[$key]['type']=$val['type'];
		$dataTmp[$key]['is_required']=$val['is_required'];
		$dataTmp[$key]['editable']=$val['editable'];
		$dataTmp[$key]['default_value']=$val['default_value'];
		$dataTmp[$key]['is_show']=$val['is_show'];
		$dataTmp[$key]['search_allow']=$val['search_allow'];

		if( $val['container_id'] && $val['depth'] != '0')
		{
			$container_name = $db->queryOne("select name from meta_field where meta_table_id={$meta_table_id} and type='container' and meta_field_id='{$val['container_id']}'");

			$dataTmp[$key]['container_name']=$container_name;
		}
		else
		{
			$dataTmp[$key]['container_name']="";
		}
	}
*/
	//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/test_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")." ::xxxxxx: mov_view_json -> all :::"."\r\n".print_r($all, true)."\r\n\n", FILE_APPEND);
	if ($all){
		foreach ($all as $f) {
			array_push($content_array, $f);
		}
	}


	foreach($content_array as $key=>$val)
	{
		if($val['type'] == 'container')
		{
			$content_array[$key]['container_name'] = "";
		}
	}

	echo json_encode(array(
		'success' => true,
		'total' => count($all),
		'data' => $content_array//$dataTmp
	));
}

function check_limit_code_to_date($code)
{
	global $db;
	$query = "select name from bc_code bc where code='$code' and bc.code_type_id in (select id from bc_code_type where code='UCDDDT')";
	$re = $db->queryOne($query);

	return $re;
}

function get_table_list()
{
	global $db;

	$all = $db->queryAll('select ud_content_id, ud_content_title from bc_ud_content order by show_order');

	echo json_encode(array(
		'success' => true,
		'total' => count($all),
		'data' => $all
	));
}

function get_content_type_list()
{
	global $db;

	$all = $db->queryAll("select * from bc_bs_content order by show_order");

	echo json_encode(array(
		'success' => true,
		'total' => count($all),
		'data' => $all
	));
}

// 2010-11-08 추가 (컨테이너 추가 by CONOZ)
function get_container_list($ud_content_id)
{
	global $db;
	$ud_content_id = $_POST['ud_content_id'];

	$basic_container_id = $db->queryAll("
											SELECT 	USR_META_FIELD_ID
											FROM 	BC_USR_META_FIELD
											WHERE 	UD_CONTENT_ID = $ud_content_id
											ORDER BY USR_META_FIELD_ID ASC
												");
	$usr_meta_field_id = $basic_container_id[0]['usr_meta_field_id'];
	$all = $db->queryAll("	SELECT 	CONTAINER_ID,
									USR_META_FIELD_TITLE,
									USR_META_FIELD_ID,
									CASE
										WHEN USR_META_FIELD_ID = '$usr_meta_field_id' THEN
											'1'
										ELSE
											'0'
									END AS IS_DEFAULT
							FROM  	BC_USR_META_FIELD
							WHERE 	UD_CONTENT_ID = '$ud_content_id'
							AND 	USR_META_FIELD_TYPE='container'
							AND 	DEPTH <= 1
							ORDER BY SHOW_ORDER");

	echo json_encode(array(
		'success' => true,
		'total' => count($all),
		'data' => $all
	));
}

function manage_category_list(){
	global $db;

	$category = $db->queryAll("
								SELECT 	A.CATEGORY_ID,
										A.CATEGORY_TITLE,
										CASE
										WHEN B.UD_CONTENT_ID IS NULL THEN
											'N'
										ELSE
											'Y'
										END AS USE_YN,
										(SELECT C.UD_CONTENT_TITLE
										FROM BC_UD_CONTENT C
										WHERE B.UD_CONTENT_ID = C.UD_CONTENT_ID) AS UD_CONTENT_TITLE
								FROM 	BC_CATEGORY A
								LEFT OUTER JOIN BC_CATEGORY_MAPPING B ON A.CATEGORY_ID = B.CATEGORY_ID
								WHERE 	PARENT_ID = 0
								ORDER BY A.CATEGORY_ID ASC
							");

	echo json_encode(array(
			'success' => true,
			'data' => $category
	));

}

?>