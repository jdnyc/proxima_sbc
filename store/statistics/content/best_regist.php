<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
$data = array(
	'success' => true,
	'data' => array()
);

$end_date = $_POST['end_date'];
$start_date = $_POST['start_date'];

$get_datas = $mdb->queryAll("select count(c.content_id) as value, c.reg_user_id, m.user_nm 
											from bc_content c, bc_member m 
											where c.reg_user_id = m.user_id 
												  and c.created_date 
												  between $start_date and $end_date group by c.reg_user_id,m.user_nm order by count(c.content_id) desc");
$i = "1";
foreach($get_datas as $get_data){
	array_push($data['data'], array('rank'=>$i, 'user'=>$get_data['reg_user_id'], 'name'=>$get_data['user_nm'], 'count'=>$get_data['value']));
	$i++;
}
echo json_encode(
	$data
);

?>