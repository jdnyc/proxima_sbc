<?php
namespace Api\Support\Commands;

use Api\Models\User;
use Api\Models\FolderMng;
use Api\Types\CategoryType;
use Illuminate\Support\Str;
use Api\Models\ArchiveMedia;
use Api\Types\BsContentType;
use Api\Types\UdContentType;
use Api\Models\FolderMngUser;
use \Api\Services\DTOs\MediaDto;
use \Api\Services\DTOs\ContentDto;
use Api\Models\FolderMngOwnerUser;
use Api\Services\FolderMngService;
use Api\Services\DTOs\FolderMngDto;
use Api\Support\Helpers\MetadataMapper;
use \Api\Services\DTOs\ContentStatusDto;
use \Api\Services\DTOs\ContentSysMetaDto;
use \Api\Services\DTOs\ContentUsrMetaDto;
use ProximaCustom\core\FolderAuthManager;
use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * cms 사용자 마이그레이션
 * 추가 / 업데이트 갱신
 */
class AuthMigration extends Command
{
    public $isDebug = true;

    protected function configure()
    {
        $this->setName('mig:auth')
            ->setDescription('auth migration.')
            ->addOption('start', 's', InputOption::VALUE_OPTIONAL, 'Start')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Limit')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'Type');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {        
        require_once __DIR__.'/../../../lib/config.php';        
        //require_once __DIR__.'/../../../lib/bootstrap.php';

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


        //db 접속 재정의
        $settings = [
            'logging' => false,
            'connections' => [
                //$devSettings,
                \Api\Support\Helpers\DatabaseHelper::getSettings(),
                \Api\Support\Helpers\DatabaseHelper::getSettings('bis'),
               // $migSettings,
              //  $cmsSettings
            ],
        ];       
        $capsule = \Api\Support\Helpers\DatabaseHelper::getConnection($settings);

        //컨테이너
        $container = app()->getContainer();

        dump("start: ".date("Y-m-d H:i:s"));
        $container['logger']->info("start: ".date("Y-m-d H:i:s"));

        $start = Str::upper($input->getOption('start'));
        $limit = Str::upper($input->getOption('limit'));
        $type = Str::upper($input->getOption('type'));

         
        
        dump( "param");
        dump( "start: "."{$start}");
        dump( "limit: "."{$limit}");
        dump( "total: "."{$total}");


        $migrationService = new \ProximaCustom\services\MigrationService();            
        $userService = new \Api\Services\UserService($container);
        $sysCodeService = new \Api\Services\SysCodeService($container);
        $programService = new \Api\Services\BisProgramService($container);
        $folderMngService = new FolderMngService($container);
        

        $service = [
            'migrationService' => $migrationService,
            'contentService' => $contentService,
            'mediaService' => $mediaService,
            'mediaSceneService' => $mediaSceneService,
            'timecodeService' => $timecodeService
        ];


        $authFolderCode = $sysCodeService->codeMapByCodeType('AUTH_FOLDER');
        dump($authFolderCode);
        $storageFsname = $authFolderCode->get('fsname');
        dump($storageFsname);
        $isTest = $authFolderCode->get('is_test');
        dump($isTest);
        $authOwner = $authFolderCode->get('auth_owner');
        dump($authOwner);

        //스크래치
        $vol1_mid_path = $authFolderCode->get('mid_path_scratch');
        $vol1_prefix_path = '/Volumes/' . $storageFsname . '/' . $vol1_mid_path;
        $pathAuth = $authFolderCode->get('path_auth_scratch');

        $authMng = new \ProximaCustom\core\FolderAuthManager([
            'storageFsname' =>  $storageFsname,
            'vol1_prefix_path' => $vol1_prefix_path,
            'vol1_mid_path' => $vol1_mid_path,
            'pathAuth' => $pathAuth
        ]);
        //$users = $authMng->getUsersFromOD();

         if ($type == 'PRODUCT' || $type == 'NEWS') {
                $groups = $authMng->getGroupsFromOD();
         }
        //dump($groups);
        //기존 od 폴더 그룹과 
        // for($i=0; $i<count($groups);$i++)
        // {
        //     dump($groups[$i]['group_name']);
        //     dump($groups[$i]['member_list']);
        //     if($i >= 5){
        //     break;
        //     }
        // }

        if($type == 'PRODUCT'){


            $query = FolderMng::query();
            $query->where('PARENT_ID', 2);
            //where('USING_YN','Y')->
            $folders = $query->get();

            $totalCount = $folders->count();

            foreach($folders as $key =>  $folder){
                dump($group['group_name']);
                $folderPath = $folder->folder_path;
                $pgmId = $folder->pgm_id;
                $folderId = $folder->id;
                
            
                foreach($groups as $group){
                    if( $folderPath  == $group['group_name'] ){
                        //일치
                        dump( $folderPath );
                        $pdUsers = [];

                        if( !empty($pgmId) ){               
                            $pgmInfo = $programService->search('pgm_id', $pgmId)->first();
                            if($pgmInfo->director){
                                $pdLists = trim($pgmInfo->director);
                                $pdLists = explode(',', $pdLists);
                                $pdUsers = [];
                                foreach($pdLists as $pdNm ){
                                    $pdUsers [] = $userService->findByUserNm($pdNm);
                                }  
                            }
                            if( !empty($pdUsers) && count($pdUsers) > 2){
                                dd($pdUsers);
                            }
                        
                            //종방여부
                            $dvsYn = $pgmInfo->dvs_yn;
                            $useYn = $pgmInfo->channels->use_yn;
                            $folder->dvs_yn = $dvsYn;
                            $folder->use_yn = $useYn;
                            dump( '$dvsYn:'.$dvsYn  );
                            dump( '$useYn:'.$useYn );
                            if( $dvsYn == 'Y' || $useYn == 'N' ){
                                $folder->using_yn = 'N';
                            }
                            $folder->save();
                            dump('저장: '. $folderPath );

                            if( $folder->using_yn == 'Y' ){
                                //사용중인경우
                                //스크래치 폴더 생성
                                //pd권한 추가
                                if (!empty($pdUsers)) {
                                    foreach($pdUsers as $pdUser){
                                        $findUser = User::where('DEL_YN', 'N')->where('user_id', $pdUser->user_id)->first();
                                        if ($findUser) {
                                            //  dump('found: '. $findUser->user_nm.':'.$findUser->user_id);
                                            if ($findUser->user_id) {
                                                $folderMngUser = FolderMngUser::where('folder_id', $folder->id)->where('user_id', $findUser->user_id)->first();
                                                if (empty($folderMngUser)) {
                                                    $folderMngUser              = new FolderMngUser();
                                                    $folderMngUser->folder_id   = $folder->id;
                                                    $folderMngUser->user_id     = $findUser->user_id;
                                                    $folderMngUser->save();
        
                                                    dump('생성 ' .$folderPath.' - '. $findUser->user_id);
                                                    //od 계정 생성?
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        foreach($group['member_list'] as $odUser){
                            $findUser = User::where('DEL_YN','N')->where('user_id' , $odUser )->first();
                            if($findUser){
                            //  dump('found: '. $findUser->user_nm.':'.$findUser->user_id);
                                if( $findUser->user_id ){
                                    $folderMngUser = FolderMngUser::where('folder_id' , $folder->id )->where('user_id', $findUser->user_id)->first();
                                    if( empty($folderMngUser) ){
                                        $folderMngUser              = new FolderMngUser();
                                        $folderMngUser->folder_id   = $folder->id;
                                        $folderMngUser->user_id     = $findUser->user_id;
                                        $folderMngUser->save();

                                        dump( '생성 ' .$folderPath.' - '. $findUser->user_id );
                                        //od 계정 생성?
                                    }
                                }
                            }else{
                            //  dump('not found: '. $odUser);
                            }
                        }
                    }
                }
                //if( $key > 3 ) break;
            }
            
        }else if($type == 'NEWS'){
            $query = FolderMng::query();
            $query->where('PARENT_ID', 3);
            //where('USING_YN','Y')->
            $folders = $query->get();

            $totalCount = $folders->count();

            $newsGroups = [
                "news_monday",
                "news_tuesday",
                "news_wednesday",
                "news_thursday",
                "news_friday",
                "news_saturday",
                "news_sunday"
            ];
            
            foreach($folders as $key =>  $folder){
                dump($group['group_name']);
                $folderPath = $folder->folder_path;
                $pgmId = $folder->pgm_id;
                $folderId = $folder->id;

                foreach($groups as $group){
          
                    if( in_array($group['group_name'], $newsGroups ) ){      
                        //일치
                        dump( $folderPath );

                        foreach($group['member_list'] as $odUser){
                            $findUser = User::where('DEL_YN','N')->where('user_id' , $odUser )->first();
                            if($findUser){
                            //  dump('found: '. $findUser->user_nm.':'.$findUser->user_id);
                                if( $findUser->user_id ){
                                    $folderMngUser = FolderMngUser::where('folder_id' , $folder->id )->where('user_id', $findUser->user_id)->first();
                                    if( empty($folderMngUser) ){
                                        $folderMngUser              = new FolderMngUser();
                                        $folderMngUser->folder_id   = $folder->id;
                                        $folderMngUser->user_id     = $findUser->user_id;
                                        $folderMngUser->save();

                                        dump( '생성 ' .$folderPath.' - '. $findUser->user_id );
                                        //od 계정 생성?
                                    }
                                }
                            }else{
                            //  dump('not found: '. $odUser);
                            }
                        }
                    }
                }
                //if( $key > 3 ) break;
            }
            
        }

        if($type == 'SCRATCH'){

            $storageFsname = config('auth_config')['fsname'];
            $linkage = config('auth_config')['linkage']; 
            $authOwner = config('auth_config')['auth_owner'];
             //스크래치
             $vol1_mid_path = config('auth_config')['mid_path_scratch'];
             $vol1_prefix_path = '/Volumes/' . $storageFsname . '/' . $vol1_mid_path;
             $pathAuth = config('auth_config')['path_auth_scratch'];

             $authMng = new FolderAuthManager([
                'storageFsname' =>  $storageFsname,
                'vol1_prefix_path' => $vol1_prefix_path,
                'vol1_mid_path' => $vol1_mid_path,
                'pathAuth' => $pathAuth
            ]);


            $query = FolderMng::query();
            $query->where('PARENT_ID', 2);
            $query->where('USING_YN','Y');
            $folders = $query->get();

            $user = new User();
            $user->user_id = 'admin';

            foreach($folders as $idx=> $folder){
                $pgmId = $folder->pgm_id;
                $authGroupNm = 'group_'.$folder->folder_path;
                $folderPath = $folder->folder_path;
                $folderPathNm = $folder->folder_path_nm;

                dump($idx.'/'.$pgmId);

                $newFolder = FolderMng::where('PARENT_ID', 4)->where('USING_YN','Y')->where('folder_path',$folderPath)->first();
            
                //폴더 추가
                if( empty($newFolder) ){

                    $data = [
                        'parent_id'=> 4,
                        'step'=> 2,
                        'folder_path_nm'=> $folderPathNm,
                        'folder_path'=> $folderPath,
                        'chmod'=> '775',
                        'owner_cd'=> 'ingest',
                        'group_cd'=> $authGroupNm,
                        'quota'=> 1,
                        'quota_unit'=> 'TB',
                        'grace_period'=> 1,
                        'grace_period_unit'=> 'Minutes',                    
                        'ntcn_yn'=> 'N',
                        'using_yn' => 'Y'
                    ];
                    
                    $dto = new FolderMngDto($data);  
    
                    //스크래치 폴더 생성 
                    //그룹생성
                    $return = $authMng->createGroupFromOD($authGroupNm, $folderPathNm);
                    //폴더생성 권한 부여
                    $return = $authMng->makeFolderSetAuthor($folderPath, $authGroupNm, $authOwner);
                    //쿼터 부여
                    $return = $authMng->createQuota([
                        'fsname'             => $authMng::$storageFsname,
                        'type'               => 'dir',
                        'directory'          => $authMng::$vol1_mid_path . '/' . $folderPath
                    ]);
                    $return = $authMng->updateQuota([
                        'gracePeriod_unit'   => $dto->grace_period_unit,
                        'softLimit_unit'     => $dto->quota_unit,
                        'hardLimit_unit'     => $dto->quota_unit,
                        'fsname'             => $authMng::$storageFsname,
                        'softLimit'          => $dto->quota,
                        'hardLimit'          => $dto->quota,
                        'gracePeriod'        => $dto->grace_period,
                        'type'               => 'dir',
                        'directory'          => $authMng::$vol1_mid_path . '/' . $folderPath
                    ]);
                    $newFolder = $folderMngService->create($dto, $user);
                }
            };
        }

        //프로그램 폴더와 동일하게 스크래치 생성
        if($type == 'SCRATCH_USER'){

            $storageFsname = config('auth_config')['fsname'];
            $linkage = config('auth_config')['linkage']; 
            $authOwner = config('auth_config')['auth_owner'];
             //스크래치
             $vol1_mid_path = config('auth_config')['mid_path_scratch'];
             $vol1_prefix_path = '/Volumes/' . $storageFsname . '/' . $vol1_mid_path;
             $pathAuth = config('auth_config')['path_auth_scratch'];

             $authMng = new FolderAuthManager([
                'storageFsname' =>  $storageFsname,
                'vol1_prefix_path' => $vol1_prefix_path,
                'vol1_mid_path' => $vol1_mid_path,
                'pathAuth' => $pathAuth
            ]);


            $query = FolderMng::query();
            $query->where('PARENT_ID', 2);
            $query->where('USING_YN','Y');
            $folders = $query->get();

            $user = new User();
            $user->user_id = 'admin';

            foreach ($folders as$idx=> $folder) {
                $pgmId = $folder->pgm_id;
                $folderPath =  $folder->folder_path;

                dump($idx.'/'.$pgmId);
             
                $newFolder = FolderMng::where('PARENT_ID', 4)->where('USING_YN', 'Y')->where('folder_path', $folderPath)->first();
            
                if (!empty($newFolder)) {                
                                
                    $mapUsers = [];                        
                    //pd 매핑
                    if (!empty($pgmId)) {
                        $pgmInfo = $programService->search('pgm_id', $pgmId)->first();
                        if ($pgmInfo->director) {
                            $pdLists = trim($pgmInfo->director);
                            $pdLists = explode(',', $pdLists);
                            $pdUsers = [];
                            foreach ($pdLists as $pdNm) {
                                $pdUsers [] = $userService->findByUserNm($pdNm);
                            }
                        }

                        //pd 계정도 사용자 매핑
                        if (!empty($pdUsers)) {
                            foreach ($pdUsers as $pdUser) {
                                $findUser = User::where('DEL_YN', 'N')->where('user_id', $pdUser->user_id)->first();
                                if ($findUser) {
                                    $mapUsers [] = $findUser->user_id;

                                    $folderMngUser = FolderMngOwnerUser::where('folder_id', $newFolder->id)->where('user_id', $findUser->user_id)->first();
                                    if (empty($folderMngUser)) {
                                        $folderMngUser              = new FolderMngOwnerUser();
                                        $folderMngUser->folder_id   = $newFolder->id;
                                        $folderMngUser->user_id     = $findUser->user_id;
                                        $folderMngUser->save();
                                    }
                                }
                            }
                        }
                    }
                    

                    //사용자 매핑
                    $users = FolderMngUser::where('folder_id', $folder->id)->get();
                  
                    if (!empty($users)) {
                        foreach ($users  as $mapUser) {
                            $mapUsers [] = $mapUser->user_id;
                        }
                    }

                    if (!empty($newFolder)) {
                        $addList = [];
                        $delList = [];
                        if (!empty($mapUsers)) {
                            foreach ($mapUsers as $userId) {
                                $userInfo = $authMng::findUserFromOD($userId);
                                if ($userInfo) {
                                    $addList[] = $userInfo;
                                }
                            }
                        }
            
                        if (!empty($addList) || !empty($delList)) {
                            //그룹에 계정 추가
                            $return = $authMng::groupMapUserFromOD($newFolder->group_cd, $addList, []);
                        }                
                
                        $r = $folderMngService->saveUser($newFolder->id, $mapUsers, $user);
                    }
                }
            }       
        }

   
        dump('total : '.$totalCount );
      

        dump( date("Y-m-d H:i:s").'] '.' 100% complete.' );
        $container['logger']->info(date("Y-m-d H:i:s").'] '.' 100% complete.');
    }
}
