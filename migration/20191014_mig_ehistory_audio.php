<?php
use \Api\Models\User;
use \Api\Services\DTOs\ContentDto;
use \Api\Services\DTOs\MediaDto;
use \Api\Services\DTOs\ContentStatusDto;
use \Api\Services\DTOs\ContentSysMetaDto;
use \Api\Services\DTOs\ContentUsrMetaDto;

define('TEMP_ROOT', 'D:/Project/Git/proxima_v3_ktv_git');
require_once(TEMP_ROOT.'/lib/config.php');
require_once(TEMP_ROOT.'/lib/functions.php');
require_once(TEMP_ROOT.'/workflow/lib/task_manager.php');//2011.12.17 Adding Task Manager Class
require_once(TEMP_ROOT.'/lib/timecode.class.php');
require_once(TEMP_ROOT.'/lib/interface.class.php');

try{

    //328건 작업
    //디버그 모드
    $isDebug = true;

     $migDB = new CommonDatabase('oracle','bis', 'bis', '10.10.50.135'.':'.'1521'.'/'.'orcl' );
    $migDB->setLimit(1000, 0 );
    
    $lists = $migDB->queryAll('SELECT            
        "176_DTA_OCCRRNC_SE" as DTA_OCCRRNC_SE,
        "179_EHISTRY_ID" as EHISTRY_ID,
        "178_DTA_SE" as DTA_SE,
        "177_PHOTO_OCCRRNC_SE" as PHOTO_OCCRRNC_SE,
        "AUDIO_SUBJECT" as EHISTRY_ORIGIN,
        "183_SUMRY" as SUMRY,
        "185_SHOOTING_DE" as SHOOTING_DE,
        "184_RECRD_DE" as RECRD_DE,
        "187_AUDIO_SE" as AUDIO_SE,
        "188_LANG_SE" as LANG_SE,
        "189_CREAT_INSTT" as CREAT_INSTT,
        "190_CPYRHT" as CPYRHT,
        "193_SHOOTING_DIRCTR" as SHOOTING_DIRCTR,
        "195_CN_PLACE" as CN_PLACE,
        "196_CN_INCDNT" as CN_INCDNT,
        "197_INCDNT_INDICT_AT" as INCDNT_INDICT_AT,
        "232_DTA_DETAIL_ID" as DTA_DETAIL_ID,
        "179_PHOTO_SE" as PHOTO_SE,
        "180_PHOTO_ID" as PHOTO_ID,
        "181_PHOTO_DETAIL_ID" as PHOTO_DETAIL_ID,
        "ORI_FILE_PATH" as ORI_FILE_PATH,
        "ORI_FILE_NM" as ORI_FILE_NM,
        "182_TITLE" as TITLE,
        "204_CLIP_BEGIN_TIME" as CLIP_BEGIN_TIME,
        "205_CLIP_END_TIME" as CLIP_END_TIME,
        "198_USE_AT" as USE_AT,
        to_char("230_REGIST_DT",\'YYYYMMDDHH24MISS\') AS REGIST_DT,
        to_char("231_UPDT_DT",\'YYYYMMDDHH24MISS\') AS UPDT_DT,
        "202_CN" as CN,
        "233_KWRD" as KWRD,
        CASE "186_CLOR" when \'M\' then \'monochrome\' WHEN \'C\' THEN \'color\' ELSE \'\' end AS CLOR,
        "191_IMAGE_RSOLTN" as IMAGE_RSOLTN,
        "201_VOICE_ENNC" as VOICE_ENNC,
        "206_PHOTO_DETAIL_SE" as PHOTO_DETAIL_SE,
        "207_UCI" as UCI,
        "208_PHOTO_SN" as PHOTO_SN,
        "209_EVENT_NM" as EVENT_NM,
        "210_EVENT_PURPS" as EVENT_PURPS,
        "211_RELATE_ISSUE" as RELATE_ISSUE,
        "212_ATDRN_NM" as ATDRN_NM,
        "213_CPYRHTOWN" as CPYRHTOWN,
        "214_AUTHR" as AUTHR,
        "215_PTOGRFER" as PTOGRFER,
        "216_POTOGRF_NATION" as POTOGRF_NATION,
        "217_POTOGRF_CTY" as POTOGRF_CTY,
        "218_EVENT_PLACE" as EVENT_PLACE,
        "219_PRSN_1" as PRSN_1,
        "220_PRSN_CHOISE_1" as PRSN_CHOISE_1,
        "221_PRSN_SE_1" as PRSN_SE_1,
        "222_PRSN_LC_1" as PRSN_LC_1,
        "223_PRSN_2" as PRSN_2,
        "224_PRSN_CHOISE_2" as PRSN_CHOISE_2,
        "225_PRSN_SE_2" as PRSN_SE_2,
        "226_PRSN_LC_2" as PRSN_LC_2,
        "227_EHISTRY_THEMA_CL" as EHISTRY_THEMA_CL,
        "229_PHOTO_CL" as PHOTO_CL,
        "234_RTAT_SE" as RTAT_SE,
        "235_KOGL_TY" as KOGL_TY        
    FROM 
    MIGRATION_EHISTORY_AUDIO ');

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
    $bs_content_id = 515;
    $category_id= 2010;
    $ud_content_id= 4;
    $status = 2;
    $user_id = 'ehistory';
    //등록 채널
    $channel = 'regist_ehistory';

    //제작단계구분
    $prod_step_se = 'M';
    $rdcst_stle_se = 'P';
    //영상유형구분
    $vido_ty_se = 'B';


    foreach ($lists as $list) {
        if ($isDebug) {
            dump($list);
        }
        $dtaDetailId = $list['dta_detail_id'];       
        
        $isExist = $migrationService->isExistehistoryId($dtaDetailId);

        unset($list['mdb2rn']);
        if( $isExist ){
            $contentId      =  $isExist ;
        }else{
            if($isDebug){
                $contentId = 1111;
            }else{
                $contentId      = $migrationService->getContentId();
            }
        }
        $dto            = new ContentDto(['content_id' => $contentId]);
        $statusDto      = new ContentStatusDto(['content_id' => $contentId]);
        $sysMetaDto     = new ContentSysMetaDto(['sys_content_id' => $contentId]);
        $usrMetaDto     = new ContentUsrMetaDto(['usr_content_id' => $contentId]);

       
        $dto = $migrationService->dtoMapper($dto , $list, $metaKeyMap);
        $statusDto = $migrationService->dtoMapper($statusDto , $list);
        $sysMetaDto = $migrationService->dtoMapper($sysMetaDto , $list);
        $usrMetaDto = $migrationService->dtoMapper($usrMetaDto , $list);
        
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

        
        //원본 및 저해상도
        $originalPathInfo = $migrationService->getPath( $list['ori_file_path'], $list['ori_file_nm'] , '/e_movie/StreamRoot/' );
      

        if( !is_null($list['clip_end_time']) ){
            $clipTime =  (int)(( $list['clip_end_time'] -  $list['clip_begin_time']) / 1000 );
            
            $sysMetaDto->sys_clip_begin_time = $list['clip_begin_time'];
            $sysMetaDto->sys_clip_end_time = $list['clip_end_time'];
            $sysMetaDto->sys_video_rt = timecode::getConvTimecode( $clipTime ).';00';

            $sysMetaDto->sys_filename =  $originalPathInfo['filename'];
            $sysMetaDto->sys_ori_filename =  $originalPathInfo['filename'];
        }


        $createTime = strtotime($dto->created_date);
        $createTimeYmd =  date('Ymd', $createTime);
        $createTimeYmdhis = date('YmdHis', $createTime);

        if (!$isExist) {
            //미디어ID 발급
            $usrMetaDto->media_id = \ProximaCustom\services\ContentService::getMediaId($bs_content_id, $category_id, $createTimeYmd);
        }


        if (!$isDebug && $isExist) {
            dump('exist '.$contentId);
            //콘텐츠 생성
            $dto = $migrationService->updateNestDto($dto);
            $statusDto = $migrationService->updateNestDto($statusDto);
            $sysMetaDto = $migrationService->updateNestDto($sysMetaDto);
            $usrMetaDto = $migrationService->updateNestDto($usrMetaDto);
          
            $contentService->update($contentId, $dto, $statusDto, $sysMetaDto, $usrMetaDto, $user);
            continue;
        }else{
            //콘텐츠 생성
            if (!$isDebug) {
                $contentService->create($dto, $statusDto, $sysMetaDto, $usrMetaDto, $user);
            }else{
                dump($dto);
                dump($statusDto);
                dump($sysMetaDto);
                dump($usrMetaDto);
            }
        }
  
        //미디어 생성

        $filesize = 10;
       
        $mediaType = 'original';
        $storageId = $migrationService->getStorageId($bs_content_id, $ud_content_id, $category_id, $mediaType );
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
    }else{
        dump($mediaDto);
    }

    //     //저해상도 미디어 생성 

       $mediaType = 'proxy';
       $storageId = $migrationService->getStorageId($bs_content_id, $ud_content_id, $category_id, $mediaType );
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
        }else{
            dump($mediaDto);
        }
        
        // //입수 마이그레이션 워크플로우 수행
        // $task = new TaskManager($db);
        // $task_id = $task->insert_task_query_outside_data($contentId, $reg_type, 1, $user_id, $srcPath );
       // exit;
       dump($contentId);
    }
}catch(Exception $e){
    echo $e->getMessage();
}
?>