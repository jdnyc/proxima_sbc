<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$type = $_POST['type'];

try
{
	$insert_data_array = json_decode($_POST['values'], true);
	$insert_data = array();
	foreach( $insert_data_array as $key=>$value ){
		if( $value == '' ){
		}else{
			$insert_data[$key] = $value;
		}
	}

	switch($type){
		case 'code_type':
		break;
		case 'code':
			$insert_data['use_yn'] =empty($insert_data_array['use_yn']) ? 'N' : 'Y';
			$check_where = " AND	CODE_TYPE_ID = ".$insert_data['code_type_id']." ";
		break;
	}

	$query_check = "
		SELECT	count(code)
		FROM		BC_".strtoupper(trim($type))."
		WHERE	(CODE = '".trim($insert_data['code'])."'
		OR			NAME = '".trim($insert_data['name'])."' )
		".$check_where."
	";
	$check = $db->queryOne($query_check);

	if( $check > 0 ){
		$check_r = false;
	}else{
		$seq_id = getSequence('seq_bc_'.$type.'_id');
		$insert_data['id'] = $seq_id;
		$query = $db->insert('BC_'.strtoupper(trim($type)), $insert_data, 'exec');
		$check_r = true;
	}

	echo json_encode(array(
		'success'	 => true,
		'add'	=>	 $check_r,
		'check_query' => $query_check,
		'query'	=>	 $query
	));
}
catch(Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => _text('MN00022')." : ".$e
	));
}

?>