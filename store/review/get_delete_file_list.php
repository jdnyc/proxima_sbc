<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

define('TEMP_ROOT', 'C:/Proxima-Apps_proximas/nps/');
$logpath = TEMP_ROOT.'log/';

/*
BC_CONTENT STATUS = CONTENT_STATUS_DELETE_REQEUST
*/

//file_put_contents(LOG_PATH.'/get_list_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")."ccccc \r\n".print_r($_POST, true)."\r\n", FILE_APPEND);
$doc_response =  new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'.chr(10).'<response />');
$doc_delete_list = $doc_response->addChild('deletedlist');

//디스크 용량에 따른 삭제대상 데이터 추출 기준일 : 즉 가장오래된 날짜부터 몇일이내의 데이터를 삭제할 것인가?
$review_delete_term = 7;

//디스크 용량확인:20% 미만으로 남았을경우 가장 오래된 데이터 부터 삭제처리함
$disk_limit = 20;
$disk_delete_yn = 'N';

$disk_total = disk_total_space('d:');
$disk_free = disk_free_space('d:');
$disk_limit = $disk_total * $disk_limit / 100;

try
{
	if($disk_free < $disk_limit){
		$disk_delete_yn = 'Y';
	}

	define("ROOT_MAIN_STORAGE", 'D:/Storage');
	$xml = urldecode(file_get_contents('php://input'));

	$doc = simplexml_load_string($xml);


	$type   = $doc->getdeletedlist['type'];
	if($type == '1')
	{
		$root_path = ROOT_MAIN_STORAGE;
	}
	else
	{
		$root_path = ROOT_MAIN_STORAGE;
	}

	/**
	 * 삭제대상 조회
	 * 1. 설정된 개월수 이전의 데이터
	 * 		- 영구보관 일 경우 9999개월 이전의 데이터를 삭제 하기때문에 실제 삭제가 안됨
	 * 2. 디스크가 20% 미만으로 남았을 때 가장 오래된 데이터의 날짜 + 7일 까지의 발생 데이터
	 */
/*
	$query = "
	SELECT	DISTINCT A.CONTENT_ID
			,A.CREATED_DATE
			,M.MEDIA_ID AS ID
			,M.PATH
			,M.MEDIA_TYPE AS TYPE
	FROM	(
			SELECT	A.CONTENT_ID
					,A.CREATED_DATE
			FROM	NPS_WORK_LIST A
					LEFT OUTER JOIN NPS_REVIEW B ON(B.NPS_WORK_LIST_ID = A.NPS_WORK_LIST_ID)
			WHERE	A.WORK_TYPE		= 'review'
			--AND		B.BRODYMD	< '20150606'
			AND		B.BRODYMD	< TO_CHAR(ADD_MONTHS(SYSDATE, (	SELECT	DECODE(CODE, -1, 9999, CODE)
																FROM	BC_CODE
																WHERE	CODE_TYPE_ID = 231	-- Review Metadata 삭제기간
																AND		ID = 515) * -1), 'YYYYMMDD')
			UNION ALL
			SELECT	A.CONTENT_ID
					,A.CREATED_DATE
			FROM	NPS_WORK_LIST A
					LEFT OUTER JOIN NPS_REVIEW B ON(B.NPS_WORK_LIST_ID = A.NPS_WORK_LIST_ID)
			WHERE	A.WORK_TYPE		= 'review'
			AND		B.BRODYMD	< 	COALESCE((	SELECT	TO_CHAR(TO_DATE(MIN(CREATED_DATE), 'YYYYMMDDHH24MISS') + $review_delete_term, 'YYYYMMDDHH24MISS')
											FROM	NPS_WORK_LIST
											WHERE	WORK_TYPE	= 'review'
											AND		'Y'			= '$disk_delete_yn'), '')
			) A
			LEFT OUTER JOIN BC_CONTENT C ON(C.CONTENT_ID = A.CONTENT_ID)
			LEFT OUTER JOIN BC_MEDIA_REVIEW M ON(M.CONTENT_ID = A.CONTENT_ID)
	WHERE	C.IS_DELETED = 'N'
	AND		M.FLAG IS NULL
	AND		M.MEDIA_TYPE = 'proxy'
	AND		M.DELETE_DATE IS NULL
	";
*/
	$query = "
		SELECT	A.CONTENT_ID, A.ID, A.CREATED_DATE, S.PATH || A.PATH AS PATH, A.TYPE,
				DECODE(A.SCENE_PATH, NULL, NULL, S.PATH || A.SCENE_PATH) AS SCENE_PATH
		FROM	(
				SELECT	M.CONTENT_ID, M.MEDIA_ID AS ID, M.CREATED_DATE, M.PATH, M.MEDIA_TYPE AS TYPE, M.STORAGE_ID,
						Z.PATH AS SCENE_PATH
				FROM	BC_CONTENT C
						LEFT OUTER JOIN BC_MEDIA M ON (M.CONTENT_ID = C.CONTENT_ID)
						LEFT OUTER JOIN BC_SCENE Z ON (M.MEDIA_ID = Z.MEDIA_ID)
				WHERE	C.STATUS IN ('".CONTENT_STATUS_DELETE_APPROVE."', '".CONTENT_STATUS_YOUTUBE_DELETE_REQEUST."')
				) A
				LEFT OUTER JOIN BC_STORAGE S ON(S.STORAGE_ID = A.STORAGE_ID)
	";

	$delete_media_list = $db->queryAll($query);


	//print_r($delete_media_list); exit;
	/*
	* <response>
	*  <deletedlist>
	*   <item media_id="23234" scean_id="3032" created_time="" media_type="original">Y:/Archive/2011/09/23/17/2722/50231727.mxf</item>
	*   <item media_id="23234" scean_id="3232" created_time="" media_type="original">Y:/Archive/2011/09/23/17/2722/50231727.mxf</item>
	*  </deletedlist>
	* </response>
	*/

	foreach ($delete_media_list as $delete_media) {
		$media_id			= $delete_media['id'];
		$media_type			= strtoupper($delete_media['type']);
		$media_path			= $delete_media['path'];
		$media_create_date	= $delete_media['created_date'];

		$doc_delete_media = $doc_delete_list->addChild('item', $media_path);
		$doc_delete_media->addAttribute('isFile',		'true');
		$doc_delete_media->addAttribute('media_id',		$media_id);
		$doc_delete_media->addAttribute('media_type',	$media_type);
		$doc_delete_media->addAttribute('created_time', $media_create_date);

		if ( ! empty($delete_media['scene_path'])) {
			$scene_path = $delete_media['scene_path'];

			$doc_delete_media = $doc_delete_list->addChild('item', $scene_path);
			$doc_delete_media->addAttribute('isFile',		'true');
			$doc_delete_media->addAttribute('media_id',		$media_id);
			$doc_delete_media->addAttribute('media_type',	'catalog');
			$doc_delete_media->addAttribute('created_time', $media_create_date);
		}
	}


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
