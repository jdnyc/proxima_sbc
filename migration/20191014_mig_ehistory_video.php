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

try {

    dump(date("Y-m-d H:i:s"));
    
    //디버그 모드
    $isDebug = false;

    $migDB = new CommonDatabase('oracle', 'bis', 'bis', '10.10.50.135'.':'.'1521'.'/'.'orcl');
    
    $limit = 2000;

    $query = 'SELECT            
    "176_DTA_OCCRRNC_SE" AS DTA_OCCRRNC_SE,
    "179_EHISTRY_ID" AS EHISTRY_ID,
    "178_DTA_SE" AS DTA_SE,
    "177_PHOTO_OCCRRNC_SE" AS PHOTO_OCCRRNC_SE,
    "179_PHOTO_SE" AS PHOTO_SE,
    "VIDEO_SUBJECT" AS EHISTRY_ORIGIN,
    "183_SUMRY" AS SUMRY,
    "184_RECRD_DE" AS RECRD_DE,
    CASE "186_CLOR" when \'M\' then \'monochrome\' WHEN \'C\' THEN \'color\' ELSE \'\' end AS CLOR,
    "188_LANG_SE" AS LANG_SE,
    "195_CN_PLACE" AS CN_PLACE,
    "196_CN_INCDNT" AS CN_INCDNT,
    "197_INCDNT_INDICT_AT" AS INCDNT_INDICT_AT,
    "201_VOICE_ENNC" AS VOICE_ENNC,
    "198_USE_AT" AS USE_AT,
    "VIDEO_NUMBER" AS EHISTRY_VIDEO_NO,
    "232_DTA_DETAIL_ID" AS DTA_DETAIL_ID,
    "CLIP_SPLITNO" AS CLIP_ORDR,
    "206_PHOTO_DETAIL_SE" AS PHOTO_DETAIL_SE,
    "180_PHOTO_ID" AS PHOTO_ID,
    "181_PHOTO_DETAIL_ID" AS PHOTO_DETAIL_ID,
    "182_TITLE" AS TITLE,
    "185_SHOOTING_DE" AS SHOOTING_DE,
    "189_CREAT_INSTT" AS CREAT_INSTT,
    "190_CPYRHT" AS CPYRHT,
    "193_SHOOTING_DIRCTR" AS SHOOTING_DIRCTR,
    "204_CLIP_BEGIN_TIME" AS CLIP_BEGIN_TIME,
    "205_CLIP_END_TIME" AS CLIP_END_TIME,
    to_char("230_REGIST_DT",\'YYYYMMDDHH24MISS\') AS REGIST_DT,
    to_char("231_UPDT_DT",\'YYYYMMDDHH24MISS\') AS UPDT_DT,
    "202_CN" AS CN,
    "233_KWRD" AS KWRD,
    "THUMB_FILE_PATH" AS THUMB_FILE_PATH,
    "THUMB_FILE_NM" AS THUMB_FILE_NM,
    "213_CPYRHTOWN" AS CPYRHTOWN,
    "191_IMAGE_RSOLTN" AS IMAGE_RSOLTN,
    "192_IMAGE_RSOLTN" AS IMAGE_RSOLTN_2,
    "214_AUTHR" AS AUTHR,
    "215_PTOGRFER" AS PTOGRFER,
    "ORI_FILE_PATH" AS ORI_FILE_PATH,
    "ORI_FILE_NM" AS ORI_FILE_NM,
    "207_UCI" AS UCI,
    "208_PHOTO_SN" AS PHOTO_SN,
    "209_EVENT_NM" AS EVENT_NM,
    "210_EVENT_PURPS" AS EVENT_PURPS,
    "211_RELATE_ISSUE" AS RELATE_ISSUE,
    "212_ATDRN_NM" AS ATDRN_NM,
    "216_POTOGRF_NATION" AS POTOGRF_NATION,
    "217_POTOGRF_CTY" AS POTOGRF_CTY,
    "218_EVENT_PLACE" AS EVENT_PLACE,
    "219_PRSN_1" AS PRSN_1,
    "220_PRSN_CHOISE_1" AS PRSN_CHOISE_1,
    "221_PRSN_SE_1" AS PRSN_SE_1,
    "222_PRSN_LC_1" AS PRSN_LC_1,
    "223_PRSN_2" AS PRSN_2,
    "224_PRSN_CHOISE_2" AS PRSN_CHOISE_2,
    "225_PRSN_SE_2" AS PRSN_SE_2,
    "226_PRSN_LC_2" AS PRSN_LC_2,
    "227_EHISTRY_THEMA_CL" AS EHISTRY_THEMA_CL,
    "229_PHOTO_CL" AS PHOTO_CL,
    "234_RTAT_SE" AS RTAT_SE,
    "235_KOGL_TY" AS KOGL_TY
FROM 
MIGRATION_EHISTORY_video order by "232_DTA_DETAIL_ID"';
    $total = $migDB->queryOne("select count(*) from MIGRATION_EHISTORY_video ");
    dump(date("Y-m-d H:i:s").' - total : '.$total);
	for($start = 23500 ; $start < $total ; $start+=$limit)
	{            
        $migDB->setLimit($limit, $start);
        
        $lists = $migDB->queryAll($query);


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
        $bs_content_id= 506;
        $category_id= 2010;
        $ud_content_id= 3;
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
            $dtaDetailId = $list['dta_detail_id'];
            
            $isExist = $migrationService->isExistehistoryId($dtaDetailId);

            unset($list['mdb2rn']);
            if ($isExist) {
                $contentId      =  $isExist ;
            } else {
                if ($isDebug) {
                    $contentId = 1111;
                } else {
                    $contentId      = $migrationService->getContentId();
                }
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

            if( empty($dto->title) ){
                $dto->title = '-';
            }

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

            if (!is_null($list['clip_end_time'])) {
                $clipTime =  (int)(($list['clip_end_time'] -  $list['clip_begin_time']) / 1000);
                
                $sysMetaDto->sys_clip_begin_time = $list['clip_begin_time'];
                $sysMetaDto->sys_clip_end_time = $list['clip_end_time'];
                $sysMetaDto->sys_video_rt = timecode::getConvTimecode($clipTime).';00';
                
                $sysMetaDto->sys_filename =  $originalPathInfo['filename'];
                $sysMetaDto->sys_ori_filename =  $originalPathInfo['filename'];
            }


            if (!$isDebug && $isExist) {
                echo 'exist '.$contentId.'<br />';
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
                //dump($mediaDto);
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
             dump($contentId);

            //dd(11);
        }
    }
}catch(Exception $e){
    echo $e->getMessage();
}
?>