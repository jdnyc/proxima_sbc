<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$keyword = $_POST['keyword'];
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];
$start_date = date('YmdHis', strtotime($start_date));
$end_date = date('YmdHis', strtotime($end_date));

$limit = $_POST['limit'];
$start = $_POST['start'];

if(empty($limit)) $limit = 20;
if(empty($start)) $start = 0;

if(!empty($keyword)) $keyword_q = " AND A.TITLE LIKE '%".$keyword."%' ";
$date_q = " AND A.CREATED_DATE BETWEEN '".$start_date."' AND '".$end_date."' ";

$db->setLimit($limit, $start);
$result = $db->queryAll("
	SELECT	A.*, B.UD_CONTENT_TITLE, C.USER_NM AS REG_USER_NM
	FROM	BC_CONTENT A
			LEFT OUTER JOIN BC_UD_CONTENT B
				ON A.UD_CONTENT_ID=B.UD_CONTENT_ID
			LEFT OUTER JOIN BC_MEMBER C
				ON A.REG_USER_ID=C.USER_ID
	WHERE	A.STATUS ='".CONTENT_STATUS_WATCHFOLDER_REG_WAIT."'
	".$keyword_q.$date_q."
");
foreach($result as $i => $res) {
	$result[$i]['category_full_path_name'] = getCategoryPathTitle($res['category_full_path'], ' > ');
	$result[$i]['created_date'] = date('Y-m-d H:i:s', strtotime($res['created_date']));
	if($result[$i]['reg_user_nm'] == '') $result[$i]['reg_user_nm'] = $result[$i]['user_id'];
}

echo json_encode(array(
	'success' => true,
	'data' => $result
));
