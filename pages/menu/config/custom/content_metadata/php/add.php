<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/util.php';
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

switch($_REQUEST['action']) {
    case 'add_table':
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
    global $db;

	$bs_content_title	= $_POST['bs_content_title'];
	$name				= $_POST['name'];
	$bs_content_code	= strtoupper($_POST['bs_content_code']);
	$allow_extension	= $_POST['allow_extension'];
	$description		= $_POST['description'];
	$created_date		= date('YmdHis');

	$is_exists = $db->queryOne("select count(*) from bc_bs_content where bs_content_title='$bs_content_title'");
	if($is_exists > 0) _print(_text('MSG02116'));//존재하는 메타데이터 이름 입니다.

	$is_exists = $db->queryOne("select count(*) from bc_bs_content where bs_content_code='$bs_content_code'");
    if($is_exists > 0) _print(_text('MSG01048'));//존재하는 테이블명입니다.
    

	$sort = $db->queryOne("select max(show_order) + 1 from bc_bs_content");
	if(empty($sort)) $sort = 1;
    $seq = getSequence('SEQ_BS_CONTENT_ID');

		//메타테이블에 필드 추가
	$addquery = MetaDataClass::addTableQuery('sys', $seq , $bs_content_code ) ;
	$r = $db->exec($addquery);

    $result = $db->exec("insert into bc_bs_content (bs_content_id, bs_content_title, bs_content_code, show_order, allowed_extension, description, created_date) values ($seq, '$name','$bs_content_code', $sort, '$allow_extension', '$description', '$created_date')");

    echo json_encode(array(
        'success' => true,
        'msg' => _text('MN02178')//'완료'
    ));
}

function add_field()
{
    global $db;

	$sys_meta_field_title = $_POST['sys_meta_field_title'];
	$sys_meta_field_code	= strtoupper($_POST['sys_meta_field_code']);


	$bs_content_id			= !empty($_POST['bs_content_id'])			? $_POST['bs_content_id'] : '';
	$sys_meta_field_title	= !empty($_POST['sys_meta_field_title'])	? $_POST['sys_meta_field_title'] : '';
	$type					= !empty($_POST['type'])					? $_POST['type'] : '';
	if (empty($_POST['sort']))
	{
		$sort = $db->queryOne("select max(show_order) from bc_sys_meta_field where bs_content_id = $bs_content_id");
		if(empty($sort)) $sort = 1;
	}
	else
	{
		$sort = $_POST['sort'];
	}
	$is_visible			= !empty($_POST['is_visible'])		? $_POST['is_visible'] : 0;
	$default_value		= !empty($_POST['default_value'])	? $_POST['default_value'] : '';

	if (empty($sys_meta_field_title))	_print(_text('MSG01051').'(' . $sys_meta_field_title . ')');//메타데이터 명이 존재 하지않습니다.
	
    $is_exists = $db->queryOne("select count(*) from bc_sys_meta_field where bs_content_id=$bs_content_id and sys_meta_field_title = '$sys_meta_field_title'");
	if($is_exists > 0) _print(_text('MSG02116'));//존재하는 메타데이터 이름 입니다.

	$is_exists = $db->queryOne("select count(*) from bc_sys_meta_field where bs_content_id=$bs_content_id and sys_meta_field_code = '$sys_meta_field_code'");
	if($is_exists > 0) _print(_text('MSG01048'));//존재하는 테이블명입니다.

	//메타테이블에 필드 추가
	$addquery = MetaDataClass::addFieldQuery('sys', $bs_content_id , $sys_meta_field_code ) ;
	$r = $db->exec($addquery);

	$seq = getSequence('seq');
    $r = $db->exec("insert into bc_sys_meta_field (bs_content_id, sys_meta_field_id, sys_meta_field_title, sys_meta_field_code, field_input_type, show_order, is_visible,  default_value) values (" .
                "$bs_content_id, $seq, '$sys_meta_field_title', '$sys_meta_field_code', '$type', $sort, $is_visible, '$default_value')");

    echo json_encode(array(
        'success' => true,
        'msg' => _text('MN02178')//'완료'
    ));
}
?>