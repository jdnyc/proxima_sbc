<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/store/metadata/function.php');

$content_ids = $_REQUEST['content_ids'];
$ud_content_id = $_REQUEST['ud_content_id'];
$arr_content_ids = json_decode($content_ids, true);
try {
$data =array();
foreach($arr_content_ids as $content_id) {
	$table = MetaDataClass::getTableName('usr', $ud_content_id);

	$field = MetaDataClass::getFieldIdtoNameMap('usr', $ud_content_id);

	$field_is_social_list = $db->queryAll("
											SELECT	*
											FROM		BC_USR_META_FIELD
											WHERE	UD_CONTENT_ID = ".$ud_content_id."
											AND IS_SOCIAL = '1'
											AND		IS_SHOW = '1'
											ORDER BY SHOW_ORDER
										");

	$field_name = array();
	$field_title = array();
	$str_query_content_title = "SELECT TITLE FROM BC_CONTENT WHERE CONTENT_ID=".$content_id;
	$content_title = $db->queryOne($str_query_content_title);

	if(!empty($field_is_social_list)){
		foreach ($field_is_social_list as $field_is_social_key => $field_is_social) {
			$field_id = $field_is_social['usr_meta_field_id'];
			$field_title[] = $field_is_social['usr_meta_field_title'];
			$field_name[] = $field[$field_id];
		}

		$str_query = "SELECT ".join(', ', $field_name)." FROM ".$table." WHERE USR_CONTENT_ID=".$content_id;
		$field_value = $db->queryRow($str_query);



		$str_return = array();

		for($i = 0; $i<count($field_title); $i++){
			$field = strtolower($field_name[$i]);
			$str_return[] = $field_title[$i]." : ".$field_value[$field];
		}
		$data[] = array(
				'title' => $content_title,
				'sns_message' =>$str_return
		);
	}else{
		$str_return = array();

		$data[] = array(
				'title' => $content_title,
				'sns_message' =>$str_return
		);
	}
}

header("Content-Type: application/json;charset=utf-8");
echo json_encode(array(
		'success' => true,
		'data' => $data
));

}catch(Exception $e) {
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}

?>