<?php
session_start();
require_once ($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
require_once ($_SERVER['DOCUMENT_ROOT'] . '/workflow/lib/task_manager.php');

//2013.11.07 추가 (임찬모)
//기존 실패난 아카이브에 대해서 아카이브가 되었는지 여부를 확인
//확인 이후 재시작 및 성공처리해주기 위해서 추가

$job_priority = 1;
$user_id = $_SESSION['user']['user_id'];
$insert_task = new TaskManager($db);

foreach ($data as $content_id) {
        $query = "select count(media_id) from bc_media where media_type = 'archive' and content_id = '$content_id'";
        $is_archive = $db->queryOne($query);
        // 아카이브 확인은 아카이브가 되어 있을 경우에만 됨
        if ( $is_archive > 0 ) {    
            $query = "select ud_content_id from bc_content where content_id = '$content_id'";
            $check_ud_content = $db->queryOne($query);

            if($check_ud_content == '358')
            {
                    $channel = 'nps_archive_list';
            }
            else if($check_ud_content == '334')
            {
                    $channel = 'news_archive_list';
            }
            else if($check_ud_content == '202' || $check_ud_content == '374' || $check_ud_content == '394')
            {
                    $channel = 'tape_archive_list';
            }
            else
            {
                    $channel = 'sgl_archive_list';
            }
            $insert_task->start_task_workflow($content_id, $channel, $user_id);
        }
}

echo json_encode(array(
	'success' => true,
	'msg' => '아카이브확인 요청이 완료되었습니다.'
));

?>