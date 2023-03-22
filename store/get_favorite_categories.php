<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$user_id = $_SESSION['user']['user_id'];
$content_type = $_REQUEST['content_type']; // 미디어 인지 오디오 인지 구분하기 위한 필드

$lists = $db->queryAll("
                        select
                                *
                        from
                                bc_favorite_category
                        where
                                user_id = '$user_id'
                            and
                                content_type = '$content_type'
                        order by favorite_category_id
        ");
	
foreach ($lists as $list) {
        $data[] = array(
                'id' => $list['favorite_category_id'],
                'type' => '',
                'text' => $list['favorite_category_title'],
                'icon' => '/led-icons/star_1.png',
                'read' => 1,
                'add' => 0,
                'edit' => 1,
                'del' => 1,
                'hidden' => 0,
                'favorite' => $list['content_type'],
                'leaf' => true
        );
}

echo json_encode($data);

?>