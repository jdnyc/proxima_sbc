<?php

namespace ProximaCustom\core;

use Api\Types\TaskType;
use Api\Types\TaskStatus;
use Api\Types\ArchiveStatus;
use Api\Services\UserService;
use Api\Services\ZodiacService;
use Api\Services\ContentService;
use Api\Services\SnsPostService;
use Api\Types\Social\PrivacyStatus;
use Api\Types\Social\PublishStatus;
use Api\Support\Helpers\SMSMessageHelper;
use Api\Types\UdContentType;

class TaskEventHandler
{
    /**
     * 트랜스코딩 완료 후 로직 처리를 위한 핸들러
     *
     * @param array $event
     * @return void
     */
    public static function handleAfterTranscode($event)
    {
        $task = $event['task'];
        if ($task['destination'] === 'tc_sns_publish_media') {
            // SNS 게시 작업 추가...
            $container = \Api\Application::container();
            $taskId = $task['task_id'];
            $snsPostService = new SnsPostService($container);
            $post = $snsPostService->findByTaskId($taskId);
            if ($post === null) {
                return;
            }

            // 변환된 미디어를 Post에 추가해준다.
            $post->media_id = $task['trg_media_id'];
            $post->status = PublishStatus::TC_FINISHED;
            $post->save();

            // 예약 작업이면 API 작업을 추가 하지 않는다.
            if ($post->private_status === PrivacyStatus::BOOK) {
                return;
            }

            // API 작업 추가
            $snsPostService->createPublishApiJob($post);
        }else if($task['src_content_id'] && strstr($task['destination'], 'create_proxy_') ){
            //추가 작업 후 포털 api 업데이트
            $contentId = $task['src_content_id'];
            $container = \Api\Application::container();
            $contentService = new \Api\Services\ContentService($container);
            $contentMap = $contentService->getContentForPush($contentId);     
            if($contentMap){                
                $apiJobService = new \Api\Services\ApiJobService($container);
                $apiJobService->createApiJob( 'Api\Services\ContentService', 'update', $contentMap , $contentId );
            }
        }
    }

    /**
     * 작업 에러 발생 시 로직 처리를 위한 핸들러
     *
     * @param array $event
     * @return void
     */
    public static function handleAfterError($event)
    {
        $task = $event['task'];
        $errorLog = $event['error_log'];
        
        $taskId = $task['task_id'];

        // SNS 게시 미디어 변환 실패 시 SnsPost 에 상태 변경해준다.
        if ($task['destination'] === 'tc_sns_publish_media') {
            $container = \Api\Application::container();
            $snsPostService = new SnsPostService($container);
            $post = $snsPostService->findByTaskId($taskId);
            if ($post === null) {
                return;
            }

            $post->status = PublishStatus::TC_FAILED;
            $post->save();
        }

        if ($task['type'] == TaskType::CATALOG || $task['type'] == TaskType::THUMBNAIL) {
            //카탈로깅 섬네일 오류시 재시도 처리
            // $findTask = Task::where('task_id',$task['task_id'])->first();
            // if($findTask){
            //     $retry_cnt = $findTask->retry_cnt;
            //         if($retry_cnt < 10) {
            //             $retry_cnt = $retry_cnt + 1;
            //             //$task_query = "update bc_task set status = 'queue', retry_cnt=".$retry_cnt." where task_id = ".$task_id." ";
            //         }
            // }
        }

        if( $task['type'] == TaskType::TRANSCODING || $task['type'] == TaskType::RESTORE  ){

            //작업 실패시 문자 알림
            $contentService = new ContentService(app()->getContainer());
            if(  config('sms')['task'] &&  $task['src_content_id'] != null ){
                
                $content = $contentService->find($task['src_content_id']);
                $userService = new UserService(app()->getContainer());
                $zodiacService = new ZodiacService(app()->getContainer());

                $targetPhones = [];

                //작업요청자 조회
                if( !empty($task['task_user_id']) ){
                    $ownerUser = $userService->findByUserId($task['task_user_id']);
                    $ownerUserPhone = $ownerUser->phone;
                }
                //없으면 등록자
                if( empty($ownerUserPhone) ){
                    $ownerUser = $userService->findByUserId($content->reg_user_id);
                    $ownerUserPhone = $ownerUser->phone;
                }
                if ( !empty($ownerUserPhone) ) {
                    $targetPhones [] = $ownerUserPhone;
                }              
                //알림 그룹 별도 지정  
                $adminUsers = $userService->getAlertUsers();
                if (!empty($adminUsers)) {
                    foreach ($adminUsers as $adminUser) {
                        $targetPhones [] = $adminUser->phone;
                    }

                    //중복제거
                    if (!empty($targetPhones)) {
                        $targetPhones = array_unique($targetPhones);

                        $usrMeta = $contentService->findContentUsrMeta($task['src_content_id']);
                        $content->media_id = $usrMeta->media_id;
                        $smsMsg = SMSMessageHelper::makeMsgTaskError($errorLog, $content);
                        foreach ($targetPhones as $phone) {
                            $zodiacService->sendSMS($phone, $smsMsg);
                        }
                    }
                }
            }
        }

        if ( $task['type'] == TaskType::DELETE ) {
            
        }
    }

    /**
     * 등록 후 자동 전송(주,부조) 이벤트 처리
     *
     * @param [type] $event
     * @return void
     */
    public static function handleAfterInfoView($event)
    {
        $task = $event['task'];
        $taskId = $task['task_id'];
        $contentId = $task['src_content_id'];
        $taskUserId = $task['task_user_id'];

        $container = app()->getContainer();

        $contentService = new \Api\Services\ContentService($container);
        $taskService = new \Api\Services\TaskService($container);

        $contentStatus = $contentService->findStatusMeta($contentId);

        if ($contentStatus->scr_trnsmis_sttus == 'request') {
            //전송 작업 수행
            $taskMgr = $taskService->getTaskManager();
            $channel = 'transmission_zodiac_ab';
            $new_task_id = $taskMgr->start_task_workflow($contentId, $channel, $taskUserId);
            //작업 추가 후 상태 변경 처리 
            $contentStatus->scr_trnsmis_sttus = 'requested';
            $contentStatus->save();
        }

        if ($contentStatus->scr_news_trnsmis_sttus == 'request') {
            //전송 작업 수행
            $taskMgr = $taskService->getTaskManager();
            $channel = 'transmission_zodiac_news';
            $new_task_id = $taskMgr->start_task_workflow($contentId, $channel, $taskUserId);
            //작업 추가 후 상태 변경 처리 
            $contentStatus->scr_news_trnsmis_sttus = 'requested';
            $contentStatus->save();
        }

        if ($contentStatus->mcr_trnsmis_sttus == 'request') {
            //전송 작업 수행 
            $taskMgr = $taskService->getTaskManager();
            $channel = 'transfer_to_maincontrol';
            $new_task_id = $taskMgr->start_task_workflow($contentId, $channel, $taskUserId);
             //작업 추가 후 상태 변경 처리 
            $contentStatus->mcr_trnsmis_sttus = 'requested';
            $contentStatus->save();
        }

        return true;
    }

    public static function handleAfterFTP($event)
    {
        $logger = new \Proxima\core\Logger(__FUNCTION__);
        $logger->info(print_r($event, true));

        $task = $event['task'];
        $request = $event['request'];
        $taskId = $task['task_id'];
        $contentId = $task['src_content_id'];
        $taskUserId = $task['task_user_id'];

        $status = (string) $request->Status;
        $progress = (string) $request->Progress;
        $log = (string) $request->Log;

        $container = app()->getContainer();

        $contentService = new \Api\Services\ContentService($container);
        $userService = new UserService(app()->getContainer());
        $zodiacService = new ZodiacService(app()->getContainer());

        // 2022-12-19 EJ 추가 전송하는 경우 오류 있어서 다 각각 설정 / 
        if (strstr($task['destination'], 'transmission_zodiac')) {
            $logger->info(print_r($task['destination'], true));
            //부조 전송 후
            $zodiac = $container->get('zodiac');
            $contentStatus = $contentService->findStatusMeta($contentId);
            if ($task['trg_storage_id'] == \Api\Types\StorageIdMap::SCR_AB || $task['trg_storage_id'] == \Api\Types\StorageIdMap::SCR_NEWS)
            {
                if ($contentStatus) {
                    if ($status == TaskStatus::COMPLETE) {
                        $contentStatus->scr_trnsmis_end_dt = date("YmdHis");

                        if(empty($contentStatus->scr_trnsmis_ty) ) {
                            // 추후 동시전송 기능 추가하게 된다면 수정 필요
                            if($task['trg_storage_id'] == \Api\Types\StorageIdMap::SCR_AB) {
                                // AB 전송
                                $contentStatus->scr_trnsmis_ty = 'ab';
                            } else {
                                $contentStatus->scr_trnsmis_ty = 'news';
                            }
                        } else {
                            if($contentStatus->scr_trnsmis_ty == 'ab' && $task['trg_storage_id'] == \Api\Types\StorageIdMap::SCR_NEWS) {
                                // 처음 AB전송 했다가 추가로 뉴스 전송 한 경우
                                $contentStatus->scr_trnsmis_ty = 'all';
                                $contentStatus->scr_news_trnsmis_sttus = $status;
                            } else if($contentStatus->scr_trnsmis_ty == 'news' && $task['trg_storage_id'] == \Api\Types\StorageIdMap::SCR_AB) {
                                // 처음 뉴스전송 했다가 추가로 AB 전송 한 경우
                                $contentStatus->scr_trnsmis_ty = 'all';
                                $contentStatus->scr_trnsmis_sttus = $status;
                            } 
                        }
                        if($contentStatus->scr_trnsmis_ty == 'all' && $task['trg_storage_id'] == \Api\Types\StorageIdMap::SCR_AB) {
                            $contentStatus->scr_trnsmis_sttus = $status;
                        } else if($contentStatus->scr_trnsmis_ty == 'all' &&$task['trg_storage_id'] == \Api\Types\StorageIdMap::SCR_NEWS) {
                            $contentStatus->scr_news_trnsmis_sttus = $status;
                        }else if($contentStatus->scr_trnsmis_ty =='news') {
                            $contentStatus->scr_news_trnsmis_sttus = $status;
                        } else {
                            $contentStatus->scr_trnsmis_sttus = $status;
                        }
                    }

                    if (empty($contentStatus->scr_trnsmis_begin_dt)) {
                        $contentStatus->scr_trnsmis_begin_dt = date("YmdHis");
                    }
                    $contentStatus->save();
                    $logger->info(print_r($contentStatus, true));
                }
                $trans = \Api\Models\TbOrdTrans::where('task_id', $taskId)->first();
                if ($trans) {
                    $trans->tr_status = $status;
                    $trans->tr_progress = $progress;

                    switch ($status) {
                        case 'processing':
                            $trnst_st = '2000';
                            break;
                        case 'complete':
                            $trnst_st = '3000';
                            break;
                        case 'error':
                            $trnst_st = '4001';
                            break;
                        default:
                            $trnst_st = '1000';
                            break;
                    }

                    $media_cd = '001';
                    // if(in_array($ud_content_id, $MEDIA_LIST)) {
                    //     $media_cd = '001';
                    // } else if(in_array($ud_content_id, $CG_LIST)) {
                    //     if( $bs_content_id == SEQUENCE) {
                    //         $media_cd = '003';
                    //     } else {
                    //         $media_cd = '002';
                    //     }
                    // }

                    // 1000   매핑됨
                    // 2000   진행중
                    // 3000   성공
                    // 4000   실패
                    // 4001   전송실패
                    // 5000   재시도중
                    // 6000   연기
                    // 7000   수동
                    // 9000   삭제됨
                    $param = [
                        'plyout_id' => $trans->playout_id,
                        'media_cd' => $media_cd,
                        'trnsf_rate' => $progress,
                        'trnst_st' => $trnst_st,
                        'server' => $task['assign_ip'],
                        'server_ip' => $task['assign_ip'],
                        'message' => $log,
                        'usr_id' => $task['task_user_id'],
                    ];
                    $logger->info(print_r($param, true));
                    $return = $zodiac->putUpdateContentsTransfer($param);
                    $logger->info(print_r($return, true));
                    if ($status == 'processing' && $progress == 0) {
                        $trans->request_time = date('YmdHis');
                    } elseif ($status == 'complete') {
                        $trans->complete_time = date('YmdHis');
                    }
                    $trans->save();
                }
            }
        }

        if (strstr($task['destination'], 'transfer_to_maincontrol')) {
            //주조 전송 후
            if ($status == TaskStatus::COMPLETE) {
                $content = $contentService->find($contentId);
                $usrMeta = $contentService->findContentUsrMeta($contentId);
                $sysMeta = $contentService->findContentSysMeta($contentId);

                $filePathInfo = new \Api\Core\FilePath($task['target']);

                $data = (object) [];
                //확장자 제외
                $data->filename = $filePathInfo->filename;
                $data->mtrl_id = $filePathInfo->filename;
                $data->title = $content->title;
                $data->matr_knd = $usrMeta->matr_knd;
                $data->sys_video_rt = $sysMeta->sys_video_rt;
                $data->brdcst_de = $usrMeta->brdcst_de;
                $data->progrm_code = $usrMeta->progrm_code;
                $data->tme_no = $usrMeta->tme_no;
                $data->trns_flag = 3000;
                $data->remark = '';

                $bisService = new \Api\Services\BisCommonService($container);
                $logger->info('BisCommonService');
                $logger->info(print_r($data, true));

                //운영임 주석 풀고 테스트
                $return = $bisService->createContent($data, $taskUserId);
                $logger->info(print_r($return, true));
            }
        }

        //주부조 전송 완료 후 처리
        if( strstr($task['destination'], 'transfer_to_maincontrol') || strstr($task['destination'], 'transmission_zodiac') ){
            if ($status == TaskStatus::COMPLETE) {
                //프레임레이트 확인 25프레임인경우 등록자 알림          
                $taskUserId = $task['task_user_id'];
                //SELECT SYS_FRAME_RATE FROM BC_SYSMETA_MOVIE WHERE SYS_FRAME_RATE != '29.97 Frame/s';
                $sysMeta = $contentService->findContentSysMeta($contentId);
                if (trim($sysMeta['sys_frame_rate']) == '29.97' ||  trim($sysMeta['sys_frame_rate']) == '29.97 Frame/s') {
                }else{
                    //알림 그룹 별도 지정
                    $targetPhones = [];  
                    $adminUsers = $userService->getAlertUsers();
                    if (!empty($adminUsers)) {
                        foreach ($adminUsers as $adminUser) {
                            $targetPhones [] = $adminUser->phone;
                        }
                    }
                    $taskUser = $userService->findByUserId($taskUserId);                                   
                    if(!empty($taskUser) && !empty($taskUser->phone)){
                        $targetPhones [] = $taskUser->phone;
                    }
                    if (!empty($targetPhones)) {
                        $targetPhones = array_unique($targetPhones);
                        
                        $errorLog = "프레임레이트 정보 : ".$sysMeta['sys_frame_rate'];
                        $content = $contentService->find($contentId);
                        $smsMsg = SMSMessageHelper::makeMsgFrameRate($errorLog,$content);
                        foreach($targetPhones as $phone)
                        {
                            $zodiacService->sendSMS($phone, $smsMsg);
                        }
                    }
                }
            }
        }

        return true;
    }

    public static function handleAfterArchive($event)
    {
        $logger = new \Proxima\core\Logger(__FUNCTION__);
        $logger->info(print_r($event, true));

        $task = $event['task'];
        $request = $event['request'];
        $taskId = $task['task_id'];
        $contentId = $task['src_content_id'];
        $taskUserId = $task['task_user_id'];

        $status = (string) $request->Status;
        $progress = (string) $request->Progress;
        $log = (string) $request->Log;

        $container = app()->getContainer();
        $contentService = new \Api\Services\ContentService($container);
        $archiveService = new \Api\Services\ArchiveService($container);
        $mediaService = new \Api\Services\MediaService($container);
        $taskService = new \Api\Services\TaskService($container);
        //아카이브
        if (strstr($task['destination'], 'dtl_archive')) {
            $logger->info(print_r($task['destination'], true));

            if ($task['type'] == ARIEL_TRANSFER_FS || $task['type'] == 91) {
                //니어라인
                // ARCHV_BEGIN_DT,ARCHV_STTUS,DTL_ARCHV_BEGIN_DT,DTL_ARCHV_END_DT,DTL_ARCHV_STTUS

                $contentStatus = $contentService->findStatusMeta($contentId);
                if ($contentStatus) {
                    if ($status == TaskStatus::COMPLETE) {
                        $contentStatus->archv_end_dt = date("YmdHis");
                        if ($contentStatus->archive_status == ArchiveStatus::DTL) {
                            $contentStatus->archive_status = ArchiveStatus::NEARLINE_AND_DTL;
                        } else if ($contentStatus->archive_status != ArchiveStatus::NEARLINE_AND_DTL) {
                            $contentStatus->archive_status = ArchiveStatus::NEARLINE;
                        }
                    } else {
                        if (empty($contentStatus->archv_begin_dt) || $status == TaskStatus::ASSIGNING) {
                            $contentStatus->archv_begin_dt = date("YmdHis");
                        }
                    }
                    $contentStatus->archv_sttus = $status;
                    $contentStatus->save();
                }
            }

            if ($task['type'] == ARCHIVE) {
                $content = $contentService->find($contentId);
                //니어라인
                $contentStatus = $contentService->findStatusMeta($contentId);
                if ($contentStatus) {
                    if ($status == TaskStatus::COMPLETE) {
                        $contentStatus->dtl_archv_end_dt = date("YmdHis");
                        if ($contentStatus->archive_status == ArchiveStatus::NEARLINE) {
                            $contentStatus->archive_status = ArchiveStatus::NEARLINE_AND_DTL;
                        } else if ($contentStatus->archive_status != ArchiveStatus::NEARLINE_AND_DTL) {
                            $contentStatus->archive_status = ArchiveStatus::DTL;
                        }
                    } else {
                        if (empty($contentStatus->dtl_archv_begin_dt)) {
                            $contentStatus->dtl_archv_begin_dt = date("YmdHis");
                        }
                    }
                    $contentStatus->dtl_archv_sttus = $status;
                    $contentStatus->save();
                }

                if($content){
                    //클린,뉴스편집,마스터본 아카이브 만료일 4주
                    if( $content->ud_content_id == 2 || $content->ud_content_id == 3 || $content->ud_content_id == 9 ){
                        $media = $mediaService->find($task['src_media_id']);
                        if($media['media_type'] == 'archive'){
                            $media->expired_date = date('YmdHis', strtotime("+28 days"));
                            $media->save();
                        }
                    }
                }
            }

        }

        //리스토어
        //니어라인 리스토어
        if ( $task['destination'] == 'dtl_restore' || $task['destination'] == 'dtl_restore_copy' || strstr($task['destination'], 'file_restore')) {
            $logger->info(print_r($task['destination'], true));
            $content = $contentService->find($contentId);

            if ($task['type'] == ARIEL_TRANSFER_FS || $task['type'] == 91) {
                //니어라인
                // ARCHV_BEGIN_DT,ARCHV_STTUS,DTL_ARCHV_BEGIN_DT,DTL_ARCHV_END_DT,DTL_ARCHV_STTUS
                //RESTORE_AT,RESTORE_BEGIN_DT,RESTORE_DATE,RESTORE_END_DT,RESTORE_STTUS

                $contentStatus = $contentService->findStatusMeta($contentId);
                if ($contentStatus) {
                    if ($status == TaskStatus::COMPLETE) {
                        $contentStatus->restore_end_dt = date("YmdHis");
                         $contentStatus->restore_at = '1';
                   } else {
                        if (empty($contentStatus->restore_begin_dt) || $status == 'assigning') {
                            $contentStatus->restore_begin_dt = date("YmdHis");
                        }
                    }
                    $contentStatus->restore_sttus = $status;
                    $contentStatus->save();

                               
                    if ($status == TaskStatus::COMPLETE) {
                        $container['searcher']->update($contentId);
                    }
                    $logger->info(print_r($task['destination'], true));
                }
                if ($content) {
                    $expiredDate = date('YmdHis', strtotime("+14 days"));
                    $content->expired_date = $expiredDate;
                    $content->updated_at = date("YmdHis");
                    $content->last_modified_date = date("YmdHis");
                    $content->updated_user_id = $taskUserId;
                    $content->save();
                }
            }else if($task['type'] == RESTORE){
                //리스토어 후 니어라인 스토리지 만료일자 설정
                if($content){
                    if( $content->ud_content_id == 2 || $content->ud_content_id == 3 || $content->ud_content_id == 9 ){
                        $media = $mediaService->find($task['trg_media_id']);
                        if($media['media_type'] == 'archive'){
                            $media->expired_date = date('YmdHis', strtotime("+28 days"));
                            $media->save();
                        }
                    }

                    if ($status == TaskStatus::COMPLETE) {
                        //라우드니스 작업 재요청
                        $loudnessInfo = \Api\Models\TbLoudness::where('content_id', $contentId)->first();
                        if (!empty($loudnessInfo)) {
                            $logger->info(print_r($loudnessInfo->unit, true));
                            if ($loudnessInfo->unit == 'LKFS') {
                                //lufs정보가 있는 경우 재작업 요청
                                $taskMgr = $taskService->getTaskManager();
                                $channel = 'loudness_measure_near';
                                $logger->info(print_r('start_task_workflow: '.$contentId.'|'. $channel.'|'.$taskUserId, true));
                                $taskMgr->start_task_workflow($contentId, $channel, $taskUserId);
                            }
                        }else{

                            if($content->ud_content_id == 3 && ( strstr($content->category_full_path,'/0/100/200')  ) ){
                                $taskMgr = $taskService->getTaskManager();
                                $channel = 'loudness_measure_near';
                                $logger->info(print_r('start_task_workflow: '.$contentId.'|'. $channel.'|'.$taskUserId, true));
                                $taskMgr->start_task_workflow($contentId, $channel, $taskUserId);
                            }
                        }
                    }
                }
            }
        }

         //임시 주석

        //리스토어 니어라인 변환 후 재 아카이브
        if ($task['destination'] == 'dtl_restore_near_mig') {   
            $logger->info(print_r($task['destination'], true));
            $contentStatus = $contentService->findStatusMeta($contentId);
            if ($contentStatus) {
                if ($status == TaskStatus::COMPLETE) {
                    if ($task['type'] == 91) {
                        //미디어ID 변경
                        //replace 수정중
                        //$contentUsrMeta = $contentService->findContentUsrMeta($contentId);
                        //$contentUsrMeta->media_id = $contentUsrMeta->media_id;
                        //아카이브 작업
                        $user = new \Api\Models\User();
                        $user->user_id = $taskUserId;
                        $logger->info(print_r('archiveDtlMig', true));
                        $rtn = $archiveService->archiveDtlMig($contentId, $user, $task);
                        $logger->info(print_r($rtn, true));
                    }
                }
            }
        }

        if (strstr($task['destination'], 'dtl_delete')) {
            $contentStatus = $contentService->findStatusMeta($contentId);
            if ($contentStatus) {
                if ($status == TaskStatus::COMPLETE) {
                    $contentStatus->archive_status = 0;
                    $contentStatus->save();

                    $archiveService->deleteArchiveMedia($contentId);
                }
            }
        }
    }

    //외부 연동 동기화
    public static function handleAfterSync($event)
    {
        $task = $event['task'];
        $request = $event['request'];
        
        // $taskId = $task['task_id'];
        $contentId = $task['src_content_id'];
        // $taskUserId = $task['task_user_id'];

        $status = (string)$request->Status;

        if ($status == TaskStatus::COMPLETE) {
            $container = app()->getContainer();
            $contentService = new \Api\Services\ContentService($container);
            $apiJobService = new \Api\Services\ApiJobService($container);
            //업데이트
            if ($contentId) {
                $contentMap = $contentService->getContentForPush($contentId);
                if ($contentMap) {
                    $apiJobService->createApiJob('Api\Services\ContentService', 'update', $contentMap, $contentId);
                }
            }
        }
        return true;
    }

    //이관 등록 후 자동 아카이브
    public static function handleAfterMigration($event)
    {
        $logger = new \Proxima\core\Logger(__FUNCTION__);
        $task = $event['task'];
        $request = $event['request'];
        $logger->info(print_r($event, true));
        // $taskId = $task['task_id'];
        $contentId = $task['src_content_id'];
        // $taskUserId = $task['task_user_id'];

        $status = (string)$request->Status;

        if ($status == TaskStatus::COMPLETE) {
            $container = app()->getContainer();
            $contentService = new \Api\Services\ContentService($container);
            $apiJobService = new \Api\Services\ApiJobService($container);
            if (strstr($task['destination'], 'regist_tape_mig')) {
                $mig = new \Api\Support\Commands\ExcelMigration();
                
                $taskId = $mig->archiveDD($contentId, 'admin');
                $logger->info(print_r($taskId, true));
            }
        }
        return true;
    }

    public static function handleAfterLoudness($event)
    {
        $task = $event['task'];
        
        $request = $event['request'];
        $taskId = $task['task_id'];
        $contentId = $task['src_content_id'];
        $taskUserId = $task['task_user_id'];

        $status = (string) $request->Status;
        $progress = (string) $request->Progress;
        $log = (string) $request->Log;

        if ($status == TaskStatus::COMPLETE && $task['task_rule_id'] === '370') {
            
            $contentService = new ContentService(app()->getContainer());
            $userService = new UserService(app()->getContainer());
            $zodiacService = new ZodiacService(app()->getContainer());

            $loudnessInfo = \Api\Models\TbLoudness::where('content_id', $contentId)->first();

            
            $targetPhones = [];
            $adminUsers = $userService->getAlertLoudnessUsers();
            if (!empty($adminUsers)) {
                foreach ($adminUsers as $adminUser) {
                    $targetPhones [] = $adminUser->phone;
                }

                //중복제거
                if (!empty($targetPhones)) {
                    $targetPhones = array_unique($targetPhones);
                    $integrate = (int)$loudnessInfo['integrate'];

                    if( $integrate <= -22 && $integrate >= -26 ){
                        //-22LKFS ~ -26 INTEGRATE
                    }else{
                        $content = $contentService->find($task['src_content_id']);
                        $usrMeta = $contentService->findContentUsrMeta($task['src_content_id']);
                        $content->media_id = $usrMeta->media_id;
                        $errorLog = "LKFS 측정값: ".$loudnessInfo['integrate'];

                        $smsMsg = SMSMessageHelper::makeMsgTaskLoudness($errorLog, $content);

                        foreach ($targetPhones as $phone) {
                            $zodiacService->sendSMS($phone, $smsMsg);
                        }

                        if($content['ud_content_id'] == UdContentType::MASTER && $usrMeta['vido_ty_se'] =='B'){
                            $taskUser = $userService->findByUserId($taskUserId);
                          
                            if(!empty($taskUser) && !empty($taskUser->phone)){
                                $zodiacService->sendSMS($taskUser->phone, $smsMsg);
                            }
                        }
                    }
                }
            }
          
        }
    }
}
