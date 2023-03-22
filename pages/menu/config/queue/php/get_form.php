<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$data = $db->queryAll("select " .
						"t.meta_table_id as meta_table_id, t.name as asset_type, " .
						"f.meta_field_id as meta_field_id, f.type as meta_field_type, f.name as meta_field_name, f.is_required, f.default_value " .
						"from meta_table t, meta_field f where t.meta_table_id='{$_REQUEST['meta_table_id']}' and t.meta_table_id=f.meta_table_id");

if($data) {
	foreach ($data as $k => $v) {
		if ($v['meta_field_type'] == 'combo') {
			$data[$k]['store'] = explode(";", $data[$k]['default_value']);
		}
	}
	echo json_encode(array(
		'success' => true,
		'data' => $data
	));
}else{
	echo json_encode(array(
		'success' => false,
		'msg' => $_REQUEST['type'] . ' 에 대한 메타데이터가 정의되어있지 않습니다.'
	));
}

?>