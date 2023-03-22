<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/pages/statistics_new/statistics_filter.php');
fn_checkAuthPermission($_SESSION);

$user_id = $_SESSION['user']['user_id'];
$is_admin = $_SESSION['user']['is_admin'];
$limit = $_POST['limit'];
$start = $_POST['start'];

if($_POST[is_excel] == 1)
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
$total = $mdb->queryOne("select count(M.log_id) from bc_log M where M.action = 'read' and M.created_date between '".$s_date."' and '".$e_date."' ".$where);
$read_rank = array(
	'success' => true,
	'total' => $total,
	'read_rank' => array()
);
if($_POST[is_excel] != 1)
{
	$db->setLimit($limit,$start);
}
$read_log = $mdb->queryAll("select M.* from bc_log M where M.action = 'read' and M.created_date between '".$s_date."' and '".$e_date."' ".$where." order by created_date desc ");

//$read_log = $mdb->queryAll($query);

$i = $start+1;
foreach($read_log as $read)
{
	$content = $mdb->queryRow("select * from bc_content where content_id = '{$read['content_id']}'");
	$user_name = $db->queryOne("select user_nm from bc_member where user_id='{$read['user_id']}'");
	if ( empty($user_name) )
	{
		$user_name = $read['user_id'];
	}
	array_push($read_rank['read_rank'], array('rank'=>$i, 'type'=>$mappingMetaTable[$read['ud_content_id']], 'title'=>$content['title'], 'user'=>$user_name, 'date'=>$read['created_date']));

	$i++;
}

if($_POST[is_excel] == 1)
{
	$columns = json_decode($_POST[columns], true);
	$array = array();
	foreach($read_rank[read_rank] as $d)
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

	echo createExcelFile(_text('MSG02150'),$array);
}
else
{
	echo json_encode(
		$read_rank
	);
}


//print_r($down_rank);
//순위 /타입  / 파일명 / 다운로드횟수 / 생성일
//  1   movie      2       3	  2010/02/11

?>

