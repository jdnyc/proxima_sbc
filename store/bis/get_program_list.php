<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/pages/bis/bis.class.php');

$pgm_nm = $_POST['pgm_nm'];
$pgm_id = $_POST['pgm_id'];
$use_yn = $_POST['use_yn'];
$page = $_POST['page'];
$row_per_page = $_POST['row_per_page'];
$sort_field = $_POST['sort_field'];
$sort_dir = $_POST['sort_dir'];

if (empty($pgm_nm)) {
    $pgm_nm = '';
}

if (empty($use_yn)) {
    $use_yn = 'Y';
}

if (empty($page) || $page == 0) {
    $page = 1;
}

if (empty($row_per_page)) {
    $row_per_page = 99999;
}

if (empty($sort_field)) {
    $sort_field = 'pgm_nm';
}

if (empty($sort_dir)) {
    $sort_dir = 'asc';
}

try {
	$bis = new BIS();

    $params = array(
        'chan_cd' => 'CH_B',
        'pgm_nm' => $pgm_nm,
        'use_yn' => $use_yn,
        'row_per_page' => $row_per_page,
        'page' => $page,
        'sort_field' => $sort_field,
        'sort_dir' => $sort_dir
    );

    $logger->addInfo('params', $params);

	$data = $bis->ProgramList($params);

    $datas = json_decode($data, true);

    echo json_encode(array(
        'success' => true,
        'total' => count($datas),
        'data' => $datas
    ));
}
catch(Exception $e)
{
    echo json_encode(array(
        'success'   =>  false,
        'msg'   =>  $e->getMessage()
    ));
}
?>