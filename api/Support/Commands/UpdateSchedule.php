<?php
namespace Api\Support\Commands;

use Illuminate\Support\Str;
use \Api\Support\Helpers\DatabaseHelper;
use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class UpdateSchedule extends Command
{
    protected function configure()
    {
        $this->setName('schedule:update_meta')
            ->setDescription('update Meta')
            ->addOption('content', 'c', InputOption::VALUE_OPTIONAL, 'ContentId')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'type')
            ->addOption('time', 'i', InputOption::VALUE_OPTIONAL, 'time');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        require_once __DIR__.'/../../../lib/config.php';
        //운영 DB
        $settings = [
            'logging' => false,
            'connections' => [
                \Api\Support\Helpers\DatabaseHelper::getSettings(),
            ],
        ];

        $capsule = \Api\Support\Helpers\DatabaseHelper::getConnection($settings);
    
        $contentId = $input->getOption('content');
        $type = Str::upper($input->getOption('type'));
        $time = $input->getOption('time');
        
        dump(date("Y-m-d H:i:s").' - start');

        $container = app()->getContainer();

        $search =  $container['searcher'];
        $contentService = new \Api\Services\ContentService($container);

        $query = \Api\Models\Content::query();

        $udContentIds =  [1,2,7,9,3];
        $isDeleted = 'N';

        if ($type == 'EMBARGO') {
            //엠바고 여부 및 엠바고해제일시가 지난 엠바고 조회
            //지났다면 엠바고 여부 N 변경
            //포털 업데이트 
            //SELECT uc.EMBG_RELIS_DT,uc.EMBG_AT,c.CONTENT_ID,c.title,c.UD_CONTENT_ID,c.STATUS FROM bc_content c JOIN BC_USRMETA_CONTENT uc ON (c.content_id=uc.usr_content_id) WHERE c.IS_DELETED='N' and uc.EMBG_AT='Y';
            $status = [0,2];
        }else{
            dd('empty type');
        }
        $query->where('BC_CONTENT.is_deleted', '=', $isDeleted);
        $query->whereIn('BC_CONTENT.status', $status);
        $query->whereIn('BC_CONTENT.ud_content_id', $udContentIds);
        $query->join('BC_CONTENT_STATUS as s', "BC_CONTENT.CONTENT_ID", '=', "s.CONTENT_ID");
        $query->join('BC_USRMETA_CONTENT as u', "BC_CONTENT.CONTENT_ID", '=', "u.USR_CONTENT_ID");
        $query->where('u.EMBG_AT', 'Y');
        $query->select(
            'u.EMBG_RELIS_DT',
            'u.EMBG_AT',
            'BC_CONTENT.CONTENT_ID',
            'BC_CONTENT.title',
            'BC_CONTENT.UD_CONTENT_ID',
            'BC_CONTENT.BS_CONTENT_ID',
            'BC_CONTENT.STATUS'
        );
        
        $query->orderBy('BC_CONTENT.CONTENT_ID', 'asc');
       
        $totalCount = $query->count();
     
      
        dump('total : '.$totalCount );
 
        //dd($query->toSql());
         $page = 1;
         $perPage = 1000;
         $createdCount = 0;

                      
         $user = new \Api\Models\User();
         $user->user_id ='admin';

         $contentService = new \Api\Services\ContentService(app()->getContainer());
         $logService = new \Api\Services\LogService(app()->getContainer());
        
         while (true) {
            //unset($records);
             dump(date("Y-m-d H:i:s").' - start'.$query->toSql());
             $records = $query->simplePaginate($perPage, ['*'], 'page', $page);
             dump(date("Y-m-d H:i:s").' - end');
             if (count($records) === 0) {
                 dump('end');
                 break;
             }
             dump( 'page: '.$page );
             foreach ($records as $row => $record) {               
                 $numCnt = $row + 1 + ( ($page - 1) * $perPage );
               //  dump( $record->media_id );
                //dump( $record );
                $contentId = $record->content_id;
                //엠바고해제일시 지나면 업데이트
                if( $record->embg_relis_dt < date("YmdHis") ){
                    dump( $contentId.':'.$record->embg_relis_dt );
                    $usrMeta = [
                        'embg_at' => 'N'
                    ] ;

                    $content = $contentService->updateUsingArray($contentId, [],[],[],$usrMeta , $user);

                    $logData = [
                        'action' => 'edit',
                        'description' => '엠바고 자동해제',
                        'content_id' => $contentId,
                        'bs_content_id' => $record->bs_content_id,
                        'ud_content_id' => $record->ud_content_id
                    ];
                    $log = $logService->create($logData, $user);
                    $logDetailData = [
                        'action' =>'edit',
                        'usr_meta_field_code' => 'embg_at',
                        'new_contents' => $usrMeta['embg_at'],
                        'old_contents' => $record->embg_at
                    ];
                    $logDetail = $logService->createDetail($log->log_id, $logDetailData);
                }
             }
             dump(  date("Y-m-d H:i:s").'] '.$createdCount . ' records created...' . $createdCount/$totalCount*100 . '% complete.' );
             
             //우선 1000건 제한
             dd('1000건');
             $createdCount = $perPage * $page;
             $page++;
         }
         dump( date("Y-m-d H:i:s").'] '.' 100% complete.' );
         die();
    }
}
