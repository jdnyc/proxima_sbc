<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

// Log 세부항목 보기 
// 2012.02.03 허광회
// 기존 V3의 경우 기준값이 usr_meta_field_id였지만 usr_meta_field_code로 바뀌면서
// 해당 부분 관련 수정 2018.01.11 Alex

$log_id = $_POST['log_id'];
$ud_content_id = $_POST['ud_content_id'];

$query = "
		SELECT 	usr_meta_field_id, 
				usr_meta_field_title 
		FROM 	bc_usr_meta_field 
		WHERE 	ud_content_id = {$ud_content_id} 
		AND 	usr_meta_field_type <> 'container' 
		ORDER BY 	usr_meta_field_id
	";

$r = $db->queryAll($query);

foreach($r as $rr)
{
	$v = $rr['usr_meta_field_id'];
	$usr_meta_field[$v]=$rr['usr_meta_field_title'];
}

$title_text = _text('MN00249');
$category_text = _text('MN00387');

$query = "
		SELECT  log_id, 
	        old_contents,
	        new_contents,
			CASE
				WHEN bld.usr_meta_field_code = 'k_title' THEN
					'$title_text'
				WHEN bld.usr_meta_field_code = 'c_category_id' THEN
					'$category_text'
				ELSE
					( 
					  SELECT  usr_meta_field_title 
					  FROM  bc_usr_meta_field 
					  WHERE ud_content_id = {$ud_content_id}
					  AND bld.usr_meta_field_code = lower(usr_meta_field_code) 
					)
					
			END AS field_id
		FROM  bc_log_detail bld 
		WHERE   bld.log_id = {$log_id}
		ORDER BY  log_id ASC
	";

$datas = $db->queryAll($query);

$send_data = array();
foreach($datas as $data)
{
	$d['log_id'] = $data['log_id'];
	$d['old_contents'] = $data['old_contents'];
	$d['new_contents'] = $data['new_contents'];
	$d['field_id'] = $data['field_id'];

	array_push($send_data,$d);
}

echo json_encode(array(
	'success'=>true,
	'details'=>$send_data
));



?>