<?php

use Proxima\models\content\Media;
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$now = date('Ymd');
$start = empty($_POST['start']) ? 0 : $_POST['start'];
$limit = empty($_POST['limit']) ? 20 : $_POST['limit'];
$sdate = empty($_POST['sdate']) ? $now.'000000' : $_POST['sdate'];
$edate = empty($_POST['edate']) ? $now.'240000' : $_POST['edate'];

try{
	$total = $db->queryOne("SELECT COUNT(CONTENT_ID) FROM BC_LOG WHERE ACTION = 'delete' AND CREATED_DATE >= '$sdate' AND CREATED_DATE <= '$edate'");
	$db->setLimit($limit, $start);
	$datas = $db->queryAll("SELECT  L.CREATED_DATE, L.DESCRIPTION, C.TITLE, L.CONTENT_ID,
						(SELECT UD_CONTENT_TITLE FROM BC_UD_CONTENT WHERE UD_CONTENT_ID = L.UD_CONTENT_ID ) AS UD_CONTENT_TITLE,
						C.IS_DELETED CONTENT_IS_DELETED,
						(SELECT STATUS FROM BC_MEDIA WHERE CONTENT_ID = L.CONTENT_ID AND MEDIA_TYPE='".Media::MEDIA_TYPE_ORIGINAL."') MEDIA_STATUS,
						(SELECT USER_NM FROM BC_MEMBER WHERE USER_ID = L.USER_ID) AS USER_NM
				FROM	BC_LOG L
						LEFT OUTER JOIN BC_CONTENT C ON C.CONTENT_ID = L.CONTENT_ID
                WHERE   L.ACTION = 'delete'
                AND     L.CREATED_DATE >= '$sdate' AND L.CREATED_DATE <= '$edate'
				ORDER BY L.CREATED_DATE DESC");

	for($i = 0; $i < count($datas); $i++) {
		if($datas[$i]['content_is_deleted'] == 'Y') {
			$datas[$i]['content_is_deleted'] = '삭제됨';
		} else {
			$datas[$i]['content_is_deleted'] = '';
		}

		$mediaStatus = trim($datas[$i]['media_status']);

		if(empty($mediaStatus)) {
			$datas[$i]['media_status'] = '';
		} else {
			if($mediaStatus == 'M') {
				$datas[$i]['media_status'] = '메인 삭제됨';
			} else if($mediaStatus == 'B') {
				$datas[$i]['media_status'] = '메인/백업 삭제됨';
			}			
		}
	}

	$del_rank = array(
		'success' => true,
		'total' => $total,
		'data' => $datas
	);

	echo json_encode(
		$del_rank
	);
} catch(Exception $e) {
	$msg = $e->getMessage();
	$code = $e->getCode();
	
	echo json_encode(array(
			'success' => false,
			'msg' => $msg,
			'code' => $code
	));
}
?>