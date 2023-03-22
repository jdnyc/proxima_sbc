<?php
/*필드
	fields: [
		{name: 'id'},
		{name: 'title'},
		{name: 'created_time', type: 'date', dateFormat: 'YmdHis'}
	]
*/
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$user_id = $_SESSION['user']['user_id'];
$limit = $_POST['limit'];
$start = $_POST['start'];
$search = '';
$search_date = '';


if(empty($limit)){
    $limit = 10;
}

try
{
	if(!empty($_POST['search']))//제목 검색
	{
		$search = $_POST['search'];
		$search_field = " and notic_title like '%$search%'";
	}

	if(!empty($_POST['start_date']))//날짜 검색
	{
		$start_date = $_POST['start_date'];
		$end_date = $_POST['end_date'];

		$search_date = ' and created_date between '.$start_date.' and '.$end_date;
	}

	$notice_q = "select * from bc_notice where notice_id is not null {$search_date} {$search_field} order by created_date desc";
	$total_q = "select count(*) from bc_notice where notice_id is not null {$search_date} {$search_field} ";

	$db->setLimit($limit, $start);//notice 공지사항 테이블의 값 불러오기
	$notices = $db->queryAll($notice_q);
	if(PEAR::isError($notices)) throw new Exception($notices->getMessage());
	$total = $db->queryOne($total_q);
	if(PEAR::isError($total)) throw new Exception($total->getMessage());

	$data = array(
		'success'	=> true,
		'data'		=> $notices,
		'total'		=> $total
	);
	echo json_encode($data);
}
catch (Exception $e)
{
	echo '오류 : '.$e->getMessage();
}
?>