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

    $service = new \Api\Services\ContentService($app->getContainer());
    
    $migrationService = new \ProximaCustom\services\MigrationService();
        
    $contentService = new \Api\Services\ContentService($app->getContainer());
    $mediaService = new \Api\Services\MediaService($app->getContainer());
    // $contents = $service->getContentList($param);
    // $contents = $service->getContentByContentId(31);
    // echo print_r($contents , true);
    $migDB = new CommonDatabase('oracle','bis', 'bis', '10.10.50.135'.':'.'1521'.'/'.'orcl' );
    $migDB->setLimit(1000, 0 );
    $lists = $migDB->queryAll("select 
    to_char(BRDCST_DE, 'YYYYMMDDHH24MISS') AS BRDCST_DE,
    to_char(REGIST_DT, 'YYYYMMDDHH24MISS') AS REGIST_DT,
    to_char(UPDT_DT, 'YYYYMMDDHH24MISS') AS UPDT_DT,
    TB,
SCENARIO,
DELETE_AT,
VIDEO_DURATION_MIN,
VIDEO_DURATION_SEC,
BRDCST_TIME_HM_HH,
BRDCST_TIME_HM_MM,
HMPG_OTHBC_AT,
MEMO,
TITLE,
ALL_VIDO_AT,
REGIST_USER_ID,
UPDT_USER_ID,
PROGRM_CODE,
TME_NO,
HMPG_CONTENT_ID,
PROGRAM_NAME,
ORI_FILE_PATH,
ORI_FILE_NM,
THUMB_FILE_PATH,
THUMB_FILE_NM,
MOBILE_MEDIA_FILE
 from MIGRATION_CM_CONTENT_audio order by HMPG_CONTENT_ID asc");

    $metaMap = array(
        'created_date' => 'regist_dt',
        'reg_user_id' =>     'regist_user_id',
        'updated_at' =>   'updt_dt' ,
        'last_modified_date' =>   'updt_dt' ,
        'updated_user_id' =>    'updt_user_id' ,
        'hmpg_cntnts_id'  => 'hmpg_content_id',
        'brdcst_time_hm' => 'brdcst_time_hm_hh',
        'sys_video_rt' => 'video_duration_min',
        'instt' => 'org_ids',
        'progrm_nm' => 'program_name'
    );

    foreach($lists as $list){
        //dd($list);
        $homepageId = $list['hmpg_content_id'];

        $isExist = isExistHomepageId( $homepageId );
        if($isExist){
            echo 'exist'.'<br />';
            continue;
        }

        unset($list['mdb2rn']);
        $contentId      = getSequence('SEQ_CONTENT_ID');
        $dto            = new ContentDto(['content_id' => $contentId]);
        $statusDto      = new ContentStatusDto(['content_id' => $contentId]);
        $sysMetaDto     = new ContentSysMetaDto(['sys_content_id' => $contentId]);
        $usrMetaDto     = new ContentUsrMetaDto(['usr_content_id' => $contentId]);

        foreach ($dto as $key => $val) {
            $newKey = $key;
            if( !is_null($metaMap[$key]) ){
                
                $newKey = $metaMap[$key];
            }
        
            if (isset($list[$newKey])) {  
            
                $dto->$key = renderVal($newKey, $list[$newKey]);
            }
        }

        //오디오
        $bs_content_id= 515;
        $category_id= 2009;
        
        //고정
        $dto->category_id= $category_id;
        $dto->category_full_path= '/0/100/2009';

        $dto->bs_content_id = $bs_content_id;
        //마스터본
        $dto->ud_content_id = '4';
        //승인
        $dto->status = '2';

        $user_id = 'homepage';
        $user = new User();
        $user->user_id = $user_id;

        //제작단계구분
        $usrMetaDto->prod_step_se = 'M';
        $usrMetaDto->brdcst_stle_se = 'P';
        //영상유형구분
        $usrMetaDto->vido_ty_se = 'B';

        //등록 채널        
       $reg_type = 'regist_homepage';

       //마이그레이션 경로 정보 고정값
       $prefixMigPath = '/data/HDVOD/wenmedia/Repository/OUTPUT/7df25851-29bb-48ce-b3ed-12d715903294/';

        $createTime = strtotime($dto->created_date);
        $createTimeYmd =  date('Ymd',$createTime );        
        $createTimeYmdhis = date('YmdHis', $createTime );


        foreach($statusDto as $key => $val){
            $newKey = $key;
            if( !is_null($metaMap[$key]) ){
                $newKey = $metaMap[$key];
            }            
            if (isset($list[$newKey])) {
            $statusDto->$key = renderVal($newKey,$list[$newKey]);
            }  
        }

        foreach($sysMetaDto as $key => $val){
            $newKey = $key;
            if( !is_null($metaMap[$key]) ){
                $newKey = $metaMap[$key];
            }            
            if (isset($list[$newKey])) {
            $sysMetaDto->$key = renderVal($newKey,$list[$newKey]);
            if($newKey == 'video_duration_min' ){
                $minNum =  (int)$list[$newKey];     
                if( $minNum >= 60 ){
                    $hour = (int)( $minNum / 60 );
                    $hour = str_pad($hour,  2, "0", STR_PAD_LEFT);
                }else{
                    $hour = '00';
                }
                $sysMetaDto->$key =  $hour.':'.str_pad($list[$newKey],  2, "0", STR_PAD_LEFT).':'.str_pad($list['video_duration_sec'],  2, "0", STR_PAD_LEFT).';00';
                }
            }  
        }

       // dd($usrMetaDto);
        foreach($usrMetaDto->toArray() as $key => $val){
            $newKey = $key;
            if( !is_null($metaMap[$key]) ){
                $newKey = $metaMap[$key];
            }    
            if (isset($list[$newKey])) { 
        //      dump($newKey);  
            $usrMetaDto->$key = renderVal($newKey,$list[$newKey]);
            if($newKey == 'brdcst_time_hm_hh' ){
                if( !empty($list[$newKey]) && !empty($list['brdcst_time_hm_mm'])){
                $usrMetaDto->$key = $list[$newKey].$list['brdcst_time_hm_mm'];
                }                   
                }
            }
        }

         //미디어ID 발급
         $usrMetaDto->media_id = \ProximaCustom\services\ContentService::getMediaId($bs_content_id,  $category_id , $createTimeYmd  );


        //콘텐츠 생성
        $service->create( $dto , $statusDto ,  $sysMetaDto ,  $usrMetaDto , $user);


        //미디어 생성
       
        $mediaType = 'original';
        $storageId = 120;
        $path = $list['ori_file_path'] .  $list['ori_file_nm'];
        $srcPath = str_replace($prefixMigPath,'', $path);
        $mediaPath = trim($srcPath ,'/');
        $filesize = 10;
    //  dd($src_path);
        $srcPathArray = explode( '.', $srcPath);
        $srcExt = strtolower( array_pop($srcPathArray) );
        //$midPath = \ProximaCustom\services\ContentService::getFolderPath($category_id);
        //$mediaPath = $midPath.'/'. date('Y/m/d', $createTime ).'/'.$contentId.'/'.$usrMetaDto->media_id.'_'.$contentId.'.'.$srcExt;
        $mediaData = [
                'content_id' => $contentId,
                'storage_id' => $storageId,
                'media_type' => $mediaType,
                'path' => $mediaPath,
                'reg_type' => $reg_type,
                'filesize' => $filesize,
                'created_date' => $createTimeYmdhis
        ];
       $mediaDto = new MediaDto($mediaData);
       //dump($mediaDto);
       $mediaService->create($mediaDto , $user);



       //$mediaPath = date('Y/m/d',strtotime($createTimeYmd ) ).'/'.$contentId.'/'.'PROXY_'.$contentId.'.mp4' ;
       $mediaType = 'proxy';
       $storageId = 120;
        $filesize = 10;

       $mediaData = [
            'content_id' => $contentId,
            'storage_id' => $storageId,
            'media_type' => $mediaType,
            'path' => $mediaPath,
            'reg_type' => $reg_type,
            'filesize' => $filesize,
            'created_date' => $createTimeYmdhis
        ];
        $mediaDto = new MediaDto($mediaData);
        $mediaService->create($mediaDto , $user);
        //dump($mediaDto);

        
        $mediaType = 'thumb';
        $storageId = 120;
        $path = $list['thumb_file_path'] .  $list['thumb_file_nm'];
        $srcPath = str_replace('','', $path);
        $mediaPath = trim($srcPath ,'/');
        $mediaData = [
            'content_id' => $contentId,
            'storage_id' => $storageId,
            'media_type' => $mediaType,
            'path' => $mediaPath,
            'reg_type' => $reg_type,
            'filesize' => $filesize,
            'created_date' => $createTimeYmdhis
        ];
        $mediaDto = new MediaDto($mediaData);
       
        $mediaService->create($mediaDto, $user);
        //dump($mediaDto);

        
        //입수 마이그레이션 워크플로우 수행
        // $task = new TaskManager($db);
        // $task_id = $task->insert_task_query_outside_data($contentId, $reg_type, 1, $user_id, $srcPath );
       // exit;
       echo $contentId.'<br />';
    }
}catch(Exception $e){
    echo $e->getMessage();
}

function renderVal($key , $val){

    //$dates = ['created_date','last_modified_date','updated_at'];
    $dates = ['regist_dt','updt_dt'];
    $dates8 = ['brdcst_de'];
    if( in_array( $key, $dates ) ){
        $carbon = new \Carbon\Carbon($val);
        $val = $carbon->format('YmdHis');
     }else if( in_array( $key, $dates8 )  ){
        $carbon = new \Carbon\Carbon($val);
        $val = $carbon->format('Ymd');
     }

     return  $val;
}

function isExistHomepageId( $homepageId ){
    global $db;

    $row = $db->queryRow("SELECT  * FROM BC_USRMETA_CONTENT U join BC_CONTENT C ON (U.USR_CONTENT_ID=C.CONTENT_ID)  WHERE C.IS_DELETED='N' AND U.hmpg_cntnts_id = '$homepageId'");
    if( !empty($row) ){
        return true;
    }else{
        return false;
    }
}

?>