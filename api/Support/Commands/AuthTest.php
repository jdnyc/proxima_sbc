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
 * ODS 서버변경으로 인한 테스트
 * 
 */
class AuthTest extends Command
{
    public $isDebug = true;

    protected function configure()
    {
        $this->setName('mig:auth_test')
            ->setDescription('auth migration.')
            ->addOption('start', 's', InputOption::VALUE_OPTIONAL, 'Start')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Limit')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'Type')
            ->addOption('user_id', 'u', InputOption::VALUE_OPTIONAL, 'user')
            ->addOption('group_cd', 'g', InputOption::VALUE_OPTIONAL, 'group')
            ->addOption('id', 'i', InputOption::VALUE_OPTIONAL, 'id');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {        
        require_once __DIR__.'/../../../lib/config.php';        
        //require_once __DIR__.'/../../../lib/bootstrap.php';

        //db 접속 재정의
        $settings = [
            'logging' => false,
            'connections' => [
                //$devSettings,
                \Api\Support\Helpers\DatabaseHelper::getSettings(),
            ],
        ];       
        $capsule = \Api\Support\Helpers\DatabaseHelper::getConnection($settings);

        //컨테이너
        $container = app()->getContainer();

        dump("start: ".date("Y-m-d H:i:s"));
        $container['logger']->info("start: ".date("Y-m-d H:i:s"));

        $start = Str::upper($input->getOption('start'));
        $limit = Str::upper($input->getOption('limit'));
        $type = $input->getOption('type');

        $group_cd = empty($input->getOption('group_cd'))? 'test0727':$input->getOption('group_cd');
        $user_id = empty($input->getOption('user_id'))? 'gemiso02':$input->getOption('user_id');
        $id = empty($input->getOption('id'))? '99':$input->getOption('id');

         
        
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
    
        $storageFsname = $authFolderCode->get('fsname');
      
        $isTest = $authFolderCode->get('is_test');
  
        $authOwner = $authFolderCode->get('auth_owner');
  

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


        
        $authMng::$telnet_server_ip = '10.10.51.12';
        $authMng::$telnet_user = 'ktv';//VOL1_TELNET_USER
        $authMng::$telnet_pwd = 'admin12345';//VOL1_TELNET_PWD
        $authMng::$vol1_prefix_path = '/Volumes/MAIN/CMS/test';
        //public static $vol1_prefix_path = '/Volumes';

        $authMng::$vol1_mid_path = 'CMS/test';

        $authMng::$pathAuth = 755;

        $authMng::$mdcUrl = 'http://10.10.50.11:81/sws/v2/quota/snquota?';
        $authMng::$mdcsystemUrl =  'http://10.10.50.11:81/sws/v2/system/filesystem/info?';
        $authMng::$storageFsname = 'MAIN';

        $authMng::$ldap_server_ip = '10.10.50.234';
        $authMng::$ldap_domain = 'dc=New-ods1,dc=nps,dc=ktv,dc=go,dc=kr';
        $authMng::$dir_user_name = 'diradmin';
        $authMng::$dir_pwd = 'diradmin';

        $authMng::$ldap_telnet_user = 'admin';//VOL1_TELNET_USER
        $authMng::$ldap_telnet_pwd = 'admin12345';//VOL1_TELNET_PWD

        $authMng::$ldap_default_group_id = '20';//VOL1_TELNET_PWD

        //public static $ldap_home_dir = '/Users';
        $authMng::$ldap_home_dir = '/Users';

        dump($authMng::$telnet_server_ip);
        dump($authMng::$vol1_prefix_path);
        dump($authMng::$vol1_mid_path);
        dump($authMng::$ldap_server_ip);
        dump($authMng::$ldap_home_dir);
        $folderPathNm = $group_cd;
        $authGroupNm = 'group_'.$group_cd;
        $userId = $user_id;
        
        dump($type);

        if($type == 'create_group'){       
            //폴더 생성
            dump($authGroupNm);
            dump($folderPathNm);
            $return = $authMng->createGroupFromOD($authGroupNm, $folderPathNm);
            dump($return);
        }

        if ($type == 'create_folder') {
            $return = $authMng->makeFolderSetAuthor($folderPath, $authGroupNm, $authOwner);
            dump($return);
        }

        if($type == 'create_user'){
                    
            //계정 생성
            $userInfo = $authMng::createUserFromOD($userId,$userId,$id,$userId);
            dump($return);
        }

        if ($type == 'map_user') {
            
            $userInfo = $authMng::findUserFromOD($userId);
            if ($userInfo) {
                $addList [] = $userInfo;
                $return = $authMng::groupMapUserFromOD($authGroupNm, $addList, []);
                dump($return);
            }          

        }
   
        dump('total : '.$totalCount );
      

        dump( date("Y-m-d H:i:s").'] '.' 100% complete.' );
        $container['logger']->info(date("Y-m-d H:i:s").'] '.' 100% complete.');
    }
}
