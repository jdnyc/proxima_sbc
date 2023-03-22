<?php
namespace Api\Support\Commands;

use Api\Models\Task;
use Api\Models\DivaTape;
use Illuminate\Support\Str;
use Api\Models\ArchiveMedia;
use Api\Models\DivaTapeInfo;
use Api\Types\ArchiveStatus;
use \Api\Support\Helpers\DatabaseHelper;
use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class ArchiveMigSchedule extends Command
{
    protected function configure()
    {
        $this->setName('schedule:archive_mig')
            ->setDescription('archive')
            ->addOption('content', 'c', InputOption::VALUE_OPTIONAL, 'ContentId')
            ->addOption('udcontent', 'u', InputOption::VALUE_OPTIONAL, 'UdcontentId')
            ->addOption('count', 'cnt', InputOption::VALUE_OPTIONAL, 'count')
            ->addOption('dir', 'r', InputOption::VALUE_OPTIONAL, 'dir');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        
        require_once __DIR__.'/../../../lib/config.php';

        $settings = [
            'logging' => false,
            'connections' => [
                \Api\Support\Helpers\DatabaseHelper::getSettings(),
                \Api\Support\Helpers\DatabaseHelper::getSettings('diva')
            ],
        ];
        dump( $settings);
        $capsule = \Api\Support\Helpers\DatabaseHelper::getConnection($settings);
    
        $contentId = $input->getOption('content');
        $targetCount = (int)$input->getOption('count');

        //제한 건수
        $targetCount = empty($targetCount) ? config('schedule.archive_mig_cnt'): $targetCount;

        //설정도 0 이면 종료
        if(empty($targetCount)){
            dd(date("Y-m-d H:i:s").' - skip targetCount : '.$targetCount);
        }
     
        //실행 건수
        $exeCount = 0;

        $dir = $input->getOption('dir');
        dump(date("Y-m-d H:i:s").' - start');

        $container = app()->getContainer();

        $search =  $container['searcher'];        
        $contentService = new \Api\Services\ContentService($container);
 
         $page = 1;
         $perPage = 2000;
         $createdCount = 0;

                      
         $user = new \Api\Models\User();
         $user->user_id ='admin';

         
         //테잎 조회
         $taskService = new \Api\Services\TaskService($container);
         $archiveService = new \Api\Services\ArchiveService($container);

         $task = $taskService->getTaskManager();    
         
                
       $currentCount = Task::where('type','110')->where('destination','dtl_archive_each')->whereIn('status',['queue','processing','scheduled'])->get()->count();
        
       dump('currentCount:'.$currentCount);

        if($currentCount > 0){
            dd('working die');
        }
        
         $deleteArchiveMedia = DB::table('Z_MIG_CMS as z')
         ->join("BC_TASK AS t",function($join){
            //$join->where('IS_DELETED', '=', 'N');
             $join->on('z.TASK_ID', '=', 't.TASK_ID');
         })
         ->leftJoin('bc_task as tt',function($join){
            $join->on('t.SRC_CONTENT_ID', '=', 'tt.SRC_CONTENT_ID')
            ->where('tt.TYPE', '=', '91')
            ->on('tt.ROOT_TASK', '=', 't.TASK_ID');           
         })
         ->leftJoin('bc_task as archive',function($join){
            $join->on('z.CONTENT_ID', '=', 'archive.src_content_id')
            ->where('archive.TYPE', '=', '110')
            ->where('archive.DESTINATION', '=', 'dtl_archive_each')
            ->whereRaw("substr(a.ARCHIVE_ID,9,1)='R'");
            $join->join('ARCHIVE as a',function($subJoin){
                $subJoin->on('archive.TASK_ID', '=', 'a.TASK_ID');
                $subJoin->whereRaw("substr(a.ARCHIVE_ID,9,1)='R'");
            });           
         })
         ->leftJoin('bc_media as ma',function($join){
            $join->on('t.SRC_CONTENT_ID', '=', 'tt.SRC_CONTENT_ID')
            ->where('ma.MEDIA_TYPE', '=', 'archive')
            ->on('z.CONTENT_ID', '=', 'ma.CONTENT_ID');           
         })
         ->where('IS_WORK','=','Y')->where('archive.status','=','complete')->where('ma.status','=',0)
         ->whereBetween('archive.COMPLETE_DATETIME',[date("YmdHis",strtotime('-30 days')) , date("YmdHis",strtotime('-2 days'))])
         ->select('ma.content_id','ma.MEDIA_ID','ma.PATH','ma.status','ma.CREATED_DATE')
         ->orderBy('t.task_id');
        $deleteContents = $deleteArchiveMedia->paginate(100);

        if(!empty($deleteContents)){
            foreach($deleteContents as $deleteContent)
            {
                //continue;
                $task->set_priority(400);
                $channel ='delete_media_archive';
                $deleteTaskId = $task->start_task_workflow($deleteContent->content_id, $channel, $user->user_id );
                dump($deleteContent->content_id.':'.$deleteTaskId);
            }
        }
     
        //dump('delMedias',$medias); 
           
                // $task->set_priority(400);
                // $channel ='delete_media_archive';
                // $deleteTaskId = $task->start_task_workflow($delMedia->content_id, $channel, $user->user_id );
                // dump($delMedia->content_id.':'.$deleteTaskId);
          
        
       //  dd($deleteArchiveMedia->dd());

        $query = DB::table('DP_TAPE_MIG as z');
        $query->where('z.MEDIA_CODE','V')->where('z.OF_GROUP_NAME','backup')->whereNull('z.IS_COMPLETE')->orderBy('z.OF_BARCODE');
        $query->join("DP_TAPES AS t",function($join){
            $join->where('tape_se', '=', 'diva');
            $join->where('ta_group', '=', 'backup');
             $join->on('z.OF_BARCODE', '=', 't.ta_barcode');
         });
        //  $query = DivaTape::query();
        //  $query->where('tape_se','diva');
        //  $query->where('ta_group','backup');
        //  $query->orderBy('ta_barcode', 'asc');
      
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
                 dump('numCnt:'.$exeCount.'/'.$createdCount);
                 //소산 테잎 제외
                 if($record->disprs_at == 'Y'){
                    continue;
                 }
                 $barcode = $record->ta_barcode;
                 dump('barcode:'.$barcode);
                 $medias = DivaTapeInfo::query();
                 $medias->where('of_barcode', $barcode);
                 $mediaInfo = $medias->orderBy('of_object_name', 'asc')->get();
         
                 //         SELECT am.OBJECT_NAME,c.title, m.FILESIZE, cs.RESTORE_AT FROM 
                 // ARCHIVE_MEDIAS am 
                 // JOIN BC_CONTENT c ON am.CONTENT_ID=c.CONTENT_ID 
                 // JOIN BC_CONTENT_STATUS cs ON c.CONTENT_ID=cs.CONTENT_ID
                 // JOIN BC_MEDIA m ON c.CONTENT_ID=m.CONTENT_ID
                 // WHERE m.MEDIA_TYPE='original' and  am.OBJECT_NAME='20130703V77601' AND am.DELETED_AT IS NULL ;
                 dump('total:'.count($mediaInfo)  );
                 $cmsCount = 0;

                 $cmsWorkingCount = 0;
                 $cmsMigCount = 0;
                 foreach($mediaInfo as $media)
                 {
                    $createdCount++;

                    $objectName =  $media['of_object_name'];
                     $mediaQ = ArchiveMedia::where('object_name',$objectName)
                     ->join("BC_CONTENT c",function($join){
                        $join->where('IS_DELETED', '=', 'N');
                         $join->on('ARCHIVE_MEDIAS.content_id', '=', 'c.content_id');
                     })->join("BC_MEDIA m",function($join){
                         $join->where('media_type', '=', 'original');
                         $join->on('c.content_id', '=', 'm.content_id');
                     })->join("BC_CONTENT_STATUS cs",function($join){
                        $join->whereNotNull('cs.bfe_video_id');
                        $join->on('c.content_id', '=', 'cs.content_id');
                     })->select('ARCHIVE_MEDIAS.OBJECT_NAME','c.title','c.content_id','m.media_id', 'm.FILESIZE', 'cs.RESTORE_AT');
                     $detail = $mediaQ->first();
                    // $mediaQ->whereRaw("c.content_id NOT IN (SELECT CONTENT_ID FROM Z_MIG_CMS WHERE IS_WORK='Y')");
                    // dd($mediaQ->dd());
                    // $detail = $mediaQ->first();
                   //  dd($detail);
                    // AND c.content_id NOT IN (SELECT CONTENT_ID FROM Z_MIG_CMS WHERE IS_WORK='Y')
                    // dd($detail->toSql());
                     //$result['detail'] = $detail;
                     if( empty($detail->content_id) ){
                        
                        $isMigCompInfo = $archiveService->getMigTable()->where('mediaid',$objectName)->first();
                        if(!empty( $isMigCompInfo )){
                            $cmsCount++;
                            if($isMigCompInfo->is_work =='Y'){
                                $cmsWorkingCount++;
                                if( !empty($isMigCompInfo->mediaid_new) ){
                                    $cmsMigCount++;
                                }
                            }
                        }
                        continue;
                     }
                     $contentId = $detail->content_id;

                     $cmsCount++;

                     $migInfo = $archiveService->mediaMigInfo($contentId);
                     dump( 'is_work: '.$migInfo->is_work  );

                     //마이그레이션 대상 목록
                     if( !empty($migInfo) && ($migInfo->is_work == 'Y') ){
                        $cmsWorkingCount++;
                        if( strstr($detail->object_name,'R') ){
                            //아카이브 완료
                            $cmsMigCount++;
                        }
                        continue;
                     }
                   //  dd( $migInfo->content_id  );
                     //

                     //니어라인 존재
                     //니어라인 변환 교체
                     //신규 아카이브 미디어ID 신규 발급 V => R
                     //V는 테이블 이동 ARCHIVE_TRD_MEDIAS


                     //없으면 니어라인 리스토어

                     //니어라인 변환

                     //
                     $taskId = $archiveService->restoreNear($contentId , $user);
                     dump( 'taskId:'.$taskId );
                     $updateData = ['is_work'=> 'Y'];
                     if(!empty($taskId)){
                        $updateData['task_id'] = $taskId;
                     }
                     $archiveService->getMigTable()->where('content_id',$contentId)->update($updateData);                     

                     $exeCount++;

                     $media['content_id'] =  $detail['content_id'];
                     dump( 'of_object_name:'.$media['of_object_name']  );

                     if($targetCount <= $exeCount){
                        //제한 건수만 처리되고 종료
                       dd('complete:'.$exeCount.'/'.$createdCount);
                    }
                 }

                if( $cmsMigCount == $cmsCount ){
                    $updateRow = [
                        'is_complete' => 'Y',
                        'cms_cnt' => $cmsCount,
                        'working_cnt' => $cmsWorkingCount,
                        'archive_cnt' => $cmsMigCount
                    ];
                }else{
                    $updateRow = [
                        'cms_cnt' => $cmsCount,
                        'working_cnt' => $cmsWorkingCount,
                        'archive_cnt' => $cmsMigCount
                    ];
                }
                 
                if($record->is_complete != 'Y'){
                    $r = DB::table('DP_TAPE_MIG as z')->where('z.MEDIA_CODE','V')->where('z.OF_GROUP_NAME','backup')->where('z.OF_BARCODE',$barcode)
                    ->update($updateRow );
                }
   
                //$userId =  $record->reg_user_id;            

              //  die();
             }
             dump(  date("Y-m-d H:i:s").'] '.$exeCount . ' records created...' );
 
             $page++;
         }
         dump( date("Y-m-d H:i:s").'] '.' 100% complete.' );
         die();
    }
}
