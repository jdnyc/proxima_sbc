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
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');

$user_id = $_SESSION['user']['user_id'];
$limit = $_POST['limit'];
$start = $_POST['start'];
if(empty($limit)){
    $limit = 10;
}
$action = $_POST['action'];
try
{
	$db->setLimit($limit, $start);//notice 공지사항 테이블의 값 불러오기
	$logs = $db->queryAll("
		select *
		from
			log
		where
			action='$action'
		order by created_time desc
	");
	if(PEAR::isError($logs)) throw new Exception($logs->getMessage());

	$total = $db->queryOne("
		select
			count(id)
		from
			log
		where
			action='$action'
	");
	if(PEAR::isError($total)) throw new Exception($total->getMessage());

	$data = array(
		'success'	=> true,
		'data'		=> array(),
		'total'		=> $total
	);

	foreach($logs as $log)
	{
		array_push($data['data'], array(
			'id'				=> $log['id'],
			'action'			=> $log['action'],
			'user_id'			=> $log['user_id'],
			'content_id'		=> $log['link_table_id'],
			'created_time'		=> $log['created_time'],
			'description'		=> $log['description']
		));
	}
	echo json_encode($data);
}
catch (Exception $e)
{
	echo '오류 : '.$e->getMessage();
}
?>