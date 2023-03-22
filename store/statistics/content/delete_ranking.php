<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/pages/statistics_new/statistics_filter.php');
fn_checkAuthPermission($_SESSION);

$user_id = $_SESSION['user']['user_id'];
$is_admin = $_SESSION['user']['is_admin'];
$limit = $_POST['limit'];
$start = $_POST['start'];

if($_POST['is_excel'] == 1)
{
	$s_date = $_POST['search_sdate'];
	$e_date = $_POST['search_edate'];
}
else
{
	$s_date = $_POST['start_date'];
	$e_date = $_POST['end_date'];
}

$where = ' AND L.'.filter_group_user($is_admin, $user_id);

$total_q = "select count(L.content_id) from bc_log L where L.action = 'delete' and L.created_date between '".$s_date."' and '".$e_date."' ".$where;
$total = $mdb->queryOne($total_q);



if($_POST['is_excel'] != 1)
{
	$db->setLimit($limit,$start);
}

$query = "
	SELECT	L.CONTENT_ID, L.USER_ID, L.CREATED_DATE, D.REASON
	FROM	BC_LOG L, BC_DELETE_CONTENT D
	WHERE	L.CONTENT_ID = D.CONTENT_ID AND
			L.ACTION = 'delete' AND
			L.CREATED_DATE BETWEEN '".$s_date."' AND '".$e_date."' AND
			D.STATUS = 'SUCCESS'
			".$where."
";
$order = " order by l.created_date desc ";
//$del_log = $mdb->queryAll("select content_id, user_id, created_date, description from bc_log where action = 'delete'  and created_date between ".$s_date." and ".$e_date." order by created_date desc");

$del_log = $mdb->queryAll($query.$order);


$del_rank = array(
	'q' => $query,
	'success' => true,
	'total' => $total,
	'del_rank' => array()
);

$i = $start+1;
foreach($del_log as $del)
{
	$content = $mdb->queryRow("select bs_content_id, title, ud_content_id from bc_content where content_id = '{$del['content_id']}'");
	$get_type = $mdb->queryOne("select ud_content_title from bc_ud_content where ud_content_id = '{$content['ud_content_id']}'");

	$User_Name = $mdb->queryOne("select user_nm from bc_member where user_id = '{$del['user_id']}'");

	array_push($del_rank['del_rank'], array('rank'=>$i, 'type'=>$get_type, 'title'=>$content['title'], 'user'=>$User_Name, 'description'=>$del['reason'], 'date'=>$del['created_date']));

	$i++;
}

if($_POST['is_excel'] == 1)
{
	$columns = json_decode($_POST['columns'], true);
	$array = array();
	foreach($del_rank['del_rank'] as $d)
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

	echo createExcelFile(_text('MSG02148'),$array);
}
else
{
	echo json_encode(
		$del_rank
	);
}



//print_r($down_rank);
//순위 /타입  / 파일명 / 삭제자 / 삭제일
//  1   movie      2       3	   2010/02/11

?>

