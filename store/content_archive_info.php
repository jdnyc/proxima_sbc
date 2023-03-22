<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
fn_checkAuthPermission($_SESSION);

$limit =		$_POST['limit'];
$start =		$_POST['start'];
$s_date =		$_POST['start_date'];
$e_date =		$_POST['end_date'];
$index =		$_POST['index'];
$search_val	=$_POST['search_val'];

$delete_combo = $_POST['delete_combo'];


if(empty($limit)){
    $limit = 100;
}

//현재 유저의 content 테이블 불러오기
try
{
	$action = $_POST['action'];
	$d_action = $_POST['date_mode'];
	$end_date = $_POST['end_date'];
	$start_date = $_POST['start_date'];

	$_where = array();
	if( $action != 'all' && !empty($action) ){
		array_push($_where, " A.STATUS = '".$action."' ");
	}

	if( $d_action != 'disable' && !empty($d_action)){
		array_push($_where, " A.".$d_action." BETWEEN '".$start_date."' AND '".$end_date."' ");
	}

	$where = count($_where) > 0 ? "	AND ".join(' AND ', $_where) : "";


	$db->setLimit($limit,$start);
	/*
	$query_m = "
		SELECT  A.*,
				BC.BS_CONTENT_ID, BC.CATEGORY_FULL_PATH, BC.TITLE, 
				BM.MEDIA_TYPE, BM.FILESIZE AS FILE_SIZE, BM.PATH,
				BU.UD_CONTENT_TITLE,
				BB.BS_CONTENT_TITLE
		FROM    BC_ARCHIVE_REQUEST A 
				LEFT JOIN BC_CONTENT BC
				ON  BC.CONTENT_ID = A.CONTENT_ID
				LEFT JOIN (
					SELECT  *
					FROM    BC_MEDIA
					WHERE   MEDIA_TYPE = 'original' 
				  ) BM
				ON A.CONTENT_ID = BM.CONTENT_ID,
				BC_UD_CONTENT BU,
				BC_BS_CONTENT BB
		WHERE   BU.UD_CONTENT_ID = BC.UD_CONTENT_ID
		AND     BB.BS_CONTENT_ID = BC.BS_CONTENT_ID
		".$where."
		ORDER   BY A.REQUEST_ID DESC 
	";
	*/
	
	$query_m = "
		SELECT *
		FROM (SELECT  A.*,
						BC.BS_CONTENT_ID, BC.CATEGORY_FULL_PATH, BC.TITLE, 
						BM.MEDIA_TYPE, BM.FILESIZE AS FILE_SIZE, BM.PATH,
						BU.UD_CONTENT_TITLE,
						BB.BS_CONTENT_TITLE
				FROM    BC_ARCHIVE_REQUEST A 
						LEFT JOIN BC_CONTENT BC
						ON  BC.CONTENT_ID = A.CONTENT_ID
						LEFT JOIN (
							SELECT  *
							FROM    BC_MEDIA
							WHERE   MEDIA_TYPE = 'original' 
						  ) BM
						ON A.CONTENT_ID = BM.CONTENT_ID,
						BC_UD_CONTENT BU,
						BC_BS_CONTENT BB
				WHERE   BU.UD_CONTENT_ID = BC.UD_CONTENT_ID
				AND     BB.BS_CONTENT_ID = BC.BS_CONTENT_ID
				AND		BC.IS_GROUP IN ('G', 'I')
				".$where."
			UNION ALL 
		    SELECT  A.*,
					BC.BS_CONTENT_ID, BC.CATEGORY_FULL_PATH, BC.TITLE, 
					BM.MEDIA_TYPE, BM.FILESIZE AS FILE_SIZE, BM.PATH,
					BU.UD_CONTENT_TITLE,
					BB.BS_CONTENT_TITLE
			FROM    BC_ARCHIVE_REQUEST A 
			LEFT JOIN BC_CONTENT BC
			ON  BC.CONTENT_ID = A.ORI_CONTENT_ID
			LEFT JOIN (
				SELECT  *
				FROM    BC_MEDIA
				WHERE   MEDIA_TYPE = 'original' 
		  	) BM
			ON A.CONTENT_ID = BM.CONTENT_ID,
			BC_UD_CONTENT BU,
			BC_BS_CONTENT BB
			WHERE   BU.UD_CONTENT_ID = BC.UD_CONTENT_ID
			AND     A.REQUEST_TYPE = 'PFR'
			AND     A.STATUS = 'REQUEST'
			AND     BB.BS_CONTENT_ID = BC.BS_CONTENT_ID
			".$where."
		) RRR
		ORDER BY RRR.REQUEST_ID DESC
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
				//echo("\n $result[content_id] $query : $p :  $i \n");
				$re = $db->queryOne($query);
				$r_path.=$re;
				$i++;
			}
		}

		$datas = $result;
		//$datas['file_size'] = $result['filesize'];
		$datas['category'] = $r_path;
		
		$datas['file_size'] = formatByte($datas['file_size']);

		if($result['status'] =='REJECT'){
			$datas['date_time'] = $result['reject_datetime'];
		}else{
			$datas['date_time'] = $result['approve_datetime'];
		}

		if($result['request_type'] == 'PFR'){
			$frame_rate = getFrameRate($result['ori_content_id']);
			$tc_start	= round($result['start_frame'] * $frame_rate);
			$tc_end		= round($result['end_frame'] * $frame_rate);
			$datas['start_frame'] = frameToTimeCode($tc_start,$result['ori_content_id']);
			$datas['end_frame'] = frameToTimeCode($tc_end,$result['ori_content_id']);
		}
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
