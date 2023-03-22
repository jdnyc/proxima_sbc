<?php
session_start();
require_once ($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
require_once ($_SERVER['DOCUMENT_ROOT'] . '/workflow/lib/task_manager.php');

//2013.04.04 수정(임찬모)
//컨텐츠 유형별로 워크플로우 채널 분리 

//$channel = 'sgl_restore';
$job_priority = 1;
$user_id = $_SESSION['user']['user_id'];
$ask_admin = false;
$insert_task = new TaskManager($db);

foreach ($data as $content_id) {
        $query = "select ud_content_id from bc_content where content_id = '$content_id'";
	$check_ud_content = $db->queryOne($query);
	$full_path =$db->queryOne("select path from bc_media where media_id = (select max(media_id) from bc_media where content_id = '$content_id' and media_type = 'archive')");
	$arr_full_path = explode('/', $full_path);
	$filename = array_pop($arr_full_path);
	$filename_arr = explode('.', $filename);
	$archive_type = array_pop($filename_arr);
        
        if($check_ud_content == '358')
        {
            $channel = 'unknown_restore';
        }
        else if($check_ud_content == '374')
        {
	    if($archive_type == 'mov')
	    {
		$channel = 'unknown_tape_restore_hd';
	    }
	    else if($archive_type == 'mxf')
	    {
		$channel = 'unkonown_tape_restore_hd_mxf';
	    }
        }
        $insert_task->set_priority(200);
        $insert_task->start_task_workflow($content_id, $channel, $user_id);
        
        //리스토어가 진행중인것을 표기하기 위해 bc_restore_ing에 해당 content_id를 입력함 (2014.05.16 임찬모)
        $query = "insert into bc_restore_ing (content_id) values ($content_id)";
        $db->exec($query);
        //리스토어 실패등으로 인해서 찌꺼기가 남을 경우 자동으로 삭제하기 위해서 restore_date를 업데이트 함 (2014.05.16 임찬모)
        //restore_date 업데이트는 아카이브가 되어 있고 원본이 삭제된 경우에 한해서만 작동하도록 조건을 둠
        //위 작업 이후에는 원래 로직을 따름
        $restore_time = date('YmdHis');

        $query = "update bc_media set status = 0, delete_date = null  where content_id = $content_id and media_type = 'original' and status = 1 ";
        $db->exec($query);
        $query = "update bc_content set del_status = 0, del_yn = 'N', restore_date = '$restore_time' where content_id = $content_id and del_status = 1 and del_yn='Y'";
        $db->exec($query);
}

if($channel == 'not_regist')
{
    echo json_encode(array(
	'success' => false,
	'msg' => '해당 영상은 Unknown 리스토어가 불가능합니다'
    ));
}
else
{
    echo json_encode(array(
            'success' => true,
            'msg' => '리스토어 요청이 완료되었습니다.'
    ));
}
