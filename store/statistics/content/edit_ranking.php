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

// 사용자 컨텐츠 정의 목록 가져오기
$ud_content_list = $db->queryAll("select ud_content_id, ud_content_title from bc_ud_content order by show_order");
foreach ($ud_content_list as $ud_content)
{
	$mappingMetaTable[$ud_content['ud_content_id']] = $ud_content['ud_content_title'];
}

$total = $mdb->queryOne("select count(M.content_id) from bc_log M where M.action = 'edit' and M.created_date between '".$s_date."' and '".$e_date."' ".$where);
$edit_rank = array(
	'success' => true,
	'total' => $total,
	'edit_rank' => array()
);

if($_POST['is_excel'] != 1)
{
	$db->setLimit($limit,$start);
}

$edit_log = $db->queryAll("select M.* from bc_log M where M.action = 'edit' and M.created_date between '".$s_date."' and '".$e_date."' ".$where." order by M.created_date desc");

$i = $start+1;
foreach($edit_log as $edit)
{
	$content = $db->queryRow("select * from bc_content where content_id = '{$edit['content_id']}'");
	$user_name = $db->queryOne("select user_nm from bc_member where user_id='{$edit['user_id']}'");

	array_push($edit_rank['edit_rank'], array('rank'=>$i, 'type'=>$mappingMetaTable[$edit['ud_content_id']], 'title'=>$content['title'], 'user'=>$user_name, 'date'=>$edit['created_date'], 'description'=>$edit['description']));

$i++;
}

if($_POST['is_excel'] == 1)
{
	$columns = json_decode($_POST['columns'], true);
	$array = array();
	foreach($edit_rank['edit_rank'] as $d)
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

	echo createExcelFile(_text('MSG02147'),$array);
}
else
{
	echo json_encode(
		$edit_rank
	);
}

//print_r($down_rank);
//순위 /타입  / 파일명 / 수정한사람 / 수정일
//  1   movie      2       3	   2010/02/11

?>

