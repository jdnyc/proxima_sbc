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


class ArchiveSchedule extends Command
{
    protected function configure()
    {
        $this->setName('schedule:archive')
            ->setDescription('archive')
            ->addOption('content', 'c', InputOption::VALUE_OPTIONAL, 'ContentId')
            ->addOption('udcontent', 'u', InputOption::VALUE_OPTIONAL, 'UdcontentId')
            ->addOption('time', 'tt', InputOption::VALUE_OPTIONAL, 'time')
            ->addOption('dir', 'r', InputOption::VALUE_OPTIONAL, 'dir');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        require_once __DIR__ . '/../../../lib/config.php';

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
        dump(date("Y-m-d H:i:s") . ' - start');

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

        if (empty($time)) {
            $expiredDate = date('Ymd', strtotime('-1 days'));
        } else {
            $expiredDate = $time;
        }

        if (empty($time)) {
            $expiredDate = date('Ymd', strtotime('-1 days'));
        } else {
            $expiredDate = $time;
        }
        $status = [0, 2];
        $is_deleted = 'N';
        $udContentIds =  [2, 7, 9, 3, 8];
        $query = \Api\Models\Content::query();
        $query->where('BC_CONTENT.is_deleted', '=', $is_deleted);
        $query->whereIn('BC_CONTENT.status', $status);
        $query->whereIn('BC_CONTENT.ud_content_id', $udContentIds);
        $query->join('bc_media as m', "BC_CONTENT.CONTENT_ID", '=', "m.CONTENT_ID");
        $query->where('m.media_type', '=', 'original');
        $query->where('m.status', '=', '0');

        //아카이브 미디어가 없는것 대상
        $query->leftJoin(DB::raw("( SELECT * FROM bc_media WHERE media_type='archive' ) AM "), "BC_CONTENT.CONTENT_ID", '=', "AM.CONTENT_ID");
        $query->whereNull('am.media_id');

        $query->join('BC_CONTENT_STATUS as s', "BC_CONTENT.CONTENT_ID", '=', "s.CONTENT_ID");
        $query->join('BC_USRMETA_CONTENT as u', "BC_CONTENT.CONTENT_ID", '=', "u.USR_CONTENT_ID");

        //아카이브 요청 체크 요청중이거나 승인된건 제외
        $query->leftJoin(DB::raw("( SELECT nps_content_id,req_status,req_type FROM TB_REQUEST WHERE req_type='archive' AND req_status=2 ) R "), "BC_CONTENT.CONTENT_ID", '=', "R.NPS_CONTENT_ID");
        $query->whereNull('r.NPS_CONTENT_ID');

        //뉴스,제작,텔레시네 유형만
        $query->where(function ($query) {
            $query->where('BC_CONTENT.category_full_path', 'like', '/0/100/200%')
                ->orWhere('BC_CONTENT.category_full_path', 'like', '/0/100/201%')
                ->orWhere('BC_CONTENT.category_full_path', 'like', '/0/100/205%')
                ->orWhere('BC_CONTENT.category_full_path', 'like', '/0/100/203%');
        });

        //만료일이 2주 지났거나 등록되닞 2주 지난것
        $query->where(function ($query) use ($expiredDate) {
            $query->where('BC_CONTENT.EXPIRED_DATE', '<=',  $expiredDate);
            //$query->orWhere('BC_CONTENT.CREATED_DATE', '<=', $expiredDate );
        });
        $query->whereNull('s.ARCHIVE_STATUS');

        $query->where(function ($query) {
            $query->where('s.ARCHV_TRGET_AT', '!=', 'N')
                ->orWhereNull('s.ARCHV_TRGET_AT');
        });

        $query->select(
            's.ARCHIVE_STATUS',
            's.ARCHV_STTUS',
            's.DTL_ARCHV_STTUS',
            's.RESTORE_AT',
            's.RESTORE_STTUS',
            'BC_CONTENT.CREATED_DATE',
            'BC_CONTENT.EXPIRED_DATE',
            'BC_CONTENT.UD_CONTENT_ID',
            'BC_CONTENT.TITLE',
            'BC_CONTENT.REG_USER_ID',
            'BC_CONTENT.content_id',
            's.BFE_VIDEO_ID'
        );

        //$query->select('BC_CONTENT.content_id');
        //$totalCount = $query->count();


        //$query->leftJoin('bc_content_status', 'bc_content.content_id', '=', 'bc_content_status.content_id');
        //$query->leftJoin('bc_usrmeta_content', 'bc_content.content_id', '=', 'bc_usrmeta_content.usr_content_id');
        //$query->leftJoin('bc_sysmeta_movie', 'bc_content.content_id', '=', 'bc_sysmeta_movie.sys_content_id');

        $query->orderBy('BC_CONTENT.EXPIRED_DATE', 'asc');
        $query->orderBy('BC_CONTENT.CREATED_DATE', 'asc');
        //dd( $query->dd());
        dump('total : ' . $totalCount);

        $page = 1;
        $perPage = 2000;
        $createdCount = 0;


        $user = new \Api\Models\User();
        $user->user_id = 'admin';
        $reqComment = "자동 아카이브 요청";


        $contentService = new \Api\Services\ContentService($app->getContainer());

        $archiveService = new \Api\Services\ArchiveService($app->getContainer());

        while (true) {
            //unset($records);
            dump(date("Y-m-d H:i:s") . ' - start' . $query->toSql());
            $records = $query->simplePaginate($perPage, ['*'], 'page', $page);
            dump(date("Y-m-d H:i:s") . ' - end');
            if (count($records) === 0) {
                dump('end');
                break;
            }
            dump('page: ' . $page);
            foreach ($records as $row => $record) {
                $numCnt = $row + 1 + (($page - 1) * $perPage);
                dump($record);

                $content_id = $record->content_id;

                $now = date("YmdHis");

                if ($contentService->isArchived($content_id)) {
                    dump(date("Y-m-d H:i:s") . '] ' . 'archive exist:' . $content_id);
                    continue;
                }

                $userId =  $record->reg_user_id;

                $req_no = getSequence('SEQ_REQUEST_ARCHIVE');
                DB::table('TB_ARCHIVE_REQUEST')->insert([
                    'req_no' => $req_no,
                    'nps_content_id' => $content_id,
                    'das_content_id' => $content_id,
                    'req_type' => 'archive',
                    'req_comment' => $reqComment,
                    'status' => '1',
                    'req_user_id' => $userId,
                    'req_time' => $now
                ]);

                //콘텐츠 상태 업데이트                    
                $contentStatusData = [
                    'archv_requst_at' => date('YmdHis'),
                    'archv_rqester' => $userId
                ];
                $contentStatusDto = new \Api\Services\DTOs\ContentStatusDto($contentStatusData);
                $keys       = array_keys($contentStatusData);
                $contentStatusDto = $contentStatusDto->only(...$keys);
                $contentService->update($content_id, null, $contentStatusDto, null, null, $user);

                $usrMetaInfo = $contentService->findContentUsrMeta($content_id);
                // if (empty($usrMetaInfo->brdcst_stle_se) || ($usrMetaInfo->brdcst_stle_se == 'N') || ($usrMetaInfo->brdcst_stle_se != 'B' && $usrMetaInfo->brdcst_stle_se != 'S' && $usrMetaInfo->matr_knd == 'ZP')) {
                //     //자동 요청                    
                //     DB::table('TB_REQUEST')->insert([
                //         'req_no' => $req_no,
                //         'nps_content_id'=> $content_id,
                //         'das_content_id'=> $content_id, 
                //         'req_type'=> 'archive', 
                //         'req_comment'=> $reqComment,
                //         'REQ_STATUS'=> '1',
                //         'req_user_id'=> $userId,
                //         'req_time'=> $now                 
                //     ]);
                // }else{
                // 2022.10.25 EJ 콘텐츠 등록 승인 상관없이 자동 승인
                $archiveInfo = $archiveService->archive($content_id, $user);
                if (!$archiveInfo) {
                    dump(date("Y-m-d H:i:s") . '] ' . 'archive error:' . $content_id);
                    continue;
                }
                $taskId = $archiveInfo->task_id;

                $reqComment = "자동 아카이브 승인";
                if($usrMetaInfo->brdcst_stle_se == 'N') {
                    $reqComment = "자동 아카이브 승인(뉴스)";
                } else if($usrMetaInfo->matr_knd == 'ZP') {
                    $reqComment = "자동 아카이브 승인(프로그램)";
                } else if($usrMetaInfo->brdcst_stle_se == 'B' || $usrMetaInfo->brdcst_stle_se == 'S') {
                    $reqComment = "자동 아카이브 승인(구매,지원)";
                }
                // if (
                //     // 원래 있던 조건
                //     empty($usrMetaInfo->brdcst_stle_se) ||
                //     ($usrMetaInfo->brdcst_stle_se == 'N') ||
                //     ($usrMetaInfo->brdcst_stle_se != 'B' &&
                //         $usrMetaInfo->brdcst_stle_se != 'S' &&
                //         $usrMetaInfo->matr_knd == 'ZP')
                // ) {
                //     $reqComment = "자동 아카이브 승인";
                // }
                DB::table('TB_REQUEST')->insert([
                    'req_no' => $req_no,
                    'nps_content_id' => $content_id,
                    'das_content_id' => $content_id,
                    'req_type' => 'archive',
                    'req_comment' => $reqComment,
                    'REQ_STATUS' => '2',
                    'req_user_id' => $user->user_id,
                    'req_time' => $now,
                    'APPR_TIME' => $now,
                    'APPR_USER_ID' => $user->user_id,
                    'APPR_COMMENT' => '',
                    'TASK_ID' => $taskId
                ]);
                // }

                //스케줄 작업 시작
                //--update bc_task SET status='queue' , PRIORITY='400' WHERE status='scheduled';
                DB::table('bc_task')->where('status', 'scheduled')->where('DESTINATION', 'like', '%dtl_archive%')->update([
                    'status' => 'queue',
                    'priority' => '400'
                ]);
                //  die();
                //   50건만
                if ($row === 49) {
                    dd();
                }
            }
            dump(date("Y-m-d H:i:s") . '] ' . $createdCount . ' records created...' . $createdCount / $totalCount * 100 . '% complete.');

            $createdCount = $perPage * $page;
            $page++;
        }
        dump(date("Y-m-d H:i:s") . '] ' . ' 100% complete.');
        die();
    }
}
