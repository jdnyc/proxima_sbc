<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/pages/bis/bis.class.php');

$category_id	= $_REQUEST['category_id'];
$pgm_id		= $_REQUEST['pgm_id'];
$date		= $_REQUEST['date'];
$start		= $_REQUEST['start'];
$limit		= $_REQUEST['limit'];
$sort		= $_REQUEST['sort'];
$dir		= $_REQUEST['dir'];

if(empty($start)) {
    $start = 0;
}

if(empty($limit)) {
    $limit = 20;
}

if(empty($sort)) {
    $sort = 'brd_ymd';
}

if(empty($dir)) {
    $dir = 'desc';
}

if(is_null($pgm_id) && empty($pgm_id)) {
    $pgm_id = $db->queryOne("select path from path_mapping where category_id =$category_id");
}

try {

	$bis = new BIS();
	$data = $bis->EpisodeList(array(
        'pgm_id' => $pgm_id,
        'pgm_nm' => '',
        'epsd_no' => '',
        'epsd_nm' => ''
	));

    $datas = json_decode($data, true);

    echo json_encode(array(
        'success' => true,
        'total' => count($datas),
        'data' => $datas
    ));

} catch(Exception $e) {
    echo json_encode(array(
        'success'   =>  false,
        'msg'   =>  $e->getMessage()
    ));
}
?>