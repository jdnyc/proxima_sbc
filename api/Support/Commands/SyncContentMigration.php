<?php
namespace Api\Support\Commands;

ini_set('memory_limit','512M');
use \Api\Models\User;
use Api\Models\Content;
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


class SyncContentMigration extends Command
{
    public $isDebug = false;

    protected function configure()
    {
        $this->setName('mig:sync_content')
            ->setDescription('Content migration.')
            ->addOption('start', 's', InputOption::VALUE_OPTIONAL, 'Start')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Limit')
            ->addOption('total', 'tt', InputOption::VALUE_OPTIONAL, 'Total')
            ->addOption('table', 't', InputOption::VALUE_OPTIONAL, 'Table')
            ->addOption('step', 'st', InputOption::VALUE_OPTIONAL, 'step');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {        
        require_once __DIR__.'/../../../lib/config.php';
        require_once __DIR__.'/../../../lib/timecode.class.php';
        //require_once __DIR__.'/../../../lib/bootstrap.php';
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

        $cmsSettings = [
            'name' => 'cms',
            'driver' => 'oracle',
            'host' => '10.10.50.127',
            'database' => 'orcl',
            'username' => 'ktv',
            'password' => 'ktv',
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

         
        
        dump( "param");
        dump( "start: "."{$start}");
        dump( "limit: "."{$limit}");
        dump( "total: "."{$total}");
        dump( "table: "."{$table}");
        $container['logger']->info("table: "."{$table}");

        if(  $table == 'CMS' ){
            $stepMap =[
                'F' => 403726926356,
                'M' => 403726926360,
                'O' => 403726926355,
                'P' => 403726926359,
                'C' => 403726926358
            ];
                                
            $udContentIdMap = [
                'O' => 1,
                'F' => 7,
                'W' => 7,
                'C' => 2,
                'P' => 9,
                'M' => 3,
                'A'=> 3
            ];
            if( empty($step)){
                dd('empty step');
            }

            //입수된 콘텐츠 전수 에서 
            // SELECT (SELECT ud_content_title FROM bc_ud_content WHERE ud_content_id=c.ud_content_id) ud_content_title, c.UD_CONTENT_ID,m.prod_step_se, count(c.content_id) --SELECT c.content_id 
            // from (
            // SELECT * FROM bc_content WHERE  IS_DELETED='N' AND status=2 ) c
            // LEFT OUTER JOIN bc_content_status s
            // ON (c.content_id=s.content_id) 
            // LEFT OUTER JOIN BC_USRMETA_CONTENT m
            // ON (c.content_id=m.usr_content_id)WHERE s.BFE_VIDEO_ID IS NOT NULL GROUP BY c.UD_CONTENT_ID,m.prod_step_se;
            $query = DB::table('bc_content')
            ->where( 'bc_content.IS_DELETED', 'N' )
            ->where( 'bc_content.status', '2' )
            ->join('bc_content_status', 'bc_content.content_id', '=', 'bc_content_status.content_id')
            ->join('BC_USRMETA_CONTENT', 'bc_content.content_id', '=', 'BC_USRMETA_CONTENT.usr_content_id')
            ->whereNotNull('bc_content_status.BFE_VIDEO_ID')
            ->where( 'bc_content.ud_content_id', $udContentIdMap[$step] )
            ->selectRaw('bc_content.content_id,BC_USRMETA_CONTENT.media_id')
            ->orderBy('bc_content.content_id');

           // dd($query->toSql());
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
        $totalCount = $query->count();

        dump('total : '.$totalCount );
        $container['logger']->info('total : '.$totalCount);
        $page = 1;
        $perPage = 1000;
        $createdCount = 0;
        while (true) {
            $records = $query->simplePaginate($perPage, ['*'], 'page', $page)->all();
         
            if (count($records) === 0) {
                dump($end);
                break;
            }
            dump( 'page: '.$page );
          
            foreach ($records as $row => $record) {              
                $numCnt = $row + 1 + ( ($page - 1) * $perPage );
                dump($numCnt);

                if ( !empty($start) && ( $start > $numCnt ) ) {
                    continue;
                }

                if ( !empty($limit) && ( ($numCnt - $start) > $limit ) ) {
                    die();
                }
               
                $list = (array)$record;
                if ($table == 'CMS') {
                    $mediaId = $list['media_id'];
                    $contentId  =$list['content_id'];
                //코난에 없는 미디어아이디 제거
                    $isExistAsIs = DB::table('nds_video_tb', 'cms')
                    ->join('nds_videometa_tb', 'nds_video_tb.VIDEOMETAID', '=', 'nds_videometa_tb.VIDEOMETAID')
                    ->join('NDS_PROXYVIDEO_TB', 'nds_video_tb.VIDEOID', '=', 'NDS_PROXYVIDEO_TB.VIDEOID')
                    ->where('nds_video_tb.CATALOGINGSTATUS', 3000)
                    ->where('nds_video_tb.TRANSCODINGSTATUS', 3000)
                    ->where('nds_videometa_tb.mediaid', $mediaId)
                    ->selectRaw("nds_videometa_tb.mediaid")->first();
                    if( empty( $isExistAsIs->mediaid) ){
                        //삭제된 콘텐츠

                        dump('off contentId: '.$contentId .', media_id: '.$mediaId);

                         $content = Content::find($contentId);
                         if( $content && $content->is_deleted == 'N' ){
                            $content->is_deleted = 'Y';
                            $content->save();
                         }
                    }else{
                        dump('on contentId: '.$contentId .', media_id: '.$mediaId);
                    }
                }
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
            ->where('nds_video_tb.CATALOGINGSTATUS',3000)
            ->where('nds_video_tb.TRANSCODINGSTATUS',3000)
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
            'P' => 3,
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
            //return $contentId;
        } else {
            //$contentId = 1111;
            $contentId      = $migrationService->getContentId();
            //dump('target:'.$contentId);
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
                $container['logger']->error("empty categoryInfo: ".$mediaId);
                return false;
            }
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

        foreach ($statusDto as $key => $val) {
            $newKey = $key;
            if (!is_null($metaMap[$key])) {
                $newKey = $metaMap[$key];
            }
            if (isset($list[$newKey])) {
                $statusDto->$key = $migrationService->renderVal($newKey, $list[$newKey]);
            }
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
       if ($isExist && !empty( $existMedias[$media_type] ) ) {
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

            if ($isExist && !empty($existMedias[$media_type])) {
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

            //dump($proxyMedia);
            if ( !$isExist ){
                $proxyMediaId = $proxyMedia->media_id;
                
                //대표 이미지       
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

                if( !empty($thumbInfo) ){
                    $thumbInfo = (array)$thumbInfo;
                    //dump($thumbInfo);
                    $pathCode = $thumbInfo['titleimagefile'];
                    $mediaPath = $migrationService->getImagePath($pathCode);
                    $media_type = 'thumb';

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
                        $thumbMedia = $mediaService->create($mediaDto , $user);
                    } else {
                        dump($mediaDto);
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
                        $sceneMedias = $mediaSceneService->delAndCreate($catalogDatas, $proxyMediaId);
                    } else {
                        dump($catalogDatas);
                    }

                    //dump($sceneMedias);
                }

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
            if ( $isExist && !empty($existMedias[$media_type]) ) {
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
        } elseif (!empty($contentInfo['archive'])) {
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

            if ( $isExist && !empty($existMedias[$media_type]) ) {
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
            $archiveMedia = new ArchiveMedia();
            $archiveMedia->content_id = $contentId;
            $archiveMedia->media_id = 0;
            $archiveMedia->object_name = $contentInfo['archive']['media_id'];
            $archiveMedia->archive_category = 'cms';
            $archiveMedia->archive_group = 'SPM_STORAGE';
            $archiveMedia->qos = '3';
            $archiveMedia->destinations = 'san';
            $archiveMedia->user_id = 'admin';//$contentInfo['archive']['created_date']
            if (!$isDebug) {
                $archiveMedia->save();
            } else {
                dump($archiveMedia->toArray());
            }
        }
        
        return $contentId;
    }
}
