<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

//file_put_contents(LOG_PATH.'/get_list_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")."ccccc \r\n".print_r($_POST, true)."\r\n", FILE_APPEND);
$doc_response =  new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'.chr(10).'<response />');
$doc_delete_list = $doc_response->addChild('deletedlist');

try
{
	/**
	 * 삭제대상 조회
	 * 1. 설정된 개월수 이전의 데이터
	 * 		- 영구보관 일 경우 9999개월 이전의 데이터를 삭제 하기때문에 실제 삭제가 안됨
	 * 2. 디스크가 20% 미만으로 남았을 때 가장 오래된 데이터의 날짜 + 7일 까지의 발생 데이터

	 Z:\Storage\highres\NPS_EDIT\content\2015\09\25
	 */
	$root_path_highres = 'Z:/Storage/highres';
	$root_path_lowres = 'Z:/Storage/lowres';
	$root_path_archive = 'Y:/archive';

	$query = "
		SELECT   CONTENT_ID,
				IS_DELETED,
				CREATED_DATE,
				ID,
				TYPE,
				PATH,
				BACKUP_CHECK_YN,
				BACKUP_EXISTS_YN,
				BACKUP_PATH
		  FROM   (
				--삭제요청자료 삭제처리 대상
				SELECT   C.CONTENT_ID,
					  C.IS_DELETED,
					  E.DELETE_STATUS,
					  C.CREATED_DATE,
					  E.MEDIA_ID AS ID,
					  E.MEDIA_TYPE AS TYPE,
					  E.PATH,
					  'N' AS BACKUP_CHECK_YN,
					  'N' AS BACKUP_EXISTS_YN,
					  '' AS BACKUP_PATH
				FROM    BC_CONTENT C
						JOIN BC_MEDIA E ON(C.CONTENT_ID = E.CONTENT_ID)
				WHERE   C.STATUS = 5
				AND      E.DELETE_STATUS IS NULL




				UNION ALL

				SELECT   A.CONTENT_ID,
					  A.IS_DELETED,
					  A.DELETE_STATUS,
					  A.CREATED_DATE,
					  S.SCENE_ID AS ID,
					  'catalog' AS TYPE,
					  S.PATH,
					  'N' AS BACKUP_CHECK_YN,
					  'N' AS BACKUP_EXISTS_YN,
					  '' AS BACKUP_PATH
				FROM   (
					  SELECT   C.CONTENT_ID,
							C.IS_DELETED,
							E.DELETE_STATUS,
							C.CREATED_DATE,
							E.MEDIA_ID AS ID,
							E.MEDIA_TYPE AS TYPE,
							E.PATH
					  FROM    BC_CONTENT C
							 JOIN BC_MEDIA E ON(E.CONTENT_ID = C.CONTENT_ID)
					  WHERE   C.STATUS = 5
					  AND      E.DELETE_STATUS IS NULL
					  AND      E.MEDIA_TYPE = 'proxy'
					  ) A
					  LEFT OUTER JOIN BC_SCENE S ON(S.MEDIA_ID = A.ID)


				)
		  ORDER BY CONTENT_ID DESC
	";

	$delete_media_list = $db->queryAll($query);

	file_put_contents(LOG_PATH.'/get_delete_file_list_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")."aaaa1 \r\n".$query."\r\n", FILE_APPEND);

	/*
	* <response>
	*  <deletedlist>
	*   <item media_id="23234" scean_id="3032" created_time="" media_type="original">Y:/Archive/2011/09/23/17/2722/50231727.mxf</item>
	*   <item media_id="23234" scean_id="3232" created_time="" media_type="original">Y:/Archive/2011/09/23/17/2722/50231727.mxf</item>
	*  </deletedlist>
	* </response>
	*/

	foreach ($delete_media_list as $delete_media) {
		//file_put_contents(LOG_PATH.'/get_delete_file_list_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")."aaaa2 \r\n".print_r($delete_media, true)."\r\n", FILE_APPEND);

		if ($delete_media['type'] == 'original'
				|| $delete_media['type'] == 'rewrap'
				|| $delete_media['type'] == 'archive') {
			$root_path = $root_path_highres;
		} else {
			$root_path = $root_path_lowres;
		}

		$media_id			= $delete_media['id'];
		$media_type			= strtoupper($delete_media['type']);
		$media_path			= $delete_media['path'];
		$media_create_date	= $delete_media['created_date'];
		$backup_check_yn	= $delete_media['backup_check_yn'];
		$backup_exists_yn	= $delete_media['backup_exists_yn'];
		$backup_path		= $delete_media['backup_path'];

		$doc_delete_media = $doc_delete_list->addChild('item', $root_path.'/'.trim($media_path, '/'));
		$doc_delete_media->addAttribute('isFile',		'true');
		$doc_delete_media->addAttribute('media_id',		$media_id);
		$doc_delete_media->addAttribute('media_type',	$media_type);
		$doc_delete_media->addAttribute('created_time', $media_create_date);
		$doc_delete_media->addAttribute('backup_check_yn', $backup_check_yn);
		$doc_delete_media->addAttribute('backup_exists_yn', $backup_exists_yn);

		if(!empty($backup_path)){
			$doc_delete_media->addAttribute('backup_path',	$root_path_archive . '/'. trim($backup_path, '/'));
		}else{
			$doc_delete_media->addAttribute('backup_path',	'');
		}


		$doc_delete_media->addAttribute('delete_yn',	'N');

		if ( ! empty($delete_media['scene_path'])) {
			$scene_path = $delete_media['scene_path'];

			$doc_delete_media = $doc_delete_list->addChild('item', $scene_path);
			$doc_delete_media->addAttribute('isFile',		'true');
			$doc_delete_media->addAttribute('media_id',		$media_id);
			$doc_delete_media->addAttribute('media_type',	'catalog');
			$doc_delete_media->addAttribute('created_time', $media_create_date);
		}
	}

	$_xml = $doc_response->asXML();

	file_put_contents(LOG_PATH.'/get_delete_file_list_xml_'.date('YmdHis').'.xml', $_xml);

	echo $doc_response->asXML();
	exit;
}
catch (Exception $e) {
	$res_xml = new SimpleXMLElement("<response />");

	$err = $res_xml->addChild('error');
		$err->addChild('message', $e->getMessage());

	echo $res_xml->asXML();
}
?>