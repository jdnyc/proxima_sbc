<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$limit =		$_REQUEST['limit'];
$start =		$_REQUEST['start'];
$arc_s_date =	$_REQUEST['arc_start_date'];
$arc_e_date =	$_REQUEST['arc_end_date'];
$search_value = $_REQUEST['search_value'];
$tape_media = $_REQUEST['tape_media'];

$is_excel = $_REQUEST['is_excel'];

$genre_category = trim($_REQUEST['genre_category']);
if(!is_numeric($genre_category)) {
	$genre_category = 0;
}

if(empty($start)){
	$start = 0;
}
if(empty($limit)){
	$limit = 50;
}

//현재 유저의 content 테이블 불러오기
try {

	//카테고리
	if($genre_category != '0') {
		$genre_category_query = " AND	A.CATEGORY_FULL_PATH LIKE '%/$genre_category%' ";
	}
	// 테이프 종류(메인/백업)
	if($tape_media != 'all') {
		$tape_media_query = " AND A.MEDIA = '".$tape_media."' ";
	}
	// 검색어
	if( !empty($search_value) ) {
		$search_value = $db->escape($search_value);
		$search_value_query = " AND A.TITLE LIKE '%".$search_value."%' ";
	}
	
	$query = "
			SELECT	A.*, TR.APPR_TIME, M.FILESIZE, CA.CATEGORY_TITLE, UD.UD_CONTENT_TITLE
			FROM	(
						SELECT	AI.*, C.TITLE, C.CATEGORY_ID, C.UD_CONTENT_ID, C.CATEGORY_FULL_PATH
						FROM	BC_CONTENT C,
								(
									SELECT	CONTENT_ID, ARCHIVE_ID, MEDIA, TAPE
									FROM	ARCHIVE_INFO
									GROUP BY CONTENT_ID, ARCHIVE_ID, MEDIA, TAPE
								) AI
						WHERE	AI.CONTENT_ID = C.CONTENT_ID
						AND		C.IS_DELETED = 'N'
						AND		C.STATUS = '2'
					) A
					LEFT OUTER JOIN BC_MEDIA M ON M.CONTENT_ID = A.CONTENT_ID AND MEDIA_TYPE = 'archive'
					LEFT OUTER JOIN TB_REQUEST TR ON TR.DAS_CONTENT_ID = A.CONTENT_ID AND TR.REQ_TYPE ='archive' AND TR.REQ_STATUS = '".ARCHIVE_APPROVE."'
					LEFT OUTER JOIN BC_CATEGORY CA ON CA.CATEGORY_ID = A.CATEGORY_ID
					LEFT OUTER JOIN BC_UD_CONTENT UD ON UD.UD_CONTENT_ID = A.UD_CONTENT_ID
			WHERE	TR.APPR_TIME BETWEEN '$arc_s_date' AND '$arc_e_date'
	".$genre_category_query.$tape_media_query.$search_value_query;
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/get_tape_id_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] query ===> '.$query."\r\n", FILE_APPEND);
	if($is_excel == 'Y') {

		$results = $db->queryAll($query." ORDER BY TR.APPR_TIME DESC");

		$results_excel = array();
		$num = 1;
		foreach($results as $f => $result){
			$results_excel[$f]['순번'] = $num;
			$results_excel[$f]['Tape Media'] = $result['media'];
			$results_excel[$f]['Tape ID'] = $result['tape'];
			$results_excel[$f]['아카이브 ID'] = $result['archive_id'];
			$results_excel[$f]['크기'] = formatByte($result['filesize']);
			$results_excel[$f]['크기(Byte)'] = $result['filesize'];
			$results_excel[$f]['콘텐츠 유형'] = $result['ud_content_title'];
			$results_excel[$f]['장르'] = $result['category_title'];
			$results_excel[$f]['제목'] = $result['title'];
			$results_excel[$f]['승인일시'] = date('Y-m-d H:i:s', strtotime($result['appr_time']));
			$num = $num + 1;
		}
		echo createExcel('Tape_Library_현황', $results_excel);

		exit;
	}
	
	$total_query = "SELECT COUNT(*) FROM (".$query.") T1";
	$total = $db->queryOne($total_query);
	
	$db->setLimit($limit,$start);
	$results = $db->queryAll($query." ORDER BY TR.APPR_TIME DESC");

	$data = array(
		'success'		=> true,
		'data'			=> $results,
		'total_list'	=> $total,
		'query'			=> $query
	);

	echo json_encode($data);	

} catch (Exception $e) {
	$data = array(
		'success'	=> false,
		'msg'		=> $e->getMessage(),
		'query' => $db->last_query
	);

	echo json_encode($data);
}
?>
