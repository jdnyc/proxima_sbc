<?php
//11-11-01 승수. bc_ud_content 테이블에 만료기간 expire_date필드 추가
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/util.php';
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

switch($_REQUEST['action']) {
	case 'add_table':
		// 2011 - 12 - 07 폐기기간 추가 수정  by 허광회
		add_table();
	break;

	case 'field':
		add_field();
	break;

	default:
		echo json_encode(array(
			'success' => false,
			'msg' => 'Empty Action(' . $_REQUEST['action'] . ')'
		));
	break;
}


function add_table()
{
	 // 2011 - 12 - 07 폐기기간 추가 수정  by 허광회
	global $db;

	$bs_content_id = $_POST['bs_content_id'];
	$ud_content_title = $_POST['ud_content_title'];
	$ud_content_code = strtoupper($_POST['ud_content_code']);
	$allowed_extension = $_POST['allowed_extension'];
	$use_common_category = $_POST['use_common_category'];
	$description = $_POST['description'];
	$expire_date = $_POST['year']*365 + $_POST['month']*30 + $_POST['day'];

	$table_length = mb_strlen(MetaDataClass::USR_VALUE.'_'.$ud_content_code);

	if($table_length > 30) _print(_text('MSG02073'));//The maximum length for database table name is 16(English charactor).

	$is_exists = $db->queryOne("select count(*) from bc_ud_content where ud_content_title = '$ud_content_title'");
	if($is_exists > 0) _print(_text('MSG02072'));//A duplicate title exists on the User Defined Content.

	$is_exists = $db->queryOne("select count(*) from bc_ud_content where ud_content_code = '$ud_content_code'");
	if($is_exists > 0) _print(_text('MSG01048'));//A duplicate name exists on the database.

	//print_r($_POST);
	// 만료일 계산해서 넣어줌 우선
	$cur_date = date('YmdHis');
	$content_expire_date = $_POST['content_expire_date'];

	$expire = array();
	$data =json_decode($_POST['expire']);

	foreach($data as $key => $v)
	{
		$expire[$key]=$v;
	}

	$contents_expire_date = $expire['contents_expire_date'];

	//echo(" exire_date : $content_expire_date ");

	$curtime = date('YmdHis');

	$query = "select max(show_order)+1 from bc_ud_content";
	$show_order = $db->queryOne($query);
	if($show_order == '') $show_order = 1;
	$ud_content_id = $db->queryOne("select max(ud_content_id)+1 from bc_ud_content");
	if($ud_content_id == '') $ud_content_id = 1;
	$query = "insert into bc_ud_content (bs_content_id,ud_content_id, ud_content_title, allowed_extension, description, created_date, expired_date, con_expire_date ,show_order, ud_content_code )
			values ($bs_content_id,  '$ud_content_id' ,  '$ud_content_title', '$allowed_extension', '$description','$curtime','$content_expire_date','$contents_expire_date',$show_order,'$ud_content_code')";

	$result = $db->exec($query);

	$seq_container = getSequence('seq_usr_meta_field_id');
	$r = $db->exec("insert into bc_usr_meta_field (usr_meta_field_id, ud_content_id, show_order, usr_meta_field_title, usr_meta_field_type, is_required, is_editable, is_show, is_search_reg, is_social, default_value, container_id, depth , summary_field_cd, usr_meta_field_code )
					values (
						$seq_container, $ud_content_id, 0, '"._text('MN01089')."', 'container', 1, 1, 1, 1, 0, '', '$seq_container', '0', '0' , '' )");

	//메타테이블에 필드 추가
	$addquery = MetaDataClass::addTableQuery('usr', $ud_content_id , $ud_content_code ) ;
	$r = $db->exec($addquery);

	$seq = $db->queryOne("select max(ud_content_id) from bc_ud_content");
	$query = "insert into bc_ud_content_delete_info (ud_content_id, type_code, date_code, code_type) values ($seq,'".$_POST['content_expire_date']."','".$_POST['content_expire_date']."','UCSDDT')";
	$db->exec($query);

	$query = "insert into bc_ud_content_delete_info (ud_content_id, type_code, date_code, code_type) values ($seq,'$contents_expire_date','$contents_expire_date','UCDDDT')";
	//2015-12-17 삭제 설정 숨김
	//$db->exec($query);

	$query = "select * from bc_code where code_type_id in (select id from bc_code_type ct where ct.code = 'FLDLNM')";
	$filetype_code = $db->queryAll($query);


	foreach($filetype_code as $f_code)
	{
		$ck_name = 'del_'.$f_code['code'].'_checkbox';
		$dt_name = 'del_'.$f_code['code'].'_date';

		if(strcmp("on",$expire[$ck_name])) // exist
		{
			if($expire[$dt_name])
			{
			$query = "insert into bc_ud_content_delete_info (ud_content_id, type_code, date_code , code_type) values ($seq  ,'$f_code[code]','$expire[$dt_name]','FLDLNM')";
				$db->exec($query);
			}
		}
	}

	if ($use_common_category == 'N')
	{
		$ud_content_id = $db->queryOne("select max(ud_content_id) from bc_ud_content");
		//addExclusiveCategory($ud_content_id, $ud_content_title); 함수선언이 안되어있음 우선 주석처리
	}

	//UD_CONTENT별 카테고리 맵핑
	$category_id = $_POST['category'];
	if(!empty($category_id)) {
		$check = $db->queryRow("
					SELECT	*
					FROM	BC_CATEGORY_MAPPING
					WHERE	UD_CONTENT_ID = '$ud_content_id'
				");
		if(empty($check)) {
			$db->exec("
				INSERT INTO	BC_CATEGORY_MAPPING
					(UD_CONTENT_ID, CATEGORY_ID)
				VALUES
					($ud_content_id, $category_id)
			");
		} else {
			$db->exec("
				UPDATE	BC_CATEGORY_MAPPING
				SET		CATEGORY_ID = $category_id
				WHERE	UD_CONTENT_ID = $ud_content_id
			");
		}
	}

	////BC_UD_GROUP에 1로 고정하여 추가
	//$check_ud_group = $db->queryRow("
						//SELECT	*
						//FROM	BC_UD_GROUP
						//WHERE	UD_CONTENT_ID = '$ud_content_id'
						//AND		UD_GROUP_CODE = '1'
					//");

	//if(empty($check_ud_group)) {
		//$db->exec("
				//INSERT INTO	BC_UD_GROUP
					//(UD_GROUP_CODE, UD_CONTENT_ID, ROOT_CATEGORY_ID)
				//VALUES
					//('1', '$ud_content_id', '$category_id')
		//");
	//} else {
		//$db->exec("
				//UPDATE	BC_UD_GROUP
				//SET		ROOT_CATEGORY_ID = '$category_id'
				//WHERE	UD_CONTENT_ID = '$ud_content_id'
				//AND		UD_GROUP_CODE = '1'
		//");
	//}

	/*사용자정의콘텐츠 별 스토리지 설정*/
	$highres = $_POST['highres'];
	$lowres = $_POST['lowres'];
	$upload = $_POST['upload'];

	if( !empty($_POST['highres']) )
	{
		$check = $db->queryRow("select * from BC_UD_CONTENT_STORAGE where ud_content_id='$ud_content_id' and us_type='highres'");
		$highres = $_POST['highres'];

		if( empty($check) )
		{
			$db->exec("insert into BC_UD_CONTENT_STORAGE(UD_CONTENT_ID ,STORAGE_ID, US_TYPE) values('$ud_content_id','$highres','highres')");
		}
		else
		{
			$db->exec("update BC_UD_CONTENT_STORAGE set STORAGE_ID='$highres' where ud_content_id='$ud_content_id' and US_TYPE='highres' ");
		}
	}

	if( !empty($_POST['lowres']) )
	{
		$check = $db->queryRow("select * from BC_UD_CONTENT_STORAGE where ud_content_id='$ud_content_id' and us_type='lowres'");
		$lowres = $_POST['lowres'];

		if( empty($check) )
		{
			$db->exec("insert into BC_UD_CONTENT_STORAGE(UD_CONTENT_ID ,STORAGE_ID, US_TYPE) values('$ud_content_id','$lowres','lowres')");
		}
		else
		{
			$db->exec("update BC_UD_CONTENT_STORAGE set STORAGE_ID='$lowres' where ud_content_id='$ud_content_id' and US_TYPE='lowres' ");
		}
	}

	if( !empty($_POST['upload']) )
	{
		$check = $db->queryRow("select * from BC_UD_CONTENT_STORAGE where ud_content_id='$ud_content_id' and us_type='upload'");
		$upload = $_POST['upload'];

		if( empty($check) )
		{
			$db->exec("insert into BC_UD_CONTENT_STORAGE(UD_CONTENT_ID ,STORAGE_ID, US_TYPE) values('$ud_content_id','$upload','upload')");
		}
		else
		{
			$db->exec("update BC_UD_CONTENT_STORAGE set STORAGE_ID='$upload' where ud_content_id='$ud_content_id' and US_TYPE='upload' ");
		}
	}


	echo json_encode(array(
		'success' => true,
		'msg' => _text('MN02178')//'완료'
	));
}

function add_field()
{
	global $db;

	// 2010-11-08 추가 (컨테이너 추가 by CONOZ)
	$container_id		= !empty($_POST['container_id'])		? $_POST['container_id']	: '';

	$ud_content_id			= $_POST['ud_content_id'];
	$usr_meta_field_title	= $_POST['usr_meta_field_title'];
	$usr_meta_field_code	= strtoupper($_POST['usr_meta_field_code']);
	$usr_meta_field_type	= $_POST['usr_meta_field_type'];
	$is_required		= !empty($_POST['is_required'])			? $_POST['is_required']		: 0;
	$is_editable		= !empty($_POST['is_editable'])			? $_POST['is_editable']		: 0;
	$is_show			= !empty($_POST['is_show'])				? $_POST['is_show']			: 0;
	$is_search_reg		= !empty($_POST['is_search_reg'])		? $_POST['is_search_reg']	: 0;
	$is_social			= !empty($_POST['is_social'])			? $_POST['is_social']		: 0;
	$default_value		= !empty($_POST['default_value'])		? $_POST['default_value']	: '';

	if(empty($_POST['num_line'])) $_POST['num_line'] = 0;

	if($usr_meta_field_type == "textfield" || $usr_meta_field_type == "textarea"){
		$number_of_line = $_POST['num_line'];
	}else{
		$number_of_line = '0';
	}

	$summary_field_cd_array = array();

	foreach($_POST as $key => $taget)
	{
		if( strstr($key, 'summary_field_cd') )
		{
			$list = explode('-', $key);

			array_push($summary_field_cd_array ,$list[1]);
		}
	}

	array_sum($summary_field_cd_array);

	$summary_field_cd	= array_sum($summary_field_cd_array);

	$is_exists = $db->queryOne("select count(*) from bc_usr_meta_field where ud_content_id=$ud_content_id and usr_meta_field_title = '$usr_meta_field_title'");
	if($is_exists > 0) _print(_text('MSG02116'));
	if($usr_meta_field_type != 'container'){
		 $is_exists = $db->queryOne("select count(*) from bc_usr_meta_field where ud_content_id=$ud_content_id and usr_meta_field_code = '$usr_meta_field_code'");
		if($is_exists > 0) _print(_text('MSG01048'));//존재하는 테이블명입니다.
	}

	if($summary_field_cd == '1') {
		$is_limit_thumbView = $db->queryOne("select count(*) from bc_usr_meta_field where ud_content_id=$ud_content_id and (summary_field_cd='1' or summary_field_cd='3')");
		if($is_limit_thumbView > 0) _print('Thumbnail View metadata limit exceed(max: 1).');
	} else if ($summary_field_cd == '2') {
		$is_limit_summView = $db->queryOne("select count(*) from bc_usr_meta_field where ud_content_id=$ud_content_id and (summary_field_cd='2' or summary_field_cd='3')");
		if($is_limit_summView > 3) _print('Summary View metadata limit exceed(max: 4).');
	} else if ($summary_field_cd == '3') {
		$is_limit_thumbView = $db->queryOne("select count(*) from bc_usr_meta_field where ud_content_id=$ud_content_id and (summary_field_cd='1' or summary_field_cd='3')");
		if($is_limit_thumbView > 0) _print('Thumbnail View metadata limit exceed(max: 1).');
		$is_limit_summView = $db->queryOne("select count(*) from bc_usr_meta_field where ud_content_id=$ud_content_id and (summary_field_cd='2' or summary_field_cd='3')");
		if($is_limit_summView > 3) _print('Summary View metadata limit exceed(max: 4).');
	}

	if (empty($usr_meta_field_title))	_print(_text('MSG01051').'(' . $usr_meta_field_title . ')');//메타데이터 명이 존재 하지않습니다.
	
	$seq = getSequence('seq_usr_meta_field_id');
	//$show_order = $db->queryOne("select decode(max(show_order), null, 1, max(show_order)+1) from bc_usr_meta_field where ud_content_id=$ud_content_id");

	$show_order = $db->queryOne("
		SELECT	CASE
						WHEN MAX(SHOW_ORDER) IS NULL THEN 1
						ELSE MAX(SHOW_ORDER)+1
					END AS TT
		FROM		BC_USR_META_FIELD
		WHERE	UD_CONTENT_ID = ".$ud_content_id."
	");

	// 2010-11-08 추가 (컨테이너 추가 by CONOZ)
	if($container_id)
	{
		$depth=$db->queryOne("select depth from bc_usr_meta_field where usr_meta_field_id='{$container_id}'");
		if($usr_meta_field_type == 'container')
		{
			if($depth > 0)
			{
				_print(_text('MSG01053'));//'2차 컨테이너 안에는 컨테이너외 다른 입력형식들만 추가하실수 있습니다.'
			}
		}
		$depth=$depth+1;
	}
	else
	{
		if($usr_meta_field_type != 'container')
		{
			//'컨테이너를 선택하시거나 입력형식을 컨테이너로 선택해 주십시오.
			_print(_text('MSG01054').'(' . $type . ')');
		}
		else
		{
			$container_id=$seq;
			$depth=0;
		}
	}

	if( $usr_meta_field_type != 'container'){
		$isExistsQuery = MetaDataClass::checkFieldQuery('usr', $ud_content_id , $usr_meta_field_code ) ;
		$t = $db->queryOne($isExistsQuery);
		if ($t == 0){
			$r = $db->exec("insert into bc_usr_meta_field (usr_meta_field_id, ud_content_id, show_order, usr_meta_field_title, usr_meta_field_type, is_required, is_editable, is_show, is_search_reg, is_social, default_value, container_id, depth , summary_field_cd, usr_meta_field_code, num_line )
					values (
						$seq, $ud_content_id, $show_order, '$usr_meta_field_title', '$usr_meta_field_type', $is_required, $is_editable, $is_show, $is_search_reg, $is_social, '$default_value', '$container_id', '$depth', '$summary_field_cd' , '$usr_meta_field_code', $number_of_line )");

			//메타테이블에 필드 추가
			
			$addquery = MetaDataClass::addFieldQuery('usr', $ud_content_id , $usr_meta_field_code ) ;
			$r = $db->exec($addquery);
		} else {
			_print(_text('MSG02117'));
		}

	}else{
		 $r = $db->exec("
		 		insert into bc_usr_meta_field
		 			(usr_meta_field_id, ud_content_id, show_order, usr_meta_field_title, usr_meta_field_type, is_required, is_editable, is_show, is_search_reg, is_social, default_value, container_id, depth , summary_field_cd, num_line)
				values
		 			($seq, $ud_content_id, $show_order, '$usr_meta_field_title', '$usr_meta_field_type', $is_required, $is_editable, $is_show, $is_search_reg, $is_social, '$default_value', '$container_id', '$depth', '$summary_field_cd', '0' )");

	}

	echo json_encode(array(
		'success' => true,
		'msg' => _text('MN02178')//'완료'
	));
}

?>