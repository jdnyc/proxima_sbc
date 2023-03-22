<?php
namespace Api\Support\Commands;

use Api\Models\ArchiveTask;
use Illuminate\Support\Str;
use \Api\Support\Helpers\DatabaseHelper;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class ArchiveDeleteSchedule extends Command
{
    protected function configure()
    {
        $this->setName('schedule:archive_delete')
            ->setDescription('archive_delete')
            ->addOption('content', 'c', InputOption::VALUE_OPTIONAL, 'ContentId')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'limit')
            ->addOption('count', 'cnt', InputOption::VALUE_OPTIONAL, 'count');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        
        require_once __DIR__.'/../../../lib/config.php';

        $settings = [
            'logging' => false,
            'connections' => [
                \Api\Support\Helpers\DatabaseHelper::getSettings(),
            ],
        ];

        $capsule = \Api\Support\Helpers\DatabaseHelper::getConnection($settings);

        $logger = new \Proxima\core\Logger('ArchiveDeleteSchedule');
      
    
        $contentId = $input->getOption('content');
        $limit = $input->getOption('limit'); 
        $count = $input->getOption('count');   
        dump(date("Y-m-d H:i:s").' - start');
        $logger->info(date("Y-m-d H:i:s").' - start');

        $container = app()->getContainer();

        $search =  $container['searcher'];        
        $contentService = new \Api\Services\ContentService($container);

        $query = \Api\Models\Task::query();
        $query->where('type', '=', '150');              
        $query->where('status','=', 'scheduled' );
      
        $query->orderBy('task_id', 'asc');
        $totalCount = $query->count();
        dump('total : '.$totalCount );
        $logger->info('total : '.$totalCount);
         $page = 1;
         $perPage = empty($limit)? 1 : $limit;
         $createdCount = 0;

         $numCnt = 0;

         $workCnt = 0;

         $count = empty($count)? 1: $count;
                      
         $user = new \Api\Models\User();
         $user->user_id ='admin';
         $reqComment = "아카이브 삭제";

         
         $contentService = new \Api\Services\ContentService($app->getContainer());
                
         $archiveService = new \Api\Services\ArchiveService($app->getContainer());

         $divaApiService = new \Api\Services\DivaApiService($container);
      
         while (true) {
            //unset($records);
             dump(date("Y-m-d H:i:s").' - start'.$query->toSql());
             $records = $query->simplePaginate($perPage, ['*'], 'page', $page);
             dump(date("Y-m-d H:i:s").' - end');
             if (count($records) === 0) {
                $logger->info(' count($records) === 0 end');
                 dump('end');
                 break;
             }
             dump( 'page: '.$page );
             foreach ($records as $row => $record) {               
                $numCnt = $row + 1 + ( ($page - 1) * $perPage );
                dump( 'numCnt: '.$numCnt );
                $logger->info('numCnt: '.$numCnt );
                $contentId = $record->src_content_id;
                $taskId = $record->task_id;
                DB::table('bc_task')->where('task_id', $taskId)->update([
                    'status' =>  'processing',
                    'progress' => 0,
                    'start_datetime' => date("YmdHis"),
                    'assign_ip' => get_server_param('REMOTE_ADDR', '127.0.0.1')
                ]);

                $taskStatus = 'error';

                $archiveTask = ArchiveTask::where('task_id',$record->task_id)->first();
                $objectName = $archiveTask->archive_id;
              
                if( empty($objectName) ){
                    dump('empty objectName: '.$contentId);
                    $logger->info('empty objectName: '.$contentId);
                    DB::table('bc_task')->where('task_id', $taskId)->update([
                        'status' =>  $taskStatus
                    ]);
           
                    continue;
                }

                try {
                
                //dd( $record );
                    //$objectName =
                    $logger->info(date("Y-m-d H:i:s").'] deleteObject:'.$objectName);
                    $result = $divaApiService->deleteObject($objectName);
                }catch(RequestException $e)
                {
                    $logger->error(date("Y-m-d H:i:s").'] deleteObject:'.$e->getMessage());
                }
                $logger->info(date("Y-m-d H:i:s").'] '.print_r($result,true));
                if( !empty($result['requestId'] ) ){
                    $archiveTask->reqnum = $result['requestId'];
                    $archiveTask->save();
                }

                if( $result['statusCode'] == '1000' || $result['statusCode'] == '1009' ){
                    $archiveMedia = \Api\Models\ArchiveMedia::query();
                    $archiveMedia->where('content_id',$contentId )->where('object_name', $objectName)->delete();
                    $taskStatus = 'complete';
                }

                DB::table('bc_task')->where('task_id', $taskId)->update([
                    'status' =>  $taskStatus,
                    'progress' => 100,
                    'start_datetime' => date("YmdHis"),
                    'complete_datetime' => date("YmdHis"),
                    'assign_ip' => get_server_param('REMOTE_ADDR', '127.0.0.1')
                ]);
       
                // DB::table('bc_task')->where('task_id', $record->task_id)->update([
                //      'status' => 'complete',
                //      'START_DATETIME'
                //      'COMPLETE_DATETIME'
                // ]);

                $workCnt++;

                if($workCnt >= $count){
                    $logger->info('workCnt: '.$workCnt);
                    dd('workCnt: '.$workCnt);
                }
             }
             dump(  date("Y-m-d H:i:s").'] '.$createdCount . ' records created...' . $createdCount/$totalCount*100 . '% complete.' );
             $logger->info(date("Y-m-d H:i:s").'] '.$createdCount . ' records created...' . $createdCount/$totalCount*100 . '% complete.');
             $createdCount = $perPage * $page;
             $page++;
             die();
         }         
         dump( date("Y-m-d H:i:s").'] '.' 100% complete.' );
         $logger->info(date("Y-m-d H:i:s").'] '.' 100% complete.');
         die();
    }
}
