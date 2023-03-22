<?php
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
    // $contents = $service->getContentList($param);
    // $contents = $service->getContentByContentId(31);
    // echo print_r($contents , true);
    $migDB = new CommonDatabase('oracle','bis', 'bis', '10.10.50.89'.':'.'1521'.'/'.'orcl' );
    $migDB->setLimit(1000, 0 );
    $lists = $migDB->queryAll("select * from MIGRATION_CM_CONTENT_MEDIA WHERE REGIST_DT between TO_DATE('20150101000000','YYYYMMDDHH24MISS') AND TO_DATE('20151201000000','YYYYMMDDHH24MISS') order by HMPG_CONTENT_ID asc");
    $db = new CommonDatabase(DB_TYPE,DB_USER, DB_USER_PW, DB_HOST.':'.DB_PORT.'/'.DB_SID );
    $GLOBALS['db'] = &$db;
    //echo print_r($lists , true);
    // VIDEO_DURATION
    // BRDCST_TIME_HM
    
    $metaMap = array(
        'tb' => null,
        'delete_at' => null,// 'is_deleted', 반대
        'video_duration_min'=> null,
        'video_duration_sec'=> null,
        'brdcst_time_hm_hh'  => null,
        'brdcst_time_hm_mm' => null,
       
       // 'brdcst_de',   
        'regist_dt' => 'created_date',
        'regist_user_id'=> 'reg_user_id',
        'updt_dt' => 'updated_at',
        'updt_user_id' => 'updated_user_id',
        'hmpg_content_id' => 'hmpg_cntnts_id'
        //'org_ids',
        //'topic_ids'
    );

    $metaMap = array(
        'created_date' => 'regist_dt',
        'reg_user_id' =>     'regist_user_id',
        'updated_at' =>   'updt_dt' ,
        'last_modified_date' =>   'updt_dt' ,
        'updated_user_id' =>    'updt_user_id' ,
        'hmpg_cntnts_id'  => 'hmpg_content_id',
        'brdcst_time_hm' => 'brdcst_time_hm_hh',
        'sys_video_rt' => 'video_duration_min',
        'instt' => 'org_ids'        
    );

    foreach($lists as $list){
        unset($list['mdb2rn']);
        $contentId =getSequence('SEQ_CONTENT_ID');
         $dto = new ContentDto(['content_id' => $contentId]);
         foreach ($dto as $key => $val) {
            $newKey = $key;
            if( !is_null($metaMap[$key]) ){
                
                $newKey = $metaMap[$key];
            }
         
             if (isset($list[$newKey])) {               
                $dto->$key = renderVal($newKey, $list[$newKey]);
             }
         }

        $bs_content_id= 506;
        //홈페이지 고정
        $category_id = 2009;


        $dto->category_id= $category_id;
        $dto->category_full_path= '/0/100/2009';
        $dto->bs_content_id = $bs_content_id;
        $dto->ud_content_id = '3';
        $dto->is_deleted = 'N';
        $dto->is_hidden = '0';
        $dto->status = '2';
        $dto->expired_date = '99991231';

        $statusDto = new ContentStatusDto(['content_id' => $contentId]);
        foreach($statusDto as $key => $val){
            $newKey = $key;
            if( !is_null($metaMap[$key]) ){
                $newKey = $metaMap[$key];
            }            
             if (isset($list[$newKey])) {
                $statusDto->$key = renderVal($newKey,$list[$newKey]);
             }  
         }

        $sysMetaDto = new ContentSysMetaDto(['sys_content_id' => $contentId]);
        foreach($sysMetaDto as $key => $val){
            $newKey = $key;
            if( !is_null($metaMap[$key]) ){
                $newKey = $metaMap[$key];
            }            
             if (isset($list[$newKey])) {
                $sysMetaDto->$key = renderVal($newKey,$list[$newKey]);
                if($newKey == 'video_duration_min' ){
                    $sysMetaDto->$key = '00:'.$list[$newKey].':'.$list['video_duration_sec'].';00';
                 }
             }  
         }

        $usrMetaDto = new ContentUsrMetaDto(['usr_content_id' => $contentId]);
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

         $makeYmd =  date('Ymd', strtotime($dto->created_date) );
         //$makeYmd = date('Ymd', strtotime($list['regist_dt']) );
         $usrMetaDto->media_id = \ProximaCustom\services\ContentService::getMediaId($bs_content_id,  $category_id , $makeYmd  );

         $usrMetaDto->prod_step_se = 'M';
         $usrMetaDto->brdcst_stle_se = 'P';
         $usrMetaDto->vido_ty_se = 'B';


        // echo print_r($usrMetaDto, true);
        //$user = auth()->user();
        $user_id = 'homepage';
        $user = new User();
        $user->user_id = $user_id;

        $service->create( $dto , $statusDto ,  $sysMetaDto ,  $usrMetaDto , $user);

       $reg_type = 'regist_homepage';
       ///data/HDVOD/wenmedia/Repository/OUTPUT/7df25851-29bb-48ce-b3ed-12d715903294/
        $expired_date = '99991231000000';
        $created_date = date('YmdHis', strtotime($dto->created_date) );

       
        $media_type = 'original';
        $storage_id = 104;
        $path = $list['ori_file_path'] .  $list['ori_file_nm'];
        $src_path = str_replace('/data/HDVOD/wenmedia/Repository/OUTPUT/7df25851-29bb-48ce-b3ed-12d715903294/','', $path);
    //  dd($src_path);
        $src_path_array = explode( '.', $src_path);
        $src_ext = strtolower( array_pop($src_path_array) );
        $mdi_path = \ProximaCustom\services\ContentService::getFolderPath($category_id);
        $media_path = $mdi_path.'/'. date('Y/m/d',strtotime( $makeYmd) ).'/'.$contentId.'/'.$usrMetaDto->media_id.'_'.$contentId.'.'.$src_ext;
        $mediaData = [
            'content_id' => $contentId,
            'storage_id' => $storage_id,
            'media_type' => $media_type,
            'path' => $media_path,
            'filesize' => 10,
        //    'created_date' => $created_date,
            'reg_type' => $reg_type,
            'status' => 0,
            'expired_date' => $expired_date
       ];
       $mediaDto = new MediaDto($mediaData);
       $mediaDto->created_date = $created_date;
       $mediaService = new \Api\Services\MediaService($app->getContainer());

    //   dd($mediaDto);
     //  dump($mediaDto);
       $mediaService->create($mediaDto , $user);
       $media_path = date('Y/m/d',strtotime( $makeYmd ) ).'/'.$contentId.'/'.'PROXY_'.$contentId.'.mp4' ;
       $media_type = 'proxy';
       $storage_id = 105;
       $mediaData = [
            'content_id' => $contentId,
            'storage_id' => $storage_id,
            'media_type' => $media_type,
            'path' => $media_path,
            'filesize' => 10,
         //   'created_date' => $created_date,
            'reg_type' => $reg_type,
            'status' => 0,
            'expired_date' => $expired_date
        ];
        $mediaDto = new MediaDto($mediaData);
        $mediaDto->created_date = $created_date;
        $mediaService->create($mediaDto , $user);
      //  dd($mediaDto);
        // regist_homepage
        // $task = new TaskManager($db);
        // $task_id = $task->insert_task_query_outside_data($contentId, $reg_type, 1, $user_id, $src_path );
       // exit;
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

?>