<?php

namespace Api\Support\Commands;

use Api\Models\User;
use Api\Core\FilePath;
use Api\Types\CategoryType;
use Api\Types\StorageIdMap;
use Illuminate\Support\Str;
use Api\Types\BsContentType;
use Api\Types\UdContentType;
use Api\Services\TaskService;
use PhpOffice\PhpSpreadsheet;
use Api\Services\DTOs\MediaDto;
use Api\Services\DTOs\CategoryDto;
use Api\Support\Commands\MigrationBase;
use Api\Support\Helpers\MetadataMapper;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Database\Schema\Blueprint;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use ProximaCustom\services\MigrationService;
use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExcelMigration extends MigrationBase
{
    protected $commandName = 'mig:excel';
    protected $commandDescription = 'Migration excel tables.';


    protected function configure()
    {
        if (empty($this->commandName)) {
            echo 'Command name is required.';
            return;
        }
        $this->setName($this->commandName)
        ->addOption('table', 't', InputOption::VALUE_OPTIONAL, 'table')
        ->addOption('action', 'a', InputOption::VALUE_OPTIONAL, 'action')
        ->addOption('filename', 'f', InputOption::VALUE_OPTIONAL, 'filename')
        ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'limit')
        ->addOption('vol', 'vol', InputOption::VALUE_OPTIONAL, 'vol')
        ->addOption('update', 'up', InputOption::VALUE_OPTIONAL, 'update')
        ->addOption('contentid', 'c', InputOption::VALUE_OPTIONAL, 'contentid')
            ->setDescription($this->commandDescription);
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {        
        require_once __DIR__.'/../../../lib/config.php';
        require_once __DIR__.'/../../../lib/timecode.class.php';
       
        $this->options = [
            'table' => Str::lower($input->getOption('table')),                
            'action' => Str::lower($input->getOption('action')),                
            'filename' => $input->getOption('filename'),                
            'limit' => $input->getOption('limit'),
            'contentid' => $input->getOption('contentid'),
            'vol' => $input->getOption('vol'),
            'update' => $input->getOption('update')
        ];
    

        $settings = [
            'logging' => false, 
            'connections' => [
                \Api\Support\Helpers\DatabaseHelper::getSettings(),
                \Api\Support\Helpers\DatabaseHelper::getSettings('bis'),
            ]
        ];

        $container = app()->getContainer();
        $capsule = \Api\Support\Helpers\DatabaseHelper::getConnection($settings);

        if($this->options['action'] == 'import'){
            $table = $this->options['table'];
            $filename = $this->options['filename'];
            $output->writeln('<fire>Start migration.</fire>');
            $this->import($filename,$table,3, $this->options['update']);      
            $output->writeln('<fire>End migration.</fire>');
        }

        //테잎 이관용 excel row위치가 다름
        if($this->options['action'] == 'import2'){
            $table = $this->options['table'];
            $filename = $this->options['filename'];
            $output->writeln('<fire>Start migration.</fire>');
            $this->import($filename,$table, 2, $this->options['update']);      
            $output->writeln('<fire>End migration.</fire>');
        }

        if( $this->options['action'] == 'add'){    
            $table = $this->options['table'];
           
            
            $limit = $this->options['limit'];
            $trgTableNm = $this->options['table'];
            $volNm = $this->options['vol'];
            $num = 0;

            $tapeLists = DB::table('TAPE_MIG_LIST')->whereNull('STAT')->orderBy('ORDR')->get()->all();
            

            foreach($tapeLists as $tapeInfo)
            {
                $trgTableNm = Str::lower($tapeInfo->table_nm);
                if (!empty($tapeInfo->src_vol)) {
                    $volNm =  $tapeInfo->src_vol;
                }else{
                    $volNm =  'G:';
                }
                $lists = $this->getAll($trgTableNm);

                foreach($lists as $idx => $list)
                {
                    
                    $list->vol = $volNm;
                        
                    if( !empty($list->content_id) ) continue;
    
                    $num++;
    
                    if(strstr($table, 'tape_')){
                        $content = $this->createContentTapeSS($list);
                    }else{
    
                        $content = $this->createContent($list);
                    }
    
                    dump($content->content_id);
                    $this->checkContent($trgTableNm, $list->id, $content->content_id );
    
                    $container['searcher']->update($content->content_id);
                   // dd( $content);
    
                   if( !empty($limit) ){
                        if($limit <= $num){
                            dd('limit: '.$limit);
                        }
                    }
    
                }
            }
        }

        if( $this->options['action'] == 'update' ){
            $table = $this->options['table'];

            $lists = $this->getAll($table);

             foreach ($lists as $idx => $list) {
                
                if( empty($list->content_id) ) continue;

                $this->updateContent($list);
            }
            
        }

        if( $this->options['action'] == 'archive' ){
            $regUserId = 'system';
            $contentId = $this->options['contentid'];
            $this->archiveDD($contentId,$regUserId );
        }

     
    }

    public function createContent($list)
    {
        $container = app()->getContainer();

        $migrationService = new \ProximaCustom\services\MigrationService();            
        $contentService = new \Api\Services\ContentService($container);
        $mediaService = new \Api\Services\MediaService($container);
        $mediaSceneService = new \Api\Services\MediaSceneService($container);
        $mapperService = new MetadataMapper($container);
        $taskService = new TaskService($container);
        
        $service = [
            'migrationService' => $migrationService,
            'contentService' => $contentService,
            'mediaService' => $mediaService,
            'mediaSceneService' => $mediaSceneService
        ];

        $mapList =  $this->metadataMap($list);  
        $metaList = $contentService->getExplodeMeta($mapList);                
        $listMap = [
            'content' => [],
            'usr_meta' => [],
            'sys_meta' => [],
            'status_meta' => []
        ];
        foreach($metaList as $key => $list){
            $listMap[$key] = $mapperService->fieldMapper($list, $key, true);
        }

        
        $filePath = $list['path'];
        $filename = $list['filename'];
        
        $regUserId = 'mig_ssdb';
        
        $user = new User();
        $user->user_id = $regUserId;

        $isValidField = [
            'bc_content_id',
            'ud_content_id',
            'title',
            'ud_content_id',
            'reg_user_id',
            'shooting_orginl_atrb',
            'vido_ty_se',
            'brdcst_stle_se'
        ];

        $channel = 'regist_tele';
        $categoryId    = CategoryType::TELE;
 
        $telecine_ty_se_map = [
           // '대한뉴스'	=> 
            'D' => 2587,
          //  '대한뉴스KC'	=> 
            	'K' => 2588,
          //  '문화영화'	=> 
            	'M' => 2589,
          // '촬영자료'	=> 
            	'E' => 2590,
           // '대한뉴스예비'   =>
             'Y' => 2591,
           // '대통령기록영상'	=> 
            	'C' => 2593,
          //  '대한뉴스OT'	=> 	
            'O' => 2592
            ];

        if( !empty($telecine_ty_se_map[$listMap['usr_meta']['telecine_ty_se']]) ){
            $categoryId    =  $telecine_ty_se_map[$listMap['usr_meta']['telecine_ty_se']];
        }
        
        $listMap['content']['ud_content_id']    = UdContentType::MASTER;

        $listMap['content']['bs_content_id']    = BsContentType::MOVIE;
        $listMap['content']['category_id']    = $categoryId;
       // $listMap['content']['created_date']  =  $createdDate ;
        $listMap['content']['reg_user_id']  =  $regUserId;
        //$listMap['content']['status']  =  -1;
        $listMap['content']['expired_date']  =  date('Ymd');
        unset($listMap['content']['path']);
        unset($listMap['content']['filename']);

        
        dump($listMap);        
        $defaultMeta = [         
            'brdcst_stle_se'=>'D',//방송형태구분
            'vido_ty_se'=>'T', //프로그램유형
            'prod_step_se'=>'M', //제작구분
            'shooting_orginl_atrb'=>'general',//촬영원본속성           
            'matr_knd'=>'ZP',//소재종류
            'embg_at'=>'N',        
            'use_prhibt_at'=>'Y',       
            'use_grad'=>'useenable',            
            'kogl_ty'=>'open04',            
            'othbc_at'=>'N',          
            'reviv_posbl_at'=>'N',
            'dwld_posbl_at'=>'N',
            'media_dwld_posbl_at'=>'N',
            'cpyrht_at'=>'N',
            'tape_mg' => 'big'
        ];

        foreach( $defaultMeta as $key => $val )
        {
            $listMap['usr_meta'][$key] = $val;
        }

        // if( !empty($listMap['usr_meta']['prod_de']) ){
        //     $createdDate = date('YmdHis', strtotime($listMap['usr_meta']['prod_de']) );
        // }else{
        //    $createdDate = date('YmdHis');
        // }
        // $listMap['content']['created_date']  =  $createdDate ;
        
        $content = $contentService->createUsingArray($listMap['content'], $listMap['status_meta'], $listMap['sys_meta'], $listMap['usr_meta'] );
    
        $contentId = $content->content_id;
        if( !$contentId ){
            api_abort('system error', -101 );
        }

        
        $filesize = 10;
        $mediaType = 'original';
        $storageId = StorageIdMap::MAIN;
        $flag = 'DMC';
        $status = '1';
        $delete_date =date("YmdHis");
        $mediaPath = date("Y/m/d").'/'.$filename;
        $mediaData = [
                'content_id' => $contentId,
                'storage_id' => $storageId,
                'media_type' => $mediaType,
                'path' => $mediaPath,
                'reg_type' => $channel,
                'status' => $status,
                'delete_date' => $delete_date,
                'flag' => $flag,
                'filesize' => $filesize,
                'created_date' => date("YmdHis")
        ];
        $mediaDto = new MediaDto($mediaData);      
        $mediaService->create($mediaDto, $user);
       
        $now = date("YmdHis");

        $task = $taskService->getTaskManager();
        $task->set_priority(600);
        $taskId = $task->insert_task_query_outside_data($contentId, $channel, 1, $regUserId, $filename );
    
        if( !$taskId ){
            api_abort('system error', -102 );
        }

        $this->archiveDD($contentId,$regUserId );

        return $content;
    }

    /**
     * 테이프 이관 식스시그마
     *
     * @return void
     */
    public function createContentTapeSS($list)
    {
        dump('createContentTapeSS');
        $tapeId = $list->f_1;
        $udContentCheck = $list->f_7;
        $shootingorginlatrbCheck = $list->f_8;
        $volInfo = $list->vol;
        dump($volInfo);
        $container = app()->getContainer();

        $migrationService = new \ProximaCustom\services\MigrationService();            
        $contentService = new \Api\Services\ContentService($container);
        $mediaService = new \Api\Services\MediaService($container);
        $mediaSceneService = new \Api\Services\MediaSceneService($container);
        $mapperService = new MetadataMapper($container);
        $taskService = new TaskService($container);
        
        $service = [
            'migrationService' => $migrationService,
            'contentService' => $contentService,
            'mediaService' => $mediaService,
            'mediaSceneService' => $mediaSceneService
        ];

        $mapList =  $this->metadataMapTape($list);  
        $metaList = $contentService->getExplodeMeta($mapList);                
        $listMap = [
            'content' => [],
            'usr_meta' => [],
            'sys_meta' => [],
            'status_meta' => []
        ];
        foreach($metaList as $key => $list){
            $listMap[$key] = $mapperService->fieldMapper($list, $key, true);
        }

        
        $filename = $list['filename'].'.mxf';
        if(!empty($volInfo) ){
            $filename = $volInfo.'/'.$filename ;
        }
        dump($filename);

        dump($tapeId);

        $regUserId = 'mig_tp_2020';
        
        $user = new User();
        $user->user_id = $regUserId;

        $channel = 'regist_tape_mig';
        $categoryId    = CategoryType::NEWS;

        $defaultMeta = [         
            'brdcst_stle_se'=>'N',//방송형태구분 //뉴스
            'vido_ty_se'=>'B', //프로그램유형 //제작
            'prod_step_se'=>'F', //제작구분 //??
            'shooting_orginl_atrb'=>'general',//촬영원본속성  //일반         
            'matr_knd'=>'ZP',//소재종류 //프로그램
            'embg_at'=>'N',        
            'use_prhibt_at'=>'Y',       
            'use_grad'=>'useenable',            
            'kogl_ty'=>'open04',            
            'othbc_at'=>'N',          
            'reviv_posbl_at'=>'N',
            'dwld_posbl_at'=>'N',
            'media_dwld_posbl_at'=>'N',
            'cpyrht_at'=>'N',

            'prod_se'=>'makingself', //우선 116만
            'memo' => '2020년사업'
        ];
       
        //116A
        $listMap['content']['ud_content_id']    = UdContentType::CLIP;
        if( strstr($udContentCheck, '촬영')  ){
            $categoryId    = CategoryType::NEWS;
            $listMap['content']['ud_content_id']    = UdContentType::CLIP;
            if( strstr($shootingorginlatrbCheck, '총리' ) ){
                $defaultMeta['shooting_orginl_atrb'] = 'primeminister';
            }else if( strstr($shootingorginlatrbCheck, '청와대' ) ){
                $defaultMeta['shooting_orginl_atrb'] = 'bluehouse';
            }
        }else if( strstr($udContentCheck, '방송') ){
             //151B
             $categoryId    = 2108;
             $defaultMeta['brdcst_stle_se'] =    'P';      
            
             $listMap['content']['ud_content_id']    = UdContentType::CLEAN;
            if( strstr($shootingorginlatrbCheck, '클린' ) ){             
                $listMap['content']['ud_content_id']    = UdContentType::CLEAN;
            }else if( strstr($shootingorginlatrbCheck, '마스터' ) ){
                $listMap['content']['ud_content_id']    = UdContentType::MASTER;
            }else if( strstr($shootingorginlatrbCheck, '총리' ) ){
                $defaultMeta['shooting_orginl_atrb'] = 'primeminister';
            }else if( strstr($shootingorginlatrbCheck, '청와대' ) ){
                $defaultMeta['shooting_orginl_atrb'] = 'bluehouse';
            }
        }

        $listMap['content']['bs_content_id']    = BsContentType::MOVIE;
        $listMap['content']['category_id']    = $categoryId;
       // $listMap['content']['created_date']  =  $createdDate ;
        $listMap['content']['reg_user_id']  =  $regUserId;
        //$listMap['content']['status']  =  -1;
        $listMap['content']['expired_date']  =  date('Ymd');
        unset($listMap['content']['path']);
        unset($listMap['content']['filename']);

        		//숨김용
		//$listMap['content']['is_hidden']    = 1;

        dump($listMap);        

        foreach( $defaultMeta as $key => $val )
        {
            $listMap['usr_meta'][$key] = $val;
        }

        //뉴스 카테고리 요일별 매핑
        if($categoryId    == CategoryType::NEWS && !empty($listMap['usr_meta']['shooting_de']) ){
            $week = date('w', strtotime($listMap['usr_meta']['shooting_de']) );
            $weekMap = [
                1 => 2016,
                2 => 2017,
                3 => 2018,
                4 => 2019,
                5 => 2020,
                6 => 2021,
                0 => 2022
            ];
            if( !is_null($weekMap[$week]) ){
                $categoryId = $weekMap[$week];
                $listMap['content']['category_id']    =  $categoryId ;
            }
        }
        
        $content = $contentService->createUsingArray($listMap['content'], $listMap['status_meta'], $listMap['sys_meta'], $listMap['usr_meta'] );
        dump('af createUsingArray');
        $contentId = $content->content_id;
        if( !$contentId ){
            api_abort('system error', -101 );
        }

        
		//DB::table('bc_content')->where('content_id',$contentId)->update(['is_hidden'=>1]);
        
        $filesize = 10;
        $mediaType = 'original';
        $storageId = StorageIdMap::MAIN;
        $flag = 'DMC';
        $status = '1';
        $delete_date =date("YmdHis");
        $mediaPath = date("Y/m/d").'/'.$filename;
        $mediaData = [
                'content_id' => $contentId,
                'storage_id' => $storageId,
                'media_type' => $mediaType,
                'path' => $mediaPath,
                'reg_type' => $channel,
                'status' => $status,
                'delete_date' => $delete_date,
                'flag' => $flag,
                'filesize' => $filesize,
                'created_date' => date("YmdHis")
        ];
        $mediaDto = new MediaDto($mediaData);      
        $mediaService->create($mediaDto, $user);
        dump('af mediaService');
        $now = date("YmdHis");
        dump($filename);
        $task = $taskService->getTaskManager();
        $task->set_priority(600);
        $taskId = $task->insert_task_query_outside_data($contentId, $channel, 1, $regUserId, $filename );
        dump('af insert_task_query_outside_data');
        if( !$taskId ){
            api_abort('system error', -102 );
        }

        //$taskId = $this->archiveDD($contentId,$regUserId );

        return $content;
    }

    public function getTitleFormat($value){
            //아래 패턴만 허용
            $p_array = array(		
            '[\x{1100}-\x{11FF}\x{3130}-\x{318F}\x{AC00}-\x{D7AF}]',//한글
            '[a-zA-Z]',//영문
            '[0-9]'//숫자         
        );
        //공백 _ 치환
        $value = preg_replace("/[\s]/i", "_", $value );

        $pattern = '/'.join('|', $p_array).'+/u';
        preg_match_all($pattern, $value, $match);
        $return = implode('', $match[0]);	

        if( is_null($return) ){
            $return = 'empty';
        }
        return $return;
    }

    public function updateContent($list)
    {
        dump('updateContent');
        $contentId = $list->content_id;
        $tapeId = $list->f_1;
        $udContentCheck = $list->f_7;
        $shootingorginlatrbCheck = $list->f_8;
        $title = $list->f_13;

        $container = app()->getContainer();

        $migrationService = new \ProximaCustom\services\MigrationService();            
        $contentService = new \Api\Services\ContentService($container);
        $mediaService = new \Api\Services\MediaService($container);
        $mediaSceneService = new \Api\Services\MediaSceneService($container);
        $mapperService = new MetadataMapper($container);
        $taskService = new TaskService($container);

        $categoryService = new \Api\Services\CategoryService($container);

        $user = new \Api\Models\User();
        $user->user_id ='mig_tp_2020';
        
        $service = [
            'migrationService' => $migrationService,
            'contentService' => $contentService,
            'mediaService' => $mediaService,
            'mediaSceneService' => $mediaSceneService,
            'categoryService' => $categoryService
        ];
        
        $listMap = [
            'content' => [],
            'usr_meta' => [],
            'sys_meta' => [],
            'status_meta' => []
        ];
        //116A
        //제목
        //자료구분
        //관리번호

        //151B
        //프로그램명
        //회차
        //프로그램ID
        //부제
        //제목

        //153B
        //자료구분
        //제목
        //부제
 
        //155B 211A 211B 213A
        //제목
        //부제

        //SELECT t.content_id,c.ud_content_id,t.f_1,c.title,u.SHOOTING_ORGINL_ATRB,u.manage_no FROM TAPE_116A_001 t JOIN BC_USRMETA_CONTENT u ON t.CONTENT_ID=u.USR_CONTENT_ID
        //JOIN bc_content c ON t.content_id=c.content_id;

        //$usrMeta = $contentService->findContentUsrMeta($contentId);

        if( strstr($udContentCheck, '촬영')  ){
            $listMap['usr_meta']['brdcst_stle_se'] = 'N';
            $listMap['usr_meta']['shooting_orginl_atrb'] = 'general';
            //$categoryId    = CategoryType::NEWS;
            //$listMap['content']['ud_content_id']    = UdContentType::CLIP;
            if( strstr($shootingorginlatrbCheck, '총리' ) ){
                $listMap['usr_meta']['shooting_orginl_atrb'] =  'primeminister';
            }else if( strstr($shootingorginlatrbCheck, '청와대' ) ){
                $listMap['usr_meta']['shooting_orginl_atrb'] = 'bluehouse';
            }else{
                $listMap['usr_meta']['shooting_orginl_atrb'] = 'general';
            }
        }else if( strstr($udContentCheck, '방송') ){
            //151B
            $categoryId    = 2108;
            $listMap['usr_meta']['brdcst_stle_se'] =    'P';      
            $listMap['usr_meta']['shooting_orginl_atrb'] = 'general';
            $listMap['content']['ud_content_id']    = UdContentType::CLEAN;
            if( strstr($shootingorginlatrbCheck, '클린' ) ){             
                //$listMap['content']['ud_content_id']    = UdContentType::CLEAN;
            }else if( strstr($shootingorginlatrbCheck, '마스터' ) ){
                //$listMap['content']['ud_content_id']    = UdContentType::MASTER;
            }else if( strstr($shootingorginlatrbCheck, '총리' ) ){
                $listMap['usr_meta']['shooting_orginl_atrb'] = 'primeminister';
            }else if( strstr($shootingorginlatrbCheck, '청와대' ) ){
                $listMap['usr_meta']['shooting_orginl_atrb'] = 'bluehouse';
            }else{
                $listMap['usr_meta']['shooting_orginl_atrb'] = 'general';
            }

            //bis 조회
            //프로그램명
            //회차
            //프로그램ID
            //부제
            $pgmInfo = DB::table('BROADMED','bis')->where('TAPE_NO',$tapeId)->first();
            $pgmDetailInfo = DB::table('BROADMAS','bis')->where('TAPE_NO',$tapeId)->first();
            if(!empty($pgmInfo)){
                //be.pgm_id,be.pgm_nm,be.TAPE_NO,ba.SUBTITLE,ba.BROADDATE,ba.PROCODE,ba.VOL_NO
                $listMap['usr_meta']['progrm_code'] = $pgmInfo->pgm_id;
                $listMap['usr_meta']['progrm_nm'] = $pgmInfo->pgm_nm;
                $categoryInfo = DB::table('FOLDER_MNG')->where('PARENT_ID',2)->where('STEP',3)->where('PGM_ID',$pgmInfo->pgm_id)->first();
                
                if(empty($categoryInfo)){
                    $categoryInfo = DB::table('bc_category')->where('PARENT_ID',200)->where('code',$pgmInfo->pgm_id)->first();
                }
                if( !empty($categoryInfo) ){
                    $categoryId = $categoryInfo->category_id;
                    $listMap['content']['category_id']    = $categoryId;
                    $listMap['content']['category_full_path']    = $contentService->getCategoryFullPath($categoryId);
                }else{
                    $category_title = $this->getTitleFormat($pgmInfo->pgm_nm);
                    $categoryDto = new CategoryDto([
                        'category_title' => $category_title,
                        'parent_id' => 200,
                        'no_children' => 1,
                        'code' => $pgmInfo->pgm_id,
                        'dep' => 4
                    ]);
                    // dd($categoryDto);
    
                    $category = $categoryService->create($categoryDto, $user);
                    if ($category) {
                        $categoryId = $category->category_id;
                        $listMap['content']['category_id']    = $categoryId;
                        $listMap['content']['category_full_path']    = $contentService->getCategoryFullPath($categoryId);
                    }
                }            
            }
            if (!empty($pgmInfo)) {
                $listMap['usr_meta']['subtl'] =  $pgmDetailInfo->subtitle;
                $listMap['usr_meta']['tme_no'] =  $pgmDetailInfo->vol_no;
                if( strtotime($pgmDetailInfo->broaddate) ){
                    $listMap['usr_meta']['brdcst_de'] =  date("Ymd", strtotime($pgmDetailInfo->broaddate));
                }
            }
        }else{

        }
        
        $listMap['usr_meta']['manage_no'] = $tapeId;
        $listMap['content']['title'] = $title;
        dump($listMap);

        $content = $contentService->updateUsingArray($contentId, $listMap['content'],  [], [], $listMap['usr_meta'], $user);
        dump('af updateUsingArray');
        $contentId = $content->content_id;

        //dd($contentId);
        if( !$contentId ){
            api_abort('system error', -101 );
        }
        return $content;
    }

    /**
     * 이관용 니어라인 자동 아카이브
     *
     * @return void
     */
    public function archiveDD($contentId,$regUserId )
    {

        $container = app()->getContainer();
        $contentService = new \Api\Services\ContentService($container);
        $archiveService = new \Api\Services\ArchiveService($container);

        $isArchive = $archiveService->isArchived($contentId);
        if($isArchive){
            return false;
        }
        $now = date("YmdHis") ;
        $user = new User();
        $user->user_id = $regUserId;
        $contentMeta = [
            'content_id' => $contentId

        ];
        $statusMeta = [];
        $statusMeta['content_id']  = $contentId;
        $statusMeta['archive_status']  = '1';
        $statusMeta['archive_date']  =  date("YmdHis");
        $statusMeta['archv_begin_dt']  =  date("YmdHis");
        $statusMeta['archv_end_dt']  =  date("YmdHis");
        $statusMeta['archv_sttus']  =  'complete';

        $statusMeta['archv_requst_at']  =  date("YmdHis");
        $statusMeta['archv_rqester']  =  $regUserId;        

        $contentStatusDto = new \Api\Services\DTOs\ContentStatusDto($statusMeta);
        $keys       = array_keys($statusMeta);
        $contentStatusDto = $contentStatusDto->only(...$keys);
        $contentService->update($contentId,null, $contentStatusDto, null,null, $user);
        
        $reqComment = "자동 아카이브 요청";
        $req_no = getSequence('SEQ_REQUEST_ARCHIVE');
        DB::table('TB_ARCHIVE_REQUEST')->insert([
            'req_no' => $req_no,
            'nps_content_id'=> $contentId,
            'das_content_id'=> $contentId, 
            'req_type'=> 'archive', 
            'req_comment'=> $reqComment,
            'status'=> '1',
            'req_user_id'=> $regUserId ,
            'req_time'=> date("YmdHis")
        ]);
       
        //자동 승인                                                       
        $archiveInfo = $archiveService->archiveDtl($contentId, $user);
        if(!$archiveInfo){    
            api_abort('archive error', -102 );
        }
        $taskId = $archiveInfo->task_id ;

        $apprComment = "자동 아카이브 승인";

        DB::table('TB_REQUEST')->insert([
            'req_no' => $req_no,
            'nps_content_id'=> $contentId,
            'das_content_id'=> $contentId, 
            'req_type'=> 'archive', 
            'req_comment'=> $reqComment,
            'REQ_STATUS'=> '2',
            'req_user_id'=> $user->user_id ,
            'req_time'=> $now ,
            'APPR_TIME'=> $now ,
            'APPR_USER_ID'=> $user->user_id ,
            'APPR_COMMENT'=> $apprComment ,
            'TASK_ID'=> $taskId 
        ]);

        DB::table('bc_task')->where('task_id', $taskId )->update([
            'status' => 'queue',
            'priority' => '600'
        ]);
        return $taskId;
    }

    public function getAll($tableName)
    {
        return DB::table($tableName)->orderBy('id')->get()->all();
    }

    public function checkContent($tableName, $id, $contentId )
    {
        return DB::table($tableName)->where('id',$id)->update(['content_id'=>$contentId]);
         
    }
    
    public function metadataMap($row)
    {       
        
        $fieldMap = [
            //'no'=>'',
            ///'뉴스'=>'',
            //'제작'=>'',
            //'텔레시네'=>'',
            'f_5'=>'usr_meta-telecine_ty_se', //변환필요 텔레시네유형
            'f_6'=>'title', //제목
            'f_7'=>'usr_meta-manage_no', //호수/관리번호
            'f_8'=>'usr_meta-dirctr', //감독
            'f_9'=>'usr_meta-prod_de', //제작년도
            'f_10'=>'usr_meta-cn', //내용
            'f_11'=>'usr_meta-kwrd', //검색키워드
            //'제작정보(영상길이)'=>'',          
            //'저작권자'=>'',//
            //'저작권내용'=>'',
            'f_16'=>'usr_meta-tape_hold_at',//√ 변환필요 //테이프보유
            //'테이프미보유'=>'', //테이프미보유
            'f_18'=>'usr_meta-film_no',//FILM //테이프식별자
            'f_19'=>'usr_meta-tape_knd',//베타캠 betacam //테이프종류
            'f_20'=>'usr_meta-stndrd',//16mm,35mm //규격
            'f_21'=>'usr_meta-clor',//컬러 흑백 //색채
            'f_22'=>'path',//KC 문화, 보안/문화, 예비, 예비2 //하드명
            'f_23'=>'filename'//90(총리예비)-1080i29.97 XDCAM HD 422 50 Mbps.mxf
        ]; 

        
        //116 tape
        // 카테고리:뉴스
        // 콘텐츠 유형:클립본      
        // 촬영원본속성: 일반        
        // 소재종류: 프로그램
        //소재종류: 프로그램
        //제작구분: 본사제작
        //프로그램명
        //회자번호
        //부제
        //방송형태구분
        //촬영원본속성
        //내용
        //키워드 : 
        //촬영일자
        //촬영장소
        //방송일자

        $fieldMap = [
            //'no'=>'',
            ///'뉴스'=>'',
            //'제작'=>'',
            //'텔레시네'=>'',
            //'f_5'=>'usr_meta-telecine_ty_se', //변환필요 텔레시네유형
            'f_1'=>'usr_meta-film_no',//FILM //테이프식별자
            'f_2'=>'usr_meta-manage_no', //호수/관리번호
            'f_13'=>'title', //제목
            'f_14'=>'usr_meta-shooting_de', //촬영일자
            'f_15'=>'usr_meta-brdcst_de', //방송일자
            'f_10' => 'usr_meta-tape_mg', //테이프크기
            'f_9' => 'usr_meta-tape_knd', //테이프종류
            // 'f_8'=>'usr_meta-dirctr', //감독
            'f_26' => 'usr_meta-shooting_dirctr',//촬영감독
            // 'f_9'=>'usr_meta-prod_de', //제작년도
            // 'f_10'=>'usr_meta-cn', //내용
             'f_25'=>'usr_meta-kwrd', //검색키워드
            // //'제작정보(영상길이)'=>'',          
            // //'저작권자'=>'',//
            // //'저작권내용'=>'',
            // 'f_16'=>'usr_meta-tape_hold_at',//√ 변환필요 //테이프보유
            // //'테이프미보유'=>'', //테이프미보유
          
            // 'f_19'=>'usr_meta-tape_knd',//베타캠 betacam //테이프종류
            // 'f_20'=>'usr_meta-stndrd',//16mm,35mm //규격
            // 'f_21'=>'usr_meta-clor',//컬러 흑백 //색채
            'f_22'=>'path',//KC 문화, 보안/문화, 예비, 예비2 //하드명
            'f_23'=>'filename'//90(총리예비)-1080i29.97 XDCAM HD 422 50 Mbps.mxf
        ]; 

        $telecine_ty_se_map = [
        '대한뉴스'	=> 'D',
        '대한뉴스KC'	=> 	'K',
        '문화영화'	=> 	'M',
        '촬영자료'	=> 	'E',
        '대한뉴스예비'   => 'Y',
        '대통령기록영상'	=> 	'C',
        '대한뉴스OT'	=> 	'O'
        ];


        $tape_knd_map = [
            '2'	=> 'dct',
            '3'=> 	'digibeta',
            '1'	=> 'betacam',
            '4'	=> 'dvd',
            '5'=> 	'hd',
            '7'=> 	'blueray',
            '6'=> 	'dvcam'
        ];
        $clor_map = [
            '컬러'	=> 'color',
            '흑백'		=> 'monochrome'
        ];
        $tape_mg_map = [
            'A'	=> 'little',
            'B'		=> 'middle',
            'C'		=> 'big'
        ];
        $dataSet = [];
        foreach($row as $key => $val)
        {
            if( !empty( $fieldMap[$key] )){
                $keyMap = $fieldMap[$key];
                if(  $keyMap == 'usr_meta-telecine_ty_se'){
                   $mapVal = $telecine_ty_se_map[trim($val)];
                   if( !empty($mapVal) ){
                    $val = $mapVal;
                   }
                }

                if(  $keyMap == 'usr_meta-tape_mg'){
                    $mapVal = $tape_mg_map[trim($val)];
                    if( !empty($mapVal) ){
                     $val = $mapVal;
                    }
                 }

                if ( $keyMap == 'usr_meta-prod_de' || $keyMap == 'usr_meta-brdcst_de' || $keyMap == 'usr_meta-shooting_de' ) {
                    if( strtotime($val) ){
                        $val = date('Ymd',  strtotime($val));
                    }
                    if(strlen($val) != 8){
                        $val = str_replace('.','', $val);
                        $val = str_replace('-','', $val);
                        $val = str_replace('_','', $val);
                    }
                }

                if ($keyMap == 'usr_meta-manage_no') {
                    if( !empty($val) ){
                        $dataSet['usr_meta-hono'] = $val;
                    }
                }

                if(  $keyMap == 'usr_meta-tape_knd'){
                    $mapVal = $tape_knd_map[trim($val)];
                    if( !empty($mapVal) ){
                     $val = $mapVal;
                    }
                 }

                 if(  $keyMap == 'usr_meta-clor'){
                    $mapVal = $clor_map[trim($val)];
                    if( !empty($mapVal) ){
                     $val = $mapVal;
                    }
                 }

                if ($keyMap == 'usr_meta-tape_hold_at') {
                    if( !empty($val) ){
                        $val= 'Y';
                    }else{
                        $val= 'N';
                    }
                }

                if ($keyMap == 'usr_meta-kwrd') {
                    if( !empty($val) ){
                        if( !strstr( $val , '#') ){
                            $val = '#'.$val;
                        }else{
                            $val;
                        }                        
                    }else{

                    }
                }

                $dataSet[ $keyMap ] = trim($val);
            }
        }

        return  $dataSet;
    }

    public function metadataMapTape($row)
    {      
        
        
        //116 tape
        // 카테고리:뉴스
        // 콘텐츠 유형:클립본      
        // 촬영원본속성: 일반    
        //소재종류: 프로그램
        //제작구분: 본사제작
        //프로그램명
        //회자번호
        //부제
        //방송형태구분
        //촬영원본속성
        //내용
        //키워드 : 
        //촬영일자
        //촬영장소
        //방송일자

        $fieldMap = [
            //'f_5'=>'usr_meta-telecine_ty_se', //변환필요 텔레시네유형
            //'f_1'=>'usr_meta-film_no',//FILM //테이프식별자
            'f_1'=>'usr_meta-manage_no', //호수/관리번호
            'f_13'=>'title', //제목
            'f_14'=>'usr_meta-shooting_de', //촬영일자
            //'f_15'=>'usr_meta-brdcst_de', //방송일자 촬영본은 제외
            'f_10' => 'usr_meta-tape_mg', //테이프크기
            'f_9' => 'usr_meta-tape_knd', //테이프종류
            // 'f_8'=>'usr_meta-dirctr', //감독
            'f_26' => 'usr_meta-vido_jrnlst',//영상기자
            'f_20' => 'usr_meta-shooting_place',//촬영장소
            // 'f_9'=>'usr_meta-prod_de', //제작년도
             'f_24'=>'usr_meta-cn', //내용
             'f_25'=>'usr_meta-kwrd', //검색키워드
             'f_45' => 'usr_subtl',
            // //'제작정보(영상길이)'=>'',          
            // //'저작권자'=>'',//
            // //'저작권내용'=>'',
            // 'f_16'=>'usr_meta-tape_hold_at',//√ 변환필요 //테이프보유
            // //'테이프미보유'=>'', //테이프미보유
          
            // 'f_19'=>'usr_meta-tape_knd',//베타캠 betacam //테이프종류
            // 'f_20'=>'usr_meta-stndrd',//16mm,35mm //규격
            // 'f_21'=>'usr_meta-clor',//컬러 흑백 //색채
            //'f_22'=>'path',//KC 문화, 보안/문화, 예비, 예비2 //하드명
            'f_43'=>'filename'//90(총리예비)-1080i29.97 XDCAM HD 422 50 Mbps.mxf
        ]; 

        $telecine_ty_se_map = [
            '대한뉴스'	=> 'D',
            '대한뉴스KC'	=> 	'K',
            '문화영화'	=> 	'M',
            '촬영자료'	=> 	'E',
            '대한뉴스예비'   => 'Y',
            '대통령기록영상'	=> 	'C',
            '대한뉴스OT'	=> 	'O'
        ];


        $tape_knd_map = [
            '2'	=> 'dct',
            '3' => 'digibeta',
            '1'	=> 'betacam',
            '4'	=> 'dvd',
            '5' => 'hd',
            '7' => 'blueray',
            '6' => 'dvcam'
        ];
        $clor_map = [
            '컬러'	=> 'color',
            '흑백'		=> 'monochrome'
        ];
        $tape_mg_map = [
            'A'	=> 'little',
            'B'		=> 'middle',
            'C'		=> 'big'
        ];
        $dataSet = [];
        foreach($row as $key => $val)
        {
            if( !empty( $fieldMap[$key] )){
                $keyMap = $fieldMap[$key];
                if(  $keyMap == 'usr_meta-telecine_ty_se'){
                   $mapVal = $telecine_ty_se_map[trim($val)];
                   if( !empty($mapVal) ){
                    $val = $mapVal;
                   }
                }

                if(  $keyMap == 'usr_meta-tape_mg'){
                    $mapVal = $tape_mg_map[trim($val)];
                    if( !empty($mapVal) ){
                     $val = $mapVal;
                    }
                 }

                if ( $keyMap == 'usr_meta-prod_de' || $keyMap == 'usr_meta-brdcst_de' || $keyMap == 'usr_meta-shooting_de' ) {
                    if( strtotime($val) ){
                        $val = date('Ymd',  strtotime($val));
                    }
                    if(strlen($val) != 8){
                        $val = str_replace('.','', $val);
                        $val = str_replace('-','', $val);
                        $val = str_replace('_','', $val);
                    }
                }

                // if ($keyMap == 'usr_meta-manage_no') {
                //     if( !empty($val) ){
                //         $dataSet['usr_meta-hono'] = $val;
                //     }
                // }

                if(  $keyMap == 'usr_meta-tape_knd'){
                    $mapVal = $tape_knd_map[trim($val)];
                    if( !empty($mapVal) ){
                     $val = $mapVal;
                    }
                 }

                 if(  $keyMap == 'usr_meta-clor'){
                    $mapVal = $clor_map[trim($val)];
                    if( !empty($mapVal) ){
                     $val = $mapVal;
                    }
                 }

                if ($keyMap == 'usr_meta-tape_hold_at') {
                    if( !empty($val) ){
                        $val= 'Y';
                    }else{
                        $val= 'N';
                    }
                }

                if ($keyMap == 'usr_meta-kwrd') {
                    if( !empty($val) ){
                        if( !strstr( $val , '#') ){
                            $val = '#'.$val;
                        }else{
                            $val;
                        }                        
                    }else{

                    }
                }

                $dataSet[ $keyMap ] = trim($val);
            }
        }

        return  $dataSet;
    }



    public function dropTables()
    {
        // $tableOption = $this->options['table'] ?? null;
        // $tables = [
        //     //'attach_files'
        // ];

        // foreach ($tables as $table) {
        //     if (!empty($tableOption) && $table !== $tableOption) {
        //         continue;
        //     }
        //     DB::schema()->dropIfExists($table);
        // }
    }

    public function import($filePath, $tableName , $row = 3, $isUpdate = 0)
    {       
        return $this->loadExcelData($filePath, $tableName, $row, $isUpdate);
    }

    public function seed()
    {
        $tableOption = $this->options['table'] ?? null;

        // if (empty($tableOption) || $tableOption === 'channels') {
        //     // Channel
        //     $channels = $this->loadSeedData('channels.json');
        //     foreach ($channels as $channel) {
        //         \Api\Models\Social\Channel::create($channel);
        //     }
        // }
    }

    public function createTable($tableName, $columnNames)
    {    
        DB::schema()->create($tableName, function (Blueprint $table) use($columnNames) {
            $table->increments('id')->comment('PK');
            $table->unsignedBigInteger('content_id')->nullable()->comment('콘텐츠ID');  

            foreach($columnNames as $columnName)
            {
                if( $columnName =='f_23' ||  $columnName =='f_24' ){
                    $table->text($columnName)->nullable();
                }else{
                    $table->string($columnName, 4000)->nullable();
                }      
            }     
            $table->timestamps();
            $table->softDeletes();
        });

        //DB::statement("ALTER TABLE platforms COMMENT='SNS 플랫폼'");
        DB::statement("COMMENT ON TABLE ".$tableName." IS 'MIGRATION'");
        return true;
    }

    public function loadExcelData($filePath, $tableName, $startRow, $isUpdate = 0)
    {
        // /test/위치
        $migDataPath = dirname(__DIR__) . DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'test'.DIRECTORY_SEPARATOR;
        $filePath =  $migDataPath . $filePath;
        //set_time_limit(600);
        //$uploadedFiles = request()->getUploadedFiles();

        $extension ='xlsx';

        $pathInfo = new FilePath($filePath);
        $filename = $pathInfo->getFilename();


        $isExist = DB::table("ALL_OBJECTS")->where('OBJECT_TYPE', 'TABLE')->where('OBJECT_NAME', $tableName)->first();
 
        $allowExtensionMap = array(
            'xls' => 'Xls',
            'xlsx' => 'Xlsx'
        );


        $reader = PhpSpreadsheet\IOFactory::createReader($allowExtensionMap[$extension]);
        //$reader->setReadDataOnly(TRUE);
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getSheet(0);
        $highestRow = $worksheet->getHighestRow(); // e.g. 10
        $highestColumn = $worksheet->getHighestColumn(); // e.g 'F'
        $highestColumnIndex = PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn); // e.g. 5


        $prefixCol = 'f_';
        $columnNames = [];
        for ($col = 1; $col <= $highestColumnIndex; ++$col)
        {
            $columnNames [] = $prefixCol.$col;
        }
       // dd($columnNames);
       if ($isUpdate == 0) {
           $this->createTable($tableName, $columnNames);
       }
       $dataRowNum = $startRow;
        $colNameRowNum = $dataRowNum -1;

        for ($row = 1; $row <= $highestRow; ++$row) {

            if ( $row <= $colNameRowNum ) {
                continue;
            }
            if ($dataRowNum <= $row) {
                $createData = [];
                //$createData['tmpr_yn'] = 'Y';
                for ($col = 1; $col <= $highestColumnIndex; ++$col) {

                    $value = (string)$worksheet->getCellByColumnAndRow($col, $row)->getValue();
                    $columnName = $col;
                    //$columnName = empty($colMap[$col - 1]) ? null : $colMap[$col - 1];
                    if (!empty($columnName)) {
                        $createData[$prefixCol.$columnName] = $value;
                    }
                }
                dump($createData);
                if(empty($createData['f_1'])){
                    break;
                }
                $isRow = DB::table($tableName)->where('f_1',  $createData['f_1'] )->first();
                if(empty($isRow)){
                    DB::table($tableName)->insert($createData);
                }else{
                    DB::table($tableName)->where('f_1',  $createData['f_1'] )->update($createData);
                }
            }
        }
        dd($tableName);
    }
}
