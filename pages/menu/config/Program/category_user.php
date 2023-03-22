<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$category_id = $_POST['user_category'];

try
{
	$user_info = $db->queryAll("select m.* from user_mapping um,bc_member m where m.user_id=um.user_id and um.category_id=$category_id");

	if(PEAR::isError($user_info)) throw new Exception($user_info->getMessage());

	$total = $db->queryOne("select count(*) from user_mapping um,bc_member m where m.user_id=um.user_id and um.category_id=$category_id");

	if(PEAR::isError($total)) throw new Exception($total->getMessage());

	$data = array(
		'success'	=> true,
		'data'		=> array(),
		'total'		=> $total
	);

	$total = 1;
	foreach($user_info as $info){
		array_push($data['data'],array(
			'user_id'=>$info['user_id'],
			'name'=>$info['user_nm'],
			'dept_nm'=>$info['dept_nm'],
			'breake' => $info['breake'],
			'dep_tel_num'=>$info['dep_tel_num']
		));
	}

	echo json_encode($data);
}

catch (Exception $e)
{
	echo '오류 : '.$e->getMessage();
}
?>