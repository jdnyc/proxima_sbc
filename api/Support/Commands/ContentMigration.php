<?php
namespace Api\Support\Commands;

ini_set('memory_limit','512M');
use \Api\Models\User;
use Api\Types\CategoryType;
use Illuminate\Support\Str;
use Api\Models\ArchiveMedia;
use Api\Types\BsContentType;
use Api\Types\UdContentType;
use \Api\Services\DTOs\MediaDto;
use \Api\Services\DTOs\ContentDto;
use Api\Support\Helpers\MetadataMapper;
use \Api\Services\DTOs\ContentStatusDto;
use \Api\Services\DTOs\ContentSysMetaDto;
use \Api\Services\DTOs\ContentUsrMetaDto;
use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class ContentMigration extends Command
{
    public $isDebug = false;

    protected function configure()
    {
        $this->setName('mig:content')
            ->setDescription('Content migration.')
            ->addOption('start', 's', InputOption::VALUE_OPTIONAL, 'Start')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Limit')
            ->addOption('total', 'tt', InputOption::VALUE_OPTIONAL, 'Total')
            ->addOption('table', 't', InputOption::VALUE_OPTIONAL, 'Table')
            ->addOption('step', 'ss', InputOption::VALUE_OPTIONAL, 'step')
            ->addOption('time', 'ti', InputOption::VALUE_OPTIONAL, 'time')
            ->addOption('media', 'm', InputOption::VALUE_OPTIONAL, 'media')
            ->addOption('mediatext', 'mt', InputOption::VALUE_OPTIONAL, 'mediatext');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {        
        require_once __DIR__.'/../../../lib/config.php';
        require_once __DIR__.'/../../../lib/timecode.class.php';
        //require_once __DIR__.'/../../../lib/bootstrap.php';
        //홈페이지 역사관 이관용
        $migSettings = [
            'name' => 'mig',
            'driver' => 'oracle',
            'host' => '10.10.50.135',
            'database' => 'orcl',
            'username' => 'bis',
            'password' => 'bis',
            'port' => '1521',
            'charset' => 'AL32UTF8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'server_version' => '11g'
        ];

        //cms이관용
        $cmsSettings = [
            'name' => 'cms',
            'driver' => 'oracle',
            'host' => '10.10.51.34',
            'database' => 'CMS',
            'username' => 'ktv',
            'password' => 'ktv',
            'port' => '1521',
            'charset' => 'AL32UTF8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'server_version' => '11g'
        ];

        //운영 DB
        $defaultSettings = [
            'name' => 'default',
            'driver' => 'oracle',
            'host' => '10.10.50.110',
            'database' => 'cms',
            'username' => 'ktvcms',
            'password' => 'xhdqkd67890#',
            'port' => '1521',
            'charset' => 'AL32UTF8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'server_version' => '11g'
        ];

        //db 접속 재정의
        $settings = [
            'logging' => false,
            'connections' => [
                //$defaultSettings,
                \Api\Support\Helpers\DatabaseHelper::getSettings(),
                \Api\Support\Helpers\DatabaseHelper::getSettings('bis'),
                $migSettings,
                $cmsSettings
            ],
        ];       
        $capsule = \Api\Support\Helpers\DatabaseHelper::getConnection($settings);

        //컨테이너
        $container = app()->getContainer();

        dump("start: ".date("Y-m-d H:i:s"));
        $container['logger']->info("start: ".date("Y-m-d H:i:s"));

        $start = $input->getOption('start');
        $limit = $input->getOption('limit');
        $total = $input->getOption('total');
        $table = Str::upper($input->getOption('table'));
        $step = Str::upper($input->getOption('step'));
        $time = $input->getOption('time');
        $media = $input->getOption('media');
        $mediaText = $input->getOption('mediatext');
         
        
        dump( "param");
        dump( "start: "."{$start}");
        dump( "limit: "."{$limit}");
        dump( "total: "."{$total}");
        dump( "table: "."{$table}");
        $container['logger']->info("table: "."{$table}");

        if($table == 'E_PHOTO'){
            $targetTable = 'MIGRATION_EHISTORY_PHOTO';
            $query = $this->getSelectQuery($targetTable)->orderByRaw('"232_DTA_DETAIL_ID" , "179_EHISTRY_ID", "207_UCI"');
        }else if($table == 'E_AUDIO') {
            $targetTable = 'MIGRATION_EHISTORY_AUDIO';
            $query = $this->getSelectQuery($targetTable)->orderByRaw('"232_DTA_DETAIL_ID"');
        }else if( $table == 'E_VIDEO' ){
            $targetTable = 'MIGRATION_EHISTORY_VIDEO';
            $query = $this->getSelectQuery($targetTable)->orderByRaw('"232_DTA_DETAIL_ID"');
        }else if(  $table == 'H_VIDEO' ){
            $targetTable = 'MIGRATION_CM_CONTENT_MEDIA';
            $query = $this->getSelectQuery($targetTable)->orderByRaw('HMPG_CONTENT_ID');
        }else if(  $table == 'H_VIDEO_I' ){
            $targetTable = 'MIG_CM_CONTENT_MEDIA_1213_I';
            $query = $this->getSelectQuery($targetTable)->orderByRaw('HMPG_CONTENT_ID');
        }else if(  $table == 'H_VIDEO_U' ){
            $targetTable = 'MIG_CM_CONTENT_MEDIA_1213_U';
            $query = $this->getSelectQuery($targetTable)->orderByRaw('HMPG_CONTENT_ID');
        }else if(  $table == 'H_AUDIO' ){
            $targetTable = 'MIGRATION_CM_CONTENT_AUDIO';
            $query = $this->getSelectQuery($targetTable)->orderByRaw('HMPG_CONTENT_ID');
        }else if(  $table == 'CMS' ){
            $stepMap =[
                'F' => 403726926356,
                'M' => 403726926360,
                'O' => 403726926355,
                'P' => 403726926359,
                'C' => 403726926358
            ];
            $query = $this->getSelectQuery($table);
            if( !empty($step) && $stepMap[$step] ){
               if( empty($stepMap[$step])){
                    dd('not found step');
               }
               $query->where('nds_videometa_tb.makingstep', $stepMap[$step] );//->orderByRaw('nds_videometa_tb.mediaid');
            }
            if( !empty($time) ){
                $query->whereRaw('( nds_video_tb.CREATETIME > ? 
                OR nds_video_tb.MODIFYTIME > ? 
                OR nds_video_tb.DELETETIME > ? 
                OR nds_videometa_tb.CREATETIME > ?
                OR nds_videometa_tb.MODIFYTIME > ? 
                OR nds_videometa_tb.DELETETIME > ? )', [$time, $time, $time, $time, $time, $time ] );
            }else if(!empty($media)){
                $query->where('nds_videometa_tb.mediaid', $media );
            }else if( !empty($mediaText) ){
                $mediaIdsText =  file_get_contents(dirname(dirname(dirname(__DIR__))).'/migration/'.$mediaText);
                $mediaIds = parseTextToArray($mediaIdsText);
        
                if( empty($mediaIds) ){
                    dd('empty list');
                }
                $query->whereIn('nds_videometa_tb.mediaid', $mediaIds );
            }
            $query->orderByRaw('nds_videometa_tb.mediaid');
        }else if($table == 'CMSDEL' ){

            
            $query = DB::table('nds_video_tb_del', 'cms')
            ->join('nds_videometa_tb_del', 'nds_video_tb_del.VIDEOMETAID', '=', 'nds_videometa_tb_del.VIDEOMETAID')
            ->join('NDS_PROXYVIDEO_TB_del', 'nds_video_tb_del.VIDEOID', '=', 'NDS_PROXYVIDEO_TB_del.VIDEOID')
            ->leftJoin('nds_videometa_tb', 'nds_videometa_tb.mediaid', '=', 'nds_videometa_tb_del.mediaid')
            ->whereNotNull('nds_videometa_tb_del.mediaid')
            ->where('nds_video_tb_del.DELETETIME', '>',$time )
            ->whereNull('nds_videometa_tb.mediaid');
                 
            if(!empty($media)){
                $query->where('nds_videometa_tb_del.mediaid', $media );
            }
            
            $query->groupBy('nds_videometa_tb_del.mediaid')
            ->selectRaw("nds_videometa_tb_del.mediaid");
        }else if($table == 'CMS_VALID' ){
           
            // $stepMap =[
            //     'F' => 403726926356,
            //     'M' => 403726926360,
            //     'O' => 403726926355,
            //     'P' => 403726926359,
            //     'C' => 403726926358
            // ];
            // $query = $this->getSelectQuery('CMS');
            // if( !empty($step) && $stepMap[$step] ){
            //    if( empty($stepMap[$step])){
            //         dd('not found step');
            //    }
            //    $query->where('nds_videometa_tb.makingstep', $stepMap[$step] );//->orderByRaw('nds_videometa_tb.mediaid');
            // }
            // if( !empty($time) ){
            //     $query->whereRaw('( nds_video_tb.CREATETIME > ? 
            //     OR nds_video_tb.MODIFYTIME > ? 
            //     OR nds_video_tb.DELETETIME > ? 
            //     OR nds_videometa_tb.CREATETIME > ?
            //     OR nds_videometa_tb.MODIFYTIME > ? 
            //     OR nds_videometa_tb.DELETETIME > ? )', [$time, $time, $time, $time, $time, $time ] );
            // }else if(!empty($media)){
            //     $query->where('nds_videometa_tb.mediaid', $media );
            // }
            // $query->orderByRaw('nds_videometa_tb.mediaid');

           $query = DB::table('Z_MIG_CONTENT_SYNC')->groupBy('mediaid')->selectRaw("mediaid")->orderByRaw('mediaid');
        }
        else{
            dd('empty table');
        }

        $migrationService = new \ProximaCustom\services\MigrationService();            
        $contentService = new \Api\Services\ContentService($container);
        $mediaService = new \Api\Services\MediaService($container);
        $mediaSceneService = new \Api\Services\MediaSceneService($container);
        $timecodeService = new \timecode();

        $service = [
            'migrationService' => $migrationService,
            'contentService' => $contentService,
            'mediaService' => $mediaService,
            'mediaSceneService' => $mediaSceneService,
            'timecodeService' => $timecodeService
        ];

        $container['logger']->info("query: ".$query->toSql());
 
        //$query = $this->getSelectQuery('MIGRATION_EHISTORY_PHOTO')->orderBy('232_DTA_DETAIL_ID');
        $totalCount = $query->get()->count();// $query->count();

        dump('total : '.$totalCount );
        $container['logger']->info('total : '.$totalCount);
        $page = 1;
        $perPage = 1000;
        $createdCount = 0;
        while (true) {
            $records = $query->simplePaginate($perPage, ['*'], 'page', $page);

            if (count($records) === 0) {
                dump($end);
                break;
            }
            dump( 'page: '.$page );
          
            foreach ($records as $row => $record) {              
                $numCnt = $row + 1 + ( ($page - 1) * $perPage );
                dump($numCnt);

                // if ( !empty($start) && ( $start > $numCnt ) ) {
                //     continue;
                // }

                // if ( !empty($limit) && ( ($numCnt - $start) > $limit ) ) {
                //     die();
                // }
               
                $list = (array)$record;

                if( $table == 'E_PHOTO' ){
                    $contentId = $this->eHistoryPhoto($service, $list);
                }else if(  $table == 'E_AUDIO' ){                  
                    $contentId = $this->eHistoryAudio($service, $list);                   
                }else if( $table == 'E_VIDEO'  ){
                    $contentId = $this->eHistoryVideo($service, $list);   
                }else if(  $table == 'H_VIDEO' ||  $table == 'H_VIDEO_I' ||  $table == 'H_VIDEO_U' ){
                    $contentId = $this->homeVideo($service, $list);
                    $container['searcher']->update($contentId);
                }else if(  $table == 'H_AUDIO' ){
                    $contentId = $this->homeAudio($service, $list);
                }else if(  $table == 'CMS' ){
                    $contentId = $this->cmsVideo($service, $list);                    
                    //검색엔진 업데이트 
                    //$container['searcher']->update($contentId);
                }else if(  $table == 'CMSDEL' ){
                    $contentId = $this->cmsVideoDel($service, $list);                    
                    //검색엔진 업데이트 
                    if($contentId){
                        $container['searcher']->delete($contentId);
                    }
                }else if(  $table == 'CMS_VALID' ){
                    $contentId = $this->cmsVideoDelValid($service, $list);                    
                    //검색엔진 업데이트 
                    // if($contentId){
                    //     $container['searcher']->delete($contentId);
                    // }
                }
                dump('contentId: '.$contentId);

                // if (!$this->isDebug) {
                //     $container['logger']->info(date("Y-m-d H:i:s").'] '.'contentId: '.$contentId);
                //     $method = 'post';
                //     $baseUrl= 'http://218.38.152.44';
                //     $url = '/ap/restful/contents';
                //     $options = [
                //         'headers' => ['Content-type'=>'application/json']
                //     ];
                
                //     $contentMap = $contentService->getContentForPush($contentId);
                //     if ($contentMap) {
                //         $options['body'] = json_encode($contentMap);
                //         $client = new \Api\Core\HttpClient($baseUrl);
                //         $result = $client->request($method, $url, $params, $options);
                //         dump('result: '.$result);
                //         $container['logger']->info(date("Y-m-d H:i:s").'] '.'contentId: '.$result);
                //     }
                // }
                //if ( !empty($limit) && $limit < $numCnt) {
                //    die();
                //}
            }
            dump(  date("Y-m-d H:i:s").'] '.$createdCount . ' records created...' . $createdCount/$totalCount*100 . '% complete.' );
            $container['logger']->info(date("Y-m-d H:i:s").'] '.$createdCount . ' records created...' . $createdCount/$totalCount*100 . '% complete.');
            $createdCount = $perPage * $page;
            $page++;
        }   
        

        dump( date("Y-m-d H:i:s").'] '.' 100% complete.' );
        $container['logger']->info(date("Y-m-d H:i:s").'] '.' 100% complete.');
        die();
    }

    function getSelectQuery($table ){
        if ($table == 'MIGRATION_EHISTORY_PHOTO') {
            $select = '"176_DTA_OCCRRNC_SE" as DTA_OCCRRNC_SE,
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
                "205_CLIP_END_TIME" as CLIP_END_TIME';
        }else if( $table == 'MIGRATION_EHISTORY_AUDIO'){
            $select ='"176_DTA_OCCRRNC_SE" as DTA_OCCRRNC_SE,
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
            "235_KOGL_TY" as KOGL_TY ';
        }else if( $table == 'MIGRATION_EHISTORY_VIDEO' ){
            $select ='"176_DTA_OCCRRNC_SE" AS DTA_OCCRRNC_SE,
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
                "VIDEO_NUMBER" AS HONO,
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
                "235_KOGL_TY" AS KOGL_TY';
        }else if( $table == 'MIGRATION_CM_CONTENT_MEDIA' || $table == 'MIG_CM_CONTENT_MEDIA_1213_I' || $table == 'MIG_CM_CONTENT_MEDIA_1213_U' ){
            $select ='to_char(BRDCST_DE, \'YYYYMMDDHH24MISS\') AS BRDCST_DE,
                to_char(REGIST_DT, \'YYYYMMDDHH24MISS\') AS REGIST_DT,
                to_char(UPDT_DT, \'YYYYMMDDHH24MISS\') AS UPDT_DT,
                TB,
                SCENARIO as SCENARIO,
                SCENARIO as WEB_SCENARIO,
                DELETE_AT,
                VIDEO_DURATION_MIN as sys_video_rt,
                VIDEO_DURATION_SEC,
                BRDCST_TIME_HM_HH as brdcst_time_hm,
                BRDCST_TIME_HM_MM,
                HMPG_OTHBC_AT,
                MEMO,
                TITLE,
                ALL_VIDO_AT,
                REGIST_USER_ID,
                UPDT_USER_ID,
                PROGRM_CODE,
                TME_NO,
                HMPG_CONTENT_ID as hmpg_cntnts_id,
                ORG_IDS as instt,
                TOPIC_IDS,
                ORI_FILE_PATH,
                ORI_FILE_NM,
                THUMB_FILE_PATH,
                THUMB_FILE_NM,
                MOBILE_MEDIA_FILE';
        }else if($table == 'MIGRATION_CM_CONTENT_AUDIO' ){
            $select ='    to_char(BRDCST_DE, \'YYYYMMDDHH24MISS\') AS BRDCST_DE,
    to_char(REGIST_DT, \'YYYYMMDDHH24MISS\') AS REGIST_DT,
    to_char(UPDT_DT, \'YYYYMMDDHH24MISS\') AS UPDT_DT,
    TB,
SCENARIO,
SCENARIO as WEB_SCENARIO,
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
MOBILE_MEDIA_FILE';
        }
        
        if( $table == 'CMS' ){
            // $select = 'mediaid,
            // count(videoid) cnt ';
            // FROM nds_videometa_tb m, NDS_VIDEO_TB v 
            // WHERE 
            // v.VIDEOMETAID=m.VIDEOMETAID 
            // and m.makingstep=403726926360
            // GROUP BY m.mediaid;
            //ORDER BY mediaid asc
    
            $selectQuery = DB::table('nds_video_tb', 'cms')
            ->join('nds_videometa_tb', 'nds_video_tb.VIDEOMETAID', '=', 'nds_videometa_tb.VIDEOMETAID')
            ->join('NDS_PROXYVIDEO_TB', 'nds_video_tb.VIDEOID', '=', 'NDS_PROXYVIDEO_TB.VIDEOID')
            //->where('nds_video_tb.CATALOGINGSTATUS',3000)
           // ->where('nds_video_tb.TRANSCODINGSTATUS',3000)
            ->whereNotNull('nds_videometa_tb.mediaid')
            ->groupBy('mediaid')
            ->selectRaw("nds_videometa_tb.mediaid");

        }else{
            $selectQuery = DB::table($table, 'mig')->selectRaw($select);
        }

        return $selectQuery;
    }

    /**
     * CMS 영상 마이그레이션
     *
     * @param [type] $service
     * @param [type] $list
     * @return void
     */
    function cmsVideo($service, $list)
    {
        $isDebug = $this->isDebug;

        $mediaId = $list['mediaid'];
        $container = app()->getContainer();
        //20191121
        // Fine-Cut	F	63001
        // 마스터본	M	119190
        // 촬영원본	O	87
        // 편집완본(뉴스)	P	288376
        // 클린본	C	23339

        //         E	1	11
        // O	1	24
        // M	3	373462
        // F	7	5418
        // C	2	12382
        // M	5	324811
        // M	4	1578
        // P	3	4878
                
        $udContentIdMap = [
            'O' => 1,
            'F' => 7,
            'W' => 7,
            'C' => 2,
            'P' => 9,
            'M' => 3,
            'A'=> 3
        ];

        
        $migrationService   = $service['migrationService'];
        $contentService     = $service['contentService'];
        $mediaService       = $service['mediaService'];
        $mediaSceneService  = $service['mediaSceneService'];

        $select = "          
            nds_video_tb.dtlarchivestatus as dtl_archv_sttus,
            nds_video_tb.dtlarchivestarttime as dtl_archv_begin_dt ,
            nds_video_tb.dtlarchiveendtime as dtl_archv_end_dt,
            nds_video_tb.videoid as bfe_video_id,
            nds_video_tb.archiveexeuser as archv_exctn,
            nds_video_tb.archiverequser as archv_rqester,
            nds_video_tb.ingestuser as ingest_exctn,
            (SELECT USERNAME FROM KMS_CNF_USER_VIEW WHERE userid=nds_video_tb.softwareqcverifyuser) as qc_cnfrmr,
            nds_video_tb.transferrequser as trnsmis_rqester,
            FUNC_GETCODEVALUE(nds_video_tb.archivereqstatus) as archv_requst_at,
            nds_video_tb.archivestatus as archv_sttus,
            nds_video_tb.catalogingstatus as catlg_sttus,
            nds_video_tb.hr_videoencodingstatus as video_encd_sttus,
            nds_video_tb.maincontrol as mcr_trnsmis_sttus,
            nds_video_tb.restorestatus as restore_sttus,
            nds_video_tb.subcontrol as scr_trnsmis_sttus,
            nds_video_tb.transcodingstatus as transcd_sttus,
            nds_video_tb.transferstatus as trnsmis_sttus,
            FUNC_GETCODEVALUE(nds_video_tb.archiverequsertype) as archv_regist_requst_ty,
            nds_video_tb.archivestarttime as archv_begin_dt,
            nds_video_tb.archiveendtime as archv_end_dt,
            nds_video_tb.catalogingendtime as catlg_end_dt,
            nds_video_tb.catalogingstarttime as catlg_begin_dt,
            nds_video_tb.archiveendtime as archv_end_dt,
            nds_video_tb.ingestdatetime as ingest_dt,
            nds_video_tb.maincontrolendtime as mcr_trnsmis_end_dt,
            nds_video_tb.maincontrolstarttime as mcr_trnsmis_begin_dt,
            nds_video_tb.restoreendtime as restore_end_dt,
            nds_video_tb.restorestarttime as restore_begin_dt,
            nds_video_tb.subcontrolendtime as scr_trnsmis_end_dt,
            nds_video_tb.subcontrolstarttime as scr_trnsmis_begin_dt,
            nds_video_tb.transcodingendtime as transcd_end_dt,
            nds_video_tb.transcodingstarttime as transcd_begin_dt,
            nds_video_tb.transferreqdatetime as trnsmis_requst_dt,
            nds_video_tb.ingestplace as ingest_place,
            nds_video_tb.startframeindex as begin_frme_indx,
            nds_video_tb.isarchive as atmc_archv_execut_at,
            nds_video_tb.isarchivetarget as archv_trget_at,
            nds_video_tb.iscreatepreviewnote as archv_trget_at,
            nds_video_tb.isrestoredvideo as restore_at,
            nds_video_tb.softwareqcverification as qc_cnfirm_at,
            nds_video_tb.transmissionstatus as trnsmis_at ,
            nds_videometa_tb.nousereleasedatetime as use_prhibt_relis_dt,
            nds_videometa_tb.nousereleaser as use_prhibt_relis_user_id,
            nds_videometa_tb.nousereqdatetime as use_prhibt_set_dt,
            nds_videometa_tb.nousereqreason as use_prhibt_set_resn,
            nds_videometa_tb.nouserequester as use_prhibt_set_user_id,
            (SELECT USERNAME FROM KMS_CNF_USER_VIEW WHERE userid=nds_videometa_tb.modifyuser) as	updt_user_id,
            nds_videometa_tb.modifytime	as updated_at,
            nds_videometa_tb.modifytime	as last_modified_date,
            (SELECT USERNAME FROM KMS_CNF_USER_VIEW WHERE userid=nds_videometa_tb.createuser)	as regist_user_id,
            nds_videometa_tb.createtime	as created_date,
            nds_videometa_tb.title	as title,
            FUNC_GET_TREECODEEID(nds_videometa_tb.physicaltree) as ctgry_id,
            ( SELECT nodename FROM KMS_CNF_TREENODE_TB where nodeid=FUNC_GET_TREECODEEID(nds_videometa_tb.physicaltree)) as ctgry_path,
            ( SELECT parent FROM KMS_CNF_TREENODE_TB where nodeid=FUNC_GET_TREECODEEID(nds_videometa_tb.physicaltree)) as ctgry_parent,
            nds_videometa_tb.keepexpiredate as	expired_date,
            FUNC_GETCODEVALUE( nds_videometa_tb.broadcasttype) as brdcst_stle_se,
            FUNC_GETCODEVALUE( nds_videometa_tb.videotypedivision) as vido_ty_se,
            FUNC_GETCODEVALUE( nds_videometa_tb.makingstep) as prod_step_se,
            FUNC_GETCODEVALUE( nds_videometa_tb.videosrcattdivision) as shooting_orginl_atrb,
            nds_videometa_tb.programcode as progrm_code,
            nds_videometa_tb.programname as progrm_nm,
            nds_videometa_tb.programseqnumber as tme_no,
            nds_videometa_tb.subtitle as subtl,
            FUNC_GETCODEVALUE( nds_videometa_tb.programonairdate ) as brdcst_de,
            nds_videometa_tb.contents as cn,
            nds_videometa_tb.keywords as kwrd,
            nds_videometa_tb.mediaid as media_id,
            FUNC_GETCODEVALUE( nds_videometa_tb.materialtype ) as matr_knd,
            nds_videometa_tb.embargobool as embg_at,
            nds_videometa_tb.embargoreason as embg_resn,
            nds_videometa_tb.embargoreleasedatetime as embg_relis_dt,
            nds_videometa_tb.nousebool as use_prhibt_at,
            nds_videometa_tb.keepextendreason as prsrv_pd_et_resn,
            nds_videometa_tb.productpd as prod_pd_nm,
            nds_videometa_tb.cast as cast,
            nds_videometa_tb.mediarepoter as shooting_dirctr,
            nds_videometa_tb.shootingdate as shooting_de,
            nds_videometa_tb.shootingplace as shooting_place,
            nds_videometa_tb.interviewrepoter as sbjslct_jrnlst,
            nds_videometa_tb.deskcp as desk_cp_nm,
            nds_videometa_tb.copyright as cpyrhtown,
            nds_videometa_tb.copyrightdescription as cpyrht_cn,
            FUNC_GETCODEVALUE( nds_videometa_tb.usegrade) as use_grad,
            FUNC_GETCODEVALUE(nds_videometa_tb.telecinetype) as telecine_ty_se,
            CASE nds_videometa_tb.iskeepingtelecinetape when 420906795556 THEN 'N' WHEN 420906795555 THEN 'Y' ELSE '' end as tape_hold_at,
            nds_videometa_tb.telecinebarcode as film_no,
            FUNC_GETCODEVALUE(nds_videometa_tb.telecinetapekind) as tape_knd,
            FUNC_GETCODEVALUE(nds_videometa_tb.telecinetapesize) as tape_mg,
            nds_videometa_tb.partnumber as hono,
            nds_videometa_tb.partnumber as manage_no,
            nds_videometa_tb.telecinedirector as dirctr,
            nds_videometa_tb.telecineproductdate as prod_de,
            FUNC_GETCODEVALUE(nds_videometa_tb.telecinestandard) as stndrd,
            FUNC_GETCODEVALUE(nds_videometa_tb.telecinecolor) as clor,
            FUNC_GETCODEVALUE(nds_video_tb.jurisdiction)	AS vido_psitn_relm,
            nds_video_tb.hr_video_cliplength as sys_video_frme,
            nds_video_tb.hdbool as sys_rsoltn_se,
            nds_video_tb.hr_videofps as sys_frame_rate,
            nds_video_tb.hr_videoresolution as sys_display_size,
            FUNC_GETCODECAPTION(nds_video_tb.hr_video_aspectratio) as sys_video_asperto,
            nds_video_tb.hr_video_bandwidth as sys_video_bitrate,
            FUNC_GETCODECAPTION(nds_video_tb.hr_video_wrappertype) as sys_video_wraper,
            FUNC_GETCODECAPTION(nds_video_tb.hr_video_compformat) as sys_video_codec,
            nds_video_tb.hr_video_prodlength as sys_video_rt,
            FUNC_GETCODECAPTION(nds_video_tb.hr_audio_sampling) as sys_audio_samplrate,
            nds_video_tb.hr_audio_bandwidth as sys_audio_bitrate,
            FUNC_GETCODEVALUE(nds_video_tb.hr_audio_channel) as sys_audio_channel,
            FUNC_GETCODECAPTION(nds_video_tb.hr_audio_compformat) as sys_audio_codec,
            nds_video_tb.hr_videolength as video_filesize,
            FUNC_GETSTORAGE(nds_video_tb.hr_storagepath) as strge_id,
            nds_video_tb.hr_filename as sys_filename,
            nds_video_tb.hr_filepath as sys_filepath
        ";

        $contentMedias = DB::table('nds_video_tb', 'cms')
        ->join('nds_videometa_tb', 'nds_video_tb.VIDEOMETAID', '=', 'nds_videometa_tb.VIDEOMETAID')
        ->where('nds_videometa_tb.mediaid', $mediaId)
        ->selectRaw($select)
        ->orderBy("nds_video_tb.VIDEOMETAID")
        ->get()->toArray();

        

        $contentInfo = [
            'meta' => null,
            'original' => null,
            'nearline' => null,
            'archive' => null
        ];
        foreach ($contentMedias as $key => $contentMedia) {
            $contentMedia = (array)$contentMedia;

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
            $container['logger']->error("empty meta: ".$mediaId);
            return false;
        }
          
        $videoId = $list['bfe_video_id'];
        unset($list['mdb2rn']);
        $isExist = $migrationService->isExistMediaId($mediaId);
        //dump($contentInfo);

        $list = $contentInfo['meta'];

        $list['regist_user_id'] = empty($list['regist_user_id']) ? 'admin': $list['regist_user_id'];
        $list['updt_user_id'] = empty($list['updt_user_id']) ? 'admin': $list['updt_user_id'];

        unset($list['mdb2rn']);
        if ($isExist) {           
            $contentId      =  $isExist ;
            dump('target:'.$contentId);
            return $contentId;
        } else {
            //$contentId = 1111;
            $contentId      = $migrationService->getContentId();
            //dump('target:'.$contentId);
        }
        $dto            = new ContentDto(['content_id' => $contentId]);
        $statusDto      = new ContentStatusDto(['content_id' => $contentId]);
        $sysMetaDto     = new ContentSysMetaDto(['sys_content_id' => $contentId]);
        $usrMetaDto     = new ContentUsrMetaDto(['usr_content_id' => $contentId]);

        $metaMap = [];
        foreach ($dto as $key => $val) {
            $newKey = $key;
            if (!is_null($metaMap[$key])) {
                $newKey = $metaMap[$key];
            }        
            if (isset($list[$newKey])) {
                $dto->$key = $migrationService->renderVal($newKey, $list[$newKey]);
            }
        }

        $bs_content_id  = BsContentType::MOVIE;

         
        $oldToNewCateogyMap = [
            286 => 205,
            1992=> 205,
            1993=> 205,
            1994=> 205,
            1995=> 205,
            1996=> 205,
            1997=> 205,
            3612=> 205
        ];
       
        $isCategoryMap =  $oldToNewCateogyMap[$list['ctgry_id']];

        if( !empty($isCategoryMap) ){
            $categoryInfo = \Api\Models\Category::where('category_id', $isCategoryMap)->get()->first();
                        
            if (!empty($categoryInfo)) {
                $category_id = $categoryInfo['category_id'];
                $dto->category_id = $category_id;
                $dto->category_full_path = '/0/100/'.$category_id;
            } else {         
                $container['logger']->error("empty categoryInfo: ".$mediaId);
                return false;
            }
        }else{
            $categoryInfo = \Api\Models\Category::where('extra_order', $list['ctgry_id'])->get()->first();
                        
            if (!empty($categoryInfo)) {
                $category_id = $categoryInfo['category_id'];
                $dto->category_id = $category_id;
                $dto->category_full_path = '/0/100/'.$categoryInfo['parent_id'].'/'.$category_id;
            } else {

                if( $list['brdcst_stle_se'] == 'N' ){
                    //방송형태구분이 뉴스이면 월요일로 강제 매핑
                    $category_id= 2016;
                    $dto->category_id = $category_id;
                    $dto->category_full_path = '/0/100/'.'201'.'/'.$category_id;
                }else{
                    //그외 제작 리스토어폴더로 
                    $category_id = 2108;
                    $dto->category_id = $category_id;
                    $dto->category_full_path = '/0/100/'.'200'.'/'.$category_id;
                }
                $container['logger']->error("empty categoryInfo: ".$mediaId);
                //return false;
            }
        }
 
        //고정
        $dto->bs_content_id = $bs_content_id;
        //마스터본
        $dto->ud_content_id = $udContentIdMap[ $list['prod_step_se'] ];
        //승인
        $dto->status = '2';
        $user_id = $list['regist_user_id'];

          
        if( empty($dto->title) ){
            $dto->title = '제목없음';
        }

        $user = new User();
        $user->user_id = $user_id;
        $originalFlag = null;
        if (!empty($contentInfo['original'])) {
            //오리지널 있는경우
            $originalStatus = 0;
            $oriMedia = $contentInfo['original'];
        } else {
            //없는 경우 삭제처리
            $oriMedia = $list;
            $originalStatus = 1;
            $originalFlag = 'DMC';
        }

        //등록 채널
        $reg_type = 'regist_mig_cms';
        //마이그레이션 경로 정보 고정값
        $prefixMigPath = '';

        $createTime = strtotime($dto->created_date);
        $createTimeYmd =  date('Ymd', $createTime);
        $createTimeYmdhis = date('YmdHis', $createTime);
        $sttusList = [
            'archv_sttus',
            'mcr_trnsmis_sttus',
            'scr_trnsmis_sttus',
            'trnsmis_sttus',
            'video_encd_sttus',
            'catlg_sttus',
            'restore_sttus',
            'transcd_sttus',
            'dtl_archv_sttus'
        ];
        foreach ($statusDto as $key => $val) {
            $newKey = $key;
            if (!is_null($metaMap[$key])) {
                $newKey = $metaMap[$key];
            }
            if (isset($list[$newKey])) {
                if( in_array($newKey, $sttusList) ){
                    if( $list[$newKey] == 3000){
                        $list[$newKey] ='complete';
                    }
                }
                $statusDto->$key = $migrationService->renderVal($newKey, $list[$newKey]);
            }
        }

        if( !empty($contentInfo['nearline']) && !empty($contentInfo['archive'])  ){
            //니어라인 dtl 다 있음
            $statusDto->archive_status = 3;
        }else if( !empty($contentInfo['archive'])  ){
            $statusDto->archive_status = 2;
        }else if( !empty($contentInfo['nearline'])  ){
            $statusDto->archive_status = 1;
        }

        foreach ($sysMetaDto as $key => $val) {
            $newKey = $key;
            if (!is_null($metaMap[$key])) {
                $newKey = $metaMap[$key];
            }
            if (isset($oriMedia[$newKey])) {
                $sysMetaDto->$key = $migrationService->renderVal($newKey, $oriMedia[$newKey]);
            }
        }

        foreach ($usrMetaDto->toArray() as $key => $val) {
            $newKey = $key;
            if (!is_null($metaMap[$key])) {
                $newKey = $metaMap[$key];
            }
            if (isset($list[$newKey])) {
                $usrMetaDto->$key = $migrationService->renderVal($newKey, $list[$newKey]);
            }
        }
        
        //기본값
        $usrMetaDto->othbc_at ='Y';
        $usrMetaDto->kogl_ty='open04';
        $usrMetaDto->reviv_posbl_at='Y';
        $usrMetaDto->dwld_posbl_at='N';

        //dump($dto);
        //dump($statusDto);
        //dump($sysMetaDto);
        //dump($usrMetaDto);

        //dd('end');

        if ($isExist) {
            //콘텐츠 생성
            $keys = $migrationService->getNotNull($dto);
            $dto = $dto->only(...$keys);
            $keys = $migrationService->getNotNull($statusDto);
            $statusDto = $statusDto->only(...$keys);
            $keys = $migrationService->getNotNull($sysMetaDto);
            $sysMetaDto = $sysMetaDto->only(...$keys);
            $keys = $migrationService->getNotNull($usrMetaDto);
            $usrMetaDto = $usrMetaDto->only(...$keys);  
            
            if (!$isDebug) {
                $contentService->update($contentId, $dto, $statusDto, $sysMetaDto, $usrMetaDto, $user);
            } else {
                dump($dto);
                dump($statusDto);
                dump($sysMetaDto);
                dump($usrMetaDto);
            }
            //$contentService->update($contentId, $dto, $statusDto, $sysMetaDto, $usrMetaDto, $user);
            //return false;
        } else {
            //콘텐츠 생성
            if (!$isDebug) {
                $contentService->create($dto, $statusDto, $sysMetaDto, $usrMetaDto, $user);
            } else {
                dump($dto);
                dump($statusDto);
                dump($sysMetaDto);
                dump($usrMetaDto);
            }
        }

        //미디어 생성
        if ($isExist) {
            $bfMedias = $mediaService->getMediaByContentId($contentId);
            $existMedias = [];
            foreach($bfMedias as $bfMedia){
                $existMedias [ $bfMedia->media_type ] = $bfMedia;
            }
        }
       
        $media_type = 'original';
        $oriStorageId = 104;
        $originalPath = str_replace('\\', '/', $oriMedia['sys_filepath']);
        $originalPath = str_replace('/CMS/', '', $originalPath);
        $originalPath = trim($originalPath, '/').'/' .$oriMedia['sys_filename'];
        $originalFilesize = $oriMedia['video_filesize'];
        $mediaData = [
            'content_id' => $contentId,
            'storage_id' => $oriStorageId,
            'media_type' => $media_type,
            'path' => $originalPath,
            'filesize' => $originalFilesize,
            'status' => $originalStatus,
            'flag' => $originalFlag,
            'reg_type' => $reg_type
       ];
       if (!empty( $existMedias[$media_type] ) ) {
           //미디어 조회 기존 있음 업데이트
           $existMedias[$media_type]->storage_id = $oriStorageId;
           $existMedias[$media_type]->path = $originalPath;
           $existMedias[$media_type]->filesize = $originalFilesize;
           $existMedias[$media_type]->status = $originalStatus;
           $existMedias[$media_type]->flag = $originalFlag;
           if (!$isDebug) {
               $existMedias[$media_type]->save();
           }else{
                dump($existMedias[$media_type]);
           }
       }else{
            $mediaData = [
                'content_id' => $contentId,
                'storage_id' => $oriStorageId,
                'media_type' => $media_type,
                'path' => $originalPath,
                'filesize' => $originalFilesize,
                'status' => $originalStatus,
                'flag' => $originalFlag,
                'reg_type' => $reg_type
            ];
           $mediaDto = new MediaDto($mediaData);
           $mediaDto->created_date = $createTimeYmdhis;
           if (!$isDebug) {
               $oriMedia = $mediaService->create($mediaDto, $user);
           } else {
               dump($oriMedia);
           }
       }

        if (!empty($contentInfo['archive'])) {
            $proxyVideoId = $contentInfo['archive']['bfe_video_id'];
        } elseif (!empty($contentInfo['nearline'])) {
            $proxyVideoId = $contentInfo['nearline']['bfe_video_id'];
        } else {
            $proxyVideoId = $contentInfo['original']['bfe_video_id'];
        }


       // dd($proxyVideoId);

        //저해상도는 DTL에 있는거  
        $proxyInfo = DB::table('NDS_PROXYVIDEO_TB', 'cms')     
        ->where('videoid', $proxyVideoId)
        ->selectRaw("
        VIDEOID,
        PROXYVIDEOID,
        CREATETIME,
        video_filesize as video_filesize,
        FUNC_GETSTORAGE(storagepath) as strge_id,
        storagepath,
        video_filename as filename,
        video_filepath as file_path 
        ")->first();

        //저해상도 미디어 생성

        //$mediaPath = date('Y/m/d',strtotime($createTimeYmd ) ).'/'.$contentId.'/'.'PROXY_'.$contentId.'.mp4' ;
  
        if (!empty($proxyInfo)) {
            $proxyInfo = (array)$proxyInfo;
            $mediaPath = str_replace('\\', '/', $proxyInfo['file_path']);
            $mediaPath = str_replace('CMS/ProxyVideo/', '', $mediaPath);
            $mediaPath = trim($mediaPath, '/').'/'.$proxyInfo['filename'];
            $media_type = 'proxy';
            $proxyStorageId = 119;

            if (!empty($existMedias[$media_type])) {
                //미디어 조회 기존 있음 업데이트
                $existMedias[$media_type]->storage_id = $proxyStorageId;
                $existMedias[$media_type]->path = $mediaPath;
                $existMedias[$media_type]->filesize = $proxyInfo['video_filesize'];
                if (!$isDebug) {
                    $existMedias[$media_type]->save();
                } else {
                    dump($existMedias[$media_type]);
                }
            } else {
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
                if (!$isDebug) {
                    $proxyMedia = $mediaService->create($proxyMediaDto, $user);
                } else {
                    dump($proxyMediaDto);
                }
            }

            //원본이 삭제 재생성시 카달로그를 새로 만든다....
            //동기화마다 삭제 후 넣어줘야하나..?

            $proxyMediaId = $proxyMedia->media_id;
            
            //대표 이미지 있으면 업데이트     
            $thumbInfo = DB::table('nds_scene_tb', 'cms')     
            ->where('videoid', $proxyVideoId)
            ->selectRaw("
            (SELECT FILESIZE FROM KMS_DAT_FILE_TB WHERE FILEID=TITLEIMAGEFILE ) AS filesize,
            CREATETIME,
            SEGMENTID,
            VIDEOID,
            TITLEIMAGEFILE,
            STARTFRAMEINDEX,
            ENDFRAMEINDEX,
            STARTTIMECODE,
            ENDTIMECODE 
            ")->first();

            if (!empty($thumbInfo)) {
                $thumbInfo = (array)$thumbInfo;
                //dump($thumbInfo);
                $pathCode = $thumbInfo['titleimagefile'];
                $mediaPath = $migrationService->getImagePath($pathCode);
                $media_type = 'thumb';

                if (!empty($existMedias[$media_type])) {
                    $existMedias[$media_type]->storage_id = 118;
                    $existMedias[$media_type]->path = $mediaPath;
                    $existMedias[$media_type]->filesize = $thumbInfo['filesize'];
                    if (!$isDebug) {
                        $existMedias[$media_type]->save();
                    } else {
                        dump($existMedias[$media_type]);
                    }
                } else {
                    $mediaData = [
                        'content_id' => $contentId,
                        'storage_id' => 118,
                        'media_type' => $media_type,
                        'path' => $mediaPath,
                        'filesize' => $thumbInfo['filesize'],
                        'status' => 0,
                        'reg_type' => $reg_type
                    ];
                    $mediaDto = new MediaDto($mediaData);
                    $mediaDto->created_date = $thumbInfo['createtime'];
                    if (!$isDebug) {
                        $thumbMedia = $mediaService->create($mediaDto, $user);
                    } else {
                        dump($mediaDto);
                    }
                }
            }
                

            //카달로그 이미지
            $catalogInfo = DB::table('nds_shot_tb', 'cms')
            ->where('videoid', $proxyVideoId)
            ->selectRaw("
                (SELECT FILESIZE FROM KMS_DAT_FILE_TB WHERE FILEID=TITLEIMAGEFILE ) AS filesize,
                SEGMENTID,
                VIDEOID,
                TITLEIMAGEFILE,
                STARTFRAMEINDEX,
                ENDFRAMEINDEX,
                STARTTIMECODE,
                ENDTIMECODE 
            ")->orderBy('STARTFRAMEINDEX')->get()->toArray();

            if (!empty($catalogInfo)) {
                $catalogDatas = [];
                foreach ($catalogInfo as $ckey=> $catalog) {
                    $catalog = (array)$catalog;
                    $pathCode = $catalog['titleimagefile'];
                    $mediaPath = $migrationService->getImagePath($pathCode);
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
                if (!$isDebug) {
                    
                    if( !empty($existMedias['proxy']) ){
                        $asIsProxyId = $existMedias['proxy']->media_id;
                        $asIsScenes = $mediaSceneService->getMediaSceneByMediaId($asIsProxyId);
                        if( count($catalogDatas) != $asIsScenes->count() ){
                            //총 갯수가 다르거나 생성일이 다르다면 재생성?
                            $sceneMedias = $mediaSceneService->delAndCreate($catalogDatas, $proxyMediaId);
                        }
                    }else{
                        $sceneMedias = $mediaSceneService->delAndCreate($catalogDatas, $proxyMediaId);
                    }
                } else {
                    dump($catalogDatas);
                }
            }

            //있으면 제외
            if ( !$isExist ){
                $qcInfo = DB::table('nds_qualitycheck_tb', 'cms')
                    ->where('videoid', $proxyVideoId)
                    ->selectRaw("
                        videoid,
                        starttimecode,
                        endtimecode,
                        startframeindex start_tc ,
                        endframeindex end_tc,
                        func_getcodevalue(qualitychecktype) quality_type,
                        qualitycheckid show_order 
                    ")->orderBy('qualitycheckid')->get()->toArray();
                //dump($qcInfo);
                $qcDatas = [];
                if (!empty($qcInfo)) {
                    foreach ($qcInfo as $ckey=> $qc) {
                        $qc = (array)$qc;
                        $qc_start = substr($qc['starttimecode'], 0, 2)*3600+substr($qc['starttimecode'], 3, 2)*60+substr($qc['starttimecode'], 6, 2);
                        $qc_end = substr($qc['endtimecode'], 0, 2)*3600+substr($qc['endtimecode'], 3, 2)*60+substr($qc['endtimecode'], 6, 2);
                        $qcDatas  [] = [
                            'media_id' => $proxyMediaId,
                            'quality_type' => $qc['quality_type'],
                            'start_tc' => $qc_start,
                            'end_tc' =>   $qc_end,
                            'show_order' => $qc['show_order']
                        ];
                    }

                    if (!$isDebug) {
                        $migrationService->createQcInfo($proxyMediaId, $qcDatas);
                    } else {
                        dump($qcDatas);
                    }
                }
            }
        }

    
        if (!empty($contentInfo['nearline'])) {
            //니어라인 미디어 생성
            $media_type = 'archive';
            $nearStorageId = 112;
            $mediaPath = str_replace('\\', '/', $contentInfo['nearline']['sys_filepath']);
            $mediaPath = str_replace('/CMS/', '', $mediaPath);
            $mediaPath = trim($mediaPath, '/').'/' .$contentInfo['nearline']['sys_filename'];
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
            if ( !empty($existMedias[$media_type]) ) {
                //미디어 조회 기존 있음 업데이트
                $existMedias[$media_type]->storage_id = $nearStorageId;
                $existMedias[$media_type]->path = $mediaPath;
                $existMedias[$media_type]->filesize = $originalFilesize;
                $existMedias[$media_type]->status = 0;
                $existMedias[$media_type]->flag = null;
                if (!$isDebug) {
                    $existMedias[$media_type]->save();
                } else {
                    dump($existMedias[$media_type]);
                }
            }else{
                $mediaDto = new MediaDto($mediaData);
                $mediaDto->created_date = $contentInfo['nearline']['created_date'];
                if (!$isDebug) {
                    $nearMedia = $mediaService->create($mediaDto, $user);
                } else {
                    dump($mediaDto);
                }
            }
        }else if (!empty($contentInfo['archive'])) {
            //니어가 없거나 삭제 처리인데..
            //미디어 넣어줌
            //니어라인 미디어 생성
            $media_type = 'archive';
            $nearStorageId = 112;
            $mediaPath = str_replace('\\', '/', $contentInfo['archive']['sys_filepath']);
            $mediaPath = str_replace('/CMS/', '', $mediaPath);
            $mediaPath = trim($mediaPath, '/').'/' .$contentInfo['archive']['sys_filename'];
            $originalFilesize = $contentInfo['archive']['video_filesize'];
            $mediaData = [
                'content_id' => $contentId,
                'storage_id' => $nearStorageId,
                'media_type' => $media_type,
                'path' => $mediaPath,
                'filesize' => $originalFilesize,
                'status' => 1,
                'flag' => 'DMC',
                'reg_type' => $reg_type
            ];

            if ( !empty($existMedias[$media_type]) ) {
                    //미디어 조회 기존 있음 업데이트
                    $existMedias[$media_type]->storage_id = $nearStorageId;
                    $existMedias[$media_type]->path = $mediaPath;
                    $existMedias[$media_type]->filesize = $originalFilesize;
                    $existMedias[$media_type]->status = 1;
                    $existMedias[$media_type]->flag = 'DMC';
                    if (!$isDebug) {
                        $existMedias[$media_type]->save();
                    } else {
                        dump($existMedias[$media_type]);
                    }
            }else{
                $mediaDto = new MediaDto($mediaData);
                $mediaDto->created_date = $contentInfo['archive']['created_date'];
                if (!$isDebug) {
                    $nearMedia = $mediaService->create($mediaDto, $user);
                } else {
                    dump($mediaDto);
                }
            }
        }

 
        if (!empty($contentInfo['archive'])) {
            
            $archiveMedia = ArchiveMedia::where('object_name', $contentInfo['archive']['media_id'])->first();

            if( !empty($archiveMedia) ){
                $archiveMedia->content_id = $contentId;
                $archiveMedia->media_id = 0;
                $archiveMedia->archive_category = 'cms';
                $archiveMedia->archive_group = 'SPM_STORAGE';
                $archiveMedia->qos = '3';
                $archiveMedia->destinations = 'san';
                $archiveMedia->user_id = 'admin';//$contentInfo['archive']['created_date']
            }else{
                $archiveMedia = new ArchiveMedia();
                $archiveMedia->content_id = $contentId;
                $archiveMedia->media_id = 0;
                $archiveMedia->object_name = $contentInfo['archive']['media_id'];
                $archiveMedia->archive_category = 'cms';
                $archiveMedia->archive_group = 'SPM_STORAGE';
                $archiveMedia->qos = '3';
                $archiveMedia->destinations = 'san';
                $archiveMedia->user_id = 'admin';//$contentInfo['archive']['created_date']
            }
            if (!$isDebug) {
                $archiveMedia->save();
            } else {
                dump($archiveMedia->toArray());
            }
        }
        
        return $contentId;
    }

    function cmsVideoDel($service, $list)
    {
        $isDebug = $this->isDebug;

        $mediaId = $list['mediaid'];
        $container = app()->getContainer();

          //20191121
        // Fine-Cut	F	63001
        // 마스터본	M	119190
        // 촬영원본	O	87
        // 편집완본(뉴스)	P	288376
        // 클린본	C	23339

        //         E	1	11
        // O	1	24
        // M	3	373462
        // F	7	5418
        // C	2	12382
        // M	5	324811
        // M	4	1578
        // P	3	4878
                
        $udContentIdMap = [
            'O' => 1,
            'F' => 7,
            'W' => 7,
            'C' => 2,
            'P' => 9,
            'M' => 3,
            'A'=> 3
        ];

        
        $migrationService   = $service['migrationService'];
        $contentService     = $service['contentService'];
        $mediaService       = $service['mediaService'];
        $mediaSceneService  = $service['mediaSceneService'];

        $select = "          
            nds_video_tb.dtlarchivestatus as dtl_archv_sttus,
            nds_video_tb.dtlarchivestarttime as dtl_archv_begin_dt ,
            nds_video_tb.dtlarchiveendtime as dtl_archv_end_dt,
            nds_video_tb.videoid as bfe_video_id,
            nds_video_tb.archiveexeuser as archv_exctn,
            nds_video_tb.archiverequser as archv_rqester,
            nds_video_tb.ingestuser as ingest_exctn,
            (SELECT USERNAME FROM KMS_CNF_USER_VIEW WHERE userid=nds_video_tb.softwareqcverifyuser) as qc_cnfrmr,
            nds_video_tb.transferrequser as trnsmis_rqester,
            FUNC_GETCODEVALUE(nds_video_tb.archivereqstatus) as archv_requst_at,
            nds_video_tb.archivestatus as archv_sttus,
            nds_video_tb.catalogingstatus as catlg_sttus,
            nds_video_tb.hr_videoencodingstatus as video_encd_sttus,
            nds_video_tb.maincontrol as mcr_trnsmis_sttus,
            nds_video_tb.restorestatus as restore_sttus,
            nds_video_tb.subcontrol as scr_trnsmis_sttus,
            nds_video_tb.transcodingstatus as transcd_sttus,
            nds_video_tb.transferstatus as trnsmis_sttus,
            FUNC_GETCODEVALUE(nds_video_tb.archiverequsertype) as archv_regist_requst_ty,
            nds_video_tb.archivestarttime as archv_begin_dt,
            nds_video_tb.archiveendtime as archv_end_dt,
            nds_video_tb.catalogingendtime as catlg_end_dt,
            nds_video_tb.catalogingstarttime as catlg_begin_dt,
            nds_video_tb.archiveendtime as archv_end_dt,
            nds_video_tb.ingestdatetime as ingest_dt,
            nds_video_tb.maincontrolendtime as mcr_trnsmis_end_dt,
            nds_video_tb.maincontrolstarttime as mcr_trnsmis_begin_dt,
            nds_video_tb.restoreendtime as restore_end_dt,
            nds_video_tb.restorestarttime as restore_begin_dt,
            nds_video_tb.subcontrolendtime as scr_trnsmis_end_dt,
            nds_video_tb.subcontrolstarttime as scr_trnsmis_begin_dt,
            nds_video_tb.transcodingendtime as transcd_end_dt,
            nds_video_tb.transcodingstarttime as transcd_begin_dt,
            nds_video_tb.transferreqdatetime as trnsmis_requst_dt,
            nds_video_tb.ingestplace as ingest_place,
            nds_video_tb.startframeindex as begin_frme_indx,
            nds_video_tb.isarchive as atmc_archv_execut_at,
            nds_video_tb.isarchivetarget as archv_trget_at,
            nds_video_tb.iscreatepreviewnote as archv_trget_at,
            nds_video_tb.isrestoredvideo as restore_at,
            nds_video_tb.softwareqcverification as qc_cnfirm_at,
            nds_video_tb.transmissionstatus as trnsmis_at ,
            nds_videometa_tb.nousereleasedatetime as use_prhibt_relis_dt,
            nds_videometa_tb.nousereleaser as use_prhibt_relis_user_id,
            nds_videometa_tb.nousereqdatetime as use_prhibt_set_dt,
            nds_videometa_tb.nousereqreason as use_prhibt_set_resn,
            nds_videometa_tb.nouserequester as use_prhibt_set_user_id,
            (SELECT USERNAME FROM KMS_CNF_USER_VIEW WHERE userid=nds_videometa_tb.modifyuser) as	updt_user_id,
            nds_videometa_tb.modifytime	as updated_at,
            nds_videometa_tb.modifytime	as last_modified_date,
            (SELECT USERNAME FROM KMS_CNF_USER_VIEW WHERE userid=nds_videometa_tb.createuser)	as regist_user_id,
            nds_videometa_tb.createtime	as created_date,
            nds_videometa_tb.title	as title,
            FUNC_GET_TREECODEEID(nds_videometa_tb.physicaltree) as ctgry_id,
            ( SELECT nodename FROM KMS_CNF_TREENODE_TB where nodeid=FUNC_GET_TREECODEEID(nds_videometa_tb.physicaltree)) as ctgry_path,
            ( SELECT parent FROM KMS_CNF_TREENODE_TB where nodeid=FUNC_GET_TREECODEEID(nds_videometa_tb.physicaltree)) as ctgry_parent,
            nds_videometa_tb.keepexpiredate as	expired_date,
            FUNC_GETCODEVALUE( nds_videometa_tb.broadcasttype) as brdcst_stle_se,
            FUNC_GETCODEVALUE( nds_videometa_tb.videotypedivision) as vido_ty_se,
            FUNC_GETCODEVALUE( nds_videometa_tb.makingstep) as prod_step_se,
            FUNC_GETCODEVALUE( nds_videometa_tb.videosrcattdivision) as shooting_orginl_atrb,
            nds_videometa_tb.programcode as progrm_code,
            nds_videometa_tb.programname as progrm_nm,
            nds_videometa_tb.programseqnumber as tme_no,
            nds_videometa_tb.subtitle as subtl,
            FUNC_GETCODEVALUE( nds_videometa_tb.programonairdate ) as brdcst_de,
            nds_videometa_tb.contents as cn,
            nds_videometa_tb.keywords as kwrd,
            nds_videometa_tb.mediaid as media_id,
            FUNC_GETCODEVALUE( nds_videometa_tb.materialtype ) as matr_knd,
            nds_videometa_tb.embargobool as embg_at,
            nds_videometa_tb.embargoreason as embg_resn,
            nds_videometa_tb.embargoreleasedatetime as embg_relis_dt,
            nds_videometa_tb.nousebool as use_prhibt_at,
            nds_videometa_tb.keepextendreason as prsrv_pd_et_resn,
            nds_videometa_tb.productpd as prod_pd_nm,
            nds_videometa_tb.cast as cast,
            nds_videometa_tb.mediarepoter as shooting_dirctr,
            nds_videometa_tb.shootingdate as shooting_de,
            nds_videometa_tb.shootingplace as shooting_place,
            nds_videometa_tb.interviewrepoter as sbjslct_jrnlst,
            nds_videometa_tb.deskcp as desk_cp_nm,
            nds_videometa_tb.copyright as cpyrhtown,
            nds_videometa_tb.copyrightdescription as cpyrht_cn,
            FUNC_GETCODEVALUE( nds_videometa_tb.usegrade) as use_grad,
            FUNC_GETCODEVALUE(nds_videometa_tb.telecinetype) as telecine_ty_se,
            CASE nds_videometa_tb.iskeepingtelecinetape when 420906795556 THEN 'N' WHEN 420906795555 THEN 'Y' ELSE '' end as tape_hold_at,
            nds_videometa_tb.telecinebarcode as film_no,
            FUNC_GETCODEVALUE(nds_videometa_tb.telecinetapekind) as tape_knd,
            FUNC_GETCODEVALUE(nds_videometa_tb.telecinetapesize) as tape_mg,
            nds_videometa_tb.partnumber as hono,
            nds_videometa_tb.partnumber as manage_no,
            nds_videometa_tb.telecinedirector as dirctr,
            nds_videometa_tb.telecineproductdate as prod_de,
            FUNC_GETCODEVALUE(nds_videometa_tb.telecinestandard) as stndrd,
            FUNC_GETCODEVALUE(nds_videometa_tb.telecinecolor) as clor,
            FUNC_GETCODEVALUE(nds_video_tb.jurisdiction)	AS vido_psitn_relm,
            nds_video_tb.hr_video_cliplength as sys_video_frme,
            nds_video_tb.hdbool as sys_rsoltn_se,
            nds_video_tb.hr_videofps as sys_frame_rate,
            nds_video_tb.hr_videoresolution as sys_display_size,
            FUNC_GETCODECAPTION(nds_video_tb.hr_video_aspectratio) as sys_video_asperto,
            nds_video_tb.hr_video_bandwidth as sys_video_bitrate,
            FUNC_GETCODECAPTION(nds_video_tb.hr_video_wrappertype) as sys_video_wraper,
            FUNC_GETCODECAPTION(nds_video_tb.hr_video_compformat) as sys_video_codec,
            nds_video_tb.hr_video_prodlength as sys_video_rt,
            FUNC_GETCODECAPTION(nds_video_tb.hr_audio_sampling) as sys_audio_samplrate,
            nds_video_tb.hr_audio_bandwidth as sys_audio_bitrate,
            FUNC_GETCODEVALUE(nds_video_tb.hr_audio_channel) as sys_audio_channel,
            FUNC_GETCODECAPTION(nds_video_tb.hr_audio_compformat) as sys_audio_codec,
            nds_video_tb.hr_videolength as video_filesize,
            FUNC_GETSTORAGE(nds_video_tb.hr_storagepath) as strge_id,
            nds_video_tb.hr_filename as sys_filename,
            nds_video_tb.hr_filepath as sys_filepath
        ";

        $contentMedias = DB::table('nds_video_tb', 'cms')
        ->join('nds_videometa_tb', 'nds_video_tb.VIDEOMETAID', '=', 'nds_videometa_tb.VIDEOMETAID')
        ->where('nds_videometa_tb.mediaid', $mediaId)
        ->selectRaw($select)
        ->orderBy("nds_video_tb.VIDEOMETAID")
        ->get()->toArray();

        if( !empty($contentMedias) ){
            dump('존재함');
            return false;
        }

        $isExist = $migrationService->isExistMediaId($mediaId);
        if( empty($isExist) ){
            dump('cms에 없음');
            return false;
        }else{
            $contentId      =  $isExist;
            $user = new User();
            $user->user_id = 'admin';

                
            //미디어 생성
            if ($isExist) {
                $bfMedias = $mediaService->getMediaByContentId($contentId);
                $existMedias = [];
                foreach($bfMedias as $bfMedia){
                    $existMedias [ $bfMedia->media_type ] = $bfMedia;
                }
            }
        
            $media_type = 'original';
            if ( !empty($existMedias[$media_type]) ) {
                //미디어 조회 기존 있음 업데이트
                $existMedias[$media_type]->status = 1;
                $existMedias[$media_type]->flag = 'DMC';
                if (!$isDebug) {
                    $existMedias[$media_type]->save();
                } else {
                    dump($existMedias[$media_type]);
                }
            }

            $contentService->delete($contentId, $user);    
        }
    }

    function cmsVideoDelValid($service, $list)
    {
        $isDebug = $this->isDebug;

        $mediaId = $list['mediaid'];
        $container = app()->getContainer();

          //20191121
        // Fine-Cut	F	63001
        // 마스터본	M	119190
        // 촬영원본	O	87
        // 편집완본(뉴스)	P	288376
        // 클린본	C	23339

        //         E	1	11
        // O	1	24
        // M	3	373462
        // F	7	5418
        // C	2	12382
        // M	5	324811
        // M	4	1578
        // P	3	4878
                
        $udContentIdMap = [
            'O' => 1,
            'F' => 7,
            'W' => 7,
            'C' => 2,
            'P' => 9,
            'M' => 3,
            'A'=> 3
        ];

        
        $migrationService   = $service['migrationService'];
        $contentService     = $service['contentService'];
        $mediaService       = $service['mediaService'];
        $mediaSceneService  = $service['mediaSceneService'];

        $select = "          
            nds_video_tb.dtlarchivestatus as dtl_archv_sttus,
            nds_video_tb.dtlarchivestarttime as dtl_archv_begin_dt ,
            nds_video_tb.dtlarchiveendtime as dtl_archv_end_dt,
            nds_video_tb.videoid as bfe_video_id,
            nds_video_tb.archiveexeuser as archv_exctn,
            nds_video_tb.archiverequser as archv_rqester,
            nds_video_tb.ingestuser as ingest_exctn,
            (SELECT USERNAME FROM KMS_CNF_USER_VIEW WHERE userid=nds_video_tb.softwareqcverifyuser) as qc_cnfrmr,
            nds_video_tb.transferrequser as trnsmis_rqester,
            FUNC_GETCODEVALUE(nds_video_tb.archivereqstatus) as archv_requst_at,
            nds_video_tb.archivestatus as archv_sttus,
            nds_video_tb.catalogingstatus as catlg_sttus,
            nds_video_tb.hr_videoencodingstatus as video_encd_sttus,
            nds_video_tb.maincontrol as mcr_trnsmis_sttus,
            nds_video_tb.restorestatus as restore_sttus,
            nds_video_tb.subcontrol as scr_trnsmis_sttus,
            nds_video_tb.transcodingstatus as transcd_sttus,
            nds_video_tb.transferstatus as trnsmis_sttus,
            FUNC_GETCODEVALUE(nds_video_tb.archiverequsertype) as archv_regist_requst_ty,
            nds_video_tb.archivestarttime as archv_begin_dt,
            nds_video_tb.archiveendtime as archv_end_dt,
            nds_video_tb.catalogingendtime as catlg_end_dt,
            nds_video_tb.catalogingstarttime as catlg_begin_dt,
            nds_video_tb.archiveendtime as archv_end_dt,
            nds_video_tb.ingestdatetime as ingest_dt,
            nds_video_tb.maincontrolendtime as mcr_trnsmis_end_dt,
            nds_video_tb.maincontrolstarttime as mcr_trnsmis_begin_dt,
            nds_video_tb.restoreendtime as restore_end_dt,
            nds_video_tb.restorestarttime as restore_begin_dt,
            nds_video_tb.subcontrolendtime as scr_trnsmis_end_dt,
            nds_video_tb.subcontrolstarttime as scr_trnsmis_begin_dt,
            nds_video_tb.transcodingendtime as transcd_end_dt,
            nds_video_tb.transcodingstarttime as transcd_begin_dt,
            nds_video_tb.transferreqdatetime as trnsmis_requst_dt,
            nds_video_tb.ingestplace as ingest_place,
            nds_video_tb.startframeindex as begin_frme_indx,
            nds_video_tb.isarchive as atmc_archv_execut_at,
            nds_video_tb.isarchivetarget as archv_trget_at,
            nds_video_tb.iscreatepreviewnote as archv_trget_at,
            nds_video_tb.isrestoredvideo as restore_at,
            nds_video_tb.softwareqcverification as qc_cnfirm_at,
            nds_video_tb.transmissionstatus as trnsmis_at ,
            nds_videometa_tb.nousereleasedatetime as use_prhibt_relis_dt,
            nds_videometa_tb.nousereleaser as use_prhibt_relis_user_id,
            nds_videometa_tb.nousereqdatetime as use_prhibt_set_dt,
            nds_videometa_tb.nousereqreason as use_prhibt_set_resn,
            nds_videometa_tb.nouserequester as use_prhibt_set_user_id,
            (SELECT USERNAME FROM KMS_CNF_USER_VIEW WHERE userid=nds_videometa_tb.modifyuser) as	updt_user_id,
            nds_videometa_tb.modifytime	as updated_at,
            nds_videometa_tb.modifytime	as last_modified_date,
            (SELECT USERNAME FROM KMS_CNF_USER_VIEW WHERE userid=nds_videometa_tb.createuser)	as regist_user_id,
            nds_videometa_tb.createtime	as created_date,
            nds_videometa_tb.title	as title,
            FUNC_GET_TREECODEEID(nds_videometa_tb.physicaltree) as ctgry_id,
            ( SELECT nodename FROM KMS_CNF_TREENODE_TB where nodeid=FUNC_GET_TREECODEEID(nds_videometa_tb.physicaltree)) as ctgry_path,
            ( SELECT parent FROM KMS_CNF_TREENODE_TB where nodeid=FUNC_GET_TREECODEEID(nds_videometa_tb.physicaltree)) as ctgry_parent,
            nds_videometa_tb.keepexpiredate as	expired_date,
            FUNC_GETCODEVALUE( nds_videometa_tb.broadcasttype) as brdcst_stle_se,
            FUNC_GETCODEVALUE( nds_videometa_tb.videotypedivision) as vido_ty_se,
            FUNC_GETCODEVALUE( nds_videometa_tb.makingstep) as prod_step_se,
            FUNC_GETCODEVALUE( nds_videometa_tb.videosrcattdivision) as shooting_orginl_atrb,
            nds_videometa_tb.programcode as progrm_code,
            nds_videometa_tb.programname as progrm_nm,
            nds_videometa_tb.programseqnumber as tme_no,
            nds_videometa_tb.subtitle as subtl,
            FUNC_GETCODEVALUE( nds_videometa_tb.programonairdate ) as brdcst_de,
            nds_videometa_tb.contents as cn,
            nds_videometa_tb.keywords as kwrd,
            nds_videometa_tb.mediaid as media_id,
            FUNC_GETCODEVALUE( nds_videometa_tb.materialtype ) as matr_knd,
            nds_videometa_tb.embargobool as embg_at,
            nds_videometa_tb.embargoreason as embg_resn,
            nds_videometa_tb.embargoreleasedatetime as embg_relis_dt,
            nds_videometa_tb.nousebool as use_prhibt_at,
            nds_videometa_tb.keepextendreason as prsrv_pd_et_resn,
            nds_videometa_tb.productpd as prod_pd_nm,
            nds_videometa_tb.cast as cast,
            nds_videometa_tb.mediarepoter as shooting_dirctr,
            nds_videometa_tb.shootingdate as shooting_de,
            nds_videometa_tb.shootingplace as shooting_place,
            nds_videometa_tb.interviewrepoter as sbjslct_jrnlst,
            nds_videometa_tb.deskcp as desk_cp_nm,
            nds_videometa_tb.copyright as cpyrhtown,
            nds_videometa_tb.copyrightdescription as cpyrht_cn,
            FUNC_GETCODEVALUE( nds_videometa_tb.usegrade) as use_grad,
            FUNC_GETCODEVALUE(nds_videometa_tb.telecinetype) as telecine_ty_se,
            CASE nds_videometa_tb.iskeepingtelecinetape when 420906795556 THEN 'N' WHEN 420906795555 THEN 'Y' ELSE '' end as tape_hold_at,
            nds_videometa_tb.telecinebarcode as film_no,
            FUNC_GETCODEVALUE(nds_videometa_tb.telecinetapekind) as tape_knd,
            FUNC_GETCODEVALUE(nds_videometa_tb.telecinetapesize) as tape_mg,
            nds_videometa_tb.partnumber as hono,
            nds_videometa_tb.partnumber as manage_no,
            nds_videometa_tb.telecinedirector as dirctr,
            nds_videometa_tb.telecineproductdate as prod_de,
            FUNC_GETCODEVALUE(nds_videometa_tb.telecinestandard) as stndrd,
            FUNC_GETCODEVALUE(nds_videometa_tb.telecinecolor) as clor,
            FUNC_GETCODEVALUE(nds_video_tb.jurisdiction)	AS vido_psitn_relm,
            nds_video_tb.hr_video_cliplength as sys_video_frme,
            nds_video_tb.hdbool as sys_rsoltn_se,
            nds_video_tb.hr_videofps as sys_frame_rate,
            nds_video_tb.hr_videoresolution as sys_display_size,
            FUNC_GETCODECAPTION(nds_video_tb.hr_video_aspectratio) as sys_video_asperto,
            nds_video_tb.hr_video_bandwidth as sys_video_bitrate,
            FUNC_GETCODECAPTION(nds_video_tb.hr_video_wrappertype) as sys_video_wraper,
            FUNC_GETCODECAPTION(nds_video_tb.hr_video_compformat) as sys_video_codec,
            nds_video_tb.hr_video_prodlength as sys_video_rt,
            FUNC_GETCODECAPTION(nds_video_tb.hr_audio_sampling) as sys_audio_samplrate,
            nds_video_tb.hr_audio_bandwidth as sys_audio_bitrate,
            FUNC_GETCODEVALUE(nds_video_tb.hr_audio_channel) as sys_audio_channel,
            FUNC_GETCODECAPTION(nds_video_tb.hr_audio_compformat) as sys_audio_codec,
            nds_video_tb.hr_videolength as video_filesize,
            FUNC_GETSTORAGE(nds_video_tb.hr_storagepath) as strge_id,
            nds_video_tb.hr_filename as sys_filename,
            nds_video_tb.hr_filepath as sys_filepath
        ";

        $contentMedias = DB::table('nds_video_tb', 'cms')
        ->join('nds_videometa_tb', 'nds_video_tb.VIDEOMETAID', '=', 'nds_videometa_tb.VIDEOMETAID')
        ->where('nds_videometa_tb.mediaid', $mediaId)
        ->selectRaw($select)
        ->orderBy("nds_video_tb.VIDEOMETAID")
        ->get()->toArray();

        dump( $mediaId);

               

        $contentInfo = [
            'meta' => null,
            'original' => null,
            'nearline' => null,
            'archive' => null
        ];
        foreach ($contentMedias as $key => $contentMedia) {
            $contentMedia = (array)$contentMedia;

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

            // DB::table('Z_MIG_CONTENT_SYNC')->insert([
            //     'mediaid'=> $mediaId,
            //     'loc'=> $mediaLoc,
            //     'meta'=> json_encode($contentMedia)
            // ]);    

        }

        //return false;


        if( $contentInfo['archive'] ){
            $contentInfo['meta'] = $contentInfo['archive'];
        }else if( $contentInfo['nearline'] ){
            $contentInfo['meta'] = $contentInfo['nearline'];
        }else if( $contentInfo['original'] ){
            $contentInfo['meta'] = $contentInfo['original'];
        }

        if( !$contentInfo['meta'] ){
            $container['logger']->error("empty meta: ".$mediaId);
            return false;
        }
          
        $videoId = $list['bfe_video_id'];
        unset($list['mdb2rn']);
        $isExist = $migrationService->isExistMediaId($mediaId);
        //dump($contentInfo);

        $list = $contentInfo['meta'];

        $list['regist_user_id'] = empty($list['regist_user_id']) ? 'admin': $list['regist_user_id'];
        $list['updt_user_id'] = empty($list['updt_user_id']) ? 'admin': $list['updt_user_id'];

        unset($list['mdb2rn']);
        if ($isExist) {           
            $contentId      =  $isExist ;
            dump('target:'.$contentId);
            //return $contentId;
        }else{
            return false;
        }
        $bs_content_id  = BsContentType::MOVIE;

        
        $user_id = $list['regist_user_id'];
        $user = new User();
        $user->user_id = $user_id;
        $originalFlag = null;
        if (!empty($contentInfo['original'])) {
            //오리지널 있는경우
            $originalStatus = 0;
            $oriMedia = $contentInfo['original'];
        } else {
            //없는 경우 삭제처리
            $oriMedia = $list;
            $originalStatus = 1;
            $originalFlag = 'DMC';

            dump('원본 삭제: '.$mediaId);
            $container['logger']->info('원본 삭제: '.$mediaId);
            //cms 확인
            if ($isExist) {
                $bfMedias = $mediaService->getMediaByContentId($contentId);
                $existMedias = [];
                foreach($bfMedias as $bfMedia){
                    $existMedias [ $bfMedia->media_type ] = $bfMedia;
                }
                
                $media_type ='original';
                $bfOriginalMedia = $existMedias[$media_type];

                if( !empty($bfOriginalMedia) ){
                    if( $bfOriginalMedia->status == 0 ){
                        //원본이 없는데 
                        //온라인 경우
                        $container['logger']->info("삭제이력: "."update bc_media set status='1',flag='DMC',delete_date='".date("YmdHis")."' where media_type='original' and content_id=".$contentId);
                        if( $bfOriginalMedia->reg_type == 'dtl_restore' || $bfOriginalMedia->reg_type == 'file_restore_xdcam' ){
                           // $container['logger']->info("update bc_media set status='1',flag='DMC',delete_date='".date("YmdHis")."' where media_type='original' and content_id=".$contentId);
                            return $contentId;
                        }else{
                            dump('konan 삭제 cms online:'.$contentId);

                          //  $container['logger']->info("update bc_media set status='1',flag='DMC',delete_date='".date("YmdHis")."' where media_type='original' and content_id=".$contentId);

                        }
                    }
                }
            }
        }

        //dd('end');

        // if ($isExist) {
        //     //콘텐츠 생성
        //     $keys = $migrationService->getNotNull($dto);
        //     $dto = $dto->only(...$keys);
        //     $keys = $migrationService->getNotNull($statusDto);
        //     $statusDto = $statusDto->only(...$keys);
        //     $keys = $migrationService->getNotNull($sysMetaDto);
        //     $sysMetaDto = $sysMetaDto->only(...$keys);
        //     $keys = $migrationService->getNotNull($usrMetaDto);
        //     $usrMetaDto = $usrMetaDto->only(...$keys);  
            
        //     if (!$isDebug) {
        //         $contentService->update($contentId, $dto, $statusDto, $sysMetaDto, $usrMetaDto, $user);
        //     } else {
        //         dump($dto);
        //         dump($statusDto);
        //         dump($sysMetaDto);
        //         dump($usrMetaDto);
        //     }
        //     //$contentService->update($contentId, $dto, $statusDto, $sysMetaDto, $usrMetaDto, $user);
        //     //return false;
        // }

        return false;
    }

    /**
     * e영상역사관 사진 마이그레이션
     *
     * @param [type] $service
     * @param [type] $list
     * @return void
     */
    function eHistoryPhoto($service, $list){

        $isDebug = $this->isDebug;

        $migrationService   = $service['migrationService'];
        $contentService     = $service['contentService'];
        $mediaService       = $service['mediaService'];
        $mediaSceneService  = $service['mediaSceneService'];
               
        $metaKeyMap = array(
            'created_date' => 'regist_dt',
            //'reg_user_id' =>     'regist_user_id',
            'updated_at' =>   'updt_dt' ,
            'last_modified_date' =>   'updt_dt' ,
            'updated_user_id' =>    'updt_user_id'       
        );
        
        //고정 값
        $bs_content_id  = BsContentType::IMAGE;
        $category_id    = CategoryType::HISTORY;
        $ud_content_id  = UdContentType::IMAGE;
        $status = 2;        
        $user_id = 'ehistory';
        //등록 채널
        $channel = 'regist_ehistory';

        //제작단계구분
        $prod_step_se = 'M';
        $rdcst_stle_se = 'P';
        //영상유형구분
        $vido_ty_se = 'B';
        
        $dtaDetailId = $list['dta_detail_id'];
        $ehistryId = $list['ehistry_id'];
        $uci = $list['uci'];
        $isExist = $migrationService->isExistehistoryId($dtaDetailId,$bs_content_id, $ehistryId, $uci );

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

        
        if( empty($dto->title) ){
            $dto->title = '제목없음';
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
        $thumbPathInfo = $migrationService->getPath($list['thumb_file_path'], $list['thumb_file_nm'], ['/movie_pds/','/photo_pds/']);

        //원본 및 저해상도
        $originalPathInfo = $migrationService->getPath($list['ori_file_path'], $list['ori_file_nm'], ['/movie_pds/','/photo_pds/'] );

        //영상 '/e_movie/','/movie_pds/'
        //오디오  /e_movie/

        if (!$isExist) {
            //미디어ID 발급
            $usrMetaDto->media_id = $contentService->getMediaId($bs_content_id, $category_id, $createTimeYmd);
        }

        $sysMetaDto->sys_filename =  $originalPathInfo['filename'];
        $sysMetaDto->sys_ori_filename =  $originalPathInfo['filename'];
        $sysMetaDto->sys_display_size =  $list['image_rsoltn'];

        if ($isExist) {
             dump('exist '.$contentId);
             if (!$isDebug) {

                //콘텐츠 생성
                 $dto = $migrationService->updateNestDto($dto);
                 $statusDto = $migrationService->updateNestDto($statusDto);
                 $sysMetaDto = $migrationService->updateNestDto($sysMetaDto);
                 $usrMetaDto = $migrationService->updateNestDto($usrMetaDto);
        
                 $contentService->update($contentId, $dto, $statusDto, $sysMetaDto, $usrMetaDto, $user);
             }else {
                dump($dto);
                dump($statusDto);
                dump($sysMetaDto);
                dump($usrMetaDto);
            }
        
        } else {
            //콘텐츠 생성
            if (!$isDebug) {
                $contentService->create($dto, $statusDto, $sysMetaDto, $usrMetaDto, $user);
            } else {
                dump($dto);
                dump($statusDto);
                dump($sysMetaDto);
                dump($usrMetaDto);
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
             dump($mediaDto);
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
             dump($mediaDto);
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
             dump($mediaDto);
        }


        // //입수 마이그레이션 워크플로우 수행
        // $task = new TaskManager($db);
        // $task_id = $task->insert_task_query_outside_data($contentId, $reg_type, 1, $user_id, $srcPath );
        // exit;
        return $contentId;
    }

    function eHistoryAudio($service, $list){
        $isDebug = $this->isDebug;

        $migrationService   = $service['migrationService'];
        $contentService     = $service['contentService'];
        $mediaService       = $service['mediaService'];
        $mediaSceneService  = $service['mediaSceneService'];
        $timecodeService  = $service['timecodeService'];
               
        $metaKeyMap = array(
            'created_date' => 'regist_dt',
            //'reg_user_id' =>     'regist_user_id',
            'updated_at' =>   'updt_dt' ,
            'last_modified_date' =>   'updt_dt' ,
            'updated_user_id' =>    'updt_user_id'       
        );
        
        //고정 값
        $bs_content_id  = BsContentType::SOUND;
        $category_id    = CategoryType::HISTORY;
        $ud_content_id  = UdContentType::AUDIO;
        $status = 2;        
        $user_id = 'ehistory';
        //등록 채널
        $channel = 'regist_ehistory';

        //제작단계구분
        $prod_step_se = 'M';
        $rdcst_stle_se = 'P';
        //영상유형구분
        $vido_ty_se = 'B';
        
        $dtaDetailId = $list['dta_detail_id'];
        $ehistryId = $list['ehistry_id'];
        $uci = $list['uci'];
        $isExist = $migrationService->isExistehistoryId($dtaDetailId,$bs_content_id );

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
        //원본 및 저해상도
        $originalPathInfo = $migrationService->getPath($list['ori_file_path'], $list['ori_file_nm'], ['/e_movie/'] );

        //영상 '/e_movie/','/movie_pds/'
        //오디오  /e_movie/

        if (!$isExist) {
            //미디어ID 발급
            $usrMetaDto->media_id = $contentService->getMediaId($bs_content_id, $category_id, $createTimeYmd);
        }

        if( !is_null($list['clip_end_time']) ){
            $clipTime =  (int)(( $list['clip_end_time'] -  $list['clip_begin_time']) / 1000 );
            
            $sysMetaDto->sys_clip_begin_time = $list['clip_begin_time'];
            $sysMetaDto->sys_clip_end_time = $list['clip_end_time'];
            $sysMetaDto->sys_video_rt = $timecodeService::getConvTimecode( $clipTime ).';00';

            $sysMetaDto->sys_filename =  $originalPathInfo['filename'];
            $sysMetaDto->sys_ori_filename =  $originalPathInfo['filename'];
        }

        if ($isExist) {
             dump('exist '.$contentId);
             if (!$isDebug) {

                //콘텐츠 생성
                 $dto = $migrationService->updateNestDto($dto);
                 $statusDto = $migrationService->updateNestDto($statusDto);
                 $sysMetaDto = $migrationService->updateNestDto($sysMetaDto);
                 $usrMetaDto = $migrationService->updateNestDto($usrMetaDto);
        
                 $contentService->update($contentId, $dto, $statusDto, $sysMetaDto, $usrMetaDto, $user);
             }else {
                dump($dto);
                dump($statusDto);
                dump($sysMetaDto);
                dump($usrMetaDto);
            }
        
        } else {
            //콘텐츠 생성
            if (!$isDebug) {
                $contentService->create($dto, $statusDto, $sysMetaDto, $usrMetaDto, $user);
            } else {
                dump($dto);
                dump($statusDto);
                dump($sysMetaDto);
                dump($usrMetaDto);
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
             dump($mediaDto);
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
             dump($mediaDto);
        }

        // //입수 마이그레이션 워크플로우 수행
        // $task = new TaskManager($db);
        // $task_id = $task->insert_task_query_outside_data($contentId, $reg_type, 1, $user_id, $srcPath );
        // exit;
        return $contentId;
    }

    function eHistoryVideo($service, $list){
        $isDebug = $this->isDebug;

        $migrationService   = $service['migrationService'];
        $contentService     = $service['contentService'];
        $mediaService       = $service['mediaService'];
        $mediaSceneService  = $service['mediaSceneService'];
        $timecodeService  = $service['timecodeService'];
               
        $metaKeyMap = array(
            'created_date' => 'regist_dt',
            //'reg_user_id' =>     'regist_user_id',
            'updated_at' =>   'updt_dt' ,
            'last_modified_date' =>   'updt_dt' ,
            'updated_user_id' =>    'updt_user_id'       
        );
        
        //고정 값
        $bs_content_id  = BsContentType::MOVIE;
        $category_id    = CategoryType::HISTORY;
        $ud_content_id  = UdContentType::MASTER;
        $status = 2;
        $user_id = 'ehistory';
        //등록 채널
        $channel = 'regist_ehistory';

        //제작단계구분
        $prod_step_se = 'M';
        $rdcst_stle_se = 'P';
        //영상유형구분
        $vido_ty_se = 'B';
        
        $dtaDetailId = $list['dta_detail_id'];
        $ehistryId = $list['ehistry_id'];
        $uci = $list['uci'];
        $isExist = $migrationService->isExistehistoryId($dtaDetailId,$bs_content_id );

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
        $thumbPathInfo = $migrationService->getPath($list['thumb_file_path'], $list['thumb_file_nm'], ['/e_movie/','/movie_pds/'] );

        //원본 및 저해상도
        $originalPathInfo = $migrationService->getPath($list['ori_file_path'], $list['ori_file_nm'], ['/e_movie/','/movie_pds/'] );

        //영상 '/e_movie/','/movie_pds/'
        //오디오  /e_movie/

        if (!$isExist) {
            //미디어ID 발급
            $usrMetaDto->media_id = $contentService->getMediaId($bs_content_id, $category_id, $createTimeYmd);
        }

        if( !is_null($list['clip_end_time']) ){
            $clipTime =  (int)(( $list['clip_end_time'] -  $list['clip_begin_time']) / 1000 );
            
            $sysMetaDto->sys_clip_begin_time = $list['clip_begin_time'];
            $sysMetaDto->sys_clip_end_time = $list['clip_end_time'];
            $sysMetaDto->sys_video_rt = $timecodeService::getConvTimecode( $clipTime ).';00';

            $sysMetaDto->sys_filename =  $originalPathInfo['filename'];
            $sysMetaDto->sys_ori_filename =  $originalPathInfo['filename'];
        }

        if ($isExist) {
             dump('exist '.$contentId);
             if (!$isDebug) {

                //콘텐츠 생성
                 $dto = $migrationService->updateNestDto($dto);
                 $statusDto = $migrationService->updateNestDto($statusDto);
                 $sysMetaDto = $migrationService->updateNestDto($sysMetaDto);
                 $usrMetaDto = $migrationService->updateNestDto($usrMetaDto);
        
                 $contentService->update($contentId, $dto, $statusDto, $sysMetaDto, $usrMetaDto, $user);
             }else {
                dump($dto);
                dump($statusDto);
                dump($sysMetaDto);
                dump($usrMetaDto);
            }
        
        } else {
            //콘텐츠 생성
            if (!$isDebug) {
                $contentService->create($dto, $statusDto, $sysMetaDto, $usrMetaDto, $user);
            } else {
                dump($dto);
                dump($statusDto);
                dump($sysMetaDto);
                dump($usrMetaDto);
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
             dump($mediaDto);
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
             dump($mediaDto);
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
            dump($mediaDto);
        }

        // //입수 마이그레이션 워크플로우 수행
        // $task = new TaskManager($db);
        // $task_id = $task->insert_task_query_outside_data($contentId, $reg_type, 1, $user_id, $srcPath );
        // exit;
        return $contentId;
    }

    function homeVideo($service , $list){
        $isDebug = $this->isDebug;
              
        $migrationService   = $service['migrationService'];
        $contentService     = $service['contentService'];
        $mediaService       = $service['mediaService'];
        $mediaSceneService  = $service['mediaSceneService'];
        $timecodeService  = $service['timecodeService'];
               
        $metaKeyMap = array(
            'created_date' => 'regist_dt',
            //'reg_user_id' =>     'regist_user_id',
            'updated_at' =>   'updt_dt' ,
            'last_modified_date' =>   'updt_dt' ,
            'updated_user_id' =>    'updt_user_id'       
        );
        
        //고정 값
        $bs_content_id  = BsContentType::MOVIE;
        $category_id    = CategoryType::HOME;
        $ud_content_id  = UdContentType::MASTER;
        $status = 2;
        $user_id = 'homepage';
        //등록 채널
        $reg_type = 'regist_homepage';

        //제작단계구분
        $prod_step_se = 'M';
        $rdcst_stle_se = 'P';
        //영상유형구분
        $vido_ty_se = 'O';
           
        $homepageId = $list['hmpg_cntnts_id'];
        dump('homepageId: '.$homepageId);
        $isExist = $migrationService->isExistHomepageId($homepageId);

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

        foreach ($sysMetaDto as $key => $val) {
             
            if ( $key == 'sys_video_rt' ) {
                if( empty($isExist) ){
                    $minNum =  (int)$val;
                    if ($minNum >= 60) {
                        $hour = (int)($minNum / 60);
                        $hour = str_pad($hour, 2, "0", STR_PAD_LEFT);
                    } else {
                        $hour = '00';
                    }
                    $sysMetaDto->$key =  $hour.':'.str_pad($val, 2, "0", STR_PAD_LEFT).':'.str_pad($list['video_duration_sec'], 2, "0", STR_PAD_LEFT).';00';
                }else{
                    unset($sysMetaDto->$key);
                }
            }            
        }
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

        $prefixMigPath = '/data/HDVOD/wenmedia/Repository/OUTPUT/';
        //대표이미지
        $thumbPathInfo = $migrationService->getPath($list['thumb_file_path'], $list['thumb_file_nm'], '/media/' );

        //원본 및 저해상도
        $originalPathInfo = $migrationService->getPath($list['ori_file_path'], $list['ori_file_nm'], $prefixMigPath );
  
        //영상 '/e_movie/','/movie_pds/'
        //오디오  /e_movie/

        if (!$isExist) {
            //미디어ID 발급
            $usrMetaDto->media_id = $contentService->getMediaId($bs_content_id, $category_id, $createTimeYmd);
        }

                
        // dd($usrMetaDto);
        foreach ($usrMetaDto as $key => $val) {        

            if ($key == 'othbc_at') {
                $usrMetaDto->$key = 'Y';
            }
            if ($key == 'kogl_ty') {
                $usrMetaDto->$key = 'open04';
            }
            if ($key == 'brdcst_time_hm') {
                if (!empty($list[$key]) && !empty($list['brdcst_time_hm_mm'])) {
                    $usrMetaDto->$key = $val.$list['brdcst_time_hm_mm'];
                }
            }
            if( $key == 'scenario' ){
                $val = str_replace( "\r","",$val);
                $val = str_replace( "\n","",$val);
                $val = str_replace( "(한국정책방송 KTV 위성방송 ch161, www.ktv.go.kr )","",$val);
                $val = str_replace( "< 저작권자 ⓒ 한국정책방송원 무단전재 및 재배포 금지 >","",$val);

                $val = str_replace( "( KTV 국민방송 케이블방송, 위성방송 ch161, www.ktv.go.kr )","",$val);
                $val = str_replace( "ⓒ 한국정책방송원 무단전재 및 재배포 금지","",$val);
        
                $val = str_replace( "<br>","\n",$val);
                $val = str_replace( "<br/>","\n",$val);
                $val = str_replace( "<br />","\n",$val);
                $val = str_replace( "&gt;","",$val);
                $val = str_replace( "&lt;","",$val);

                $usrMetaDto->$key =  strip_tags($val);
            }
        }
        
        if (!empty($usrMetaDto->progrm_code) && !empty($usrMetaDto->tme_no)) {
  
            if ($usrMetaDto->all_vido_at == 'N') {
                //관련 영상인 경우
                //부모 영상 찾아야함
                $parents_id = $migrationService->getFindHomepageParents($usrMetaDto->progrm_code, $usrMetaDto->tme_no);
             
                if( $parents_id ){
                    $dto->parent_content_id = $parents_id;                   
                }
            }else{
                $migrationService->getFindHomepageChildren($contentId, $usrMetaDto->progrm_code, $usrMetaDto->tme_no);
            }
        }


        $sysMetaDto->sys_filename =  $originalPathInfo['filename'];
        $sysMetaDto->sys_ori_filename =  $originalPathInfo['filename'];


        if ($isExist) {
             dump('exist '.$contentId);
             if (!$isDebug) {

                //콘텐츠 생성
                 $dto = $migrationService->updateNestDto($dto);
                 $statusDto = $migrationService->updateNestDto($statusDto);
                 $sysMetaDto = $migrationService->updateNestDto($sysMetaDto);
                 $usrMetaDto = $migrationService->updateNestDto($usrMetaDto);
        
                 $contentService->update($contentId, $dto, $statusDto, $sysMetaDto, $usrMetaDto, $user);
             }else {
                dump($dto);
                dump($statusDto);
                dump($sysMetaDto);
                dump($usrMetaDto);
            }
        
        } else {
            //콘텐츠 생성
            if (!$isDebug) {
                $contentService->create($dto, $statusDto, $sysMetaDto, $usrMetaDto, $user);
            } else {
                dump($dto);
                dump($statusDto);
                dump($sysMetaDto);
                dump($usrMetaDto);
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
                'reg_type' => $reg_type,
                'filesize' => $filesize,
                'created_date' => $createTimeYmdhis
        ];
        $mediaDto = new MediaDto($mediaData);
        if (!$isDebug) {
            if( $isExist){  
                $media = \Api\Models\Media::where('content_id', $contentId )->where('media_type', $mediaType)->first();
                if( !empty($media) ){
                    $media->storage_id = $storageId;
                    $media->path = $mediaPath;
                    $media->reg_type = $reg_type;
                    $media->filesize = $storageId;
                    $media->filesize = $filesize;
                    $media->created_date = $createTimeYmdhis;
                    $media->save();
                }else{
                    $mediaService->create($mediaDto, $user); 
                }
            }else{
                $mediaService->create($mediaDto, $user);
            }
        } else {
             dump($mediaDto);
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
                'reg_type' => $reg_type,
                'filesize' => $filesize,
                'created_date' => $createTimeYmdhis
            ];
        $mediaDto = new MediaDto($mediaData);
        if (!$isDebug) {
            if( $isExist){  
                $media = \Api\Models\Media::where('content_id', $contentId )->where('media_type', $mediaType)->first();
                if( !empty($media) ){
                    $media->storage_id = $storageId;
                    $media->path = $mediaPath;
                    $media->reg_type = $reg_type;
                    $media->filesize = $storageId;
                    $media->filesize = $filesize;
                    $media->created_date = $createTimeYmdhis;
                    $media->save();
                }else{
                    $mediaService->create($mediaDto, $user); 
                }
            }else{
                $mediaService->create($mediaDto, $user);
            }
        } else {
             dump($mediaDto);
        }

        
        $mediaType = 'thumb';
        $storageId = $migrationService->getStorageId($bs_content_id, $ud_content_id, $category_id, $mediaType);
        $mediaPath = $thumbPathInfo['fullPath'];
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
        if (!$isDebug) {
            if( $isExist){  
                $media = \Api\Models\Media::where('content_id', $contentId )->where('media_type', $mediaType)->first();
                if( !empty($media) ){
                    $media->storage_id = $storageId;
                    $media->path = $mediaPath;
                    $media->reg_type = $reg_type;
                    $media->filesize = $storageId;
                    $media->filesize = $filesize;
                    $media->created_date = $createTimeYmdhis;
                    $media->save();
                }else{
                    $mediaService->create($mediaDto, $user); 
                }
            }else{
                $mediaService->create($mediaDto, $user);
            }
        } else {
            dump($mediaDto);
        }

        // //입수 마이그레이션 워크플로우 수행
        // $task = new TaskManager($db);
        // $task_id = $task->insert_task_query_outside_data($contentId, $reg_type, 1, $user_id, $srcPath );
        // exit;
        return $contentId;
    }

    function homeAudio($service , $list){
        $isDebug = $this->isDebug;
              
        $migrationService   = $service['migrationService'];
        $contentService     = $service['contentService'];
        $mediaService       = $service['mediaService'];
        $mediaSceneService  = $service['mediaSceneService'];
        $timecodeService  = $service['timecodeService'];
               
        $metaKeyMap = array(
            'created_date' => 'regist_dt',
            //'reg_user_id' =>     'regist_user_id',
            'updated_at' =>   'updt_dt' ,
            'last_modified_date' =>   'updt_dt' ,
            'updated_user_id' =>    'updt_user_id'       
        );
        
        //고정 값
        $bs_content_id  = BsContentType::SOUND;
        $category_id    = CategoryType::HOME;
        $ud_content_id  = UdContentType::AUDIO;
        $status = 2;
        $user_id = 'homepage';
        //등록 채널
        $reg_type = 'regist_homepage';

        //제작단계구분
        $prod_step_se = 'M';
        $rdcst_stle_se = 'P';
        //영상유형구분
        $vido_ty_se = 'B';
           
        $homepageId = $list['hmpg_cntnts_id'];
        dump('homepageId: '.$homepageId);
        $isExist = $migrationService->isExistHomepageId($homepageId);

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

        foreach ($sysMetaDto as $key => $val) {
             
            if ( $key == 'sys_video_rt' ) {
                if( empty($isExist) ){
                    $minNum =  (int)$val;
                    if ($minNum >= 60) {
                        $hour = (int)($minNum / 60);
                        $hour = str_pad($hour, 2, "0", STR_PAD_LEFT);
                    } else {
                        $hour = '00';
                    }
                    $sysMetaDto->$key =  $hour.':'.str_pad($val, 2, "0", STR_PAD_LEFT).':'.str_pad($list['video_duration_sec'], 2, "0", STR_PAD_LEFT).';00';
                }else{
                    unset($sysMetaDto->$key);
                }
            }            
        }
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

        $prefixMigPath = '/data/HDVOD/wenmedia/Repository/OUTPUT/';
        //대표이미지
        $thumbPathInfo = $migrationService->getPath($list['thumb_file_path'], $list['thumb_file_nm'], '/media/' );

        //원본 및 저해상도
        $originalPathInfo = $migrationService->getPath($list['ori_file_path'], $list['ori_file_nm'], $prefixMigPath );
  
        //영상 '/e_movie/','/movie_pds/'
        //오디오  /e_movie/

        if (!$isExist) {
            //미디어ID 발급
            $usrMetaDto->media_id = $contentService->getMediaId($bs_content_id, $category_id, $createTimeYmd);
        }

                
        // dd($usrMetaDto);
        foreach ($usrMetaDto as $key => $val) {        
        
            if ($key == 'brdcst_time_hm') {
                if (!empty($list[$key]) && !empty($list['brdcst_time_hm_mm'])) {
                    $usrMetaDto->$key = $val.$list['brdcst_time_hm_mm'];
                }
            }

            if( $key == 'scenario' ){
                $val = str_replace( "\r","",$val);
                $val = str_replace( "\n","",$val);
                $val = str_replace( "(한국정책방송 KTV 위성방송 ch161, www.ktv.go.kr )","",$val);
                $val = str_replace( "< 저작권자 ⓒ 한국정책방송원 무단전재 및 재배포 금지 >","",$val);

                $val = str_replace( "<br>","\n",$val);
                $val = str_replace( "<br/>","\n",$val);
                $val = str_replace( "<br />","\n",$val);
                $usrMetaDto->$key =  strip_tags($val);
            }
        }
        
        if (!empty($usrMetaDto->progrm_code) && !empty($usrMetaDto->tme_no)) {
  
            if ($usrMetaDto->all_vido_at == 'N') {
                //관련 영상인 경우
                //부모 영상 찾아야함
                $parents_id = $migrationService->getFindHomepageParents($usrMetaDto->progrm_code, $usrMetaDto->tme_no);
             
                if( $parents_id ){
                    $dto->parent_content_id = $parents_id;                   
                }
            }else{
                $migrationService->getFindHomepageChildren($contentId, $usrMetaDto->progrm_code, $usrMetaDto->tme_no);
            }
        }


        $sysMetaDto->sys_filename =  $originalPathInfo['filename'];
        $sysMetaDto->sys_ori_filename =  $originalPathInfo['filename'];


        if ($isExist) {
             dump('exist '.$contentId);
             if (!$isDebug) {

                //콘텐츠 생성
                 $dto = $migrationService->updateNestDto($dto);
                 $statusDto = $migrationService->updateNestDto($statusDto);
                 $sysMetaDto = $migrationService->updateNestDto($sysMetaDto);
                 $usrMetaDto = $migrationService->updateNestDto($usrMetaDto);
        
                 $contentService->update($contentId, $dto, $statusDto, $sysMetaDto, $usrMetaDto, $user);
             }else {
                dump($dto);
                dump($statusDto);
                dump($sysMetaDto);
                dump($usrMetaDto);
            }
        
        } else {
            //콘텐츠 생성
            if (!$isDebug) {
                $contentService->create($dto, $statusDto, $sysMetaDto, $usrMetaDto, $user);
            } else {
                dump($dto);
                dump($statusDto);
                dump($sysMetaDto);
                dump($usrMetaDto);
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
                'reg_type' => $reg_type,
                'filesize' => $filesize,
                'created_date' => $createTimeYmdhis
        ];
        $mediaDto = new MediaDto($mediaData);
        if (!$isDebug) {
            $mediaService->create($mediaDto, $user);
        } else {
             dump($mediaDto);
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
                'reg_type' => $reg_type,
                'filesize' => $filesize,
                'created_date' => $createTimeYmdhis
            ];
        $mediaDto = new MediaDto($mediaData);
        if (!$isDebug) {
            $mediaService->create($mediaDto, $user);
        } else {
             dump($mediaDto);
        }

        
        $mediaType = 'thumb';
        $storageId = $migrationService->getStorageId($bs_content_id, $ud_content_id, $category_id, $mediaType);
        $mediaPath = $thumbPathInfo['fullPath'];
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
        if (!$isDebug) {
            $mediaService->create($mediaDto, $user);
        } else {
            dump($mediaDto);
        }

        // //입수 마이그레이션 워크플로우 수행
        // $task = new TaskManager($db);
        // $task_id = $task->insert_task_query_outside_data($contentId, $reg_type, 1, $user_id, $srcPath );
        // exit;
        return $contentId;
    }
}
