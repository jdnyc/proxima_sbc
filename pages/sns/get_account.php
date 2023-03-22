<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

try
{
	$query = "
		SELECT	*
		FROM	BC_CODE
		WHERE	CODE_TYPE_ID=(
					SELECT	ID
					FROM	BC_CODE_TYPE
					WHERE	CODE='SOCIAL_USER'
				)
		ORDER BY ID
	";
	$arr_info = $db->queryAll($query);
	$data = array();
	foreach($arr_info as $ai)
	{
		$sub = array();
		if($ai['code'] != 'FACEBOOK') {
			$sub['token'] = '';
		} else {
			$sub['token'] = $ai['ref3'];
		}
		$sub['social_type'] = $ai['name'];
		$sub['social_type_id'] = $ai['code'];
		$sub['user_id'] = $ai['ref1'];
		$sub['password'] = $ai['ref2'];
		$sub['use_yn'] = $ai['use_yn'];
		array_push($data, $sub);
	}

	echo json_encode(array(
		'success' => true,
		'data' => $data
	));
}
catch(Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}

?>