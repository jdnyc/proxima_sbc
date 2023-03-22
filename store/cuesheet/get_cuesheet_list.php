<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/pages/bis/bis.class.php');

try
{
    $broad_date = $_REQUEST['broad_date'];
    $cuesheet_type = $_REQUEST['cuesheet_type'];
    $prog_id = $_REQUEST['prog_id'];
    $subcontrol_room = $_REQUEST['subcontrol_room'];

    $select = "select * from bc_cuesheet where type ='$cuesheet_type'";

    if ( ! empty($broad_date)) {
        $select .= " and broad_date = '$broad_date'";
    }

    // prog_id 가 빈값이 아닐경우는 조회조건에 추가
    if ( ! empty($prog_id) && $prog_id != 'all') {
        $select = $select." and prog_id = '$prog_id'";
    }

    // subcontrol_room 이 빈값이 아닐 경우 조회조건에 추가
    if ( ! empty($subcontrol_room) && $subcontrol_room !='all') {
    	$select = $select." and subcontrol_room = '$subcontrol_room'";
    }

    $order = " order by cuesheet_id";
    $query = $select.$order;

    $data = $db->queryAll($query);

    echo json_encode(array(
        'success' => true,
        'data' => $data
    ));
} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'msg' => $e->getMessage(),
        'query' => $db->last_query
    ));
}
?>
