<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/store/get_content_list/libs/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');
fn_checkAuthPermission($_SESSION);
try {

	$where = array();
	//array_push($where, " C.UD_CONTENT_ID = S.UD_CONTENT_ID ");
	//array_push($where, " S.US_TYPE = 'lowres' ");
	if ($_POST['tab_id'] == 'tab_video') {
		$ud_content_ids = "506";
		array_push($where, " C.UD_CONTENT_ID  IN(SELECT UD_CONTENT_ID FROM BC_UD_CONTENT WHERE BS_CONTENT_ID = " . $ud_content_ids . " ) ");
	} else if ($_POST['tab_id'] == 'tab_graphic') {
		$ud_content_ids = "518";
		array_push($where, " C.UD_CONTENT_ID  IN(SELECT UD_CONTENT_ID FROM BC_UD_CONTENT WHERE BS_CONTENT_ID = " . $ud_content_ids . " ) ");
	}




	$search = json_decode($_POST['search'], true);
	foreach ($search as $field => $search_value) {
		if (!empty($search_value)) {
			if ($field == 'search_title') {
				array_push($where, " C.TITLE LIKE '%" . $search_value . "%' ");
			} else if($field === 'search_media'){
				array_push($where, " UC.MEDIA_ID LIKE '%" . $search_value . "%' ");
			}else if ($field == 'search_type') {
				if ($search_value == 'value' || $search_value == '전체' || $search_value == 'all') {
					array_push($where, " C.UD_CONTENT_ID IN( SELECT UD_CONTENT_ID FROM BC_UD_CONTENT WHERE BS_CONTENT_ID = " . $ud_content_ids . "  )");
				} else {
					array_push($where, " C.UD_CONTENT_ID = " . $search_value . " ");
				}
			}
		}
	}

	$search_start = empty($search['search_start']) ? 0 : date("Ymd", strtotime($search['search_start'])) . '000000';
	$search_end = empty($search['search_end']) ? date("Ymd") . '235959' : date("Ymd", strtotime($search['search_end'])) . '235959';

	array_push($where, " C.CREATED_DATE BETWEEN '" . $search_start . "' AND '" . $search_end . "' ");
	// array_push($where, " C.CONTENT_ID   =V.CONTENT_ID ");
	array_push($where, " C.IS_DELETED   ='N' ");
	//array_push($where, " C.STATUS      IN('2', '0') ");
	array_push($where, " C.IS_GROUP    != 'C' ");
	//array_push($where, " C.CONTENT_ID   =UM.USR_CONTENT_ID ");
	array_push($where, " C.CONTENT_ID   =SYS.SYS_CONTENT_ID ");
	array_push($where, " C.CONTENT_ID   =UC.USR_CONTENT_ID ");
	


	$_where = array();
	//$table_name = MetaDataClass::getTableName('usr', $ud_content_id);

	$sys_meta_table = MetaDataClass::getTableName('sys', $ud_content_ids);


	$query = "
		SELECT	C.*  , SYS.*, UC.*
		FROM		
					BC_CONTENT C ,
					BC_USRMETA_CONTENT UC,
					" . $sys_meta_table . " SYS
		WHERE	" . join(' AND ', $where) . "
		ORDER BY	C.CREATED_DATE DESC
	";


	$total = $db->queryOne("select count(*) from ( " . $query . " ) cnt ");

	if ($total <= 0) {
		$result = json_encode(array(
			'success' => true,
			'total' => $total,
			'results' => array(),
			'query' => $query
		));
	} else {
		$start = empty($_POST['start']) ? 0 : $_POST['start'];
		$limit = empty($_POST['limit']) ? 50 : $_POST['limit'];

		$db->setLimit($limit, $start);
		$content_list = $db->queryAll($query);

		$last_query = $db->last_query;
		$content_ids = array();
		foreach ($content_list as $content) {
			array_push($content_ids, $content['content_id']);
		}


		$contents = getMediaMapping($content_ids, $content_list);
		$contents = getTaskMapping($content_ids, $contents);
		$contents = getStorageMapping(null, $contents);
		//$contents = fetchMetadata($content_ids, $qtips);
		$contents = getContentStatus($content_ids, $contents);


		//print_r($contents);exit;
		$result = json_encode(array(
			'success' => true,
			'total' => $total,
			'results' => $contents,
			'query' => $query,
			'lastQuery' => $last_query
		));
	}

	echo $result;
	exit;
} catch (Exception $e) {
	//die(json_encode(array(
	//'success' => false,
	//'msg' => $e->getMessage()
	//)));

	print_r($e->getMessage());
	exit;
}
