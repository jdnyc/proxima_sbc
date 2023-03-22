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


class DeleteSchedule extends Command
{
    protected function configure()
    {
        $this->setName('schedule:delete')
            ->setDescription('delete')
            ->addOption('content', 'c', InputOption::VALUE_OPTIONAL, 'ContentId')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'type')
            ->addOption('time', 'i', InputOption::VALUE_OPTIONAL, 'time');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        require_once __DIR__.'/../../../lib/config.php';
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
        $settings = [
            'logging' => false,
            'connections' => [
                $defaultSettings,
                //\Api\Support\Helpers\DatabaseHelper::getSettings(),
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
 
        if (empty($time)) {
            $expiredDate = date('Ymd', strtotime('-1 days'));
        } else {
            $expiredDate = $time;
        }

        $udContentIds =  [1,2,7,9,3];

        if ($type == 'RESTORE') {
            $status = [0,2];
            $deleteMsg = "리스토어 영상 자동 삭제";
        }else if ($type == 'ORIGINAL') {
            $status = [0,2];
            $deleteMsg = "아카이브한 원본영상 자동 삭제";
        }else if ($type == 'UD_ORIGIN') {
            $udContentIds =  [1];
            $status = [0,2,5,3];
            $deleteMsg = "원본 콘텐츠 유형 14일 만료 후 삭제";
            //아카이브 된 콘텐츠 제외
            //아카이브 요청이나 아카이브 된 콘텐츠가 아닌 건 삭제
            $expiredDate = date('Ymd', strtotime('-14 days'));
        }else{
            dd('empty type');
        }

        $isDeleted = 'N';
        $query = \Api\Models\Content::query();
        $query->where('BC_CONTENT.is_deleted', '=', $isDeleted);
        $query->whereIn('BC_CONTENT.status', $status);
        $query->whereIn('BC_CONTENT.ud_content_id', $udContentIds);
        $query->join('BC_CONTENT_STATUS as s', "BC_CONTENT.CONTENT_ID", '=', "s.CONTENT_ID");
        $query->join('BC_USRMETA_CONTENT as u', "BC_CONTENT.CONTENT_ID", '=', "u.USR_CONTENT_ID");
        
        //분류 제작/뉴스/텔레시네
        $query->where(function ($query) {
            $query->where('BC_CONTENT.category_full_path', 'like', '/0/100/200%')
                  ->orWhere('BC_CONTENT.category_full_path', 'like', '/0/100/201%')
                  ->orWhere('BC_CONTENT.category_full_path', 'like', '/0/100/205%');
        });

        //원본이 존재할때
        $query->join('bc_media as m', "BC_CONTENT.CONTENT_ID", '=', "m.CONTENT_ID");
        $query->where('m.media_type', '=', 'original');
        $query->where('m.status', '=', '0');

        if( $type == 'RESTORE' ){
            //아카이브가 존재할때
            $query->join('bc_media as ma', "BC_CONTENT.CONTENT_ID", '=', "ma.CONTENT_ID");
            $query->where('ma.media_type', '=', 'archive');
            $query->where('ma.filesize', '>', 0);                
            //아카이브 상태
            $query->whereIn('s.ARCHIVE_STATUS', [1,2,3]);

            //원본 만료일이 지난경우
            $query->where('BC_CONTENT.EXPIRED_DATE', '<=', $expiredDate);

            //리스토어 파일
            $query->where('s.RESTORE_AT', '=', 1);
            $query->where('s.RESTORE_STTUS', '=', 'complete');

              
            $query->orderBy('BC_CONTENT.EXPIRED_DATE', 'asc');
     

        }else if( $type == 'ORIGINAL' ){
            //아카이브가 존재할때
            $query->join('bc_media as ma', "BC_CONTENT.CONTENT_ID", '=', "ma.CONTENT_ID");
            $query->where('ma.media_type', '=', 'archive');
            $query->where('ma.filesize', '>', 0);                
            //아카이브 상태
            $query->whereIn('s.ARCHIVE_STATUS', [1,2,3]);

            $query->where('BC_CONTENT.CREATED_DATE', '>', '20191216000000');
            $query->where('s.DTL_ARCHV_STTUS', '=', 'complete');
        
            $query->where(function ($query) {
                $query->where('s.RESTORE_AT', '=', 0)
                      ->orWhereNull('s.RESTORE_AT');
            });

            $query->where('s.DTL_ARCHV_END_DT', '<', date('Ymd000000') );

            $query->whereNotIn( 'BC_CONTENT.content_id',[
                1045288,
                1045069,
                1045210,
                1045205,
                1045263,
                1045408,
                1045409,
                1045325,
                1045099,
                1045097,
                1045094,
                1045076,
                1045072,
                1049850,
                1049840,
                1045258,
                1050149,
                1045224,
                1045223,
                1045214,
                1045212,
                1044367,
                1041354,
                1041391,
                1041448,
                1041520,
                1041486,
                1045209,
                1045208,
                894916,
                995698,
                995694,
                924427,
                1041325,
                1041326,
                1041328,
                1041329,
                1041333,
                1041334,
                1041335,
                1041336,
                1041337,
                1041338,
                1041339,
                1041340,
                1041341,
                1041343,
                1041344,
                1041345,
                1041346,
                1041347,
                1041348,
                1041350,
                1041352,
                1041353,
                1041355,
                1041356,
                1041358,
                1041359,
                1041360,
                1041362,
                1041363,
                1041364,
                1041365,
                1041367,
                1041368,
                1041369,
                1041370,
                1041371,
                1041372,
                1041373,
                1041374,
                1041375,
                1041376,
                1041377,
                1041379,
                1041380,
                1041381,
                1041382,
                1041383,
                1041384,
                1041385,
                1041386,
                1041388,
                1041389,
                1041390,
                1041392,
                1041393,
                1041394,
                1041395,
                1041396,
                1041397,
                1041399,
                1041400,
                1041401,
                1041403,
                1041404,
                1041405,
                1041406,
                1041407,
                1041409,
                1041410,
                1041411,
                1041412,
                1041413,
                1041415,
                1041416,
                1041417,
                1041418,
                1041419,
                1041421,
                1041422,
                1041426,
                1041460,
                1041408,
                1041427,
                1041428,
                1041429,
                1041430,
                1041431,
                1041432,
                1041433,
                1041434,
                1041435,
                1041436,
                1041437,
                1041438,
                1041439,
                1041440,
                1041441,
                1041442,
                1041443,
                1041444,
                1041446,
                1041447,
                1041449,
                1041450,
                1041451,
                1041452,
                1041453,
                1041454,
                1041456,
                1041457,
                1041458,
                1041459,
                758100,
                1041461,
                1041462,
                1041463,
                1041464,
                1041465,
                1041466,
                1041467,
                1041468,
                1041469,
                1041470,
                1041471,
                1041472,
                1041473,
                1040289,
                1041474,
                1041475,
                1041476,
                1041477,
                1041478,
                1041479,
                1041480,
                1041482,
                1041483,
                1041484,
                1041485,
                1041487,
                1041488,
                1041489,
                1041490,
                1041420,
                1041455,
                1041491,
                1041492,
                1041493,
                1041494,
                1041495,
                1041496,
                1041497,
                1041498,
                1041499,
                1041500,
                1041501,
                1041503,
                1041504,
                1041505,
                1041506,
                1041507,
                1041508,
                1041509,
                1041510,
                1041511,
                1041513,
                1041514,
                1041515
            ]);

            $query->orderBy('BC_CONTENT.content_id', 'asc');
        }else if( $type == 'UD_ORIGIN' ){

            $query->leftJoin(DB::raw("( SELECT nps_content_id,req_status,req_type FROM TB_REQUEST WHERE req_type='archive' AND (req_status=1 or req_status=2) ) R "), "BC_CONTENT.CONTENT_ID", '=' ,"R.NPS_CONTENT_ID" );
            $query->whereNull('r.NPS_CONTENT_ID');

            $query->leftJoin('archive_medias as am', "BC_CONTENT.CONTENT_ID", '=', "am.CONTENT_ID");
            $query->whereNull('am.CONTENT_ID');


            //원본 만료일이 지난경우
            $query->where('BC_CONTENT.CREATED_DATE', '<=', $expiredDate);

            $query->orderBy('BC_CONTENT.CREATED_DATE', 'asc');
        }

        //dd($query->dd());

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
         $perPage = 1000;
         $createdCount = 0;

                      
         $user = new \Api\Models\User();
         $user->user_id ='admin';

         $contentService = new \Api\Services\ContentService(app()->getContainer());
         $mediaService = new \Api\Services\MediaService(app()->getContainer());
         $taskService = new \Api\Services\TaskService(app()->getContainer());
                        
         $task = $taskService->getTaskManager();                
         $task->set_priority(400);
         $task->setStatus('scheduled');

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
                    
                    if( $type == 'UD_ORIGIN' ){
                        //BC_DELETE_CONTENT 입력 삭제요청
                        $contentDelete = $contentService->deleteRequest($content_id, $deleteMsg, $user);

                        $medias = $mediaService->getMediaByContentId($content_id);
                        foreach($medias as $media)
                        {
                            $mediaService->deleteReady($media->media_id);
                            if( $media->status == 0 && empty($media->flag) ){
                                //삭제 대상이 아닌 목록만
                                //삭제 워크플로우 수행
                                $mediaType = $media->media_type;
                                    
                                //삭제 워크플로우                    
                                if ($mediaType == 'original') {
                                    $channel ='delete_media_'.$mediaType;
                                    $originalTaskId = $task->start_task_workflow($content_id, $channel, $user->user_id );

                                }else if($mediaType == 'proxy'){
                                    $channel ='delete_media_'.$mediaType;
                                    $taskId = $task->start_task_workflow($content_id, $channel, $user->user_id );

                                }else if($mediaType == 'proxy360'){
                                    $channel ='delete_media_'.$mediaType;
                                    $taskId = $task->start_task_workflow($content_id, $channel, $user->user_id );
                                }else if($mediaType == 'proxy2m1080'){
                                    $channel ='delete_media_'.$mediaType;
                                    $taskId = $task->start_task_workflow($content_id, $channel, $user->user_id );
                                }else if($mediaType == 'proxy15m1080'){
                                    $channel ='delete_media_'.$mediaType;
                                    $taskId = $task->start_task_workflow($content_id, $channel, $user->user_id );
                                }else if($mediaType == 'publish'){

                                }else if($mediaType == 'audio'){

                                }else if($mediaType == 'yt_thumb'){

                                }else if($mediaType == 'thumb'){
                                    $channel ='delete_media_'.$mediaType;
                                    $taskId = $task->start_task_workflow($content_id, $channel, $user->user_id );
                                }
                            }
                        }

                        $contentService->delete($content_id, $user);
                        
                        //삭제 승인
                        $contentService->deleteAccept($contentDelete->id, $originalTaskId, $user);
                    }else{
                        $contentDelete = $mediaService->deleteRequest($mediaId, $deleteMsg, $user);

                        $mediaType = $media->media_type;
                        $mediaService->deleteReady($media->media_id);
                        //삭제 워크플로우
                        if ($mediaType == 'original') {
                            $channel ='delete_media_'.$mediaType;
                            $originalTaskId = $task->start_task_workflow($content_id, $channel, $user->user_id);
                        }
                        //삭제 승인
                        $contentService->deleteAccept($contentDelete->id, $originalTaskId, $user);
                    }
                }
              //  die();
             }
             dump(  date("Y-m-d H:i:s").'] '.$createdCount . ' records created...' . $createdCount/$totalCount*100 . '% complete.' );
             
             //우선 1000건 제한
                      
            DB::table('bc_task')->where('status', 'scheduled')->where('type', '=' , '100')->update([
                'status' => 'queue',
                'priority' => '400'
            ]);
             dd('1000건');
             $createdCount = $perPage * $page;
             $page++;
         }
         
         DB::table('bc_task')->where('status', 'scheduled')->where('type', '=' , '100')->update([
            'status' => 'queue',
            'priority' => '400'
        ]);
         dump( date("Y-m-d H:i:s").'] '.' 100% complete.' );
         die();
    }
}
