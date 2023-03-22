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
        
        $query = "update bc_data_transfer set request_data = 'C' where old_content_id = $content_id and transfer_count = $count and request_data = 'R'";
        $db->exec($query);
    }


    echo json_encode(array(
            'success' => true,
            'msg' => '자료 요청이 반려되었습니다.'
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
