<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
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
    $limit = 200;
}

//현재 유저의 content 테이블 불러오기
try
{
	$action = $_POST['action'];
	$d_action = $_POST['date_mode'];
	$end_date = $_POST['end_date'];
	$start_date = $_POST['start_date'];
	$content_type = $_POST['content_type'];
	$searchType = $_POST['search_type'];
	$searchKeyword = $_POST['search_keyword'];

	switch( $_SESSION['user']['lang'] ){
		case 'ko':
			$code_lang = 'NAME';
		break;
		case 'en':
			$code_lang = 'ENAME';
        break;
        case 'zh_CN':
		case 'ot':
			$code_lang = 'OTHER';
		break;
		default:
			$code_lang = 'ENAME';
		break;
	}
    $_userSearch = array();
	$_where = array();
	if( $action != 'all' && !empty($action) ){
		array_push($_where, " DL.STATUS = '".$action."' ");
	}

	if( $d_action != 'disable' && !empty($d_action)){
		if($d_action === 'created_date') {
			array_push($_where, " DL.".$d_action." BETWEEN '".$start_date."' AND '".$end_date."' ");
		} else {
			array_push($_where, " BC.".$d_action." BETWEEN '".$start_date."' AND '".$end_date."' ");
		}
	} else {
		array_push($_where, " ( DL.created_date BETWEEN '".$start_date."' AND '".$end_date."' OR BC.expired_date BETWEEN '".$start_date."' AND '".$end_date."' ) ");
	}

	if( $content_type != 'all' && !empty($content_type) ){
		array_push($_where, " BU.UD_CONTENT_ID = ".$content_type." ");
	}

	// 검색
	if($searchKeyword !== ""){
		switch($searchType){
			case 'all':
				array_push($_userSearch, " TITLE LIKE '%".$searchKeyword."%' ");
				array_push($_userSearch, " REG_USER_NM LIKE '%".$searchKeyword."%' ");
				array_push($_userSearch, " DEL_REQ_USER_NM LIKE '%".$searchKeyword."%' ");
			break;
			case 'title':
				array_push($_userSearch, " TITLE LIKE '%".$searchKeyword."%' ");
			break;
			case 'reg_user_nm':
				// $_userSearch =  "WHERE REG_USER_NM LIKE '%".$searchKeyword."%' ";
				array_push($_userSearch, " REG_USER_NM LIKE '%".$searchKeyword."%' ");
			break;
			case 'del_req_user_nm':
				array_push($_userSearch, " DEL_REQ_USER_NM LIKE '%".$searchKeyword."%' ");
				// $_userSearch =  "WHERE DEL_REQ_USER_NM LIKE '%".$searchKeyword."%' ";
			break;
		}
	}
	
	$where = count($_where) > 0 ? "	AND ".join(' AND ', $_where) : "";
	$userSearch = count($_userSearch) > 0 ? "	WHERE ".join(' OR ', $_userSearch) : "";

	$db->setLimit($limit,$start);
	/*
	$query_m = "
		SELECT  DL.CONTENT_ID, DL.DELETE_TYPE, DL.REASON, DL.ID AS DELETE_ID, DL.STATUS,
				BC.STATUS AS CONTENT_STATUS, BC.BS_CONTENT_ID, BC.UD_CONTENT_ID, BC.CATEGORY_FULL_PATH, BC.TITLE, 
				BC.REG_USER_ID, BC.CREATED_DATE,
				BM.MEDIA_ID, BM.STATUS AS MEDIA_STATUS, BM.MEDIA_TYPE, BM.FILESIZE, BM.PATH, BM.EXPIRED_DATE, BM.DELETE_DATE,
				DELETE_STATUS_CODE.".$code_lang." AS FLAG,
				DELETE_TYPE_CODE.".$code_lang." AS DELETE_TYPE_NAME,
				BU.UD_CONTENT_TITLE,
				BB.BS_CONTENT_TITLE
		FROM    BC_DELETE_CONTENT DL 
				  LEFT JOIN BC_CONTENT BC
				  ON  BC.CONTENT_ID = DL.CONTENT_ID
				  LEFT JOIN (
					SELECT  *
					FROM    BC_MEDIA
					WHERE   MEDIA_TYPE = 'original' 
				  ) BM
				  ON DL.CONTENT_ID = BM.CONTENT_ID,
				(
					SELECT CO.CODE, CO.NAME, CO.ENAME, CO.OTHER
					FROM  BC_CODE CO, BC_CODE_TYPE CT
					WHERE CO.CODE_TYPE_ID = CT.ID
					AND CT.CODE = 'CONTENT_DELETE_STATUS'
				) DELETE_STATUS_CODE,
				(
					SELECT CO.CODE, CO.NAME, CO.ENAME, CO.OTHER
					FROM  BC_CODE CO, BC_CODE_TYPE CT
					WHERE CO.CODE_TYPE_ID = CT.ID
					AND CT.CODE = 'CONTENT_DELETE_TYPE'
				) DELETE_TYPE_CODE,
				BC_UD_CONTENT BU,
				BC_BS_CONTENT BB
		WHERE   BU.UD_CONTENT_ID = BC.UD_CONTENT_ID
		AND     BB.BS_CONTENT_ID = BC.BS_CONTENT_ID
		AND		DL.STATUS = DELETE_STATUS_CODE.CODE
		AND		DL.DELETE_TYPE = DELETE_TYPE_CODE.CODE
		".$where."
		ORDER   BY DL.ID DESC 
	";
	*/
	$query_m = "
	SELECT * FROM(
        SELECT  DL.CONTENT_ID, DL.DELETE_TYPE, DL.REASON, DL.ID AS DELETE_ID, DL.STATUS, DL.CREATED_DATE AS DEL_REQ_DATE, DL.REG_USER_ID AS DEL_REQ_USER,
        (SELECT USER_NM FROM BC_MEMBER WHERE USER_ID=DL.REG_USER_ID) AS DEL_REQ_USER_NM,
		(SELECT USER_NM FROM BC_MEMBER WHERE USER_ID=BC.REG_USER_ID) AS REG_USER_NM,
		(SELECT MEDIA_ID FROM BC_USRMETA_CONTENT WHERE USR_CONTENT_ID=BC.CONTENT_ID) AS MEDIA_ID,
				BC.BS_CONTENT_ID, BC.CATEGORY_FULL_PATH, BC.TITLE, BC.UD_CONTENT_ID,
				BC.REG_USER_ID, BC.CREATED_DATE,
				BM.MEDIA_TYPE, BM.FILESIZE AS FILE_SIZE, BM.PATH, BC.EXPIRED_DATE, BM.DELETE_DATE,
				DELETE_STATUS_CODE.".$code_lang." AS FLAG,
				DELETE_TYPE_CODE.".$code_lang." AS DELETE_TYPE_NAME,
				BU.UD_CONTENT_TITLE,
				BB.BS_CONTENT_TITLE
		FROM    BC_DELETE_CONTENT DL 
				  LEFT JOIN BC_CONTENT BC
				  ON  BC.CONTENT_ID = DL.CONTENT_ID
				  LEFT JOIN (
					SELECT  *
					FROM    BC_MEDIA
					WHERE   MEDIA_TYPE = 'original' 
				  ) BM
				  ON DL.CONTENT_ID = BM.CONTENT_ID,
				(
					SELECT CO.CODE, CO.NAME, CO.ENAME, CO.OTHER
					FROM  BC_CODE CO, BC_CODE_TYPE CT
					WHERE CO.CODE_TYPE_ID = CT.ID
					AND CT.CODE = 'CONTENT_DELETE_STATUS'
				) DELETE_STATUS_CODE,
				(
					SELECT CO.CODE, CO.NAME, CO.ENAME, CO.OTHER
					FROM  BC_CODE CO, BC_CODE_TYPE CT
					WHERE CO.CODE_TYPE_ID = CT.ID
					AND CT.CODE = 'CONTENT_DELETE_TYPE'
				) DELETE_TYPE_CODE,
				BC_UD_CONTENT BU,
				BC_BS_CONTENT BB
		WHERE   BU.UD_CONTENT_ID = BC.UD_CONTENT_ID
		AND     BB.BS_CONTENT_ID = BC.BS_CONTENT_ID
		AND		DL.STATUS = DELETE_STATUS_CODE.CODE
		AND		DL.DELETE_TYPE = DELETE_TYPE_CODE.CODE
		".$where."
		ORDER   BY DL.ID DESC 
	)".$userSearch."
	";

    $results = $db->queryAll($query_m);
	$total_query = "select count(*) from (".$query_m.") t1";
	$total = $db->queryOne($total_query);
    if(empty($results)) {
        $data = array(
            'success'		=> true,
            'data'			=> array(),
            'total_list'	=> 0,
            'query'			=>	$query_m
        );
    
        die(json_encode($data));
    }


	$data = array();
	$cur_date = date('YmdHis');
	$mapping_type = array(
		'delete_hr'			=>	_text('MN01078'),//원본삭제
		'delete_request'	=>	_text('MN00034')//삭제
	);
	foreach($results as $result){

		$path = $result['category_full_path'];
		$path = explode("/",$path);
		$r_path="";
		$result['file_size']= formatByte($result['file_size']);

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



function formatByte($b, $p=null) {
    $units = array("B","KB","MB","GB","TB","PB","EB","ZB","YB");
    $c=0;

	if(empty($b) || $b < 1){
		return '';
	}
    else if(!$p && $p !== 0) {
        foreach($units as $k => $u) {
            if(($b / pow(1024,$k)) >= 1) {
                $r["bytes"] = $b / pow(1024,$k);
                $r["units"] = $u;
                $c++;
				$r_k = $b;
            }
        }
        return number_format($r["bytes"],2) . " " . $r["units"];
    } else {
        return number_format($b / pow(1024,$p), 2) . " " . $units[$p];
    }

}
?>
