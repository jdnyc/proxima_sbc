<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/util.php';

switch($_REQUEST['action'])
{
    case 'edit_table':
        edit_table();
    break;

    case 'field':
        edit_field();
	break;

	default:
        echo json_encode(array(
            'success' => false,
            'msg' => 'Empty Action(' . $_POST['action'] . ')'
        ));
    break;
}


function edit_table()
{
    global $db;

	$bs_content_id		= $_POST['bs_content_id'];
	$bs_content_title	= $_POST['bs_content_title'];
	$bs_content_code	= strtoupper($_POST['bs_content_code']);
	$allowed_extension	= $_POST['allowed_extension'];
	$description		= $_POST['description'];

	$is_exists = $db->queryOne("select count(*)
								from bc_bs_content
								where bs_content_id != $bs_content_id
								and bs_content_title = '$bs_content_title'");
    if($is_exists > 0) _print(_text('MSG01049'));//존재하는 메타데이터 이름 입니다.

	$is_exists = $db->queryOne("select count(*)
								from bc_bs_content
								where bs_content_id != $bs_content_id
								and bs_content_code = '$bs_content_code'");
    if($is_exists > 0) _print(_text('MSG01048'));//존재하는 테이블명입니다.

	$bf_info = $db->queryRow("select * from bc_bs_content where bs_content_code= '$bs_content_code'");

	if( empty($bf_info) || !( $bf_info['bs_content_id'] == $bs_content_id ) ){
		$editquery = MetaDataClass::alterTableQuery('sys' , $bs_content_id , $bs_content_code );
		$r = $db->exec($editquery);
	}

    $r = $db->exec("update bc_bs_content set bs_content_title='$bs_content_title', bs_content_code='$bs_content_code', allowed_extension='$allowed_extension', description='$description' where bs_content_id=$bs_content_id");

    echo json_encode(array(
        'success' => true,
        'msg' => _text('MN02178')//'완료'
    ));
}


function edit_field()
{
    global $db;

	$bs_content_id			= $_POST['bs_content_id'];
    $sys_meta_field_id		= !empty($_POST['sys_meta_field_id'])		? $_POST['sys_meta_field_id'] : null;
	$sys_meta_field_title	= !empty($_POST['sys_meta_field_title'])	? $_POST['sys_meta_field_title'] : '';
	$sys_meta_field_code	= !empty($_POST['sys_meta_field_code'])		? strtoupper($_POST['sys_meta_field_code']) : '';
	$type					= !empty($_POST['type'])					? $_POST['type']			: '';
	$is_visible				= !empty($_POST['is_visible'])				? 1 : 0;
	$default_value			= !empty($_REQUEST['default_value'])		? $_REQUEST['default_value'] : '';

	$beforeFieldInfo = $db->queryRow("select * from bc_sys_meta_field where bs_content_id=$bs_content_id and sys_meta_field_id = $sys_meta_field_id ");

	$before_sys_meta_field_code = strtoupper($beforeFieldInfo[sys_meta_field_code]);
	$is_exists = $db->queryOne("select count(*)
								from bc_sys_meta_field
								where bs_content_id=$bs_content_id
								and sys_meta_field_id != $sys_meta_field_id
								and sys_meta_field_title = '$sys_meta_field_title'");
	if($is_exists > 0) _print(_text('MSG01049'));//존재하는 메타데이터 이름 입니다.
 	if (empty($sys_meta_field_title))	_print(_text('MSG01051').'(' . $sys_meta_field_title . ')');//메타데이터 명이 존재 하지않습니다.
	if($before_sys_meta_field_code != $sys_meta_field_code){
		$query = MetaDataClass::alterFieldQuery('sys' , $bs_content_id , $before_sys_meta_field_code, $sys_meta_field_code );
		$r = $db->exec($query);
	}

	$r = $db->exec("update bc_sys_meta_field set
						sys_meta_field_title='$sys_meta_field_title',
						sys_meta_field_code='$sys_meta_field_code',
						field_input_type='$type',
						default_value='$default_value',
						is_visible='$is_visible'
					where sys_meta_field_id=$sys_meta_field_id");

    echo json_encode(array(
        'success' => true,
        'msg' => _text('MN02178')//'완료'
    ));
}
?>