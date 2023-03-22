<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$s_date = $_POST['start_date'];
$e_date = $_POST['end_date'];
$user_id = $_POST['user_id'];
if(empty($user_id)){
	$user_id =$_SESSION['user']['user_id'];
}
$limit = empty($_POST['limit']) ? 20 : $_POST['limit'];
$start = empty($_POST['start']) ?  1 : $_POST['start'];

//사용자별 조회 리스트

$total = $mdb->queryOne("select count(*) from bc_log where user_id = '$user_id' and action = 'read' and created_date between $s_date and $e_date");
$read_list = array(
	'success' => true,
	'total' => $total,
	'read' => array()
);

$db->setLimit($limit,$start);
$datas = $mdb->queryAll("select content_id, created_date from bc_log where user_id = '$user_id' and action = 'read' and created_date between $s_date and $e_date");
foreach($datas as $data)
{
	$get_title = $mdb->queryAll("
		select ct.bs_content_title as name, c.title
		from bc_content c, bc_bs_content ct
		where c.content_id = '{$data['content_id']}'
		and c.bs_content_id = ct.bs_content_id");

	foreach($get_title as $titles)
	{
		array_push($read_list['read'], array('content'=>$titles['title'], 'type'=>$titles['name'], 'date'=>$data['created_date']));
	}
}

echo json_encode(
	$read_list
);

//print_r($read_list);
?>