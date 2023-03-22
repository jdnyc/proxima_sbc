<?php
namespace Api\Support\Commands;

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


/**
 * cms 사용자 마이그레이션
 * 추가 / 업데이트 갱신
 */
class UserMigration extends Command
{
    public $isDebug = true;

    protected function configure()
    {
        $this->setName('mig:user')
            ->setDescription('User migration.')
            ->addOption('start', 's', InputOption::VALUE_OPTIONAL, 'Start')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Limit')
            ->addOption('total', 'tt', InputOption::VALUE_OPTIONAL, 'Total');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {        
        require_once __DIR__.'/../../../lib/config.php';        
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
            'logging' => true,
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

        $start = Str::upper($input->getOption('start'));
        $limit = Str::upper($input->getOption('limit'));
        $total = Str::upper($input->getOption('total'));

         
        
        dump( "param");
        dump( "start: "."{$start}");
        dump( "limit: "."{$limit}");
        dump( "total: "."{$total}");
    
       
        $query = $this->getSelectQuery()->orderByRaw('KMS_CNF_USER_TB.USERID');

        DB::table('KMS_CNF_RELUSERGROUP_TB')
        ->whereIn('GROUPID', [95,94,97,96,98,99,130] )
        ->where('userid', $memberId )
        ->selectRaw(' USERID,
        GROUPID,
        ( SELECT caption FROM KMS_CNF_GROUP_TB WHERE KMS_CNF_RELUSERGROUP_TB.GROUPID=GROUPID ) dept_nm');

        $migrationService = new \ProximaCustom\services\MigrationService();            
        $userService = new \Api\Services\UserService($container);
    

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

                $record->groups = 3;

                if( $record->user_id == 'admin' ) continue;
          
                $deptInfo = DB::table('KMS_CNF_RELUSERGROUP_TB','cms')
                ->whereIn('GROUPID', [95,94,97,96,98,99,130] )
                ->where('userid', $record->member_id )
                ->selectRaw(' USERID,GROUPID,( SELECT caption FROM KMS_CNF_GROUP_TB WHERE KMS_CNF_RELUSERGROUP_TB.GROUPID=GROUPID ) dept_nm')->first();
 
                if(!empty($deptInfo) ){
                    $record->dept_nm = $deptInfo->dept_nm;
                }
                $record->member_id = null;
                $user = $userService->findByUserId($record->user_id);
 
                if( !empty( $user ) ){
                    //업데이트
                    $user->password = $record->encrypt_password;
                    $user->save();

                    $user = $userService->update($user->member_id, $record);
                   
                }else{
                    //신규 
                    $user = $userService->create($record);
                } 

                dump('user_id: '.$user->user_id );
              //  die();
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

    function getSelectQuery(){
        $select = '
            KMS_CNF_USER_TB.USERID AS member_id,
            KMS_CNF_USER_TB.USERNAME AS user_id,
            KMS_CNF_USER_TB.REALNAME AS user_nm,
            KMS_CNF_USER_TB.PASSWORD AS encrypt_password,
            KMS_CNF_USEREX_TB.DELETE_YN AS del_yn,
            KMS_CNF_USEREX_TB.handphon AS phone,
            KMS_CNF_USEREX_TB.EMAILADDRESS AS email,
            KMS_CNF_USEREX_TB.CREATETIME AS created_date,
            (SELECT username FROM KMS_CNF_USER_TB WHERE userid=CREATEUSER) AS creator_id,
            KMS_CNF_USEREX_TB.MODIFYTIME AS updated_date,
            (SELECT username FROM KMS_CNF_USER_TB WHERE userid=MODIFYUSER) AS updater_id
            ';
            
            $selectQuery = DB::table('KMS_CNF_USER_TB', 'cms')
            ->join('KMS_CNF_USEREX_TB', 'KMS_CNF_USER_TB.USERID', '=', 'KMS_CNF_USEREX_TB.USERID')->selectRaw($select);
        return $selectQuery;
    }
}
