<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

try {

    $user_id = $_SESSION['user']['user_id'];
    $action = $_REQUEST['action'];

//    $query = "select c.category_title as prog_nm, p.path as prog_id
//                from bc_category c, path_mapping p, user_mapping u
//                where c.category_id = p.category_id and p.category_id = u.category_id and u.user_id = '$user_id'";

    $query = "select c.category_title as prog_nm, p.path as prog_id
		from bc_category c, path_mapping p
		where c.category_id = p.category_id";

    $programs = $db->queryAll($query);

    // 결과값에 전체항목을 넣기 위해서 처리
    if($action == 's_cuesheet') {
        $result = array(
            array(
                    'prog_nm'	=> 'All',
                    'prog_id'       => 'all'
            )
        );
    } else {
        $result = array();
    }

    foreach ($programs as $program) {
        $result[] = array(
                'prog_nm'	=> $program['prog_nm'],
                'prog_id'	=> $program['prog_id']
        );
    }

    echo json_encode(array(
            'success' => true,
            'data' => $result
    ));

} catch(Exception $e) {
    echo json_encode(array(
            'success' => false,
            'message' => $e->getMessage(),
            'query' => $db->last_query
    ));
}
?>
