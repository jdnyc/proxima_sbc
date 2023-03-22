<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
fn_checkAuthPermission($_SESSION);

$limit =		$_POST['limit'];
$start =		$_POST['start'];

if(empty($limit)){
    $limit = 100;
}

//현재 유저의 content 테이블 불러오기
try
{
	$start_date = $_POST['start_date'];
	$end_date = $_POST['end_date'];

	$db->setLimit($limit,$start);
	
	$query_m = "
		SELECT	C.CONTENT_ID,
				C.BS_CONTENT_ID, 
				C.CATEGORY_FULL_PATH, 
				C.TITLE,
				C.STATUS,
				C.CREATED_DATE, 
				BM.MEDIA_TYPE, 
				BM.FILESIZE AS FILE_SIZE, 
				BM.PATH,
				BU.UD_CONTENT_TITLE,
				BB.BS_CONTENT_TITLE
		FROM	BC_CONTENT C
			INNER JOIN BC_MEDIA BM ON (C.CONTENT_ID = BM.CONTENT_ID AND BM.MEDIA_TYPE = 'original')
			LEFT OUTER JOIN BC_UD_CONTENT BU ON (BU.UD_CONTENT_ID = C.UD_CONTENT_ID)
			LEFT OUTER JOIN BC_BS_CONTENT BB ON (BB.BS_CONTENT_ID = C.BS_CONTENT_ID)  
		WHERE	1=1
		AND	C.STATUS IN ('0', '2')
		AND	C.IS_GROUP IN ('G','I')
		AND	C.IS_DELETED = 'N'
		AND	C.CONTENT_ID IN (
				SELECT	CONTENT_ID
				FROM	BC_CONTENT
				EXCEPT
				SELECT	CONTENT_ID
				FROM	BC_ARCHIVE_REQUEST
				WHERE	STATUS IN ('REQUEST', 'PROCESSING', 'COMPLETE')
				AND		REQUEST_TYPE = 'ARCHIVE'
			)
		AND	C.CONTENT_ID IN (
				SELECT	C.CONTENT_ID
				FROM	BC_CONTENT C
				WHERE	C.CREATED_DATE <= '{$end_date}'
				EXCEPT
				SELECT	C.CONTENT_ID
				FROM	(
					SELECT	C.CONTENT_ID
							,MAX(L.CREATED_DATE) OVER(PARTITION BY L.CONTENT_ID) AS READ_DATE
					FROM	BC_CONTENT C
						LEFT OUTER JOIN BC_LOG L ON(L.ACTION = 'read' AND L.CONTENT_ID = C.CONTENT_ID)
					WHERE	1=1
					AND		L.CREATED_DATE <= '{$end_date}'
				) C
				WHERE	C.READ_DATE BETWEEN '{$start_date}' AND '{$end_date}'
			)

		ORDER BY	CONTENT_ID DESC
	";

	$results = $db->queryAll($query_m);
	$total_query = "select count(*) from (".$query_m.") t1";
	$total = $db->queryOne($total_query);


	$data = array();
	$cur_date = date('YmdHis');

	foreach($results as $result){

		$path = $result['category_full_path'];
		$path = explode("/",$path);
		$r_path="";

		$i=0;
		$c=count($path);
		foreach($path as $p)
		{
			if($c>3 && $i>0) $r_path.=" > ";
			if($p && $p!='0'){
				$query="select category_title from bc_category where category_id='$p'";
				$re = $db->queryOne($query);
				$r_path.=$re;
				$i++;
			}
		}

		$datas = $result;
		$datas['category'] = $r_path;
		
		$datas['file_size'] = formatByte($datas['file_size']);

		array_push($data, $datas);
	}
		$data = array(
		'success'		=> true,
		'data'			=> $data,
		'total_list'	=> $total,
		'query'			=>	$query_m
	);

	echo json_encode($data);

}
catch (Exception $e)
{
	echo _text('MN01039').' : '.$e->getMessage();//'오류 : '
}
?>
