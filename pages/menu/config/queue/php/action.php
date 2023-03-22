<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$ids	= $_POST['ids'];
$status = $_POST['status'];


if (empty($ids)) {
	echo json_encode(array(
		'success' => false,
		'msg' => 'id 값이 없습니다.'
	));
	exit;
}

if (empty($status)) {
	echo json_encode(array(
		'success' => false,
		'msg' => '변경할 상태값이 없습니다.'
	));
	exit;
}

$ids = explode(',', $ids);
foreach	($ids as $id) {
	if(empty($id)) continue;

	$r = $db->exec("update queue set status = '$status' where id=$id");
}

echo json_encode(array(
	'success' => true
));

?>