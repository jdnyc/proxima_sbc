<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

//아카이브 확인작업 이후 아카이브가 성공되어 있는경우
function archive_list_check_success($unique_id, $filesize)
{
    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sgl_list_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] unique_id ==> '.$unique_id.' filesize ===> '.$filesize." \r\n", FILE_APPEND);
    global $db;

    $now = date('YmdHis');
//DB의 아카이브와 filesize를 비교해서 진행
//MXF로 아카이브 된것은 DB의 MXF파일 사이즈와 비교, MOV로 아카이브 된것은 MOV랑 파일 사이즈 비교
    //content_id 를 가져옴
    $query = "select content_id from bc_media where media_type = 'original' and path like '%$unique_id%'";
    $content_id = $db->queryOne($query);
//    //MOV로 변경 된 날짜가 유형별로 다르기 때문에 해당 정보가 필요함 (HD의 YTN은 카데고리 아이디로 구분)
//    $query = "select ud_content_id, category_id, archive_date from bc_content where content_id = '$content_id'";
//    $content_info = $db->queryRow($query);
//
//    $check_ud_content = $content_info['ud_content_id'];
//    $category_id = $content_info['category_id'];
//    $archive_date = $content_info['archive_date'];
//    //2014.01.08 현재 뉴스 HD는 MOV로 NPS는 MXF로 나뉘므로 값을 다르게 해줘야됨
//    if($check_ud_content == '374')
//    {
//        if(($category_id == '362' || $category_id == '434') && $archive_date > '20140102102000')
//        {
//           $file_query = "select filesize from bc_media where media_type = 'original' and content_id ='$content_id'";
//        }
//        else if($archive_date > '20140108000000')
//        {
//           $file_query = "select filesize from bc_media where media_type = 'original' and content_id ='$content_id'";
//        }
//        else
//        {
//           $query = "select max(media_id) from bc_media where media_type = 'rewarp' and filesize is not null and content_id ='$content_id'";
//           $media_id = $db->queryOne($query);
//           $file_query = "select filesize from bc_media where media_id = $media_id";
//        }
//    }
//    else
//    {
//        $query = "select max(media_id) from bc_media where media_type = 'rewarp' and filesize is not null and content_id ='$content_id'";
//        $media_id = $db->queryOne($query);
//        $file_query = "select filesize from bc_media where media_id = $media_id";
//    }
//
    //MOV로 아카이브된 경우는 원본과 비교, MXF로 아카이브된 경우는 REWRAP과 비교 (2014..03.08 임찬모)
    $full_path =$db->queryOne("select path from bc_media where content_id = '$content_id' and media_type = 'archive' order by media_id desc");
    $arr_full_path = explode('/', $full_path);
    $filename = array_pop($arr_full_path);
    $filename_arr = explode('.', $filename);
    $extension = array_pop($filename_arr);
    if(strtoupper($extension) == 'MOV' ) {
        $file_query = "select filesize from bc_media where media_type = 'original' and content_id ='$content_id'";
    } else if(strtoupper($extension) == 'MXF') {
        $query = "select max(media_id) from bc_media where media_type = 'rewarp' and filesize is not null and content_id ='$content_id'";
        $media_id = $db->queryOne($query);
        $file_query = "select filesize from bc_media where media_id = $media_id";
    }

    $db_filesize = $db->queryOne($file_query);

    if($filesize == $db_filesize)
    {
        file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sgl_list_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] db filesize ===> '.$db_filesize.' filesize ===> '.$filesize." \r\n", FILE_APPEND);
        //가장 최근의 archive 작업을 찾아서 성공으로 업데이트
        $query = "select max(media_id) from bc_media where path like '%$unique_id%' and media_type = 'archive'";
        $archive_media_id = $db->queryOne($query);

        $query = "select max(task_id) from bc_task where media_id = $archive_media_id and type = 110";
        $task_id = $db->queryOne($query);
        $query = "update bc_task set status = 'complete', progress = '100' where task_id = $task_id";
        $db->exec($query);
        //archive_date는 현 시점으로 업데이트
        $query = "update bc_content set archive_date='$now' where content_id = '$content_id'";
        $db->exec($query);

        $task_mgr = new TaskManager($db);
        $task_mgr->add_next_job($task_id);

        file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sgl_list_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] update query ===> '.$query." \r\n", FILE_APPEND);
        //기존 bc_archive_err 테이블에 들어있던 content_id 는 삭제
        $query = "delete from bc_archive_err where content_id = $content_id";
        $db->exec($query);
        file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sgl_list_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] delete query ===> '.$query." \r\n", FILE_APPEND);
    }
    //filesize가 다를경우에는 DB에 에러타입을 기록함
    else
    {
        $query = "update bc_archive_err set err_type ='file size err' where content_id = $content_id";
        file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sgl_list_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] err_type update query ===> '.$query." \r\n", FILE_APPEND);
        $db->exec($query);
    }
    return true;
}

function archive_list_check_error($unique_id)
{
    $user_id = $_SESSION['user']['user_id'];
    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sgl_list_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] Re-try Unique_id ===> '.$unique_id."\r\n", FILE_APPEND);
    global $db;
    //아카이브가 처음부터 에러난 경우로 bc_archive_err 테이블에서 해당 content_id를 지우고 다시 아카이브 로직을 수행하는 워크플로우를 타면 됨
    $query = "select distinct t1.ud_content_id, t1.content_id from bc_content t1, bc_media t2
                where t2.path like '%$unique_id%' and t2.media_type = 'archive' and t1.content_id = t2.content_id";
    $content = $db->queryRow($query);
    $ud_content_id = $content['ud_content_id'];
    $content_id = $content['content_id'];
    // 제작HD 제외하고는 전부 MOV 아카이브로 변경됨 제작HD는 카테고리로 분리해주어야 함
	if( DB_TYPE == 'oracle' ){
		$query = "select category_id from bc_category start with category_id in (1191,2151) connect by prior category_id = parent_id";
	}else{
		$query = "
			WITH RECURSIVE q AS (
				SELECT	ARRAY[po.CATEGORY_ID] AS HIERARCHY
						,po.CATEGORY_ID
						,po.CATEGORY_TITLE
						,po.PARENT_ID
						,1 AS LEVEL
				FROM	BC_CATEGORY po
				WHERE	po.CATEGORY_ID IN (1191,2151)
				AND		po.IS_DELETED = 0
				UNION ALL
				SELECT	q.HIERARCHY || po.CATEGORY_ID
						,po.CATEGORY_ID
						,po.CATEGORY_TITLE
						,po.PARENT_ID
						,q.level + 1 AS LEVEL
				FROM	BC_CATEGORY po
						JOIN q ON q.CATEGORY_ID = po.PARENT_ID
				WHERE	po.IS_DELETED = 0
			)
			SELECT	CATEGORY_ID
					,CATEGORY_TITLE
					,PARENT_ID
			FROM	q
			WHERE 	CATEGORY_ID != 0
			AND		PARENT_ID = 0
			ORDER BY HIERARCHY
		";
	}
    //$query = "select category_id from bc_category start with category_id in (1191,2151) connect by prior category_id = parent_id";
    $categories = $db->queryAll($query);
    $category_arr = array();
    foreach($categories as $category)
    {
        array_push($category_arr, $category['category_id']);
    }
    if($ud_content_id == '358') {
            $workflow_channel = 'nps_archive_mov';
    } else if($ud_content_id == '334') {
            $workflow_channel = 'news_archive';
    } else if($ud_content_id == '374') {
            $workflow_channel = 'tape_hd_archive';
    } else if($ud_content_id == '394') {
            $workflow_channel = 'tape_hd_archive';
    } else if($ud_content_id == '202') {
            $workflow_channel = 'tape_sd_archive';
    } else {
            $workflow_channel = 'sgl_archive';
    }
    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sgl_list_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] Re-try content_id ===> '.$content_id.
            ' channel ===> '.$workflow_channel.' ud_content_id ===> '.$ud_content_id."\r\n", FILE_APPEND);

    $task_mgr = new TaskManager($db);
    $task_mgr->start_task_workflow($content_id, $workflow_channel,'agent');

    $query = "delete from bc_archive_err where content_id = $content_id";
    $db->exec($query);
     file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sgl_list_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] error delete query ===> '.$query." \r\n", FILE_APPEND);
}

?>
