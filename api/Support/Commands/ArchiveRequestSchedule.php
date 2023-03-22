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


class ArchiveRequestSchedule extends Command
{
    protected function configure()
    {
        $this->setName('schedule:archive-request')
            ->setDescription('archive')
            ->addOption('content', 'c', InputOption::VALUE_OPTIONAL, 'ContentId')
            ->addOption('udcontent', 'u', InputOption::VALUE_OPTIONAL, 'UdcontentId')
            ->addOption('time', 'tt', InputOption::VALUE_OPTIONAL, 'time')
            ->addOption('dir', 'r', InputOption::VALUE_OPTIONAL, 'dir');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        
        require_once __DIR__.'/../../../lib/config.php';

           //cms이관용
           $cmsSettings = [
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
                //$cmsSettings,
                \Api\Support\Helpers\DatabaseHelper::getSettings(),
            ],
        ];

        $capsule = \Api\Support\Helpers\DatabaseHelper::getConnection($settings);
    
        $contentId = $input->getOption('content');
        $udContentId = $input->getOption('udcontent');
        $time = Str::upper($input->getOption('time'));
        $dir = $input->getOption('dir');
        dump(date("Y-m-d H:i:s").' - start');

        $container = app()->getContainer();

        $search =  $container['searcher'];        
        $contentService = new \Api\Services\ContentService($container);

        //         --마스터, 뉴스편집본, 클린본, 클립본
        // --뉴스/제작/텔레시네
        // --만료일자 지남 2주
        // --원본 존재, 아카이브 / dtl 없음
        // SELECT am.OBJECT_NAME, ori.PATH,ori.status,ori.flag,ar.PATH,ar.status,ar.flag,s.ARCHIVE_STATUS,s.ARCHV_STTUS,s.DTL_ARCHV_STTUS,s.RESTORE_AT,s.RESTORE_STTUS ,c.CREATED_DATE,c.EXPIRED_DATE,c.UD_CONTENT_ID,c.TITLE,c.content_id,s.BFE_VIDEO_ID
        // FROM bc_content c 
        // JOIN BC_USRMETA_CONTENT u ON (c.content_id=u.usr_content_id)
        // JOIN bc_content_status s ON (c.content_id=s.content_id)
        // JOIN (SELECT * FROM bc_media WHERE media_type='original' ) ori ON (c.content_id=ori.content_id)
        // LEFT OUTER JOIN (SELECT * FROM bc_media WHERE media_type='archive' ) ar ON (c.content_id=ar.content_id)
        // LEFT OUTER JOIN ARCHIVE_MEDIAS am ON (c.content_id=am.content_id)
        // WHERE 
        // 	c.IS_DELETED='N' AND c.status='2' AND c.UD_CONTENT_ID IN ('2','7','9','3' )
        // AND ( c.category_full_path LIKE '/0/100/200%' OR c.category_full_path LIKE '/0/100/201%' OR c.category_full_path LIKE '/0/100/205%'  )
        // AND c.EXPIRED_DATE <= '20191216' AND s.ARCHIVE_STATUS IS NULL AND ori.status=0  ORDER BY c.EXPIRED_DATE,c.CREATED_DATE desc;

        if( empty($time) ){
            $expiredDate = date('Ymd',strtotime('-1 days'));
        }else{
            $expiredDate = $time;
        }
        $status = [0,2];
        $is_deleted= 'N';
        $udContentIds =  [2,7,9,3];
        $query = \Api\Models\Content::query();
        $query->where('BC_CONTENT.is_deleted', '=', $is_deleted);
        $query->whereIn('BC_CONTENT.status', $status);
        $query->whereIn('BC_CONTENT.ud_content_id', $udContentIds );
        $query->join('bc_media', "BC_CONTENT.CONTENT_ID" ,'=', "bc_media.CONTENT_ID"  );
        $query->join('BC_CONTENT_STATUS', "BC_CONTENT.CONTENT_ID" ,'=', "BC_CONTENT_STATUS.CONTENT_ID"  );
        $query->join('BC_USRMETA_CONTENT', "BC_CONTENT.CONTENT_ID", '=' ,"BC_USRMETA_CONTENT.USR_CONTENT_ID"  );
        $query->where('bc_media.media_type','=', 'original' );
        $query->where('bc_media.status','=', '0' );
        $query->where(function ($query) {
            $query->where('BC_CONTENT.category_full_path', 'like', '/0/100/200%')
                  ->orWhere('BC_CONTENT.category_full_path', 'like', '/0/100/201%')
                  ->orWhere('BC_CONTENT.category_full_path', 'like', '/0/100/205%');
        });
        $query->where('BC_CONTENT.EXPIRED_DATE','<=', $expiredDate );
        $query->whereNull('BC_CONTENT_STATUS.ARCHIVE_STATUS' );

        $query->select('BC_CONTENT_STATUS.ARCHIVE_STATUS','BC_CONTENT_STATUS.ARCHV_STTUS','BC_CONTENT_STATUS.DTL_ARCHV_STTUS','BC_CONTENT_STATUS.RESTORE_AT','BC_CONTENT_STATUS.RESTORE_STTUS' ,'BC_CONTENT.CREATED_DATE','BC_CONTENT.EXPIRED_DATE','BC_CONTENT.UD_CONTENT_ID','BC_CONTENT.TITLE','BC_CONTENT.content_id','BC_CONTENT_STATUS.BFE_VIDEO_ID','BC_CONTENT.reg_user_id');
        //$query->select('BC_CONTENT.content_id');
        $totalCount = $query->count();

     
        //$query->leftJoin('bc_content_status', 'bc_content.content_id', '=', 'bc_content_status.content_id');
        //$query->leftJoin('bc_usrmeta_content', 'bc_content.content_id', '=', 'bc_usrmeta_content.usr_content_id');
        //$query->leftJoin('bc_sysmeta_movie', 'bc_content.content_id', '=', 'bc_sysmeta_movie.sys_content_id');

        $query->orderBy('BC_CONTENT.EXPIRED_DATE', 'asc');
      
        dump('total : '.$totalCount );
 
         $page = 1;
         $perPage = 2000;
         $createdCount = 0;

                      
         $user = new \Api\Models\User();
         $user->user_id ='admin';
         $appr_comment = "자동 아카이브 요청";

         
         $contentService = new \Api\Services\ContentService($app->getContainer());
                
         $archiveService = new \Api\Services\ArchiveService($app->getContainer());
      
         while (true) {
            //unset($records);
             dump(date("Y-m-d H:i:s").' - start'.$query->toSql());
             $records = $query->simplePaginate($perPage, ['*'], 'page', $page)->all();
             dump(date("Y-m-d H:i:s").' - end');
             if (count($records) === 0) {
                 dump('end');
                 break;
             }
             dump( 'page: '.$page );
             foreach ($records as $row => $record) {               
                 $numCnt = $row + 1 + ( ($page - 1) * $perPage );
                dump( $record );
   
                $content_id = $record->content_id;
                $userId = $record->reg_user_id;

                $now = date("YmdHis");

                if( $contentService->isArchived($content_id) ){
                    dump(  date("Y-m-d H:i:s").'] '.'archive exist:'.$content_id );        
                    continue;
                }
            
                $req_no = getSequence('SEQ_REQUEST_ARCHIVE');
                DB::table('TB_ARCHIVE_REQUEST')->insert([
                    'req_no' => $req_no,
                    'nps_content_id'=> $content_id,
                    'das_content_id'=> $content_id, 
                    'req_type'=> 'archive', 
                    'req_comment'=> $appr_comment,
                    'status'=> '1',
                    'req_user_id'=> $userId,
                    'req_time'=> $now 
                ]);
               
                //콘텐츠 상태 업데이트                    
                $contentStatusData = [
                    'archive_status' => 0,
                    'archv_requst_at' => date('YmdHis'),
                    'archv_rqester' => $user->user_id 
                ];
                $contentStatusDto = new \Api\Services\DTOs\ContentStatusDto($contentStatusData);
                $keys       = array_keys($contentStatusData);
                $contentStatusDto = $contentStatusDto->only(...$keys);
                $contentService->update($content_id,null, $contentStatusDto, null,null, $user);

                DB::table('TB_REQUEST')->insert([
                    'req_no' => $req_no,
                    'nps_content_id'=> $content_id,
                    'das_content_id'=> $content_id, 
                    'req_type'=> 'archive', 
                    'req_comment'=> $appr_comment,
                    'REQ_STATUS'=> '1',
                    'req_user_id'=> $userId,
                    'req_time'=> $now 
                   
                ]);

              //  die();
             }
             dump(  date("Y-m-d H:i:s").'] '.$createdCount . ' records created...' . $createdCount/$totalCount*100 . '% complete.' );
 
             $createdCount = $perPage * $page;
             $page++;
         }  
         
         //스케줄 작업 시작
         //--update bc_task SET status='queue' , PRIORITY='400' WHERE status='scheduled';
         DB::table('bc_task')->where('status', 'scheduled')->update([
             'status' => 'queue',
             'PRIORITY' => '400'
         ]);
         dump( date("Y-m-d H:i:s").'] '.' 100% complete.' );
         die();
    }
}
