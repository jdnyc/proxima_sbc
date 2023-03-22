<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
$data = array(
	'success' => true,
	'data' => array()
);

$end_date = $_POST['end_date'];
$start_date = $_POST['start_date'];

$get_datas = $mdb->queryAll("select 
											count(l.log_id) as value, l.user_id, m.user_nm 
										from 
											bc_log l, bc_member m 
										where 
											l.action = 'delete' and l.user_id = m.user_id and l.created_date between $start_date and $end_date 
										group by l.user_id,m.user_nm 
										order by value desc");
$i = "1";
foreach($get_datas as $get_data){
	array_push($data['data'], array('rank'=>$i, 'user'=>$get_data['user_id'], 'name'=>$get_data['user_nm'], 'count'=>$get_data['value']));
	$i++;
}
echo json_encode(
	$data
);

?>