<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/MetaData.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/store/get_content_list/libs/functions.php');

$list_user_id = $_SESSION['user']['user_id'];
$content_id = $_POST['content_id'];
$bs_content_id = $_POST['bs_content_id'];

try {
	if (empty($list_user_id) || $list_user_id == 'temp') throw new Exception("Login 해주세요");
	if (empty($content_id)) throw new Exception("Param Error");

	// 'content_id', 'title', 'bs_content_id', 'thumb', 'sys_ori_filename'

	$msg = '성공';
	$sys_table = MetaDataClass::getTableName('sys', $bs_content_id);

	$result = $db->queryAll("
			SELECT	C.CONTENT_ID,
					C.BS_CONTENT_ID,
					C.TITLE,
					COALESCE(
						S.SYS_ORI_FILENAME,
						COALESCE((SELECT	PATH
						FROM	BC_MEDIA
						WHERE	CONTENT_ID = C.CONTENT_ID
						AND		MEDIA_TYPE = 'raw'),
						(SELECT	PATH
						FROM	BC_MEDIA
						WHERE	CONTENT_ID = C.CONTENT_ID
						AND		MEDIA_TYPE = 'original'))
					) SYS_ORI_FILENAME,
					(SELECT	PATH
					FROM	BC_MEDIA
					WHERE	CONTENT_ID = C.CONTENT_ID
					AND		MEDIA_TYPE = 'original') ORI_PATH,
					(SELECT	PATH
					FROM	BC_MEDIA
					WHERE	CONTENT_ID = C.CONTENT_ID
					AND		MEDIA_TYPE = 'proxy') PROXY_PATH,
					(SELECT	MAX(PATH)
					FROM	BC_MEDIA
					WHERE	CONTENT_ID = C.CONTENT_ID
					AND		MEDIA_TYPE = 'thumb') THUMB,
					(SELECT	PATH
					FROM	BC_MEDIA
					WHERE	CONTENT_ID = C.CONTENT_ID
					AND		MEDIA_TYPE = 'album') ALBUM_PATH
			FROM	BC_CONTENT C,
					$sys_table S
			WHERE	(C.PARENT_CONTENT_ID = $content_id OR C.CONTENT_ID = $content_id)
			AND		C.STATUS != '-3'
			AND		C.IS_DELETED = 'N'
			AND		C.CONTENT_ID = S.SYS_CONTENT_ID
			AND		C.IS_GROUP IN ('G', 'C')
			ORDER  BY C.GROUP_COUNT,C.TITLE
	");

	$thumb_content_id = $db->queryOne("SELECT THUMBNAIL_CONTENT_ID FROM BC_CONTENT WHERE CONTENT_ID = $content_id");
	//2015-11-19 수정
	$childs = array();
	foreach ($result as $item) {
		/**
		 * 원본파일명이 존재 할 경우에는 제목을 원본파일명으로 없으면 그냥 원래 제목으로 표기되도록 수정
		 * 2018.02.08 Alex
		 */
		$ori_filename = pathinfo($item['sys_ori_filename'], PATHINFO_FILENAME);
		$item['sys_ori_filename'] = basename($item['sys_ori_filename']);
		if(!empty($ori_filename)) {
			$item['title'] = $ori_filename;
		}
		// $item['ori_path'] = dirname($item['ori_path']);
		$item['thumbnail_content_id'] = $thumb_content_id;

		//get original file size
		$query = "
			SELECT FILESIZE
			FROM BC_MEDIA
			WHERE CONTENT_ID = ".$item['content_id']."
			AND MEDIA_TYPE = 'original'
		";
		$file_size = $db->queryOne($query);

		$item['file_size_original'] = formatByte($file_size);		
		array_push($childs, $item);
	}

	echo json_encode(array(
		'total' => $total,
		'success' => true,
		'msg' => $msg,
		'data' => $childs,
		'table' => $sys_table
	));
} catch (Exception $e) {
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}

?>
