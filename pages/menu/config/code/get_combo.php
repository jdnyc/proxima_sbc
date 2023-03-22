<?php 
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$code_type_list = $db->queryAll("select id, name from bc_code_type");
//id_number code name
$result = array();
foreach($code_type_list as $code_type)
{
	$sub['code_type_id']	= $code_type['id'];	
	$sub['code_type_name']	= $code_type['name'];

	array_push($result, $sub);	
}

echo json_encode(array(
	'success' => true,
	'total' => $total,
	'data' => $result
));

?>