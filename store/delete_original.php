<?php
require_once("../lib/config.php");

$content_id = $_POST['content_id'];

$root = $db->queryOne("SELECT PATH 
						FROM BC_CONTENT C, BC_UD_CONTENT UC, BC_STORAGE S
						WHERE C.CONTENT_ID=$content_id
						AND C.UD_CONTENT_ID=UC.UD_CONTENT_ID
						AND UC.STORAGE_ID=S.STORAGE_ID");
$root = rtrim(str_replace('upload/', '', str_replace('\\', '/', $root)), '/').'/highres';

$ori_media = $db->queryRow("SELECT PATH, MEDIA_ID AS ID
						FROM BC_MEDIA 
						WHERE CONTENT_ID=$content_id
						AND MEDIA_TYPE='original'");
//echo $root.'/'.$ori_media['path'];
$result = @unlink($root.'/'.$ori_media['path']);
if (!$result) {
//	echo json_encode(array(
//		'success' => false,
//		'msg' => '원본 파일이 존재하지않습니다.'
//	));
//	exit;
}

$db->exec("UPDATE BC_MEDIA SET STATUS=1 WHERE MEDIA_ID=".$ori_media['id']);

echo json_encode(array(
	'success' => true
));
?>