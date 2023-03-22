<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/data_transfer_function.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

//NPS->NEWS, NEWS->NPS 간의 자료요청시 작업 페이지
//관리자가 승인한 경우에 대해서 정해진 폴더에 영상 등록
//2013.06.04 임찬모

$contents = $_REQUEST['contents'];
$content_ids = explode(",",$contents);
$counts = $_REQUEST['counts'];
$count_arr = explode(",",$counts);

try
{

    //각각의 콘텐츠 아이디에 대해서 실행
    for($i=0; $i<count($content_ids); $i++)
    {
        $content_id = $content_ids[$i];
        $count = $count_arr[$i];
        $query = "select * from bc_data_transfer where old_content_id=$content_id and request_data = 'R' and transfer_count = $count";
        $info = $db->queryRow($query);

        $old_ud_content_id = $info['ori_ud_content_id'];
        $user_id = $info['reg_user_id'];

        //뉴스그룹인지 NPS그룹인지에 따라 워크 플로우를 나눔
        $query = "select code from bc_code where name = 'news_group'";
        $news_groups = $db->queryOne($query);
        $news_group_array = explode(",", $news_groups);
        foreach($news_group_array as $news)
        {
            if($old_ud_content_id == $news)
            {
                $workflow_channel = 'data_request_nps';

                break;
            }
            else
            {
                $workflow_channel = 'data_request_news';
            }
        }
        //승인버튼 클릭시 임시 승인(T)
        $query = "update bc_data_transfer set request_data = 'T' where old_content_id = $content_id and transfer_count = $count";
        $db->exec($query);
                                                    
        $file = $db->queryOne("select path from bc_media where content_id = $content_id and upper(media_type) = 'ORIGINAL'");
        $task_mgr = new TaskManager($db);
        $task_mgr->insert_task_query_outside_data($content_id, $workflow_channel, 1, $user_id, $file);
    }


    echo json_encode(array(
            'success' => true,
            'msg' => '자료 요청이 승인되었습니다.'
    ));
}
catch(Exception $e)
{
    $msg = $e->getMessage();
    echo json_encode(array(
            'success' => false,
            'msg' => $msg
    ));
}

?>
