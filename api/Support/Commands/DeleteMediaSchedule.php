<?php
namespace Api\Support\Commands;

use Illuminate\Support\Str;
use Api\Types\ArchiveStatus;
use \Api\Support\Helpers\DatabaseHelper;
use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class DeleteMediaSchedule extends Command
{
    protected function configure()
    {
        $this->setName('schedule:delete_media')
            ->setDescription('delete')
            ->addOption('content', 'c', InputOption::VALUE_OPTIONAL, 'ContentId')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'type')
            ->addOption('user', 'u', InputOption::VALUE_OPTIONAL, 'userId')
            ->addOption('time', 'i', InputOption::VALUE_OPTIONAL, 'time');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        require_once __DIR__.'/../../../lib/config.php';
        //운영 DB
        // $defaultSettings = [
        //             'name' => 'default',
        //             'driver' => 'oracle',
        //             'host' => '10.10.50.110',
        //             'database' => 'cms',
        //             'username' => 'ktvcms',
        //             'password' => 'xhdqkd67890#',
        //             'port' => '1521',
        //             'charset' => 'AL32UTF8',
        //             'collation' => 'utf8_unicode_ci',
        //             'prefix' => '',
        //             'server_version' => '11g'
        //         ];
        $settings = [
            'logging' => false,
            'connections' => [
                //$defaultSettings,
                \Api\Support\Helpers\DatabaseHelper::getSettings(),
            ],
        ];

        $capsule = \Api\Support\Helpers\DatabaseHelper::getConnection($settings);
    
        $contentId = $input->getOption('content');
        $type = Str::upper($input->getOption('type'));
        $time = $input->getOption('time');

        $regUserId = $input->getOption('user');
        
        dump(date("Y-m-d H:i:s").' - start');

        $container = app()->getContainer();

        $search =  $container['searcher'];
        $contentService = new \Api\Services\ContentService($container);
 
        if (empty($time)) {
            $expiredDate = date('Ymd', strtotime('-1 days'));
        } else {
            $expiredDate = $time;
        }

        if ($type == 'ARCHIVE') {
            $udContentIds =  [2,9,3];
            $deleteMsg = "아카이브된 영상 니어라인 자동 삭제";
        }else if($type == 'ARCHIVE_PERCENT'){
            $udContentIds =  [1,7];
            $deleteMsg = "아카이브된 영상 니어라인 용량 자동 삭제";

            $nearlinePath = '/mnt/nearline';

            $targetLimitPercent = config('delete_policy')['nearline'];

            if(empty($targetLimitPercent)){
                $targetLimitPercent = 80;
            }
            
            $nearlineTotal = disk_total_space($nearlinePath);
            $nearlineFree = disk_free_space($nearlinePath);
            $nearlineUsed = $nearlineTotal - $nearlineFree;		
            if (!empty($nearlineUsed)) {
                $nearlineUsedPercent = $nearlineUsed / $nearlineTotal * 100;
                if ($nearlineUsedPercent < $targetLimitPercent) {
                    //지정용량보다 사용량이 적은경우 중지
                    dd('delete skip');
                }
            }
        }else{
            dd('empty type');
        }
        $status = [2];
        $isDeleted = 'N';
        $query = \Api\Models\Content::query();
        $query->where('BC_CONTENT.is_deleted', '=', $isDeleted);
        $query->whereIn('BC_CONTENT.status', $status);
        $query->whereIn('BC_CONTENT.ud_content_id', $udContentIds);
        $query->join('BC_CONTENT_STATUS as s', "BC_CONTENT.CONTENT_ID", '=', "s.CONTENT_ID");
        $query->join('BC_USRMETA_CONTENT as u', "BC_CONTENT.CONTENT_ID", '=', "u.USR_CONTENT_ID");

        $query->join('ARCHIVE_MEDIAS as a', "BC_CONTENT.CONTENT_ID", '=', "a.CONTENT_ID");
        
        //분류 제작/뉴스/텔레시네
        $query->where(function ($query) {
            $query->where('BC_CONTENT.category_full_path', 'like', '/0/100/200%')
                  ->orWhere('BC_CONTENT.category_full_path', 'like', '/0/100/201%')
                  ->orWhere('BC_CONTENT.category_full_path', 'like', '/0/100/205%');
        });
        
        //원본이 존재할때
        $query->join('bc_media as m', "BC_CONTENT.CONTENT_ID", '=', "m.CONTENT_ID");
        $query->where('m.media_type', '=', 'archive');
        $query->where('m.status', '=', '0');
        $query->whereNull('m.flag');

        $query->where('m.REG_TYPE', '!=', 'dtl_restore_near_mig');

        if ($type == 'ARCHIVE') {
            $query->where(function ($query) {
                $query->where('m.EXPIRED_DATE', '>', '99981231000000')
                      ->orWhere('m.EXPIRED_DATE', '<', date("YmdHis"));
            });
        }else if($type == 'ARCHIVE_PERCENT'){
            $query->whereRaw("( substr(M.EXPIRED_DATE,0,8) >= '99981231' or substr(M.EXPIRED_DATE,0,8) < '".$expiredDate."')");
        }

        $query->orderBy("m.EXPIRED_DATE");
        $query->orderBy("BC_CONTENT.CREATED_DATE");
      
        $query->select('s.ARCHIVE_STATUS',
        's.ARCHV_STTUS',
        's.DTL_ARCHV_STTUS',
        's.RESTORE_AT',
        's.RESTORE_STTUS' ,
        's.BFE_VIDEO_ID',        
        'BC_CONTENT.CREATED_DATE',
        'BC_CONTENT.EXPIRED_DATE',
        'BC_CONTENT.UD_CONTENT_ID',
        'BC_CONTENT.TITLE',
        'BC_CONTENT.content_id',
        'BC_CONTENT.reg_user_id',
        'm.media_id',
        'm.path'
        );
        //$query->select('BC_CONTENT.content_id');
        $totalCount = $query->count();
     
      
        dump('total : '.$totalCount );
 
        //dd($query->toSql());
         $page = 1;
         $perPage = 50;
         $createdCount = 0;

                      
         $user = new \Api\Models\User();
         $user->user_id ='admin';

         $contentService = new \Api\Services\ContentService(app()->getContainer());
         $mediaService = new \Api\Services\MediaService(app()->getContainer());
         $taskService = new \Api\Services\TaskService(app()->getContainer());
                        
         $task = $taskService->getTaskManager();                
         $task->set_priority(400);
         //$task->setStatus('scheduled');

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
                dump( $record->path );
   
                $content_id = $record->content_id;
                $mediaId = $record->media_id;
 
                $media = $mediaService->findOrFail($mediaId);             

                if( $media->status == 0 && empty($media->flag) ){
                    //삭제 대상이 아닌 목록만
                    //삭제 워크플로우 수행       
                    
                    if( $type == 'ARCHIVE' || $type == 'ARCHIVE_PERCENT' ){
                        //BC_DELETE_CONTENT 입력 삭제요청
                        $medias = $mediaService->getMediaByContentId($content_id);
                        foreach($medias as $media)
                        {
                            if( $media->status == 0 && empty($media->flag) ){
                                //삭제 대상이 아닌 목록만
                                //삭제 워크플로우 수행
                                $mediaType = $media->media_type;
                                    
                                //삭제 워크플로우                    
                                if ($mediaType == 'archive') {
                                    $mediaService->deleteReady($media->media_id);                                    
                                    $channel ='delete_media_'.$mediaType;
                                    $originalTaskId = $task->start_task_workflow($content_id, $channel, $user->user_id );
                                    $contentStatus = \Api\Models\ContentStatus::where('content_id', $content_id)->first();
                                    if($contentStatus){ 
                                        $contentStatus->archive_status =  ArchiveStatus::DTL;
                                        $contentStatus->save();
                                    }
                                    dump($content_id.' : '. $originalTaskId );
                                }
                            }
                        }                   
                    }
                }
             }
             dump(  date("Y-m-d H:i:s").'] '.$createdCount . ' records created...' . $createdCount/$totalCount*100 . '% complete.' );
             
             //우선 1000건 제한
                      
            // DB::table('bc_task')->where('status', 'scheduled')->where('type', '=' , '100')->update([
            //     'status' => 'queue',
            //     'priority' => '400'
            // ]);
             dd('50건');
             $createdCount = $perPage * $page;
             $page++;
         }
         
        //  DB::table('bc_task')->where('status', 'scheduled')->where('type', '=' , '100')->update([
        //     'status' => 'queue',
        //     'priority' => '400'
        // ]);
         dump( date("Y-m-d H:i:s").'] '.' 100% complete.' );
         die();
    }
}
