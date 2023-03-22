<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/pages/statistics_new/statistics_filter.php');
fn_checkAuthPermission($_SESSION);

$search_f = $_POST['search_f'];
$search_v = $_POST['search_v'];
$search_sdate = $_POST['search_sdate'];
$search_edate = $_POST['search_edate'];
$user_id = $_SESSION['user']['user_id'];
$is_admin = $_SESSION['user']['is_admin'];

try {
	$where = filter_group_user($is_admin, $user_id);

	if(!empty($search_f)){
		if($search_f == 'user_id' && !empty($search_v)){
			$where = "USER_ID = '".$search_v."'";
		}
		else if($search_f == 'user_nm' && !empty($search_v)){
			$where = "USER_NM LIKE '%".$search_v."%'";
		}
		else if($search_f == 'user_create_date' && !empty($search_sdate) && !empty($search_edate)){
			$where .= "AND M.CREATED_DATE BETWEEN '".$search_sdate."' AND '".$search_edate."'";
		}
		else if($search_f == 'last_login' && !empty($search_sdate) && !empty($search_edate)){
			$where .= "AND M.LAST_LOGIN_DATE BETWEEN '".$search_sdate."' AND '".$search_edate."'";
		}
		else if($search_f == 'last_regist' && !empty($search_sdate) && !empty($search_edate)){
			$where .= "AND C.LAST_CONTENT_REGIST_DATE BETWEEN '".$search_sdate."' AND '".$search_edate."'";
		}
	}

	$query = "
				SELECT		M.MEMBER_ID
							, M.USER_ID
							, M.USER_NM
							, M.CREATED_DATE
							, M.LAST_LOGIN_DATE
							, L.COUNT_LOGIN
							, C.REGIST_CONTENT_CNT
							, C.CONTENTS_SIZE
							, C.LAST_CONTENT_REGIST_DATE
				FROM		BC_MEMBER M
								LEFT OUTER JOIN (
								SELECT  USER_ID
										, COUNT(LOG_ID) COUNT_LOGIN
								FROM    BC_LOG
								WHERE   ACTION = 'login'
								GROUP BY USER_ID
							) L ON (L.USER_ID = M.USER_ID)
							LEFT OUTER JOIN (
								SELECT  L.USER_ID
										, COUNT(L.LOG_ID) REGIST_CONTENT_CNT
										, SUM(M.FILESIZE) CONTENTS_SIZE
										, MAX(C.CREATED_DATE) LAST_CONTENT_REGIST_DATE
								FROM    BC_LOG L
								LEFT OUTER JOIN BC_MEDIA M ON (L.CONTENT_ID = M.CONTENT_ID AND M.MEDIA_TYPE = 'original')
								LEFT OUTER JOIN BC_CONTENT C ON (L.CONTENT_ID = C.CONTENT_ID)
								WHERE   L.ACTION = 'regist'
								GROUP BY L.USER_ID
							) C ON (L.USER_ID = C.USER_ID)
				WHERE M.".$where."
				ORDER BY M.CREATED_DATE DESC
			";
	$query_total = "SELECT COUNT(*) FROM (".$query.") AA";
	$total = $db->queryOne($query_total);
	$data = $db->queryAll($query);

	$i = 0;
	foreach($data as $row){
		$data[$i]['format_contents_size'] = formatByte($row['contents_size']);
		$i++;
	}

	if($_POST['is_excel'] == 1)
	{
		$columns = json_decode($_POST['columns'], true);
		
		//for($i=0; $i<count($data); $i++)
		$array = array();
		foreach($data as $d)
		{
			$row = array();
			foreach($columns as $col)
			{
				if( strstr($col[0], 'date')  )
				{
					if(empty($d[$col[0]]))
					{
						$value = '';
					}
					else
					{
						$value = substr($d[$col[0]],0,4).'-'.substr($d[$col[0]],4,2).'-'.substr($d[$col[0]],6,2);
					}
				}
				else
				{
					$value = $d[$col[0]];
				}

				$row[$col[1]] = $value;
			}
			array_push($array, $row);
		}

		echo createExcelFile(_text('MSG02142'),$array);
	}
	else
	{
		echo json_encode(array(
			'success' => true,
			'total' => $total,
			'data' => $data
		));
	}
}
catch(Exception $e){
	$msg = $e->getMessage();
	if($e->getCode() == ERROR_QUERY){
		$msg = $msg.'( '.$db->last_query.' )';
	}

	die(json_encode(array(
		'success' => false,
		'msg' => $msg
	)));
}

//function formatByte($b, $p=null) {
    //$units = array("B","KB","MB","GB","TB","PB","EB","ZB","YB");
    //$c=0;

	//if(empty($b) || $b < 1){
		//return '';
	//}
    //else if(!$p && $p !== 0) {
        //foreach($units as $k => $u) {
            //if(($b / pow(1024,$k)) >= 1) {
                //$r["bytes"] = $b / pow(1024,$k);
                //$r["units"] = $u;
                //$c++;
				//$r_k = $b;
            //}
        //}
        //return number_format($r["bytes"],2) . " " . $r["units"];
    //} else {
        //return number_format($b / pow(1024,$p), 2) . " " . $units[$p];
    //}

//}
?>