<?php
set_time_limit(3600);
use \Api\Models\User;
use \Api\Models\FolderMng;
use \Api\Models\ArchiveMedia;
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

    echo date("YmdHis").'<br/>';

    $metaMap = array(

    );

    
    $udContentIdMap = [
        'O' => 1,
        'F' => 7,
        'W' => 7,
        'C' => 2,
        'P' => 3,
        'M' => 3,
        'A'=> 3
    ];

    $contentService = new \Api\Services\ContentService($app->getContainer());
    $mediaService = new \Api\Services\MediaService($app->getContainer());
    $mediaSceneService = new \Api\Services\MediaSceneService($app->getContainer());

    // $contents = $service->getContentList($param);
    // $contents = $service->getContentByContentId(31);
    // echo print_r($contents , true);
    $migDB = new CommonDatabase('oracle','ktv', 'ktv', '10.10.50.127'.':'.'1521'.'/'.'orcl' );
    $migDB->setLimit(100, 0 );

    $lists = $migDB->queryAll("SELECT mediaid,
    count(videoid) cnt 
    FROM nds_videometa_tb m, NDS_VIDEO_TB v 
    WHERE v.VIDEOMETAID=m.VIDEOMETAID and m.makingstep=403726926360
    GROUP BY m.mediaid 
     ORDER BY mediaid asc");
    //dump($lists);

    foreach ($lists as $list) {
        $mediaId = $list['mediaid'];
        $contentMedias = $migDB->queryAll("SELECT            
            --status
            v.videoid as bfe_video_id,
            v.archiveexeuser as archv_exctn,
            v.archiverequser as archv_rqester,
            v.ingestuser as ingest_exctn,
            (SELECT USERNAME FROM KMS_CNF_USER_VIEW WHERE userid=v.softwareqcverifyuser) as qc_cnfrmr,
            v.transferrequser as trnsmis_rqester,
            FUNC_GETCODEVALUE(v.archivereqstatus) as archv_requst_at,
            v.archivestatus as archv_sttus,
            v.catalogingstatus as catlg_sttus,
            v.hr_videoencodingstatus as video_encd_sttus,
            v.maincontrol as mcr_trnsmis_sttus,
            v.restorestatus as restore_sttus,
            v.subcontrol as scr_trnsmis_sttus,
            v.transcodingstatus as transcd_sttus,
            v.transferstatus as trnsmis_sttus,
            FUNC_GETCODEVALUE(v.archiverequsertype) as archv_regist_requst_ty,
            v.archivestarttime as archv_begin_dt,
            v.archiveendtime as archv_end_dt,
            v.catalogingendtime as catlg_end_dt,
            v.catalogingstarttime as catlg_begin_dt,
            v.archiveendtime as archv_end_dt,
            v.ingestdatetime as ingest_dt,
            v.maincontrolendtime as mcr_trnsmis_end_dt,
            v.maincontrolstarttime as mcr_trnsmis_begin_dt,
            v.restoreendtime as restore_end_dt,
            v.restorestarttime as restore_begin_dt,
            v.subcontrolendtime as scr_trnsmis_end_dt,
            v.subcontrolstarttime as scr_trnsmis_begin_dt,
            v.transcodingendtime as transcd_end_dt,
            v.transcodingstarttime as transcd_begin_dt,
            v.transferreqdatetime as trnsmis_requst_dt,
            v.ingestplace as ingest_place,
            v.startframeindex as begin_frme_indx,
            v.isarchive as atmc_archv_execut_at,
            v.isarchivetarget as archv_trget_at,
            v.iscreatepreviewnote as archv_trget_at,
            v.isrestoredvideo as restore_at,
            v.softwareqcverification as qc_cnfirm_at,
            v.transmissionstatus as trnsmis_at ,
            m.nousereleasedatetime as use_prhibt_relis_dt,
            m.nousereleaser as use_prhibt_relis_user_id,
            m.nousereqdatetime as use_prhibt_set_dt,
            m.nousereqreason as use_prhibt_set_resn,
            m.nouserequester as use_prhibt_set_user_id,
            -- bc_content
            (SELECT USERNAME FROM KMS_CNF_USER_VIEW WHERE userid=m.modifyuser) as	updt_user_id,
            m.modifytime	as updated_at,
            m.modifytime	as last_modified_date,
            --FUNC_GETCODEVALUE(m.makingstep) as	cntnts_ty,
            (SELECT USERNAME FROM KMS_CNF_USER_VIEW WHERE userid=m.createuser)	as regist_user_id,
            m.createtime	as created_date,
            m.title	as title,
            FUNC_GET_TREECODEEID(m.physicaltree) as ctgry_id,
            ( SELECT nodename FROM KMS_CNF_TREENODE_TB where nodeid=FUNC_GET_TREECODEEID(m.physicaltree)) as ctgry_path,
            ( SELECT parent FROM KMS_CNF_TREENODE_TB where nodeid=FUNC_GET_TREECODEEID(m.physicaltree)) as ctgry_parent,
            m.keepexpiredate as	expired_date,
            --bc_usermeta_content
            FUNC_GETCODEVALUE( m.broadcasttype) as brdcst_stle_se,
            FUNC_GETCODEVALUE( m.videotypedivision) as vido_ty_se,
            FUNC_GETCODEVALUE( m.makingstep) as prod_step_se,
            FUNC_GETCODEVALUE( m.videosrcattdivision) as shooting_orginl_atrb,
            m.programcode as progrm_code,
            m.programname as progrm_nm,
            m.programseqnumber as tme_no,
            m.subtitle as subtl,
            FUNC_GETCODEVALUE( m.programonairdate ) as brdcst_de,
            m.contents as cn,
            m.keywords as kwrd,
            m.mediaid as media_id,
            FUNC_GETCODEVALUE( m.materialtype ) as matr_knd,
            m.embargobool as embg_at,
            m.embargoreason as embg_resn,
            m.embargoreleasedatetime as embg_relis_dt,
            m.nousebool as use_prhibt_at,
            m.keepextendreason as prsrv_pd_et_resn,
            m.productpd as prod_pd_nm,
            m.cast as cast,
            m.mediarepoter as shooting_dirctr,
            m.shootingdate as shooting_de,
            m.shootingplace as shooting_place,
            m.interviewrepoter as sbjslct_jrnlst,
            m.deskcp as desk_cp_nm,
            m.copyright as cpyrhtown,
            m.copyrightdescription as cpyrht_cn,
            FUNC_GETCODEVALUE( m.usegrade) as use_grad,
            FUNC_GETCODEVALUE(m.telecinetype) as telecine_ty_se,
            CASE m.iskeepingtelecinetape when 420906795556 THEN 'N' WHEN 420906795555 THEN 'Y' ELSE '' end as tape_hold_at,
            m.telecinebarcode as film_no,
            FUNC_GETCODEVALUE(m.telecinetapekind) as tape_knd,
            FUNC_GETCODEVALUE(m.telecinetapesize) as tape_mg,
            m.partnumber as hono,
            m.partnumber as manage_no,
            m.telecinedirector as dirctr,
            m.telecineproductdate as prod_de,
            FUNC_GETCODEVALUE(m.telecinestandard) as stndrd,
            FUNC_GETCODEVALUE(m.telecinecolor) as clor,
            FUNC_GETCODEVALUE(v.jurisdiction)	AS vido_psitn_relm,
            --bc_sysmeta_movie
            v.hr_video_cliplength as sys_video_frme,
            v.hdbool as sys_rsoltn_se,
            v.hr_videofps as sys_frame_rate,
            v.hr_videoresolution as sys_display_size,
            FUNC_GETCODECAPTION(v.hr_video_aspectratio) as sys_video_asperto,
            v.hr_video_bandwidth as sys_video_bitrate,
            FUNC_GETCODECAPTION(v.hr_video_wrappertype) as sys_video_wraper,
            FUNC_GETCODECAPTION(v.hr_video_compformat) as sys_video_codec,
            v.hr_video_prodlength as sys_video_rt,
            FUNC_GETCODECAPTION(v.hr_audio_sampling) as sys_audio_samplrate,
            v.hr_audio_bandwidth as sys_audio_bitrate,
            FUNC_GETCODEVALUE(v.hr_audio_channel) as sys_audio_channel,
            FUNC_GETCODECAPTION(v.hr_audio_compformat) as sys_audio_codec,

            --bc_media original
            v.hr_videolength as video_filesize,
            FUNC_GETSTORAGE(v.hr_storagepath) as strge_id,
            v.hr_filename as sys_filename,
            v.hr_filepath as sys_filepath
        FROM 
            nds_video_tb v,
            nds_videometa_tb m 
        WHERE
            v.VIDEOMETAID=m.VIDEOMETAID
        AND m.mediaid ='$mediaId'
        ORDER BY m.VIDEOMETAID asc");
        //dump($contentMedias);
        $contentInfo = [
            'meta' => null,
            'original' => null,
            'nearline' => null,
            'archive' => null
        ];
        foreach ($contentMedias as $key => $contentMedia) {

            $mediaLoc = $contentMedia['vido_psitn_relm'];

            //중앙이 있으면
            //original 정보는 중앙으로
            //없으면 original은 삭제상태
            //니어라인 이 있으면
            //니어라인 경로
            //없으면 니어라인 미디어 x
            //경로만 아카이브로 original 경로

            if ($mediaLoc == 'dtl') {
                //아카이브                
                if ($contentInfo['archive'] == null) {
                    $contentInfo['archive'] =  $contentMedia;

                }
            } elseif ($mediaLoc == 'archive') {
                //니어라인
                if ($contentInfo['nearline'] == null) {
                    $contentInfo['nearline'] =  $contentMedia;

                }
            } else {
                //중앙
                //리스토어 or 신규 구분?
                if ($contentInfo['original'] == null) {
                    $contentInfo['original'] =  $contentMedia;

                }
            }
        }


        if( $contentInfo['archive'] ){
            $contentInfo['meta'] = $contentInfo['archive'];
        }else if( $contentInfo['nearline'] ){
            $contentInfo['meta'] = $contentInfo['nearline'];
        }else if( $contentInfo['original'] ){
            $contentInfo['meta'] = $contentInfo['original'];
        }

        if( !$contentInfo['meta'] ){
            echo 'empty meta';
            continue;
        }
          
        $videoId = $list['bfe_video_id'];
        unset($list['mdb2rn']);
        $isExist = isExistMediaId($mediaId);
        //dump($contentInfo);


        $list = $contentInfo['meta'];

        $list['regist_user_id'] = empty($list['regist_user_id']) ? 'admin': $list['regist_user_id'];
        $list['updt_user_id'] = empty($list['updt_user_id']) ? 'admin': $list['updt_user_id'];

        if ($isExist) {
            //우선 제외
            continue;
            $contentId      =  $isExist ;
        } else {
            //$contentId      = 333;
            $contentId      = getSequence('SEQ_CONTENT_ID');
        }
        $dto            = new ContentDto(['content_id' => $contentId]);
        $statusDto      = new ContentStatusDto(['content_id' => $contentId]);
        $sysMetaDto     = new ContentSysMetaDto(['sys_content_id' => $contentId]);
        $usrMetaDto     = new ContentUsrMetaDto(['usr_content_id' => $contentId]);

        foreach ($dto as $key => $val) {
            $newKey = $key;
            if (!is_null($metaMap[$key])) {
                $newKey = $metaMap[$key];
            }
        
            if (isset($list[$newKey])) {
                $dto->$key = renderVal($newKey, $list[$newKey]);
            }
        }

        $bs_content_id= 506;

        $categoryInfo = \Api\Models\Category::where('extra_order', $list['ctgry_id'])->get()->first();
        if (!empty($categoryInfo)) {
            $category_id = $categoryInfo['category_id'];
            $dto->category_id = $category_id;
            $dto->category_full_path = '/0/100/'.$categoryInfo['parent_id'].'/'.$category_id;
        } else {
            echo 'empty categoryInfo';
            continue;
        }
 
        //고정
        $dto->bs_content_id = $bs_content_id;
        //마스터본
        $dto->ud_content_id = $udContentIdMap[ $list['prod_step_se'] ];
        //승인
        $dto->status = '2';
        $user_id = $list['regist_user_id'];
        $user = new User();
        $user->user_id = $user_id;
 
        if (!empty($contentInfo['original'])) {
            //오리지널 있는경우
            $originalStatus = 0;
            $oriMedia = $contentInfo['original'];
        } else {
            //없는 경우 삭제처리
            $oriMedia = $list;
            $originalStatus = 1;
        }

        //등록 채널
        $reg_type = 'regist_mig_cms';
        //마이그레이션 경로 정보 고정값
        $prefixMigPath = '';

        $createTime = strtotime($dto->created_date);
        $createTimeYmd =  date('Ymd', $createTime);
        $createTimeYmdhis = date('YmdHis', $createTime);

        foreach ($statusDto as $key => $val) {
            $newKey = $key;
            if (!is_null($metaMap[$key])) {
                $newKey = $metaMap[$key];
            }
            if (isset($list[$newKey])) {
                $statusDto->$key = renderVal($newKey, $list[$newKey]);
            }
        }

        foreach ($sysMetaDto as $key => $val) {
            $newKey = $key;
            if (!is_null($metaMap[$key])) {
                $newKey = $metaMap[$key];
            }
            if (isset($oriMedia[$newKey])) {
                $sysMetaDto->$key = renderVal($newKey, $oriMedia[$newKey]);
            }
        }

        // if (!$isExist) {
        //     //미디어ID 발급
        //     $usrMetaDto->media_id = \ProximaCustom\services\ContentService::getMediaId($bs_content_id, $category_id, $createTimeYmd);
        // }
        
        // dd($usrMetaDto);
        foreach ($usrMetaDto->toArray() as $key => $val) {
            $newKey = $key;
            if (!is_null($metaMap[$key])) {
                $newKey = $metaMap[$key];
            }
            if (isset($list[$newKey])) {
                //      dump($newKey);
                $usrMetaDto->$key = renderVal($newKey, $list[$newKey]);
            }
        }

        //dump($dto);
        //dump($statusDto);
        //dump($sysMetaDto);
        //dump($usrMetaDto);

        //dd('end');

        if ($isExist) {
            echo 'exist '.$contentId.'<br />';
            //콘텐츠 생성
            $keys = getNotNull($dto);
            $dto = $dto->only(...$keys);
            $keys = getNotNull($statusDto);
            $statusDto = $statusDto->only(...$keys);
            $keys = getNotNull($sysMetaDto);
            $sysMetaDto = $sysMetaDto->only(...$keys);
            $keys = getNotNull($usrMetaDto);
            $usrMetaDto = $usrMetaDto->only(...$keys);
          
            //$contentService->update($contentId, $dto, $statusDto, $sysMetaDto, $usrMetaDto, $user);
            continue;
        } else {
            //콘텐츠 생성
            $contentService->create($dto, $statusDto, $sysMetaDto, $usrMetaDto, $user);
        }

        //미디어 생성
       
        $media_type = 'original';
        $oriStorageId = 104;
        $originalPath = str_replace('\\', '/', $oriMedia['sys_filepath']);
        $originalPath = str_replace('/CMS/Video/', '', $originalPath);
        $originalPath = trim($originalPath, '/').'/' .$oriMedia['sys_filename'];
        $originalFilesize = $oriMedia['video_filesize'];
        $mediaData = [
            'content_id' => $contentId,
            'storage_id' => $oriStorageId,
            'media_type' => $media_type,
            'path' => $originalPath,
            'filesize' => $originalFilesize,
            'status' => $originalStatus,
            'reg_type' => $reg_type
       ];
        $mediaDto = new MediaDto($mediaData);
        $mediaDto->created_date = $createTimeYmdhis;
        $oriMedia = $mediaService->create($mediaDto , $user);
        //dump($oriMedia);

        if (!empty($contentInfo['archive'])) {
            $proxyVideoId = $contentInfo['archive']['bfe_video_id'];
        } elseif (!empty($contentInfo['nearline'])) {
            $proxyVideoId = $contentInfo['nearline']['bfe_video_id'];
        } else {
            $proxyVideoId = $contentInfo['original']['bfe_video_id'];
        }

       // dd($proxyVideoId);

        //저해상도는 DTL에 있는거
       
        $proxyInfo = $migDB->queryRow("SELECT
       VIDEOID,
       PROXYVIDEOID,
       CREATETIME,
       video_filesize as video_filesize,
       FUNC_GETSTORAGE(storagepath) as strge_id,
       storagepath,
       video_filename as filename,
       video_filepath as file_path 
       FROM NDS_PROXYVIDEO_TB where videoid='$proxyVideoId'");

        //저해상도 미디어 생성

        //$mediaPath = date('Y/m/d',strtotime($createTimeYmd ) ).'/'.$contentId.'/'.'PROXY_'.$contentId.'.mp4' ;
  
        if (!empty($proxyInfo)) {
            $mediaPath = str_replace('\\', '/', $proxyInfo['file_path']);
            $mediaPath = str_replace('CMS/ProxyVideo/', '', $mediaPath);
            $mediaPath = trim($mediaPath, '/').'/'.$proxyInfo['filename'];
            $media_type = 'proxy';
            $proxyStorageId = 105;
            $mediaData = [
                'content_id' => $contentId,
                'storage_id' => $proxyStorageId,
                'media_type' => $media_type,
                'path' => $mediaPath,
                'filesize' =>  $proxyInfo['video_filesize'],
                'reg_type' => $reg_type
            ];
            $proxyMediaDto = new MediaDto($mediaData);
            $proxyMediaDto->created_date = $proxyInfo['createtime'];
            $proxyMedia = $mediaService->create($proxyMediaDto , $user);
            //dump($proxyMedia);

            $proxyMediaId = $proxyMedia->media_id;

                
            //카달로그 이미지
            $catalogInfo = $migDB->queryAll("SELECT 
            (SELECT FILESIZE FROM KMS_DAT_FILE_TB WHERE FILEID=s.TITLEIMAGEFILE ) AS filesize,
            SEGMENTID,
            VIDEOID,
            TITLEIMAGEFILE,
            STARTFRAMEINDEX,
            ENDFRAMEINDEX,
            STARTTIMECODE,
            ENDTIMECODE 
            FROM nds_shot_tb s where videoid='$proxyVideoId' order by STARTFRAMEINDEX");
            if( !empty($catalogInfo) ){
                $catalogDatas = [];
                foreach($catalogInfo as $ckey=> $catalog){
                    $pathCode = $catalog['titleimagefile'];
                    $mediaPath = getImagePath($pathCode);
                    $catalogDatas  [] = [
                        'media_id' => $proxyMediaId,
                        'show_order' => $ckey,
                        'path' => $mediaPath,
                        'start_frame' =>  $catalog['startframeindex'],
                        'filesize' =>  $catalog['filesize'],                         
                        'scene_type' =>'S',
                        'title' => 'title '.$ckey
                    ];
                }
                //dump($catalogDatas);
                $sceneMedias = $mediaSceneService->delAndCreate($catalogDatas, $proxyMediaId);
                //dump($sceneMedias);
            }

            $qcInfo = $migDB->queryAll("SELECT
                videoid,
                starttimecode,
                endtimecode,
                startframeindex start_tc ,
                endframeindex end_tc,
                func_getcodevalue(qualitychecktype) quality_type,
                qualitycheckid show_order 
            FROM 
                nds_qualitycheck_tb
            WHERE videoid='$proxyVideoId'
            order by qualitycheckid"); 
            //dump($qcInfo);
            $qcDatas = [];
            foreach($qcInfo as $ckey=> $qc){
                   $qc_start = substr($qc['starttimecode'],0,2)*3600+substr($qc['starttimecode'],3,2)*60+substr($qc['starttimecode'],6,2);
                 $qc_end = substr($qc['endtimecode'],0,2)*3600+substr($qc['endtimecode'],3,2)*60+substr($qc['endtimecode'],6,2);
                $qcDatas  [] = [
                    'media_id' => $proxyMediaId,
                    'quality_type' => $qc['quality_type'],
                    'start_tc' => $qc_start,
                    'end_tc' =>   $qc_end,
                    'show_order' => $qc['show_order']
                ];
            }

            createQcInfo ($proxyMediaId, $qcDatas);
            //dump($qcDatas);
        }


        if ( !empty($contentInfo['nearline']) ) {
            //니어라인 미디어 생성

            $media_type = 'nearline';
            $nearStorageId = 112;
            $mediaPath = str_replace('\\','/', $contentInfo['nearline']['sys_filepath']);
            $mediaPath = str_replace('/CMS/Video/','', $mediaPath );
            $mediaPath = trim($mediaPath,'/').'/' .$contentInfo['nearline']['sys_filename'];    
            $originalFilesize = $contentInfo['nearline']['video_filesize'];
            $mediaData = [
                'content_id' => $contentId,
                'storage_id' => $nearStorageId,
                'media_type' => $media_type,
                'path' => $mediaPath,
                'filesize' => $originalFilesize,
                'status' => 0,
                'reg_type' => $reg_type
           ];
           $mediaDto = new MediaDto($mediaData);           
           $mediaDto->created_date = $contentInfo['nearline']['created_date'];         

           $nearMedia = $mediaService->create($mediaDto , $user);
           // dump($nearMedia);
        }

        if( !empty($contentInfo['archive']) ){


            $u_query = "INSERT INTO ARCHIVE_MEDIAS (CONTENT_ID, MEDIA_ID, OBJECT_NAME, ARCHIVE_CATEGORY, ARCHIVE_GROUP, QOS, DESTINATIONS, USER_ID, CREATED_AT)VALUES($contentId, 0, '{$contentInfo['archive']['media_id']}', 'cms', 'cms', '1', 'san', 'admin', '{$contentInfo['archive']['created_date']}')";

            $r = $db->exec($u_query);
        }

        //대표 이미지
        $thumbInfo = $migDB->queryRow("SELECT 
               (SELECT FILESIZE FROM KMS_DAT_FILE_TB WHERE FILEID=s.TITLEIMAGEFILE ) AS filesize,
               CREATETIME,
               SEGMENTID,
               VIDEOID,
               TITLEIMAGEFILE,
               STARTFRAMEINDEX,
               ENDFRAMEINDEX,
               STARTTIMECODE,
               ENDTIMECODE 
                FROM nds_scene_tb s  where videoid='$proxyVideoId'");

        if( !empty($thumbInfo) ){
            //dump($thumbInfo);
            $pathCode = $thumbInfo['titleimagefile'];
            $mediaPath = getImagePath($pathCode);
            $media_type = 'thumb';

            $mediaData = [
                'content_id' => $contentId,
                'storage_id' => $proxyStorageId,
                'media_type' => $media_type,
                'path' => $mediaPath,
                'filesize' => $thumbInfo['filesize'],
                'status' => 0,
                'reg_type' => $reg_type
           ];
           $mediaDto = new MediaDto($mediaData);
           $mediaDto->created_date = $thumbInfo['createtime'];
           $thumbMedia = $mediaService->create($mediaDto , $user);
           //dump($thumbMedia);
        }



    //     $mediaService->create($mediaDto , $user);

    //         $srcPath = $originalPath;
    // //  dd($src_path);
    //     $srcPathArray = explode( '.', $srcPath);
    //     $srcExt = strtolower( array_pop($srcPathArray) );
    //     $midPath = \ProximaCustom\services\ContentService::getFolderPath($category_id);
    //     $mediaPath = $midPath.'/'. date('Y/m/d', $createTime ).'/'.$contentId.'/'.$usrMetaDto->media_id.'_'.$contentId.'.'.$srcExt;

        //입수 마이그레이션 워크플로우 수행
        // $task = new TaskManager($db);
        // $task_id = $task->insert_task_query_outside_data($contentId, $reg_type, 1, $user_id, $srcPath );
       // exit;
       echo $contentId.'<br />';
      
    }
}catch(Exception $e){
    echo $e->getMessage();
}

function createQcInfo ($mediaId, $qcInfos){
    global $db;

    //QC정보 입력받기전에 전체 지움.
    $db->exec("
        DELETE	FROM BC_MEDIA_QUALITY
        WHERE	MEDIA_ID = '$mediaId'"
    );

    $i = 1; 
    foreach($qcInfos as $qc) {	
        // //이상봉실장님 버전 QC type code
        // $qc_type = array(
        //         0 => 'Black',
        //         1 => 'Single color',
        //         2 => 'Still',
        //         3 => 'Color bar',
        //         4 => 'Similar image',
        //         5 => 'No audio samples',
        //         6 => 'Mute',
        //         7 => 'Loudness'
        // );
            
        // $qc_type_str = $qc_type[(string)$qc['type']];
        // if(empty($qc_type_str)) {
        //     $qc_type_str = 'Etc';
        // }

        // $qc_start = substr($qc['start'],0,2)*3600+substr($qc['start'],3,2)*60+substr($qc['start'],6,2);
        // $qc_end = substr($qc['end'],0,2)*3600+substr($qc['end'],3,2)*60+substr($qc['end'],6,2);

        $new_qc_seq = getSequence('SEQ_BC_MEDIA_QUALITY_ID');

        $r = $db->exec("
                INSERT INTO BC_MEDIA_QUALITY
                    (QUALITY_ID, MEDIA_ID, QUALITY_TYPE, START_TC, END_TC, SHOW_ORDER, SOUND_CHANNEL, no_error)
                VALUES
                    ($new_qc_seq, '$mediaId', '".$qc['quality_type']."', '".$qc['start_tc']."', '".$qc['end_tc']."', $i, '','1')
            ");
        $i++;
    }

    // //QC 전체에 대한 정보 넣어주는 테이블
    // $idx = $i-1;
    // $hasQC = $db->queryOne("select count(content_id) from bc_media_quality_info where content_id = $content_id");

    // if($idx > 0) {
    //     if($hasQC > 0) {
    //         $query = "update bc_media_quality_info set error_count = '$idx', last_modify_date = '$now' where content_id = '$content_id'";
    //         $db->exec($query);
    //     } else {
    //         $query = "insert into bc_media_quality_info (content_id, error_count, created_date) values ('$content_id','$idx', '$now')";
    //         $db->exec($query);
    //     }
    // } else {
    //     /*검출된 정보가 없을 경우에도 QC를 진행한 부분을 확인하기 위해서 값 추가 / 있으면 업데이트 없으면 인서트 - 2018.03.20 Alex */
    //     if($hasQC > 0) {
    //         $db->exec("
    //             UPDATE	BC_MEDIA_QUALITY_INFO
    //             SET		ERROR_COUNT = $idx,
    //                     LAST_MODIFY_DATE = '$now',
    //             WHERE	CONTENT_ID = $content_id
    //         ");
    //     } else {
    //         $db->exec("
    //             INSERT INTO BC_MEDIA_QUALITY_INFO
    //                 (CONTENT_ID, ERROR_COUNT, CREATED_DATE)
    //             VALUES
    //                 ($content_id, 0, '$now')
    //         ");
    //     }
    //     $pass_add_next_job = 'true';
    // }
    return true;
}

function getImagePath($path){
    if( strlen($path) <= 3   ){
        $path = (int)$path;
        $rtn = '0'.'/'.'0'.'/'.$path.'.kdf';
    }else if( strlen($path) == 4 || strlen($path) == 5 || strlen($path) == 6 ){       
        $secPos = strlen($path) - 3 ;
        $filename = (int) substr($path, -3 ) ;
        $path = (int) substr($path, -6 , $secPos ) ;
        $rtn = '0'.'/'.$path.'/'.$filename.'.kdf';
    }else if( strlen($path) == 7 || strlen($path) == 8 || strlen($path) == 9  ){
        $secPos = strlen($path) - 6 ;
        $filename = (int) substr($path, -3 ) ;
        $subPath = (int) substr($path, -6, 3 ) ;
        $path = (int) substr($path, 0 , $secPos ) ;
        $rtn = $path.'/'.$subPath.'/'.$filename.'.kdf';
    }else{
        $rtn ='unkown.kdf';
    }
    return $rtn;
}

function getNotNull($dto){
    $newData = [] ; 
    foreach($dto as $key => $val){      
        if( $val != null ){
            $newData [] = $key;
        }
    }
    return $newData;
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

function isExistMediaId( $mediaId ){
    global $db;

    $row = $db->queryRow("SELECT  * FROM BC_USRMETA_CONTENT U join BC_CONTENT C ON (U.USR_CONTENT_ID=C.CONTENT_ID)  WHERE C.IS_DELETED='N' AND U.media_id = '$mediaId'");
    if( !empty($row) ){
        return $row['content_id'];
    }else{
        return false;
    }
}

function isExistVideoId( $videoId ){
    global $db;

    $row = $db->queryRow("SELECT  * FROM BC_CONTENT_STATUS U join BC_CONTENT C ON (U.CONTENT_ID=C.CONTENT_ID)  WHERE C.IS_DELETED='N' AND U.bfe_video_id = '$videoId'");
    if( !empty($row) ){
        return $row['content_id'];
    }else{
        return false;
    }
}

function isExistHomepageId( $homepageId ){
    global $db;

    $row = $db->queryRow("SELECT  * FROM BC_USRMETA_CONTENT U join BC_CONTENT C ON (U.USR_CONTENT_ID=C.CONTENT_ID)  WHERE C.IS_DELETED='N' AND U.hmpg_cntnts_id = '$homepageId'");
    if( !empty($row) ){
        return $row['content_id'];
    }else{
        return false;
    }
}

function getFindHomepageParents($progrm_code, $tme_no){
    global $db;
    $row = $db->queryRow("SELECT  * FROM BC_USRMETA_CONTENT U join BC_CONTENT C ON (U.USR_CONTENT_ID=C.CONTENT_ID)  WHERE C.IS_DELETED='N' AND U.ALL_VIDO_AT='Y' AND U.progrm_code = '$progrm_code' and u.tme_no = '$tme_no'");
    if( !empty($row) ){
        return $row['content_id'];
    }else{
        return false;
    }
}

function getFindHomepageChildren($contentId , $progrm_code, $tme_no ){
    global $db;
    $lists = $db->queryAll("SELECT  * FROM BC_USRMETA_CONTENT U join BC_CONTENT C ON (U.USR_CONTENT_ID=C.CONTENT_ID)  WHERE C.IS_DELETED='N' AND U.ALL_VIDO_AT='N' AND U.progrm_code = '$progrm_code' and u.tme_no = '$tme_no'");
    if( !empty($lists) ){
        foreach($lists as $list){
            $r = $db->exec("update bc_content set PARENT_CONTENT_ID='$contentId' where content_id='{$list['content_id']}'");
        }
        return true;
    }else{
        return false;
    }
}
?>