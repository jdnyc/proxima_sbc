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

$where = ' AND M.'.filter_group_user($is_admin, $user_id);
$total = $db->queryOne("select count(M.content_id) from bc_log M where M.action = 'download' and M.created_date between '".$s_date."' and '".$e_date."' ".$where);

$del_rank = array(
	'success' => true,
	'total' => $total,
	'down_rank' => array()
);

$query = "select M.content_id, M.user_id, M.created_date, M.description
							from bc_log M
							where M.action = 'download'
							and M.created_date between '".$s_date."' and '".$e_date."'
							".$where."
							order by created_date desc";

if($_POST['is_excel'] != 1)
{
	$db->setLimit($limit,$start);
}
$del_log = $mdb->queryAll($query);

$i = $start+1;
foreach($del_log as $del)
{
	$content = $mdb->queryRow("select bs_content_id, title, ud_content_id from bc_content where content_id = '{$del['content_id']}'");
	$get_type = $mdb->queryOne("select ud_content_title from bc_ud_content where ud_content_id = '{$content['ud_content_id']}'");

	$User_Name = $mdb->queryOne("select user_nm from bc_member where user_id = '{$del['user_id']}'");

	array_push($del_rank['down_rank'], array('rank'=>$i, 'type'=>$get_type, 'title'=>$content['title'], 'user'=>$User_Name, 'description'=>$del['description'], 'date'=>$del['created_date'], 'content_id'=>$del['content_id']));

	$i++;
}

if($_POST['is_excel'] == 1)
{
	$columns = json_decode($_POST['columns'], true);
	$array = array();
	foreach($del_rank['down_rank'] as $d)
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

	echo createExcelFile(_text('MSG02149'),$array);
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

