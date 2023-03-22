<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/bisUtil.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/pages/bis/bis.class.php');

$category_id = $_POST['category_id'];

try {
    $program_info = getProgramInfo($category_id);
    if (empty($program_info)) {
        throw new Exception('프로그램 정보가 없습니다.');
    }

    $programInfo = explode('/', $program_info);

    if (count($programInfo) != 2) {
        throw new Exception('회차를 선택하세요.');
    }

    $params = array(
        'pgm_id'=> $programInfo[0],
        'epsd_no'=> $programInfo[1],
        'ver_cd' => 15
    );

    $bis = new BIS();
    $data = $bis->MaterialList($params);

    $data = json_decode($data);

    echo json_encode(array(
        'success' => true,
        'data' => $data[0]
    ));

} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'msg' => $e->getMessage()
    ));
}

function getProgramInfo($category_id) {
    global $db;

    return $db->queryOne("select path from path_mapping where category_id=" . $category_id);
}