<?php

use Api\Types\ContentStatusType;
/**
 * 2017-12-23 이승수
 * 삭제시 처리하는 페이지
 * CJO에 맞게 수정함. 원본삭제 , 전체삭제 두가지임
 */

set_time_limit(180);
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/Search.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/searchengine/solr/searcher.class.php');
session_start();
try
{
	$action = $_POST['action'];
    $user_id = $_SESSION['user']['user_id'];
    $cur_datetime = date('YmdHis');

    $user = auth()->user();

    //서비스 생성
    $contentService = new \Api\Services\ContentService(app()->getContainer());
    $mediaService = new \Api\Services\MediaService(app()->getContainer());
    $tbRequestService = new \Api\Services\TbRequestService(app()->getContainer());

    $taskService = new \Api\Services\TaskService( app()->getContainer());

	if( $user_id=='temp' || empty($user_id) ) throw new Exception(_text('MSG02041'));//'세션이 만료되어 로그인이 필요합니다.'

	if ($action == 'delete' || $action == 'delete_hr' || $action == 'forceDelete' ) {
        $contents = json_decode($_POST['content_id']);
        
        //삭제제한조건 먼저 체크
        foreach ($contents as $content) {
            $content_id = $content->content_id;
            
            // if (!empty($content->reg_user_id)){
            //     $regUserId = $content->reg_user_id;
            //     if($regUserId !== $user_id){
            //         if($_SESSION['user']['is_admin'] !== 'Y'){
            //             throw new Exception("사용자가 등록한 콘텐츠만 삭제할 수 있습니다.");    
            //         }
            //     }
            // };
            // if( !empty($statusMeta->bfe_video_id) || !empty($contentUsrMeta->hmpg_cntnts_id) || !empty($contentUsrMeta->ehistry_id)  ){
            //     throw new Exception("이관 콘텐츠는 오픈전까지 삭제기능이 제한됩니다.");
            // }
            // //오픈전 기능제한
            // if (class_exists('\Api\Services\ContentService') ) {
            //     $contentService = new \Api\Services\ContentService(app()->getContainer());
            //     $statusMeta = $contentService->findStatusMeta($content_id);
            //     $contentUsrMeta = $contentService->findContentUsrMeta($content_id);
                
            //     //ASIS 이관 메타데이터 
            //     if( !empty($statusMeta->bfe_video_id) || !empty($contentUsrMeta->hmpg_cntnts_id) || !empty($contentUsrMeta->ehistry_id)  ){
            //         throw new Exception("이관 콘텐츠는 오픈전까지 삭제기능이 제한됩니다.");
            //     }
            // }
            
            $oldContent = $db->queryRow("select * from bc_content where content_id = ".$content_id);
            
            $bcDeleteContent = new \Api\Models\ContentDelete;
            $deleteCheck = $bcDeleteContent::where('delete_type','=','CONTENT')->where('content_id', $content_id)->get();
            if(count($deleteCheck) !== 0){
                throw new Exception("파일삭제 요청된 이력이 있습니다.");
            };
            // $tbRequestService = new \Api\Services\TbRequestService(app()->getContainer());
            // $statusMeta = $contentService->findStatusMeta($content_id);
            $contentUsrMeta = $contentService->findContentUsrMeta($content_id);

            // 2022.10.26 EJ 모든 콘텐츠 등록 2주 후 자동 아카이브 하는 정책 변경으로 체크 안함
            // if( !empty( $statusMeta->archive_status ) ){
            //     throw new Exception("아카이브된 콘텐츠 입니다.");
            // }

            // if($tbRequestService->isArchiveRequestByContentId($content_id)){
            //     throw new Exception("아카이브 요청중인 콘텐츠 입니다.");
            // }


			if ( !checkAllowGrant($user_id, $content_id, GRANT_CONTENT_DELETE)) {
                //throw new Exception("'".$oldContent['title']."' 에 대한 삭제권한이 없습니다.");
                if( $_SESSION['user']['is_admin'] == 'Y' ){

                }else if($oldContent['status'] != 2 && $content->reg_user_id == $user_id ){
                    //승인전 등록자면 삭제가능
                }
            }

            if($action == 'delete_hr') {
                $check_media_status = $db->queryRow("select m.* from bc_content c, bc_media m where c.content_id=m.content_id and m.media_type='original' and c.content_id='".$content_id."'");

                if( !empty(trim($check_media_status['delete_date'])) ) throw new Exception("'".$oldContent['title'].' : '._text('MSG01020'));//이미 원본이 삭제된 콘텐츠입니다.
            } else if(($action == 'delete') || ($action == 'forceDelete')) {
                if( $oldContent['is_deleted'] == 'Y' ) throw new Exception("'".$oldContent['title'].' : '.'이미 삭제된 콘텐츠입니다.');//이미 원본이 삭제된 콘텐츠입니다.
            }
        }
        $archiveDeleteFailCount = 0;
        $archiveDeleteContentIds = [];
        $tbRequestService = new \Api\Services\TbRequestService(app()->getContainer());
        //삭제처리
		foreach ($contents as $content) {
            $content_id = $content->content_id;
			$reason = $db->escape($content->delete_his);
			if($action == 'delete_hr') {
                //원본삭제 처리
                $update_arr = array(
                    'flag' => DEL_MEDIA_CONTENT_REQUEST_FLAG
                );
                $update_where = "content_id=".$content_id." and media_type='original'";
                $db->update('BC_MEDIA', $update_arr, $update_where);
                
                //BC_DELETE_CONTENT 입력
			    insert_delete_list($content_id, 'ORIGINAL', $reason, $user_id);

                searchUpdate($content_id);
			}else if($action == 'delete') {
                //삭제 요청
                //전체삭제 처리
                //BC_DELETE_CONTENT 입력 삭제요청
                $contentDelete = $contentService->deleteRequest($content_id, $content->delete_his, $user);
                //$contentDelete = insert_delete_list($content_id, 'CONTENT', $reason, $user_id); 

                $medias = $mediaService->getMediaByContentId($content_id);
                foreach($medias as $media)
                {
                    $mediaService->deleteReady($media->media_id);
                }
                
                $contentService->delete($content_id, $user);

            }else if($action == 'forceDelete') {
                
                $statusMeta = $contentService->findStatusMeta($content_id);
                if( !empty( $statusMeta->archive_status ) || $tbRequestService->isArchiveRequestByContentId($content_id)) {
                    // 아카이브된 콘텐츠거나 아카이브 요청중인 콘텐츠
                    // 아카이브 삭제 요청
                    // 콘텐츠 상태 체크 하면 반려상태라서 아카이브 삭제 요청이 안됨 주석 
                    // $contentStatusCheck = $tbRequestService->contentStatusCheck($content_id);
                    $archiveRequestDeleteCheck = $tbRequestService->archiveRequestDeleteCheck($content_id);
                    if($content->status == ContentStatusType::REGISTERING) {
                        // 등록중인 콘텐츠는 아카이브 삭제 요청 X
                        $archiveDeleteFailCount = $archiveDeleteFailCount + 1;
                    } else if(!empty($archiveRequestDeleteCheck)) {
                        // 이미 아카이브 되어있는지 중복 요청 체크
                        $archiveDeleteFailCount = $archiveDeleteFailCount + 1;
                    } else {
                        // 삭제요청할 콘텐츠들
                        array_push($archiveDeleteContentIds,$content_id);
                    }

                } else {
                    //자동 삭제처리
                    //전체삭제 처리   

                    //BC_DELETE_CONTENT 입력 삭제요청
                    $contentDelete = $contentService->deleteRequest($content_id, $content->delete_his, $user);
                    //$contentDelete = insert_delete_list($content_id, 'CONTENT', $reason, $user_id);     

                    $task = $taskService->getTaskManager();                
                    $task->set_priority(400);
                    $task->setStatus('scheduled');

                    $medias = $mediaService->getMediaByContentId($content_id);
                    foreach($medias as $media)
                    {
                        $mediaService->deleteReady($media->media_id);

                        if( $media->status == 0 && empty($media->flag) ){
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
                    }

                    $contentService->delete($content_id, $user);
                    
                    //삭제 승인
                    $contentService->deleteAccept($contentDelete->id, $originalTaskId, $user);
                }
            }
            
            //해리스 서버 연계는 우선 주석 이후 분기 처리하도록...
            //Harris 테이블 매핑정보 지움
            //$db->exec("update harris set ariel_uid = null, mam_ingest = '' where ariel_uid = $content_id");
            
            //insertLog($action, $user_id, $content_id, $reason );
		}
        // 아카이브 삭제요청
        if(!empty($archiveDeleteContentIds)) {
            $input['content_ids'] = json_encode($archiveDeleteContentIds);
            $input['restore_request_comnt'] = $reason;
            $archiveRequestDelete = $tbRequestService->request($input,'delete',$user);
        }
        
	} else {
        throw new Exception('잘못된 접근입니다.(action:'.$action.')');
    }
    if(!empty($archiveDeleteFailCount)) {
        $msg = $archiveDeleteFailCount.'건 아카이브 삭제 요청 실패했습니다.';
    } else {
        $msg = '파일삭제가 요청되었습니다.';
    }
    echo json_encode(array(
        'success' => true,
        'msg' => $msg
    ));
	
}
catch (Exception $e)
{
	$msg = $e->getMessage();
	switch($e->getCode())
	{
		case ERROR_QUERY:
			$err = $db->errorInfo();
			$msg .= $err[2].'( '.$db->last_query.' )';
		break;
	}

	echo json_encode(array(
		'success' => false,
		'msg' => $msg
	));
}


function insert_delete_list($content_id, $action, $reason, $user_id){

    $delete_status = 'REQUEST';    

    $contentDelete = new \Api\Models\ContentDelete();

	$contentDelete->id          = getSequence('SEQ_BC_DELETE_CONTENT_ID');
    $contentDelete->content_id  =  $content_id;
    $contentDelete->delete_type	= $action;
    $contentDelete->status	    = $delete_status;
    $contentDelete->reason		= $reason;
    $contentDelete->reg_user_id	= $user_id;
    $contentDelete->created_date= date('YmdHis');
    $contentDelete->save();

    return $contentDelete;
}
?>
