<?php
set_time_limit(3600);
use \Api\Models\User;
use \Api\Services\DTOs\ContentDto;
use \Api\Services\DTOs\MediaDto;
use \Api\Services\DTOs\ContentStatusDto;
use \Api\Services\DTOs\ContentSysMetaDto;
use \Api\Services\DTOs\ContentUsrMetaDto;


require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');//2011.12.17 Adding Task Manager Class
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/timecode.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/interface.class.php');

try{
   
    //디버그 모드
    $isDebug = false;

    $migDB = new CommonDatabase('oracle','bis', 'bis', '10.10.50.135'.':'.'1521'.'/'.'orcl' );
    dump(date("Y-m-d H:i:s"));

    $limit = 1000;
    //"232_DTA_DETAIL_ID","179_EHISTRY_ID","207_UCI" 중복 확인
    $query = 'SELECT            
        "176_DTA_OCCRRNC_SE" as DTA_OCCRRNC_SE,
        "179_EHISTRY_ID" as EHISTRY_ID,
        "178_DTA_SE" as DTA_SE,
        "232_DTA_DETAIL_ID" as DTA_DETAIL_ID,
        "206_PHOTO_DETAIL_SE" as PHOTO_DETAIL_SE,
        "ORI_FILE_PATH" as ORI_FILE_PATH,
        "ORI_FILE_NM" as ORI_FILE_NM,
        "THUMB_FILE_PATH" as THUMB_FILE_PATH,
        "THUMB_FILE_NM" as THUMB_FILE_NM,
        "182_TITLE" as TITLE,
        "183_SUMMRY" as SUMMRY,        
        "196_CN_INCDNT" as CN_INCDNT,
        "197_INCDNT_INDICT_AT" as INCDNT_INDICT_AT,
        "198_USE_AT" as USE_AT,
        to_char("230_REGIST_DT",\'YYYYMMDDHH24MISS\') AS REGIST_DT,
            to_char("231_UPDT_DT",\'YYYYMMDDHH24MISS\') AS UPDT_DT,
        "207_UCI" as UCI,
        "208_PHOTO_SN" as PHOTO_SN,
        "209_EVENT_NM" as EVENT_NM,
        "210_EVENT_PURPS" as EVENT_PURPS,
        "211_RELATE_ISSUE" as RELATE_ISSUE,
        "212_ATDRN_NM" as ATDRN_NM,
        "189_CREAT_INSTT" as CREAT_INSTT,
        "213_CPYRHTOWN" as CPYRHTOWN,
        "190_CPYRHT" as CPYRHT,
        "214_AUTHR" as AUTHR,
        "185_SHOOTING_DE" as SHOOTING_DE,
        CASE "186_CLOR" when \'M\' then \'monochrome\' WHEN \'C\' THEN \'color\' ELSE \'\' end AS CLOR,
        "193_SHOOTING_DIRCTR" as SHOOTING_DIRCTR,
        "215_PTOGRFER" as PTOGRFER,
        "216_POTOGRF_NATION" as POTOGRF_NATION,
        "217_POTOGRF_CTY" as POTOGRF_CTY,
        "195_CN_PLACE" as CN_PLACE,
        "218_EVENT_PLACE" as EVENT_PLACE,
        "219_PRSN_1" as PRSN_1,
        "220_PRSN_CHOISE_1" as PRSN_CHOISE_1,
        "221_PRSN_SE_1" as PRSN_SE_1,
        "222_PRSN_LC_1" as PRSN_LC_1,
        "223_PRSN_2" as PRSN_2,
        "224_PRSN_CHOISE_2" as PRSN_CHOISE_2,
        "225_PRSN_SE_2" as PRSN_SE_2,
        "226_PRSN_LC_2" as PRSN_LC_2,
        "191_IMAGE_RSOLTN" as IMAGE_RSOLTN,
        "234_RTAT_SE" as RTAT_SE,
        "227_EHISTRY_THEMA_CL" as EHISTRY_THEMA_CL,
        "202_CN" as CN,
        "229_PHOTO_CL" as PHOTO_CL,
        "235_KOGL_TY" as KOGL_TY,
        "233_KRWD" as KRWD,
        "177_PHOTO_OCCRRNC_SE" as PHOTO_OCCRRNC_SE,
        "179_PHOTO_SE" as PHOTO_SE,
        "180_PHOTO_ID" as PHOTO_ID,
        "181_PHOTO_DETAIL_ID" as PHOTO_DETAIL_ID,
        "184_RECRD_DE" as RECRD_DE,
        "187_AUDIO_SE" as AUDIO_SE,
        "188_LANG_SE" as LANG_SE,
        "201_VOICE_ENNC" as VOICE_ENNC,
        "204_CLIP_BEGIN_TIME" as CLIP_BEGIN_TIME,
        "205_CLIP_END_TIME" as CLIP_END_TIME
        
FROM 
    MIGRATION_EHISTORY_photo order by "232_DTA_DETAIL_ID"';



    $migrationService = new \ProximaCustom\services\MigrationService();
    
    $contentService = new \Api\Services\ContentService($app->getContainer());
    $mediaService = new \Api\Services\MediaService($app->getContainer());
    $mediaSceneService = new \Api\Services\MediaSceneService($app->getContainer());


    $metaKeyMap = array(
        'created_date' => 'regist_dt',
        //'reg_user_id' =>     'regist_user_id',
        'updated_at' =>   'updt_dt' ,
        'last_modified_date' =>   'updt_dt' ,
        'updated_user_id' =>    'updt_user_id'       
    );
    
    //고정 값
    $bs_content_id = 518;
    $category_id= 2010;
    $ud_content_id= 5;
    $status = 2;        
    $user_id = 'ehistory';
    //등록 채널
    $channel = 'regist_ehistory';

    //제작단계구분
    $prod_step_se = 'M';
    $rdcst_stle_se = 'P';
    //영상유형구분
    $vido_ty_se = 'B';

    $total = $migDB->queryOne("select count(*) from MIGRATION_EHISTORY_photo ");



    dump(date("Y-m-d H:i:s").' - total : '.$total);
    for ($start = 0 ; $start < $total ; $start+=$limit) {
        $migDB->setLimit($limit, $start);
        
        $lists = $migDB->queryAll($query);

        foreach ($lists as $list) {
            
            // dump($list);
            
            $dtaDetailId = $list['dta_detail_id'];
        
            $isExist = $migrationService->isExistehistoryId($dtaDetailId);

            unset($list['mdb2rn']);
            if ($isExist) {
                $contentId      =  $isExist ;
            } else {
                //$contentId = 1111;
                $contentId      = $migrationService->getContentId();
            }
            $dto            = new ContentDto(['content_id' => $contentId]);
            $statusDto      = new ContentStatusDto(['content_id' => $contentId]);
            $sysMetaDto     = new ContentSysMetaDto(['sys_content_id' => $contentId]);
            $usrMetaDto     = new ContentUsrMetaDto(['usr_content_id' => $contentId]);

       
            $dto = $migrationService->dtoMapper($dto, $list, $metaKeyMap);
            $statusDto = $migrationService->dtoMapper($statusDto, $list);
            $sysMetaDto = $migrationService->dtoMapper($sysMetaDto, $list);
            $usrMetaDto = $migrationService->dtoMapper($usrMetaDto, $list);
        
            //고정
            $dto->category_id= $category_id;
            $dto->category_full_path= '/0/100/'.$category_id;
            $dto->bs_content_id = $bs_content_id;
            $dto->ud_content_id = $ud_content_id;
            $dto->status = $status;

            $user = new User();
            $user->user_id = $user_id;

            //제작단계구분
            $usrMetaDto->prod_step_se = $prod_step_se;
            $usrMetaDto->brdcst_stle_se = $rdcst_stle_se;
            //영상유형구분
            $usrMetaDto->vido_ty_se = $vido_ty_se;


            $createTime = strtotime($dto->created_date);
            $createTimeYmd =  date('Ymd', $createTime);
            $createTimeYmdhis = date('YmdHis', $createTime);

        
            //대표이미지
            $thumbPathInfo = $migrationService->getPath($list['thumb_file_path'], $list['thumb_file_nm'], '/movie_pds/ImageRoot/');

            //원본 및 저해상도
            $originalPathInfo = $migrationService->getPath($list['ori_file_path'], $list['ori_file_nm'], '/e_movie/StreamSplit/');
        


            if (!$isExist) {
                //미디어ID 발급
                $usrMetaDto->media_id = \ProximaCustom\services\ContentService::getMediaId($bs_content_id, $category_id, $createTimeYmd);
            }

        
            $sysMetaDto->sys_filename =  $originalPathInfo['filename'];
            $sysMetaDto->sys_ori_filename =  $originalPathInfo['filename'];
            $sysMetaDto->sys_display_size =  $list['image_rsoltn'];

            if ($isExist) {
                // dump('exist '.$contentId);
                //콘텐츠 생성
                $dto = $migrationService->updateNestDto($dto);
                $statusDto = $migrationService->updateNestDto($statusDto);
                $sysMetaDto = $migrationService->updateNestDto($sysMetaDto);
                $usrMetaDto = $migrationService->updateNestDto($usrMetaDto);
          
                $contentService->update($contentId, $dto, $statusDto, $sysMetaDto, $usrMetaDto, $user);
                continue;
            } else {
                //콘텐츠 생성
                if (!$isDebug) {
                    $contentService->create($dto, $statusDto, $sysMetaDto, $usrMetaDto, $user);
                } else {
                    // dump($dto);
                    // dump($statusDto);
                    // dump($sysMetaDto);
                    // dump($usrMetaDto);
                }
            }
            //미디어 생성

            $filesize = 10;
       
            $mediaType = 'original';
            $storageId = $migrationService->getStorageId($bs_content_id, $ud_content_id, $category_id, $mediaType);
            $mediaPath = $originalPathInfo['fullPath'];
            $mediaData = [
                    'content_id' => $contentId,
                    'storage_id' => $storageId,
                    'media_type' => $mediaType,
                    'path' => $mediaPath,
                    'reg_type' => $channel,
                    'filesize' => $filesize,
                    'created_date' => $createTimeYmdhis
            ];
            $mediaDto = new MediaDto($mediaData);
            if (!$isDebug) {
                $mediaService->create($mediaDto, $user);
            } else {
                // dump($mediaDto);
            }

            //저해상도 미디어 생성

            $mediaType = 'proxy';
            $storageId = $migrationService->getStorageId($bs_content_id, $ud_content_id, $category_id, $mediaType);
            $mediaPath = $originalPathInfo['fullPath'];
            $mediaData = [
                    'content_id' => $contentId,
                    'storage_id' => $storageId,
                    'media_type' => $mediaType,
                    'path' => $mediaPath,
                    'reg_type' => $channel,
                    'filesize' => $filesize,
                    'created_date' => $createTimeYmdhis
                ];
            $mediaDto = new MediaDto($mediaData);
            if (!$isDebug) {
                $mediaService->create($mediaDto, $user);
            } else {
                // dump($mediaDto);
            }

            $mediaType = 'thumb';
            $storageId = $migrationService->getStorageId($bs_content_id, $ud_content_id, $category_id, $mediaType);
            $mediaPath = $thumbPathInfo['fullPath'];
            $mediaData = [
                    'content_id' => $contentId,
                    'storage_id' => $storageId,
                    'media_type' => $mediaType,
                    'path' => $mediaPath,
                    'reg_type' => $channel,
                    'filesize' => $filesize,
                    'created_date' => $createTimeYmdhis
                ];
            $mediaDto = new MediaDto($mediaData);
            if (!$isDebug) {
                $mediaService->create($mediaDto, $user);
            } else {
                // dump($mediaDto);
            }

        
            // //입수 마이그레이션 워크플로우 수행
            // $task = new TaskManager($db);
            // $task_id = $task->insert_task_query_outside_data($contentId, $reg_type, 1, $user_id, $srcPath );
            // exit;
            echo $contentId.'<br/>';
        }

        dd('end');
    }
}catch(Exception $e){
    echo $e->getMessage();
}
?>