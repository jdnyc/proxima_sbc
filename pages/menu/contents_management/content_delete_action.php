<?php
/// 만료기한 처리 페이지
// 2011.12.15
// by 허광회
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

$action = $_POST['action'];
$user_id = $_SESSION['user']['user_id'];

switch($action){
	case 'extend':
		delete_extend_exec();
	break;

	case 'approve':
		delete_approve_exec($user_id);
	break;

	case 'cancel':
		delete_cancel_exec();
	break;

	default :
		$msg = '조건이 맞지 않습니다';
		error($msg);
	break;
}

function delete_cancel_exec(){
	global $db;
	$data_ids = json_decode($_POST['ids']); // 일반승인에 대한 처리
	$cur_date = date('YmdHis');

	$expired_ids = array();
    $content_ids = array();
    $content_ids_status_change = array();
	$media_ids = array();
	$delete_ids = array();

	foreach( $data_ids as $info ){
		$expired_date = str_replace('-', '', substr($info->expired_date, 0, 10));
		if( $expired_date < date('Ymd') ){
			array_push($expired_ids, $info->media_id);
		} else{
			if( $info->delete_type == 'CONTENT' ){
				array_push($content_ids_status_change, $info->content_id);
			} else {
				array_push($media_ids, $info->media_id);
            }
            array_push($content_ids, $info->content_id);
			array_push($delete_ids, $info->delete_id);
		}
	}

	//취소
	//BC_CONTENT STATUS UPDATE(삭제)
	if( count($content_ids_status_change) > 0 ){
        //status는 그대로 유지. 심의 관련이 있기 때문에.
		$query_content = "
			UPDATE	BC_CONTENT	SET 
                IS_DELETED='N'
			WHERE	CONTENT_ID IN(".join(',', $content_ids_status_change).")
		";
        $db->exec($query_content);
        //삭제요청이었던 상태flag만 원복. 원본만 지워진 상태가 있다면 유지해야 하기 때문에
        $query_content_media = "
            update bc_media set
                flag=''
            where CONTENT_ID IN(".join(',', $content_ids_status_change).")
            and flag='".DEL_MEDIA_CONTENT_REQUEST_FLAG."'
        ";
        $db->exec($query_content_media);
	}

	//UPDATE BC_MEDIA STATUS(원본 삭제)
	if( count($media_ids) > 0 ){
		$query_content = "
			UPDATE	BC_MEDIA	SET 
                STATUS = '0',
                flag=''
			WHERE	MEDIA_ID IN(".join(',', $media_ids).")
		";
		$db->exec($query_content);
	}

	//BC_DELETE_CONTENT DELETE ROW : 삭제 목록에서 삭제
	if( count($delete_ids) > 0 ){
		$query_delete = "
			DELETE
			FROM	BC_DELETE_CONTENT
			WHERE	ID IN(".join(',', $delete_ids).")
		";
		$db->exec($query_delete);
	}

	 
	//EXCUTE EXPIRED CONTENT : 만료된 콘텐츠 처리
	if( count($expired_ids) > 0 ){
		$query_delete = "
			UPDATE	BC_MEDIA	SET 
				FLAG = '".DEL_MEDIA_DATE_EXPIRE_FLAG."',
				DELETE_DATE = '".$cur_date."'
			WHERE	MEDIA_ID IN(".join(',', $expired_ids).")
		";
		$db->exec($query_delete);
    }
    

    if( count($content_ids) > 0 ){
        foreach($content_ids as $content_id) {
            searchUpdate($content_id);
        }
    }

	echo json_encode(
			array(
				'success' => true,
			 		'msg' => '성공적으로  적용되었습니다'
			 	)
		);
}

function delete_approve_exec($user_id){
	global $db;

	$datas = json_decode($_POST['ids']);
    $cur_date = date('YmdHis');
    
    $user = auth()->user();

    //서비스 생성
    $contentService = new \Api\Services\ContentService(app()->getContainer());
    $mediaService = new \Api\Services\MediaService(app()->getContainer()); 
    $taskService = new \Api\Services\TaskService( app()->getContainer());


	//print_r($datas);exit;

	foreach($datas as $content){
		$id = $content->content_id;
        $delete_date = $content->delete_date;
        $no_task_run = 'N';
		if( $content->delete_type == 'ORIGINAL' ){
			if( empty($delete_date) ){
				//원본만 삭제일시 원본삭제 작업흐름 태우기
				$insert_task = new TaskManager($db);
                $workflow_channel = 'original_del';
                $insert_task->setStatus('scheduled');
                $task_id =$insert_task->start_task_workflow($id, $workflow_channel, $user_id);
                insertLog('delete_app_hr', $user_id, $id,'' );
            }
            $query_task = "";
            if(!empty($task_id)){
                $query_task = ", TASK_ID = ".$task_id." ";
            }
            $query_update = "
                UPDATE	BC_DELETE_CONTENT	SET
                    STATUS = 'CONFIRM' ".$query_task."
                WHERE	ID = ".$content->delete_id."
            ";
            if($no_task_run == 'Y') {
                $query_update = "
                    UPDATE	BC_DELETE_CONTENT	SET
                        STATUS = 'SUCCESS' ".$query_task."
                    WHERE	ID = ".$content->delete_id."
                ";
            }
            $db->exec($query_update);
		}else{
            $content_id = $content->content_id;
            $task = $taskService->getTaskManager();                
            $task->set_priority(400);
            $task->setStatus('scheduled');

            $medias = $mediaService->getMediaByContentId($content_id);
            foreach($medias as $media)
            {
                if( $media->status == 0 ){
                    //삭제 대상이 아닌 목록만
                     //삭제 워크플로우 수행
                    $mediaType = $media->media_type;                       
                         
                    //삭제 워크플로우                    
                    if ($mediaType == 'original') {
                        $channel ='delete_media_'.$mediaType;
                        $originalTaskId = $task->start_task_workflow($content_id, $channel, $user->user_id );

                    }else if($mediaType == 'proxy'){
                        $channel ='delete_media_'.$mediaType;
                        $taskId = $task->start_task_workflow($content_id, $channel, $user->user_id );

                    }else if($mediaType == 'proxy360'){

                    }else if($mediaType == 'proxy2m1080'){

                    }else if($mediaType == 'proxy15m1080'){

                    }else if($mediaType == 'publish'){

                    }else if($mediaType == 'audio'){

                    }else if($mediaType == 'yt_thumb'){

                    }else if($mediaType == 'thumb'){
                        $channel ='delete_media_'.$mediaType;
                        $taskId = $task->start_task_workflow($content_id, $channel, $user->user_id );
                    }
                }                
                //삭제 승인
                $contentService->deleteAccept($content->delete_id, $originalTaskId, $user);
            }
		}
	}

	echo json_encode(
		array(
			'success' => true,
				'msg' => '성공적으로  적용되었습니다'
			)
	);

}

function delete_extend_exec(){
	 global $db;

	 $data_ids = json_decode($_POST['ids']);
	 $date = str_replace("-","",substr(trim($_POST['date']),0,10))."000000";

	 foreach($data_ids as $id)
	 {
	 	$query= "update bc_media set expired_date = '$date', flag=null where media_id = $id";
	 	$r = $db->exec($query);
	 }

	 echo json_encode(
		 	array(
			 		'success' => true,
			 		'msg' => '성공적으로  적용되었습니다'
			 	)
	 );
}

function error($msg)
{
	echo
		json_encode(
			array(
			'success'	=> false,
			'msg' => $msg
		));
}

?>
