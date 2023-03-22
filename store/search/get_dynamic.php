<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

//print_r($_SESSION);

$meta_table_id = $_POST['meta_table_id'];


$result = array(
	array(
		'type'			=> 'textfield',
		'name'			=> '타이틀',
		'meta_field_id'	=> 'title',
		'table'			=> 'bc_content',
		'field'			=> 'title',
		'default_value'	=> ''
	)
	/*,
	array(
		'type'			=> 'datefield',
		'name'			=> '등록일',
		'meta_field_id'	=> 'created_date',
		'table'			=> 'bc_content',
		'field'			=> 'created_date',
		'default_value'	=> ''
	)*/
);

$fields = $db->queryAll("select * from bc_usr_meta_field where ud_content_id=$meta_table_id and is_search_reg='1' and usr_meta_field_type!='container' order by show_order");
foreach ($fields as $field)
{
	$result[] = array(
		'type'			=> $field['usr_meta_field_type'],
		'name'			=> $field['usr_meta_field_title'],
		'join'			=> array(
			'table' => 'bc_usr_meta_value',
			'field' => 'usr_meta_field_id'
		),
		'meta_field_id'	=> $field['usr_meta_field_id'],
		'usr_meta_field_code'	=> $field['usr_meta_field_code'],
		'default_value'	=> str_replace('(default)', '', $field['default_value'])
	);

}//
//if ($field['ud_content_id']== 4000305||  $field['ud_content_id']== 4000306 ||$field['ud_content_id']==4000325){
//	$query="select * from bc_sys_meta_field where sys_meta_field_title='해상도' ";
//
//	if($field['ud_content_id'] == 4000306){
//		$query.=" and bs_content_id='506'";
//	} else if($field['ud_content_id'] == 4000305){
//		$query.=" and bs_content_id='518'";
//	} else if($field['ud_content_id'] ==4000325){
//		$query.=" and bs_content_id='57078'";
//	}
//
//	$f = $db->queryRow($query);
//
//	$result[] = array(
//		'type'			=> $f['field_input_type'],
//		'name'			=> $f['sys_meta_field_title'],
//		'table'			=> 'mata_value',
//		'meta_field_id'	=> $f['sys_meta_field_id'],
//		'field'			=> $f['sys_meta_field_id'],
//		'default_value'	=> 'all'
//	);
//}
//array(
//		'type'			=> 'combo',
//		'name'			=> '해상도',
//		'meta_field_id'	=> 'pix',
//		'table'			=> 'bc_content',
//		'field'			=> 'pix',
//		'default_value'	=> ''
//	)


//$data = $db->queryAll("select meta_field_id, name, type, default_value from meta_field where type !='container' and meta_table_id=".$meta_table_id." ");
//
//print_r($data);
//for ($i=0; $i<count($data); $i++)
//{
//	if ( in_array($data[$i]['name'], $allowItems) )
//	{
//		$combo = trim($data[$i]['default_value']);
//		$combo = explode('(default)',$combo);
//		$data[$i]['default_value'] = $combo[1];
////		$data[$i]['default_value'] = preg_replace('#\(default\)#', '', trim($data[$i]['default_value'], ';'));
//		$filteredData[] = $data[$i];
//	}
//}


//print_r($filteredData);

echo json_encode(array(
	'success' => true,
	'data' => $result
));

?>