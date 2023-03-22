<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/store/metadata/function.php');

$content_ids = $_REQUEST['content_ids'];
$ud_content_id = $_REQUEST['ud_content_id'];
$arr_content_ids = json_decode($content_ids, true);

try {
	$containerList = $db->queryAll("SELECT * FROM BC_USR_META_FIELD WHERE UD_CONTENT_ID='{$ud_content_id}' AND CONTAINER_ID IS NOT NULL AND DEPTH=0 ORDER BY SHOW_ORDER");
	$result_data = array();
    
	foreach ($arr_content_ids as $content_id) {
		$container_array = array();
        // title and category
        //$str_query= "SELECT TITLE, CATEGORY_ID, CATEGORY_FULL_PATH  FROM BC_CONTENT WHERE CONTENT_ID=".$content_id;
        $str_query ="	SELECT BCC.TITLE, BCC.CATEGORY_ID, BCC.CATEGORY_FULL_PATH, BCC.REG_USER_ID, BCC.CREATED_DATE, BCM.USER_NM
        				FROM BC_CONTENT BCC
							LEFT JOIN BC_MEMBER BCM
								ON BCC.REG_USER_ID = BCM.USER_ID
						WHERE BCC.CONTENT_ID = $content_id";
        $content_more_info = $db->queryRow($str_query);
		$content_more_info['created_date_time'] =  date('Y-m-d H:i:s', strtotime($content_more_info['created_date']));      
        array_push($container_array, $content_more_info);
        
        // get user metadata
		foreach ($containerList as $container_key => $container) {

			$container_id_tmp = $container['container_id'];
			$container_title = addslashes($container['usr_meta_field_title']);
			$rsFields =  MetaDataClass::getFieldValueforContaierInfo('usr' , $ud_content_id, $container_id_tmp, $content_id);
			
			foreach ($rsFields as $f) {
				if($f['type'] == 'listview')
				{
					$listview = getMetaMultiXML($content_id);
				}
				if ($f['default_value']){
					$f['default_value']	= str_replace('(default)', '', $f['default_value']);
				}
				array_push($container_array, $f);
			}
		}
		$result_data[$content_id] = $container_array;
		
	}

header("Content-Type: application/json;charset=utf-8");
echo json_encode(array(
		'success' => true,
		'data' => $result_data
));

}catch(Exception $e) {
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}

?>