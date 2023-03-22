<?php
namespace Api\Support\Commands;

use Illuminate\Support\Str;
use \Api\Support\Helpers\DatabaseHelper;
use \Api\Support\Helpers\MetadataMapper;
use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * 워크플로우를 통한 작업이 필요한경우 
 */
class TaskMigration extends Command
{

    protected function configure()
    {
        $this->setName('mig:task')
            ->setDescription('task Migration.')
            ->addOption('code', 'c', InputOption::VALUE_REQUIRED, 'code')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'limit');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        
        require_once __DIR__.'/../../../lib/config.php';
                
        dump( date("Y-m-d H:i:s").' - start');

        $settings = [
            'logging' => false,
            'connections' => [
                [
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
                ]
            ],
        ];
        $capsule = \Api\Support\Helpers\DatabaseHelper::getConnection($settings);
        
        $container = app()->getContainer();
        $contentService = new \Api\Services\ContentService($container);
        $taskService = new \Api\Services\TaskService($container);
        //$type = Str::upper($input->getOption('id'));
        $code = $input->getOption('code');
        $limit = $input->getOption('limit');
        $user = new \Api\Models\User();
        $user->user_id ='admin';

        if($code == 'thumb'){
            //274245 대상 
           // SELECT instt FROM BC_CONTENT c JOIN BC_USRMETA_CONTENT uc ON c.CONTENT_ID=uc.USR_CONTENT_ID WHERE c.IS_DELETED='N' AND uc.INSTT LIKE '%c1352159%';
            //--c1352159 => c1790387
            $query = DB::table('BC_CONTENT as c')
            ->join('BC_MEDIA as m', 'c.content_id', '=', 'm.content_id')
            ->where("c.IS_DELETED",'N')
            ->where("m.storage_id",'118')        
            ->where("m.status",'0')        
            ->selectRaw("c.content_id,c.is_deleted,c.title,m.media_id,m.status,m.path")->orderBy('content_id','desc');

            $totalCount = $query->count();
            // dd($query->toSql());
            dump('total : '.$totalCount );
            $container['logger']->info('total : '.$totalCount);
            //$start = 0;
            $page = 1;
            $perPage = 500;
            $createdCount = 0;
            $num = 0;

            while (true) {
                $records = $query->simplePaginate($perPage, ['*'], 'page', $page);
    
                if (count($records) === 0) {
                    dump($end);
                    break;
                }
                dump( 'page: '.$page );
            
                foreach ($records as $row => $record) {              
                    $numCnt = $row + 1 + ( ($page - 1) * $perPage );
                                  
                    $contentId = $record->content_id;
                    $mediaId = $record->media_id;

                    
                    $num++;
               
                    DB::table('bc_media')->where('media_id',$mediaId)->update(['status'=>1]);
                 
                    dump($numCnt.':'.$contentId);
                    $channel = 'create_thumb';
                    $task = $taskService->getTaskManager();
                    $task->set_priority(600);
                    $taskId = $task->start_task_workflow($contentId, $channel, $user->user_id );
                    $container['logger']->info('contentId : '.$contentId.' / '.$taskId);

                        
                    if( !empty($limit) ){
                        if($limit <= $num){
                            $container['logger']->info('limit : '.$limit);
                            dd('limit: '.$limit);
                        }
                    }
                }
                dump(  date("Y-m-d H:i:s").'] '.$createdCount . ' records created...' . $createdCount/$totalCount*100 . '% complete.' );
                //$container['logger']->info(date("Y-m-d H:i:s").'] '.$createdCount . ' records created...' . $createdCount/$totalCount*100 . '% complete.');
                $createdCount = $perPage * $page;
                $page++;
            }   
         
        }else if($code == 'info'){

            $query = DB::table('BC_CONTENT as c')
            ->join('BC_MEDIA as m', 'c.content_id', '=', 'm.content_id')
            ->leftJoin(DB::raw("(SELECT * FROM bc_task WHERE TYPE='130' ) t"), 'c.content_id', '=', 't.src_content_id')
            ->where("c.IS_DELETED",'N')
            ->where("c.category_id",'204')  
            ->where("c.status",'2') 
            ->where("c.bs_content_id",'506')  
            ->where("m.media_type", 'original')
            ->whereNull("t.task_id")
            ->selectRaw("c.content_id,c.is_deleted,c.title,m.media_id,m.status,m.path")->orderBy('c.content_id','desc');
   
            $totalCount = $query->count();
            // dd($query->toSql());
            dump('total : '.$totalCount );
            $container['logger']->info('total : '.$totalCount);
            //$start = 0;
            $page = 1;
            $perPage = 500;
            $createdCount = 0;
            $num = 0;
            
            while (true) {
                $records = $query->simplePaginate($perPage, ['*'], 'page', $page);
    
                if (count($records) === 0) {
                    dump($end);
                    break;
                }
                dump( 'page: '.$page );
            
                foreach ($records as $row => $record) {              
                    $numCnt = $row + 1 + ( ($page - 1) * $perPage );
                                  
                    $contentId = $record->content_id;
                    $mediaId = $record->media_id;

                    $num++;
             
                    dump($numCnt.':'.$contentId);
                    $channel = 'mig_mediainfo';
                    $task = $taskService->getTaskManager();
                    $task->set_priority(600);
                    $arrayParamInfo = [[
                        'force_src_media_id' => 	$mediaId
                    ]];
                    $taskId = $task->start_task_workflow($contentId, $channel, $user->user_id , $arrayParamInfo);
                    $container['logger']->info('contentId : '.$contentId.' / '.$taskId);

                        
                    if( !empty($limit) ){
                        if($limit <= $num){
                            $container['logger']->info('limit : '.$limit);
                            dd('limit: '.$limit);
                        }
                    }
                }
                dump(  date("Y-m-d H:i:s").'] '.$createdCount . ' records created...' . $createdCount/$totalCount*100 . '% complete.' );
                //$container['logger']->info(date("Y-m-d H:i:s").'] '.$createdCount . ' records created...' . $createdCount/$totalCount*100 . '% complete.');
                $createdCount = $perPage * $page;
                $page++;
            }   
        }
       
        //dump( date("Y-m-d H:i:s").' - '.print_r($result,true) );
        dump( date("Y-m-d H:i:s").' - '.' end.' );
    }
}
