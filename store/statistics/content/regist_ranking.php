<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/pages/statistics_new/statistics_filter.php');
fn_checkAuthPermission($_SESSION);

$user_id = $_SESSION['user']['user_id'];
$is_admin = $_SESSION['user']['is_admin'];
try
{

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

// 사용자 컨텐츠 정의 목록 가져오기
$ud_content_list = $db->queryAll("select ud_content_id, ud_content_title from bc_ud_content order by show_order");
foreach ($ud_content_list as $ud_content)
{
	$mappingMetaTable[$ud_content['ud_content_id']] = $ud_content['ud_content_title'];
}

$where = filter_group_user($is_admin, $user_id);

$total = $mdb->queryOne("select count(*)
							from bc_content c
							inner join bc_member M on c.reg_user_id=M.user_id
							where (c.status='2' or c.status='0')
							and c.is_deleted='N'
							and c.created_date between '".$s_date."' and '".$e_date."'
							and M.".$where);

$regist_rank = array(
	'success' => true,
	'total' => $total,
	'regist_rank' => array()
);

if($_POST['is_excel'] != 1)
{
	$db->setLimit($limit,$start);
}
$query_log = "select *
				from bc_content c
				inner join bc_member M on c.reg_user_id=M.user_id
				where (c.status='2' or c.status='0')
				and c.is_deleted='N'
				and c.created_date between '".$s_date."' and '".$e_date."'
				and M.".$where."
				order by c.created_date desc
				";
$regist_log = $mdb->queryAll($query_log);

$i = $start+1;
foreach ($regist_log as $regist)
{
	$content = $mdb->queryRow("select * from bc_content where content_id = {$regist['content_id']} ");

	$user_name = $db->queryOne("select user_nm from bc_member where user_id='{$content['reg_user_id']}'");


	array_push($regist_rank['regist_rank'], array('rank'=>$i, 'type'=>$mappingMetaTable[$content['ud_content_id']], 'title'=>$content['title'], 'user'=>$user_name, 'date'=>$content['created_date']));

	$i++;
}

	if($_POST['is_excel'] == 1)
	{
		$columns = json_decode($_POST['columns'], true);
		$array = array();
		foreach($regist_rank['regist_rank'] as $d)
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
                        //$value = substr($d[$col[0]],0,4).'-'.substr($d[$col[0]],4,2).'-'.substr($d[$col[0]],6,2);
                        $value = date('Y-m-d', strtotime($d[$col[0]]));
					}
				}
				else
				{
					$value = $d[$col[0]];
				}

				$row[(string)$col[1]] = (string)$value;
			}
			array_push($array, $row);
		}

		echo createExcelFile(_text('MSG02146'),$array);
	}
	else
	{
		echo json_encode($regist_rank);
	}



//print_r($down_rank);
//순위 /타입  / 파일명 / 등록자 / 생성일
//  1   movie      2       admin	  2010/02/11
}
catch(Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}
?>

