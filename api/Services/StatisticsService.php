<?php

namespace Api\Services;

use Monolog\Utils;

use Api\Models\Task;
use Api\Models\Media;
use GuzzleHttp\Client;
use Api\Models\Content;
use Api\Models\InDvdLInfo;
use Api\Models\UserContent;
use GuzzleHttp\Psr7\Request;
use Api\Services\BaseService;
use Illuminate\Database\Capsule\Manager as DB;

class StatisticsService extends BaseService
{
    /**
     * 아카이브 일별 통계
     *
     * @param [type] $param
     * @return Collection
     */
    public function dailyArchiveStatisticsList($param)
    {

        $query = DB::table('bc_content as c');
        $query->join(DB::raw("(SELECT filesize, content_id, media_id,created_date FROM bc_media WHERE media_type='archive' AND filesize > 0) m"),function($join){
            $join->on('c.content_id', '=', 'm.content_id');
        });
        $query->select(         
            DB::raw('(SELECT ud_content_title FROM bc_ud_content where ud_content_id = c.ud_content_id) as ud_content_title'),
            DB::raw("substr(m.created_date,0,8) AS created_date"),
            DB::raw('count(c.content_id) cnt'),
            DB::raw("ROUND(sum(m.filesize) / 1024 / 1024 / 1024 ,2 ) AS filesize_gb"),
            DB::raw("ROUND(sum(m.filesize) / 1024 / 1024 / 1024 /1024 ,2) AS filesize_tb"),
            'c.ud_content_id'
        );
        $query->where('c.is_deleted', '=', 'N');
        $query->where('c.status', '>=', 0);
        $query->whereBetween('m.created_date',[$param['start_date'],$param['end_date']]);
        $query->groupBy(['ud_content_id',DB::raw('substr(m.CREATED_DATE,0,8)')]);
        $query->orderBy(DB::raw('substr(m.CREATED_DATE,0,8), UD_CONTENT_ID'));

        return $query->get();
    }
    /**
     * 아카이브 주간 통계
     *
     * @param [type] $param
     * @return Collection
     */
    public function weekArchiveStatisticsList($param)
    {
        $query = DB::table('bc_content as c');
        $query->join(DB::raw("(SELECT filesize, content_id, media_id,created_date FROM bc_media WHERE media_type='archive' AND filesize > 0) m"),function($join){
            $join->on('c.content_id', '=', 'm.content_id');
        });
        $query->select(         
            DB::raw('(SELECT ud_content_title FROM bc_ud_content where ud_content_id = c.ud_content_id) as ud_content_title'),
            DB::raw('count(c.content_id) cnt'),
            DB::raw("ROUND(sum(m.filesize) / 1024 / 1024 / 1024 ,2 ) AS filesize_gb"),
            DB::raw("sum(m.filesize) / 1024 / 1024 / 1024 /1024 AS filesize_tb"),
            DB::raw("to_char(ROUND(sum(m.filesize) / 1024 / 1024 / 1024 /1024 ,2),'FM99999990.00') AS filesize_tb"),
            'c.ud_content_id'
        );
        $query->where('c.is_deleted', '=', 'N');
        $query->where('c.status', '>=', 0);
        $query->whereBetween('m.created_date',[$param['start_date'],$param['end_date']]);
        $query->groupBy('ud_content_id');
        $query->orderBy('ud_content_id');
        
        $statistics = $query->get();
        $fillStatistics = $this->weekStatisticsFillInTheBlank($statistics);
        return $fillStatistics;
    }
    /**
     * 값이 없는 콘텐츠 0으로 채워주기
     *
     * @param [type] $lists
     * @return Collection
     */
    public function weekStatisticsFillInTheBlank($lists){
        $udContentIdArrays = [1,2,3,7,9,0];

        $statistics = [];
        foreach($udContentIdArrays as $udContentIdArray){
            if($udContentIdArray === 0){
                $data = (object)array(
                    'ud_content_title'=>'텔레시네',
                    'cnt'=>'0',
                    'filesize_gb'=>'0.00',
                    'filesize_tb'=>'0.00',
                    'ud_content_id'=>(string)$udContentIdArray
                );
                array_push($statistics, $data);
            }else{
                $udContent = $this->getUdContentByUdContentId($udContentIdArray);
                $udContentId = (string)$udContent->ud_content_id;
                $emptyCheck = true;
                foreach($lists as $list){
                    if($list->ud_content_id === $udContentId){
                        array_push($statistics, $list);
                        $emptyCheck = false;
                        break;
                    };
                }
                if($emptyCheck){
                    // 값이 없을떄 0으로 채워준다.
                    $data = (object)array(
                        'ud_content_title'=>$udContent->ud_content_title,
                        'cnt'=>'0',
                        'filesize_gb'=>'0.00',
                        'filesize_tb'=>'0.00',
                        'ud_content_id'=>(string)$udContent->ud_content_id
                    );
                    array_push($statistics, $data);
                }
            };
        }
        return $statistics;
    }
    /**
     * ud_content_id 로 ud_content 찾기
     *
     * @param [type] $udContentId
     * @return Collection
     */
    public function getUdContentByUdContentId($udContentId)
    {
        $query = UserContent::query();
        $udContentId = $query->find($udContentId);
        return $udContentId;
    }
    /**
     * 운영 통계
     *
     * @param [type] $input
     * @return Collection
     */
    public function operationStatisticsList($input)
    {   

        $startDate = $input['start_date'];
        $endDate = $input['end_date'];
        // 신규영상등록
        $newMedia = DB::table('bc_content')
        ->where('is_deleted', '=', 'N')
        ->where('status', '>=', 0)
        ->whereBetween('created_date', [$startDate,$endDate])
        ->select(
            DB::raw("'신규등록' as \"type\""),
            DB::raw('count(*) as count')
        );

        // 주조정실 전송
        $mainTask = DB::table('bc_task')
        ->where('type','=','80')
        ->where('destination','like',"%transfer_to_maincontrol%")
        ->whereBetween('creation_datetime', [$startDate,$endDate])
        ->select(
            DB::raw("'주조전송' as \"type\""),
            DB::raw('count(*) as count')
        );

        // 부조정실 전송
        $subTask = DB::table('bc_task')
        ->where('type','=','80')
        ->where('destination','like',"%transmission_zodiac%")
        ->whereBetween('creation_datetime', [$startDate,$endDate])
        ->select(
            DB::raw("'부조전송' as \"type\""),
            DB::raw('count(*) as count')
        );

        // 아카이브 유형
        $archiveQuery = "(select ROOT_TASK from BC_TASK where DESTINATION like '%dtl_archive%' AND CREATION_DATETIME between {$startDate} AND {$endDate} AND status='complete' GROUP BY ROOT_TASK)";
        // $archive = DB::table(DB::raw("({$archiveType})"))
        $archive = DB::table(DB::raw($archiveQuery))
        ->select(
            DB::raw("'아카이브' as \"type\""),
            DB::raw('count(*) as count')
        );

        // 아카이브 유형
        $restoreQuery = "(select ROOT_TASK from BC_TASK where DESTINATION like '%restore%' AND CREATION_DATETIME between {$startDate} AND {$endDate} AND status='complete' GROUP BY ROOT_TASK)";
        // $archive = DB::table(DB::raw("({$archiveType})"))
        $restore = DB::table(DB::raw($restoreQuery))
        ->select(
            DB::raw("'리스토어' as \"type\""),
            DB::raw('count(*) as count')
        );

        $result = $newMedia
        ->unionAll($mainTask)
        ->unionAll($subTask)
        ->unionAll($archive)
        ->unionAll($restore)
        ->get();
        return $result;
    }

    /**
     * 콘텐츠 등록
     *
     * @return Collection
     */
    public function registrationContent()
    {
        $query = DB::raw("(
                    SELECT 
                        CASE to_char(UD_CONTENT_ID) 
                            WHEN '1' 
                            THEN 'origin' 
                            WHEN '2' 
                            THEN 'clean' 
                            WHEN '3' 
                            THEN 'master' 
                            WHEN '7' 
                            THEN 'clip' 
                            WHEN '9' 
                            THEN 'news' 
                        END AS \"type\", 
                        CASE to_char(status) 
                            WHEN '2' 
                            THEN 'approval' 
                            WHEN '0' 
                            THEN 'request' 
                            WHEN '-5' 
                            THEN 'reject' 
                        END AS \"status\", 
                            count(content_id) AS \"count\" 
                    FROM bc_content 
                    WHERE status NOT IN (-1,-2,-3) 
                    AND IS_DELETED ='N' 
                    AND UD_CONTENT_ID IN (1,2,3,7,9) 
                    AND CREATED_DATE > TO_CHAR(SYSDATE - 14 , 'YYYYMMDDHHIIMMSS') 
                    GROUP BY UD_CONTENT_ID,status
                )");
        $table = DB::table($query);

        
        return $table->get();
    }

    /**
     * 콘텐츠 등록 삭제 건수
     *
     * @return Collection
     */
    public function registrationContentByDeleted()
    {
        $query = DB::raw("(
                            SELECT 
                                CASE to_char(UD_CONTENT_ID) 
                                    WHEN '1' 
                                    THEN 'origin' 
                                    WHEN '2' 
                                    THEN 'clean' 
                                    WHEN '3' 
                                    THEN 'master' 
                                    WHEN '7' 
                                    THEN 'clip' 
                                    WHEN '9' 
                                    THEN 'news' 
                                END AS \"type\", 
                                count(content_id) AS \"count\" 
                            FROM bc_content 
                            WHERE status NOT IN (-1,-2,-3) 
                            AND IS_DELETED ='Y' 
                            AND UD_CONTENT_ID IN (1,2,3,7,9) 
                            AND last_modified_date > TO_CHAR(SYSDATE - 14 , 'YYYYMMDDHHIIMMSS') 
                            GROUP BY UD_CONTENT_ID
                )");
        $table = DB::table($query);

        
        return $table->get();
    }

    /**
     * 외부 포털 업로드
     *
     * @return Collection
     */
    public function externalPortalUpload()
    {
        $query = DB::raw("(
                            SELECT 
                            CASE profile_type 
                                WHEN 'proxy' 
                                THEN '720' 
                                WHEN 'proxy360' 
                                THEN '360' 
                                WHEN 'proxy2m1080' 
                                THEN '2M' 
                                WHEN 'proxy15m1080' 
                                THEN '15M' 
                                WHEN 'thumb' 
                                THEN '대표이미지' 
                                ELSE profile_type 
                            END AS \"type\", 
                            cnt AS \"count\" 
                        FROM ( 
                                SELECT 	media_type AS profile_type,
                                        count(MEDIA_ID) AS cnt 
                                FROM bc_media m 
                                JOIN bc_content c ON m.content_id=c.content_id 
                                WHERE c.IS_DELETED='N' 
                                AND c.status >=0 
                                AND c.CATEGORY_FULL_PATH LIKE '/0/100/203%' 
                                AND m.MEDIA_TYPE IN ( 'proxy', 'proxy15m1080', 'proxy2m1080', 'proxy360' ) 
                                --AND m.CREATED_DATE > 20191216000000 
                                GROUP BY media_type 
                            ) ORDER BY profile_type       
                        )");
        $table = DB::table($query);

        
        return $table->get();
    }

    
    /**
     * 외부 포털 다운로드
     *
     * @return Collection
     */
    public function externalPortalDownload()
    {
        $query = DB::raw("(
                            SELECT 
                                CASE media_type 
                                    WHEN 'proxy' 
                                    THEN '720' 
                                    WHEN 'proxy360' 
                                    THEN '360' 
                                    WHEN 'proxy2m1080' 
                                    THEN '2M' 
                                    WHEN 'proxy15m1080' 
                                    THEN '15M' 
                                    WHEN 'thumb' 
                                    THEN '대표이미지' 
                                    ELSE media_type 
                                END AS \"type\" , 
                                count(j.id) AS \"count\" 
                            FROM FS_JOBS j 
                            LEFT OUTER JOIN FILES f ON (j.FILE_ID=f.id) 
                            LEFT JOIN bc_media m ON (f.MEDIA_ID=m.media_id) 
                            WHERE j.STATUS='finished' 
                            AND j.TYPE='download' 
                            AND m.MEDIA_TYPE IN ( 'proxy', 'proxy15m1080', 'proxy2m1080', 'proxy360' ) 
                            AND m.media_id IS NOT NULL 
                            GROUP BY m.MEDIA_TYPE
                        )");
        $table = DB::table($query);

        
        return $table->get();
    }

    /**
     * 내부 포털 업로드
     *
     * @return Collection
     */
    public function innerPortalUpload()
    {
        $query = DB::raw("(
                            SELECT 
                                CASE profile_type 
                                    WHEN 'proxy' 
                                    THEN '720' 
                                    WHEN 'proxy360' 
                                    THEN '360' 
                                    WHEN 'proxy2m1080'
                                    THEN '2M' 
                                    WHEN 'proxy15m1080' 
                                    THEN '15M' 
                                    WHEN 'thumb' 
                                    THEN '대표이미지' 
                                    ELSE profile_type 
                                END AS \"type\", 
                                cnt \"count\" 
                            FROM ( 
                                    SELECT media_type AS profile_type,
                                            count(MEDIA_ID) AS cnt 
                                    FROM bc_media m 
                                    JOIN bc_content c ON m.content_id=c.content_id 
                                    WHERE c.IS_DELETED='N' 
                                    AND c.status >=0 
                                    AND c.CATEGORY_FULL_PATH NOT LIKE '/0/100/203%' 
                                    AND m.MEDIA_TYPE IN ( 'proxy', 'proxy15m1080', 'proxy2m1080', 'proxy360' ) 
                                    --AND m.CREATED_DATE > 20191216000000 
                                    GROUP BY media_type 
                                ) ORDER BY profile_type 
                        )");
        $table = DB::table($query);

        
        return $table->get();
    }

    /**
     * 내부 포털 다운로드
     *
     * @return Collection
     */
    public function innerPortalDownload()
    {
        $query = DB::raw("(
                            SELECT 
                                CASE l.DESCRIPTION 
                                    WHEN 'proxy' 
                                    THEN '720' 
                                    WHEN 'proxy360' 
                                    THEN '360' 
                                    WHEN 'proxy2m1080' 
                                    THEN '2M' 
                                    WHEN 'proxy15m1080' 
                                    THEN '15M' 
                                    WHEN 'thumb' 
                                    THEN '대표이미지' 
                                    ELSE l.DESCRIPTION 
                                END AS \"type\" , 
                                count(l.LOG_ID) AS \"count\" 
                            FROM bc_content c 
                            JOIN BC_LOG l ON c.content_id=l.content_id 
                            WHERE l.\"ACTION\"='download' 
                            AND l.DESCRIPTION IN ( 'proxy', 'proxy15m1080', 'proxy2m1080', 'proxy360' ) 
                            GROUP BY l.DESCRIPTION
                        )");
        $table = DB::table($query);

        
        return $table->get();
    }

    public function portalStatistics()
    {
        $baseMap = [
            '360' => [
                'inner' =>[
                    'download' => 0,
                    'upload' => 0
                ],
                'external' => [
                    'download' => 0,
                    'upload' => 0
                ]
            ],
            '720' => [
                'inner' =>[
                    'download' => 0,
                    'upload' => 0
                ],
                'external' => [
                    'download' => 0,
                    'upload' => 0
                ]
            ],
            '2M' => [
                'inner' =>[
                    'download' => 0,
                    'upload' => 0
                ],
                'external' => [
                    'download' => 0,
                    'upload' => 0
                ]
            ],
            '15M' => [
                'inner' =>[
                    'download' => 0,
                    'upload' => 0
                ],
                'external' => [
                    'download' => 0,
                    'upload' => 0
                ]
            ],
        ];
        
        $inner = [
            $this->innerPortalUpload(),
            $this->innerPortalDownload()
        ];

        $external = [
            $this->externalPortalUpload(),
            $this->externalPortalDownload()
        ];
        foreach($this->innerPortalUpload() as $data){
            $baseMap[$data->type]['inner']['upload'] = $data->count;
        }
        foreach($this->innerPortalDownload() as $data){
            $baseMap[$data->type]['inner']['download'] = $data->count;
        }
        foreach($this->externalPortalUpload() as $data){
            $baseMap[$data->type]['external']['upload'] = $data->count;
        }
        foreach($this->externalPortalDownload() as $data){
            $baseMap[$data->type]['external']['download'] = $data->count;
        }
        
        return $baseMap;
        
        
    }

    public function registrationContentData()
    {
        $registrationCountMap = [
            'origin' => [
                'title' => '원본',
                'approval' => 0,
                'request' => 0,
                'reject' => 0,
                'delete' => 0
            ],
            'clean' => [
                'title' => '클린본',
                'approval' => 0,
                'request' => 0,
                'reject' => 0,
                'delete' => 0
            ],
            'master' => [
                'title' => '마스터본',
                'approval' => 0,
                'request' => 0,
                'reject' => 0,
                'delete' => 0
            ],
            'clip' => [
                'title' => '클립본',
                'approval' => 0,
                'request' => 0,
                'reject' => 0,
                'delete' => 0
            ],
            'news' => [
                'title' => '뉴스편집본',
                'approval' => 0,
                'request' => 0,
                'reject' => 0,
                'delete' => 0
            ],
        ];

        $list = $this->registrationContent();
        foreach($list as $info){
            $registrationCountMap[$info->type][$info->status] = $info->count;
        }
        $dList = $this->registrationContentByDeleted();
        foreach($dList as $dInfo){
            $registrationCountMap[$dInfo->type]['delete'] = $dInfo->count;
        }

        return $registrationCountMap;
    }

    public function storageStatistics()
    {
        $query = DB::table("monitoring_storage as m")->selectRaw("m.*");
        $query->join(DB::raw("( SELECT MAX(updated_at) AS updated_at, drive FROM monitoring_storage GROUP BY drive ) n"),function($join){
             $join->on("m.updated_at", '=', "n.updated_at")->on("m.drive", '=', "n.drive");
        });
        $results = $query->get();
        foreach($results as $key => $result)
        {
            $results[$key]->total_size = round($result->total_num / 1024/1024/1024/1024,2); 
            $results[$key]->remaining_size = round($result->available_num /1024/1024/1024/1024,2); 
            $results[$key]->used_size = round($result->used_num /1024/1024/1024/1024,2); 
            $results[$key]->used_percent = round(( $result->used_num /  $result->total_num ) * 100 ) ;
        }
        return $results;
    }

    public function divaTapeStatistics()
    {
        $query = DB::table("DP_TAPES as m")->selectRaw("case TA_SET_ID when 2 then 'main' when 3 then 'backup' end as ta_type ,count(TA_BARCODE) cnt ,( sum(TA_REMAINING_SIZE) / 1024/1024/1024 ) as REMAINING_SIZE ,( ( 2441405952  / 1024/1024/1024 ) * count(TA_ID) ) AS total_size");
        $query->groupBy('TA_SET_ID');
        $results = $query->get();
        foreach($results as $key => $result)
        {
            $results[$key]->total_size = round($result->total_size,2); 
            $results[$key]->remaining_size = round($result->remaining_size,2); 
            $results[$key]->used_size = $result->total_size - $result->remaining_size ;
            $results[$key]->used_percent = round(( $result->used_size /  $result->total_size ) * 100 ) ;
        }
        return $results;
    }

    /**
     * 운영 통계 > 다운로드 통계
     *
     * @param $param
     * @return Collection
     */
    public function downloadStatisticsList($param)
    {
        $startDate =  new \Carbon\Carbon($param['start_date']);
        $startDateFormat = $startDate->format('Y-m-d H:i:s');

        $endDate = new \Carbon\Carbon($param['end_date']);
        $endDateFormat = $endDate->format('Y-m-d H:i:s');

        //! 외부 다운로드
        $outsideQuery = DB::table('fs_jobs');
        $outsideQuery->join('files', 'fs_jobs.file_id', '=', 'files.id', 'left outer')
        ->leftJoin('bc_media', 'files.media_id', '=', 'bc_media.media_id');
        
        $outsideQuery->selectRaw("
            bc_media.media_type AS profile,
            count(fs_jobs.id) AS count,
            'outside' AS type,
            ROUND(sum(bc_media.filesize) / 1024/1024/1024,2) AS filesize
        ");
        $outsideQuery->where('fs_jobs.status', '=', 'finished');
        $outsideQuery->where('fs_jobs.type', '=', 'download');
        $outsideQuery->whereIn('bc_media.media_type', ['proxy', 'proxy15m1080', 'proxy2m1080', 'proxy360']);
        $outsideQuery->whereBetween('fs_jobs.created_at', [$startDateFormat, $endDateFormat]); 
        $outsideQuery->groupBy('bc_media.media_type');

        //! 외부 로고 다운로드
        $outsideLogoQuery = DB::table('fs_jobs');
        $outsideLogoQuery->join('files', 'fs_jobs.file_id', '=', 'files.id', 'left outer')
        ->leftJoin('bc_media', 'files.media_id', '=', 'bc_media.media_id');
        $outsideLogoQuery->selectRaw("
            bc_media.media_type AS profile,
            count(fs_jobs.id) AS count,
            'outside_logo' AS type,
            ROUND(sum(bc_media.filesize) / 1024/1024/1024,2) AS filesize
        ");
        $outsideLogoQuery->where('fs_jobs.status', '=', 'finished');
        $outsideLogoQuery->where('fs_jobs.type', '=', 'download');
        $outsideLogoQuery->whereIn('bc_media.media_type', ['proxy2m1080logo', 'proxy15m1080logo']);

        $outsideLogoQuery->whereBetween('fs_jobs.created_at', [$startDateFormat, $endDateFormat]); 
        $outsideLogoQuery->groupBy('bc_media.media_type');

        //! 내부 다운로드
        $insideQuery = DB::table('bc_content');
        $insideQuery->join('bc_log', 'bc_content.content_id', '=', 'bc_log.content_id')
                    ->leftJoin('bc_media', 'bc_content.content_id', '=', 'bc_media.content_id');

        $insideQuery->selectRaw("
            bc_log.description as profile,
            count(bc_log.log_id) AS count,
            'inside' AS type,
            ROUND(sum(bc_media.filesize) / 1024/1024/1024,2) AS filesize
        ");

        $insideQuery->whereIn('bc_log.description', ['proxy', 'proxy15m1080', 'proxy2m1080', 'proxy360','original']);
        $insideQuery->whereBetween('bc_log.created_date', [$param['start_date'], $param['end_date']]);
        $insideQuery->where('bc_log.action', '=', 'download');
        $insideQuery->whereRaw('bc_media.media_type = bc_log.description');

        $insideQuery->groupBy('bc_log.description');

        //! 내부 로고 다운로드
        $insideLogoQuery = DB::table('bc_content');
        $insideLogoQuery->join('bc_log', 'bc_content.content_id', '=', 'bc_log.content_id')
                        ->leftJoin('bc_media', 'bc_content.content_id', '=', 'bc_media.content_id');

        $insideLogoQuery->selectRaw("
            bc_log.description AS profile,
            count(bc_log.log_id) AS count,
            'inside_logo' AS type,
            ROUND(sum(bc_media.filesize) / 1024/1024/1024,2) AS filesize
        ");

        $insideLogoQuery->whereIn('bc_log.description', ['proxy2m1080logo','proxy15m1080logo']);
        $insideLogoQuery->whereBetween('bc_log.created_date', [$param['start_date'], $param['end_date']]);
        $insideLogoQuery->where('bc_log.action', '=', 'download');
        $insideQuery->whereRaw('bc_media.media_type = bc_log.description');
        
        $insideLogoQuery->groupBy('bc_log.description');

        $datas = $outsideQuery->unionAll($outsideLogoQuery)
        ->unionAll($insideQuery)
        ->unionAll($insideLogoQuery)
        ->get();

        $result = [];
        $proxyList = ['proxy', 'proxy15m1080', 'proxy2m1080', 'proxy360', 'original', 'proxy2m1080logo', 'proxy15m1080logo'];
        $keyList = ['inside', 'inside_logo', 'outside', 'outside_logo'];

        $dataValueArray = [];
        foreach ($datas as $data) {
            $dataValueArray[$data->type][$data->profile]['count'] = (double)$data->count;
            $dataValueArray[$data->type][$data->profile]['filesize'] = (double)$data->filesize;
        }

        $result = [];
        foreach ($keyList as $index => $key) {
            $makeData = $dataValueArray[$key] ?? null;
            foreach ($proxyList as $proxy) {
                if (!$makeData[$proxy]) {
                    $makeData[$proxy]['count'] = 0;
                    $makeData[$proxy]['filesize'] = 0;
                }
            }
            $makeData['type'] = $key;
            $result[$index] = $makeData;
        }
    
        return $result;
    }

    /**
     * 운영 통계 > 영상변환
     *
     * @param $param
     * @return Collection
     */
    public function videoConvertStatistics($param) {

        $startDate =  $param['start_date'];
        $endDate = $param['end_date'];

        //! 영상변환 (수동) Query
        $manualQuery = DB::table('bc_media AS bm')
                ->join('bc_content AS bc', 'bm.content_id', '=', 'bc.content_id')
                ->whereIn('bm.media_type', [
                    'proxy360','proxy','proxy2m1080','proxy15m1080'
                ])
                ->whereBetween('bm.created_date', [$startDate, $endDate])
                ->where('bm.filesize', '>', 0)
                ->where('reg_type', 'like', '%create%')
                ->selectRaw("
                    bm.media_type,
                    count(bm.media_id) as cnt,
                    'manual' as type
                ")
                ->groupBy('bm.media_type');

        //! 영상변환 (자동) Query
        $autoQuery = DB::table('bc_media AS bm')
                ->join('bc_content AS bc', 'bm.content_id', '=', 'bc.content_id')
                ->whereIn('bm.media_type', [
                    'proxy360','proxy','proxy2m1080','proxy15m1080'
                ])
                ->whereBetween('bm.created_date', [$startDate, $endDate])
                ->where('bm.filesize', '>', 0)
                ->where('reg_type', 'not like', '%create%')
                ->selectRaw("
                    bm.media_type,
                    count(bm.media_id) as cnt,
                    'auto' as type
                ")
                ->groupBy('bm.media_type');

        //! 영상변환 (로고) Query
        $logoQuery = DB::table('bc_media AS bm')
                ->join('bc_content AS bc', 'bm.content_id', '=', 'bc.content_id')
                ->whereIn('bm.media_type', [
                    'proxy2m1080logo','proxy15m1080logo'
                ])
                ->whereBetween('bm.created_date', [$startDate, $endDate])
                ->where('bm.filesize', '>', 0)
                ->where('reg_type', 'like', '%create%')
                ->selectRaw("
                    bm.media_type,
                    count(bm.media_id) as cnt,
                    'logo' as type
                ")
                ->groupBy('bm.media_type');

        $queryDatas = $manualQuery->unionAll($autoQuery)
                ->unionAll($logoQuery)
                ->get();

        $result = [];
        $autoArr = ['type' => 'auto'];
        $manualArr = ['type' => 'manual'];
        $logoArr = ['type' => 'logo'];

        foreach ($queryDatas as $key => $data) {
            if (isset($data->type)) {
                if ($data->type === 'auto') {
                    $autoArr[$data->media_type] = $data->cnt;
                } else if ($data->type === 'manual') {
                    $manualArr[$data->media_type] = $data->cnt;
                } else {
                    $mediaType = str_replace('logo', '', $data->media_type);
                    $logoArr[$mediaType] = $data->cnt;
                }
            }
        }

        $fields = ['proxy','proxy2m1080', 'proxy15m1080', 'proxy360'];

        foreach ($fields as $field) {
            $logoArr[$field] = (int) $logoArr[$field] ?? (int) 0;
            $manualArr[$field] = (int) $manualArr[$field] ?? (int) 0;
            $autoArr[$field] = (int) $autoArr[$field] ?? (int) 0;
        }

        $result = [ $logoArr, $manualArr, $autoArr ];
        
        return $result;

    }

    /**
     * 운영 통계 > 콘텐츠 통계
     *
     * @param $param
     * @return Collection
     */
    public function contentStatistics($param) {
        $startDate = $param['start_date'];
        $endDate = $param['end_date'];

        $deleteQuery = DB::table('bc_content')
                    ->whereNotIn('status', [-1, -2, -3])
                    ->where('is_deleted', 'Y')
                    ->whereIn('ud_content_id', [1,2,3,7,9])
                    ->whereBetween('last_modified_date', [$startDate, $endDate])
                    ->selectRaw("
                        case to_char(ud_content_id)
                            when '1' then 'origin'
                            when '2' then 'clean'
                            when '3' then 'master'
                            when '7' then 'clip'
                            when '9' then 'news'
                            end as item,
                        'deleted' as status,
                        count(content_id) as cnt
                    ")
                    ->groupBy('ud_content_id');

        $contentQuery = DB::table('bc_content')
                    ->whereNotIn('status', [-1, -2, -3])
                    ->where('is_deleted', 'N')
                    ->whereIn('ud_content_id', [1,2,3,7,9])
                    ->whereBetween('created_date', [$startDate, $endDate])
                    ->selectRaw("
                        case to_char(ud_content_id)
                            when '1' then 'origin'
                            when '2' then 'clean'
                            when '3' then 'master'
                            when '7' then 'clip'
                            when '9' then 'news'
                            end as item,
                        case to_char(status)
                            when '2' then 'approval'
                            when '0' then 'request'
                            when '-5' then 'reject'
                            end status,
                        count(content_id) as cnt
                    ")
                    ->groupBy('ud_content_id', 'status');

        $datas = $contentQuery->unionAll($deleteQuery)->get();

        $requestArr = [
            'type' => 'request',
            'origin' => 0,
            'clean' => 0,
            'master' => 0,
            'clip' => 0,
            'news' => 0,
            'rowTotal' => 0
        ];
        $rejectArr = [
            'type' => 'reject',
            'origin' => 0,
            'clean' => 0,
            'master' => 0,
            'clip' => 0,
            'news' => 0,
            'rowTotal' => 0
        ];
        $deletedArr = [
            'type' => 'deleted',
            'origin' => 0,
            'clean' => 0,
            'master' => 0,
            'clip' => 0,
            'news' => 0,
            'rowTotal' => 0
        ];
        $approvalArr = [
            'type' => 'approval',
            'origin' => 0,
            'clean' => 0,
            'master' => 0,
            'clip' => 0,
            'news' => 0,
            'rowTotal' => 0
        ];
        foreach ($datas as $key => $data) {
            switch ($data->status) {
                case 'request':
                    $requestArr[$data->item] = (int) $data->cnt;
                    $requestArr['rowTotal'] += (int) $data->cnt;
                    break;
                case 'reject':
                    $rejectArr[$data->item] = (int) $data->cnt;
                    $rejectArr['rowTotal'] += (int) $data->cnt;
                    break;
                case 'deleted':
                    $deletedArr[$data->item] = (int) $data->cnt;
                    $deletedArr['rowTotal'] += (int) $data->cnt;
                    break;
                case 'approval':
                    $approvalArr[$data->item] = (int) $data->cnt;
                    $approvalArr['rowTotal'] += (int) $data->cnt;
                    break;
                default:
                    break;
            }
        }

        $result = [$requestArr, $rejectArr, $deletedArr, $approvalArr];
        return $result;
    }
    

    /**
     * 사용자 > 접속자 통계 (내부 사용자)
     *
     * @param $param
     * @return void
     */
    public function internalLoginUserStatistics($param)
    {
        if(!empty($param['user_search'])) {
            $_where = "AND (bm.user_id = '".$param['user_search']."' OR bm.user_nm = '".$param['user_search']."')";
        };
        $query = DB::raw("(
            SELECT *
            FROM (
                SELECT	bm.DEPT_NM,
                        CASE when substr(bl.CREATED_DATE,9,2) = '00' THEN '24' ELSE substr(bl.CREATED_DATE,9,2) END AS HOUR
                FROM	BC_LOG bl 
                LEFT JOIN BC_MEMBER bm ON bm.USER_ID = bl.USER_ID
                WHERE	bl.ACTION = 'login'
                AND		bl.CREATED_DATE BETWEEN {$param['start_date']} AND {$param['end_date']}
                AND 	bm.dept_nm IS NOT NULL
                AND		bm.dept_nm IN (
                                    SELECT	code_itm_nm AS dept_name
                                    FROM	DD_CODE_ITEM dci
                                    WHERE 	CODE_SET_ID = 220
                                    AND		USE_YN = 'Y'
                                    AND		DELETE_DT IS NULL
                                    AND 	DP != 1
                                )
                {$_where}
                ORDER BY bm.dept_nm asc, hour ASC
            )
            pivot ( count(hour) FOR HOUR IN ('01' as \"01\",'02' as \"02\",'03' as \"03\",'04' as \"04\",'05' as \"05\",'06' as \"06\",'07' as \"07\",'08' as \"08\",'09' as \"09\",10,11,12,13,14,15,16,17,18,19,20,21,22,23,24))
        )");

        $table = DB::table($query);
        
        return $table->get();
    }
    
    /**
     * 사용자 > 접속자 통계(외부 사용자)
     *
     * @param $param
     * @return void
     */
    public function externalLoginUserStatistics($param)
    {
        if(!empty($param['user_search'])) {
            $_where = "AND (bm.user_id = '".$param['user_search']."' OR bm.user_nm = '".$param['user_search']."')";
        };
        $query = DB::raw("(
                SELECT *
                    FROM 
                    (
                        SELECT	dci.CODE_ITM_NM AS dept_nm,
                                CASE when substr(bl.CREATED_DATE,9,2) = '00' THEN '24' ELSE substr(bl.CREATED_DATE,9,2) END AS HOUR
                        FROM	BC_LOG bl 
                        LEFT JOIN BC_MEMBER bm ON bm.USER_ID = bl.USER_ID
                        LEFT JOIN DD_CODE_ITEM dci ON bm.ORG_ID = dci.CODE_ITM_CODE 
                        WHERE	bl.ACTION = 'login' 
                        AND		bl.CREATED_DATE BETWEEN {$param['start_date']} AND {$param['end_date']}
                        AND		bm.EXTERNAL_YN = 'Y'
                        AND		dci.CODE_SET_ID = 214
                        AND		dci.USE_YN = 'Y'
                        AND		dci.DELETE_DT IS NULL 
                        AND		dci.DP != 1
                        {$_where}
                    )
                    pivot ( count(hour) FOR HOUR IN ('01' as \"01\",'02' as \"02\",'03' as \"03\",'04' as \"04\",'05' as \"05\",'06' as \"06\",'07' as \"07\",'08' as \"08\",'09' as \"09\",10,11,12,13,14,15,16,17,18,19,20,21,22,23,24))
        )");

        $table = DB::table($query);

        return $table->get();
    }

    /**
     * 제작 폴더 신청 통계
     *
     * @param $param
     * @return void
     */
    public function folderRequestStatistics($param)
    {
        $selectQuery = "request_date, status";
        switch ($param['typeData']) {
            case 'week':
                # week
                $selectQuery = "(TO_CHAR(TO_DATE(request_date, 'YYYY-MM-DD'), 'YYYY')||'년 '||
                    TO_CHAR(TO_DATE(request_date, 'YYYY-MM-DD'), 'MM')||'월 '||
                    TO_CHAR(TO_DATE(TO_CHAR(TRUNC(TO_DATE(request_date, 'YYYY-MM-DD'), 'iw'), 'YYYY-MM-DD')), 'w')||'주') AS request_date,
                    status";
                break;
            case 'month':
                # month
                $selectQuery = "(TO_CHAR(TO_DATE(request_date, 'YYYY-MM-DD'), 'YYYY')||'년 '||
                    TO_CHAR(TO_DATE(request_date, 'YYYY-MM-DD'), 'MM')||'월') AS request_date,
                    status";
                break;
            case 'year':
                # year
                $selectQuery = "(TO_CHAR(TO_DATE(request_date, 'YYYY-MM-DD'), 'YYYY')||'년') AS request_date,
                    status";
                break;
            case 'one':
            default:
                $selectQuery = "request_date, status";
                break;
        }
        $query = DB::raw("(
            SELECT  
                *
            FROM (
                SELECT 
                    ${selectQuery}
                FROM 
                (
                    SELECT B.request_date,A.status
                    FROM (
                        SELECT	substr(CREATED_at,0,4) || '-' || substr(CREATED_at,5,2) || '-' || substr(CREATED_at,7,2) AS request_date,
                                CASE WHEN DELETED_AT IS NOT null THEN 'deleted' ELSE status END AS status
                        FROM 	FOLDER_MNG_REQUEST
                        WHERE   created_at between {$param['start_date']} AND {$param['end_date']}
                        ) A,
                        (
                            SELECT TO_CHAR(request_date, 'YYYY-MM-DD') AS request_date FROM (
                                SELECT TO_DATE(substr({$param['start_date']}, 0, 8), 'YYYY-MM-DD') + LEVEL - 1 AS request_date FROM dual
                                CONNECT BY LEVEL <= (TO_DATE(substr({$param['end_date']}, 0, 8), 'YYYY-MM-DD') - TO_DATE(substr({$param['start_date']}, 0, 8), 'YYYY-MM-DD') + 1)
                            )
                        ) B
                    WHERE B.request_date = A.request_date(+)
                )
            )
            pivot ( count(status) FOR status IN ('request' as request,'approval' as approval,'reject' as reject,'deleted' AS deleted))
            ORDER BY request_date ASC
        )");
        
        $table = DB::table($query);

        return $table->get();
    }

    /**
     * 방송심의,콘텐츠 등록승인 통계
     *
     * @param $param
     * @param string $type
     * @return void
     */
    public function reviewStatistics($param,$type = 'ingest')
    {
        $selectQuery = "request_date, review_reqest_sttus";
        switch ($param['typeData']) {
            case 'week':
                # week
                $selectQuery = "(TO_CHAR(TO_DATE(request_date, 'YYYY-MM-DD'), 'YYYY')||'년 '||
                    TO_CHAR(TO_DATE(request_date, 'YYYY-MM-DD'), 'MM')||'월 '||
                    TO_CHAR(TO_DATE(TO_CHAR(TRUNC(TO_DATE(request_date, 'YYYY-MM-DD'), 'iw'), 'YYYY-MM-DD')), 'w')||'주') AS request_date,
                    review_reqest_sttus";
                break;
            case 'month':
                # month
                $selectQuery = "(TO_CHAR(TO_DATE(request_date, 'YYYY-MM-DD'), 'YYYY')||'년 '||
                    TO_CHAR(TO_DATE(request_date, 'YYYY-MM-DD'), 'MM')||'월') AS request_date,
                    review_reqest_sttus";
                break;
            case 'year':
                # year
                $selectQuery = "(TO_CHAR(TO_DATE(request_date, 'YYYY-MM-DD'), 'YYYY')||'년') AS request_date,
                    review_reqest_sttus";
                break;
            case 'one':
            default:
                $selectQuery = "request_date, review_reqest_sttus";
                break;
        }
        $query = DB::raw("(
            SELECT
                *
            FROM (
                SELECT 
                    ${selectQuery}
                FROM (
                    SELECT B.request_date,A.review_reqest_sttus
                    FROM (
                        SELECT	substr(regist_dt,0,4) || '-' || substr(regist_dt,5,2) || '-' || substr(regist_dt,7,2) AS request_date,review_reqest_sttus
                        FROM 	REVIEWS r
                        WHERE   regist_dt between {$param['start_date']} AND {$param['end_date']}
                        AND     review_ty_se = '".$type."'
                        ) A,
                        (
                            SELECT TO_CHAR(request_date, 'YYYY-MM-DD')AS request_date FROM (
                                SELECT TO_DATE(substr({$param['start_date']}, 0, 8), 'YYYY-MM-DD') + LEVEL - 1 AS request_date FROM dual
                                CONNECT BY LEVEL <= (TO_DATE(substr({$param['end_date']}, 0, 8), 'YYYY-MM-DD') - TO_DATE(substr({$param['start_date']}, 0, 8), 'YYYY-MM-DD') + 1)
                            )
                        ) B
                    WHERE B.request_date = A.request_date(+)
                )
            )
            pivot ( count(review_reqest_sttus) FOR review_reqest_sttus IN ('request' as request,'approval' as approval,'reject' as reject))
            ORDER BY request_date ASC
        )");

        $table = DB::table($query);

        return $table->get();
    }

        
    /**
     * 운영 > 사용신청 승인 
     *
     * @param $param
     * @return void
     */
    public function userApprovalStatistics_back($param)
    {
        // 내부 쿼리
        $userApprovalDatas = DB::table('MEMBER_REQUEST as mr')
                ->join('DD_CODE_ITEM dci',function($join){
                    $join->on('dci.CODE_ITM_CODE', 'mr.DEPT')
                        ->on('dci.CODE_SET_ID', 220);
                })
                ->selectRaw("
                    mr.DEPT AS dept_code,
                    dci.CODE_ITM_NM AS dept_nm,
                    substr(mr.REGIST_DT, 0, 8) AS created_date,
                    count(*) as cnt
                ")
                ->whereBetween('mr.REGIST_DT', [$param['start_date'], $param['end_date']])
                ->whereNull('mr.DELETE_DT')
                ->where('mr.STATUS', 'approval')
                ->whereNotNull('mr.INSTT')
                ->where('dci.DP', 2)
                ->groupBy(['mr.DEPT', 'dci.CODE_ITM_NM', DB::raw('substr(mr.REGIST_DT, 0, 8)')]);

        // 외부 쿼리
        $outsideQuery = DB::raw("(
            SELECT
                'outside' AS DEPT_CODE,
                '외부' AS DEPT_NM,
                substr(bm.CREATED_DATE, 0, 8) AS created_date,
                count(*) AS cnt
            FROM bc_member bm
            WHERE EXTERNAL_YN = 'Y'
                AND bm.CREATED_DATE between {$param['start_date']} and {$param['end_date']}
            GROUP BY substr(bm.CREATED_DATE, 0, 8)
        )");

        $outsideApprovalDatas = DB::table($outsideQuery);

        //내부 union all 외부
        $userApprovalDatas = $userApprovalDatas->unionAll($outsideApprovalDatas)->orderBy('created_date')->get();

        //부서 리스트 쿼리
        $depts = DB::table('DD_CODE_ITEM')
                ->where('CODE_SET_ID', 220)
                ->where('DP', 2)
                ->get();

        $depts[] = (Object) [
            'code_itm_code' => 'outside',
            'code_itm_nm' => '외부'
        ];

        $startDateCarbon = new \Carbon\Carbon($param['start_date']);
        $endDateCarbon = new \Carbon\Carbon($param['end_date']);

        $diffDays = $endDateCarbon->diffInDays($startDateCarbon);
        $days = [];

        $statisticsByDateArray = [];
        foreach($userApprovalDatas as $daily) {
            $statisticsByDateArray[$daily->created_date][$daily->dept_code] = $daily;
        }

        for($i=0; $i<$diffDays; $i++) {
            $nowLoopDate = $startDateCarbon->format('Y-m-d');
            $nowLoopDateYmd = $startDateCarbon->format('Ymd');
            $days[$i]['created_date'] = $nowLoopDate;
            $statisticsData = $statisticsByDateArray[$nowLoopDateYmd] ?? null;

            if ($statisticsData) {
                $insideCnt = 0;
                foreach ($depts as $dept) {
                    $statisticsResultData = $statisticsByDateArray[$nowLoopDateYmd][$dept->code_itm_code] ?? null;
                    if (!$statisticsResultData) {
                        $statisticsResultData = (object) array(
                            'dept_code' => $dept->code_itm_code,
                            'dept_nm' => $dept->code_itm_nm,
                            'created_date' => $nowLoopDate,
                            'cnt' => (int) $dept->cnt ?? 0
                        );
                    }

                    $insideCnt += $statisticsResultData->dept_code != 'outside' ? $statisticsResultData->cnt : 0;
                    $days[$i][$dept->code_itm_code]=$statisticsResultData;
                }
                $days[$i]['inside'] = ['cnt' => $insideCnt];
            } else {
                $insideCnt = 0;
                foreach ($depts as $dept) {
                    $statisticsResultData = (object) array(
                        'dept_code' => $dept->code_itm_code,
                        'dept_nm' => $dept->code_itm_nm,
                        'created_date' => $nowLoopDate,
                        'cnt' => (int) $dept->cnt ?? 0
                    );

                    $insideCnt += $statisticsResultData->dept_code != 'outside' ? $statisticsResultData->cnt : 0;
                    $days[$i][$dept->code_itm_code]=$statisticsResultData;
                }
                $days[$i]['inside'] = ['cnt' => $insideCnt];
            }
            $startDateCarbon = $startDateCarbon->addDays(1);
        }

        return $days;
    }
    /**
     * 운영 > 사용신청 승인 
     *
     * @param $param
     * @return void
     */
    public function userApprovalStatistics($param)
    {
        $userApprovalDatas = DB::raw("(
            SELECT 
                mr.DEPT AS dept_code,
                dci.CODE_ITM_NM AS dept_nm,
                substr(mr.REGIST_DT, 0, 8) AS created_date,
                count(*) as cnt
            FROM MEMBER_REQUEST mr 
            JOIN DD_CODE_ITEM dci ON dci.CODE_ITM_CODE = mr.DEPT AND dci.CODE_SET_ID = 220
            WHERE mr.REGIST_DT BETWEEN {$param['start_date']} and {$param['end_date']}
            AND mr.DELETE_DT IS NULL 
            AND mr.STATUS = 'approval'
            AND mr.INSTT IS NOT NULL 
            AND dci.DP = 2
            GROUP BY mr.DEPT, dci.CODE_ITM_NM, substr(mr.REGIST_DT, 0, 8)
            UNION ALL (
                SELECT
                    'outside' AS DEPT_CODE,
                    '외부' AS DEPT_NM,
                    substr(bm.CREATED_DATE, 0, 8) AS created_date,
                    count(*) AS cnt
                FROM bc_member bm
                WHERE EXTERNAL_YN = 'Y'
                    AND bm.CREATED_DATE between {$param['start_date']} and {$param['end_date']}
                GROUP BY substr(bm.CREATED_DATE, 0, 8)
            )
        )");

        switch ($param['typeData']) {
            case 'week':
                # week
                $userApprovalDatas = DB::table($userApprovalDatas)->selectRaw("
                    dept_code, dept_nm, 
                    TO_CHAR(TO_DATE(created_date, 'YYYYMMDDHH24MISS'), 'YYYY') AS year,
                    TO_CHAR(TO_DATE(created_date, 'YYYYMMDDHH24MISS'), 'MM') AS month,
                    TO_CHAR(TO_DATE(TO_CHAR(TRUNC(TO_DATE(created_date, 'YYYYMMDDHH24MISS'), 'iw'), 'YYYYMMDD')), 'w') AS week,
                    sum(cnt) as cnt
                ")->groupBy([
                    'dept_code', 'dept_nm', 
                    DB::raw("TO_CHAR(TO_DATE(created_date, 'YYYYMMDDHH24MISS'), 'YYYY')"),
                    DB::raw("TO_CHAR(TO_DATE(created_date, 'YYYYMMDDHH24MISS'), 'MM')"),
                    DB::raw("TO_CHAR(TO_DATE(TO_CHAR(TRUNC(TO_DATE(created_date, 'YYYYMMDDHH24MISS'), 'iw'), 'YYYYMMDD')), 'w')")
                ])
                ->orderByRaw('YEAR, MONTH, WEEK ASC')
                ->get();
                // ->get();
                break;
            case 'month':
                # month
                $userApprovalDatas = DB::table($userApprovalDatas)->selectRaw("
                    dept_code, dept_nm, 
                    TO_CHAR(TO_DATE(created_date, 'YYYYMMDDHH24MISS'), 'YYYY') AS year,
                    TO_CHAR(TO_DATE(created_date, 'YYYYMMDDHH24MISS'), 'MM') AS month,
                    sum(cnt) as cnt
                ")->groupBy([
                    'dept_code', 'dept_nm', 
                    DB::raw("TO_CHAR(TO_DATE(created_date, 'YYYYMMDDHH24MISS'), 'YYYY')"),
                    DB::raw("TO_CHAR(TO_DATE(created_date, 'YYYYMMDDHH24MISS'), 'MM')")
                ])->get();
                break;
            case 'year':
                # year
                $userApprovalDatas = DB::table($userApprovalDatas)->selectRaw("
                    dept_code, dept_nm, 
                    TO_CHAR(TO_DATE(created_date, 'YYYYMMDDHH24MISS'), 'YYYY') AS year,
                    sum(cnt) as cnt
                ")->groupBy([
                    'dept_code', 'dept_nm', 
                    DB::raw("TO_CHAR(TO_DATE(created_date, 'YYYYMMDDHH24MISS'), 'YYYY')")
                ])->get();
                break;
            case 'one':
                # day
            default:
                $userApprovalDatas = DB::table($userApprovalDatas)->get();
                break;
        }

        // dd($userApprovalDatas);

        //부서 리스트 쿼리
        $depts = DB::table('DD_CODE_ITEM')
                ->where('CODE_SET_ID', 220)
                ->where('DP', 2)
                ->get();

        $depts[] = (Object) [
            'code_itm_code' => 'outside',
            'code_itm_nm' => '외부'
        ];

        $startDateCarbon = new \Carbon\Carbon($param['start_date']);
        $endDateCarbon = new \Carbon\Carbon($param['end_date']);

        // 날짜 타입에 따른 날짜 차이 
        switch ($param['typeData']) {
            case 'week':
                $diffDays = $endDateCarbon->diffInWeeks($startDateCarbon)+1;
                break;
            case 'month':
                $diffDays = ($endDateCarbon->diffInMonths($startDateCarbon))+1;
                break;
            case 'year':
                $diffDays = $endDateCarbon->diffInYears($startDateCarbon)+1;
                break;
            case 'one':
            default:
                $diffDays = $endDateCarbon->diffInDays($startDateCarbon);
                break;
        }

        $days = [];

        $statisticsByDateArray = [];
        foreach($userApprovalDatas as $userApprovalData) {
            switch ($param['typeData']) {
                case 'week':
                    if (!isset($userApprovalData->created_date)) $userApprovalData->created_date = $userApprovalData->year . '년 ' . $userApprovalData->month . '월 ' . $userApprovalData->week . '주';
                    break;
                case 'month':
                    if (!isset($userApprovalData->created_date)) $userApprovalData->created_date = $userApprovalData->year . '년 ' . $userApprovalData->month . '월';
                    break;
                case 'year':
                    if (!isset($userApprovalData->created_date)) $userApprovalData->created_date = $userApprovalData->year . '년';
                    break;
                case 'one':
                default:
                    break;
            }
            $statisticsByDateArray[$userApprovalData->created_date][$userApprovalData->dept_code] = $userApprovalData;
        }

        for($i=0; $i<$diffDays; $i++) {
            switch ($param['typeData']) {
                case 'week':
                    $weekOfMonth = $startDateCarbon->weekOfMonth;
                    $nowLoopDate = $startDateCarbon->format("y년 m월 {$weekOfMonth}주");
                    $nowLoopDateYmd = $nowLoopDate;
                    break;
                case 'month':
                    $nowLoopDate = $startDateCarbon->format('y년 m월');
                    $nowLoopDateYmd = $nowLoopDate;
                    break;
                case 'year':
                    $nowLoopDate = $startDateCarbon->format('y년');
                    $nowLoopDateYmd = $nowLoopDate;
                    break;
                case 'one':
                default:
                    $nowLoopDate = $startDateCarbon->format('Y-m-d');
                    $nowLoopDateYmd = $startDateCarbon->format('Ymd');
                    break;
            }
            $days[$i]['created_date'] = $nowLoopDate;
            $statisticsData = $statisticsByDateArray[$nowLoopDateYmd] ?? null;

            if ($statisticsData) {
                $insideCnt = 0;
                foreach ($depts as $dept) {
                    $statisticsResultData = $statisticsByDateArray[$nowLoopDateYmd][$dept->code_itm_code] ?? null;
                    if (!$statisticsResultData) {
                        $statisticsResultData = (object) array(
                            'dept_code' => $dept->code_itm_code,
                            'dept_nm' => $dept->code_itm_nm,
                            'created_date' => $nowLoopDate,
                            'cnt' => (int) $dept->cnt ?? 0
                        );
                    }

                    $insideCnt += $statisticsResultData->dept_code != 'outside' ? $statisticsResultData->cnt : 0;
                    $days[$i][$dept->code_itm_code]=$statisticsResultData;
                }
                $days[$i]['inside'] = ['cnt' => $insideCnt];
            } else {
                $insideCnt = 0;
                foreach ($depts as $dept) {
                    $statisticsResultData = (object) array(
                        'dept_code' => $dept->code_itm_code,
                        'dept_nm' => $dept->code_itm_nm,
                        'created_date' => $nowLoopDate,
                        'cnt' => (int) $dept->cnt ?? 0
                    );

                    $insideCnt += $statisticsResultData->dept_code != 'outside' ? $statisticsResultData->cnt : 0;
                    $days[$i][$dept->code_itm_code]=$statisticsResultData;
                }
                $days[$i]['inside'] = ['cnt' => $insideCnt];
            }
            // $startDateCarbon = $startDateCarbon->addDays(1);
            switch ($param['typeData']) {
                case 'week':
                    $startDateCarbon = $startDateCarbon->addWeeks(1);
                    break;
                case 'month':
                    $startDateCarbon = $startDateCarbon->addMonths(1);
                    break;
                case 'year':
                    $startDateCarbon = $startDateCarbon->addYears(1);
                    break;
                case 'one':
                default:
                    $startDateCarbon = $startDateCarbon->addDays(1);
                    break;
            }
        }

        return $days;
    }

        
    /**
     * 운영 > 의뢰 통계
     *
     * @param   $param
     * @return  void
     */
    public function requestStatistics($param)
    {
        if(!empty($param['user_search'])) {
            $_where = "AND ORD_WORK_ID IN (
                SELECT	user_id
                FROM	BC_MEMBER bm 
                WHERE 	user_id = '".$param['user_search']."'
                OR		user_nm LIKE '".$param['user_search']."'
            )";
        }
        $query = DB::raw("(
            SELECT
                CASE ORD_STATUS
                    WHEN 'ready' THEN substr(INPUT_DTM, 0, 8)
                    WHEN 'working' THEN substr(UPDT_DTM, 0, 8)
                    WHEN 'complete' THEN substr(COMPLETED_DTM, 0, 8)
                    WHEN 'cancel' THEN substr(UPDT_DTM, 0, 8)
                END AS CREATED_DATE,
                CASE ORD_STATUS
                    WHEN 'ready' THEN 'request'
                    WHEN 'working' THEN 'working'
                    WHEN 'complete' THEN 'complete'
                    WHEN 'cancel' THEN 'cancel'
                    ELSE NULL
                END AS ORD_STATUS
            FROM tb_ord
            WHERE input_dtm between '". $param['start_date'] ."' AND '". $param['end_date'] ."'
            AND ord_meta_cd = '".$param['filter'] ."'
            AND ord_status is not NULL
            {$_where}
        ) a");

        $startDateCarbon = new \Carbon\Carbon($param['start_date']);
        $endDateCarbon = new \Carbon\Carbon($param['end_date']);
        $diffDays = $endDateCarbon->diffInDays($startDateCarbon);

        switch ($param['typeData']) {
            case 'week':
                $query = DB::table($query)
                        ->selectRaw("
                            a.ORD_STATUS,
                            TO_CHAR(TO_DATE(a.created_date, 'YYYYMMDDHH24MISS'), 'YYYY') AS YEAR,
                            TO_CHAR(TO_DATE(a.created_date, 'YYYYMMDDHH24MISS'), 'MM') AS MONTH,
                            TO_CHAR(TO_DATE(TO_CHAR(TRUNC(TO_DATE(created_date, 'YYYYMMDDHH24MISS'), 'iw'), 'YYYYMMDD')), 'w') AS week,
                            count(*) AS cnt
                        ")
                        ->groupBy([
                            'a.ORD_STATUS', 
                            DB::raw("TO_CHAR(TO_DATE(a.created_date, 'YYYYMMDDHH24MISS'), 'YYYY')"),
                            DB::raw("TO_CHAR(TO_DATE(created_date, 'YYYYMMDDHH24MISS'), 'MM')"),
                            DB::raw("TO_CHAR(TO_DATE(TO_CHAR(TRUNC(TO_DATE(created_date, 'YYYYMMDDHH24MISS'), 'iw'), 'YYYYMMDD')), 'w')")
                        ])
                        ->orderByRaw('YEAR, MONTH, WEEK ASC')
                        ->get();

                $diffDays = $endDateCarbon->diffInWeeks($startDateCarbon)+1;
                break;
            case 'month':
                $query = DB::table($query)
                        ->selectRaw("
                            a.ORD_STATUS,
                            TO_CHAR(TO_DATE(a.created_date, 'YYYYMMDDHH24MISS'), 'YYYY') AS YEAR,
                            TO_CHAR(TO_DATE(a.created_date, 'YYYYMMDDHH24MISS'), 'MM') AS MONTH,
                            count(*) AS cnt
                        ")
                        ->groupBy([
                            'a.ORD_STATUS', 
                            DB::raw("TO_CHAR(TO_DATE(a.created_date, 'YYYYMMDDHH24MISS'), 'YYYY')"),
                            DB::raw("TO_CHAR(TO_DATE(created_date, 'YYYYMMDDHH24MISS'), 'MM')")
                        ])
                        ->orderByRaw('YEAR, MONTH ASC')
                        ->get();

                $diffDays = ($endDateCarbon->diffInMonths($startDateCarbon))+1;
                break;
            case 'year':
                $query = DB::table($query)
                        ->selectRaw("
                            a.ORD_STATUS,
                            TO_CHAR(TO_DATE(a.created_date, 'YYYYMMDDHH24MISS'), 'YYYY') AS YEAR,
                            count(*) AS cnt
                        ")
                        ->groupBy([
                            'a.ORD_STATUS', 
                            DB::raw("TO_CHAR(TO_DATE(a.created_date, 'YYYYMMDDHH24MISS'), 'YYYY')")
                        ])
                        ->orderByRaw('YEAR ASC')
                        ->get();
                $diffDays = $endDateCarbon->diffInYears($startDateCarbon)+1;
                break;
            case 'one':
            default:
                $query = DB::table($query)
                        ->selectRaw("
                            a.CREATED_DATE,
                            a.ORD_STATUS,
                            count(*) as cnt
                        ")
                        ->groupBy(['a.CREATED_DATE', 'a.ORD_STATUS'])
                        ->orderBy('a.CREATED_DATE')->get();
                $diffDays = $endDateCarbon->diffInDays($startDateCarbon);
                break;
        }

        $days = [];

        $ordStatus = ['request', 'working', 'complete', 'cancel'];

        $statisticsByDateArray = [];
        foreach($query as $daily) {
            switch ($param['typeData']) {
                case 'week':
                    if (!isset($daily->created_date)) $daily->created_date = $daily->year . '년 ' . $daily->month . '월 ' . $daily->week . '주';
                    break;
                case 'month':
                    if (!isset($daily->created_date)) $daily->created_date = $daily->year . '년 ' . $daily->month . '월';
                    break;
                case 'year':
                    if (!isset($daily->created_date)) $daily->created_date = $daily->year . '년';
                    break;
                case 'one':
                default:
                    # code...
                    break;
            }
            $statisticsByDateArray[$daily->created_date][$daily->ord_status] = $daily;
        }

        for($i=0; $i<$diffDays; $i++) {
            switch ($param['typeData']) {
                case 'week':
                    $weekOfMonth = $startDateCarbon->weekOfMonth;
                    $nowLoopDate = $startDateCarbon->format("y년 m월 {$weekOfMonth}주");
                    $nowLoopDateYmd = $nowLoopDate;
                    break;
                case 'month':
                    $nowLoopDate = $startDateCarbon->format('y년 m월');
                    $nowLoopDateYmd = $nowLoopDate;
                    break;
                case 'year':
                    $nowLoopDate = $startDateCarbon->format('y년');
                    $nowLoopDateYmd = $nowLoopDate;
                    break;
                case 'one':
                default:
                    $nowLoopDate = $startDateCarbon->format('Y-m-d');
                    $nowLoopDateYmd = $startDateCarbon->format('Ymd');
                    break;
            }
            $days[$i]['created_date'] = $nowLoopDate;
            $statisticsData = $statisticsByDateArray[$nowLoopDateYmd] ?? null;

            if ($statisticsData) {
                $totalCnt = 0;
                foreach ($ordStatus as $status) {
                    $statisticsResultData = $statisticsByDateArray[$nowLoopDateYmd][$status] ?? null;
                    if (!$statisticsResultData) {
                        $statisticsResultData = (object) array(
                            'status' => $status,
                            'created_date' => $nowLoopDate,
                            'cnt' => (int) 0
                        );
                    }

                    $totalCnt += $statisticsResultData->cnt;
                    $days[$i][$status]=$statisticsResultData;
                }
                $days[$i]['totalCnt'] = ['cnt' => $totalCnt];
            } else {
                $totalCnt = 0;
                foreach ($ordStatus as $status) {
                    $statisticsResultData = (object) array(
                        'status' => $status,
                        'created_date' => $nowLoopDate,
                        'cnt' => (int) 0
                    );

                    $totalCnt += $statisticsResultData->cnt;
                    $days[$i][$status]=$statisticsResultData;
                }
                $days[$i]['totalCnt'] = ['cnt' => $totalCnt];
            }
            
            switch ($param['typeData']) {
                case 'week':
                    $startDateCarbon = $startDateCarbon->addWeeks(1);
                    break;
                case 'month':
                    $startDateCarbon = $startDateCarbon->addMonths(1);
                    break;
                case 'year':
                    $startDateCarbon = $startDateCarbon->addYears(1);
                    break;
                case 'one':
                default:
                    $startDateCarbon = $startDateCarbon->addDays(1);
                    break;
            }
        }

        return $days;
    }


    // 콘텐츠 입수 > 유형별 
    public function contentTypeStatistics($param) {
        $selectQuery = "dt, item";
        switch ($param['typeData']) {
            case 'week':
                # week
                $selectQuery = "(TO_CHAR(TO_DATE(dt, 'YYYY-MM-DD'), 'YYYY')||'년 '||
                    TO_CHAR(TO_DATE(dt, 'YYYY-MM-DD'), 'MM')||'월 '||
                    TO_CHAR(TO_DATE(TO_CHAR(TRUNC(TO_DATE(dt, 'YYYY-MM-DD'), 'iw'), 'YYYY-MM-DD')), 'w')||'주') AS dt, 
                    item";
                break;
            case 'month':
                # month
                $selectQuery = "(TO_CHAR(TO_DATE(dt, 'YYYY-MM-DD'), 'YYYY')||'년 '||
                    TO_CHAR(TO_DATE(dt, 'YYYY-MM-DD'), 'MM')||'월') AS dt, 
                    item";
                break;
            case 'year':
                # year
                $selectQuery = "(TO_CHAR(TO_DATE(dt, 'YYYY-MM-DD'), 'YYYY')||'년') AS dt, 
                item";
                break;
            case 'one':
            default:
                $selectQuery = "dt, item";
                break;
        }
        $query = DB::raw("(
            SELECT
                *
            FROM (
                SELECT 
                    ${selectQuery}
                FROM 
                (
                    SELECT B.dt, A.ITEM FROM 
                    (
                        SELECT
                            TO_CHAR(TO_DATE(substr(CREATED_DATE, 0, 8), 'YYYY-MM-DD'), 'YYYY-MM-DD') AS CREATED_DATE,
                            case to_char(ud_content_id)
                                when '1' then 'origin'
                                when '7' then 'clip'
                                when '9' then 'news'
                                when '3' then 'master'
                                when '5' then 'image'
                                when '2' then 'clean'
                                when '4' then 'audio'
                                when '8' then 'cg'
                            end as item
                        FROM bc_content 
                        WHERE status NOT IN (-1, -2, -3)
                            AND CREATED_DATE BETWEEN {$param['start_date']} AND {$param['end_date']}
                    ) A,
                    (
                        SELECT TO_CHAR(dt, 'YYYY-MM-DD')AS dt FROM (
                            SELECT TO_DATE(substr({$param['start_date']}, 0, 8), 'YYYY-MM-DD') + LEVEL - 1 AS dt FROM dual
                            CONNECT BY LEVEL <= (TO_DATE(substr({$param['end_date']}, 0, 8), 'YYYY-MM-DD') - TO_DATE(substr({$param['start_date']}, 0, 8), 'YYYY-MM-DD') + 1)
                        )
                    ) B
                    WHERE B.dt = A.CREATED_DATE(+)
                ) 
            )
            pivot ( count(item) FOR item IN (
                        'origin' as origin,
                        'clip' as clip,
                        'news' as news,
                        'master' as master,
                        'clean' as clean, 
                        'image' AS image,
                        'audio' AS audio,
                        'cg' AS cg
                    )
            )
            ORDER BY dt ASC
        )");

        $query = DB::table($query)->get();

        return $query;
    }

    // 원본 삭제, 아카이브 삭제
    public function contentOriginalArchiveDeletedStatistics($param)
    {
        $selectQuery = "dt, item";
        switch ($param['typeData']) {
            case 'week':
                # week
                $selectQuery = "(TO_CHAR(TO_DATE(dt, 'YYYY-MM-DD'), 'YYYY')||'년 '||
                    TO_CHAR(TO_DATE(dt, 'YYYY-MM-DD'), 'MM')||'월 '||
                    TO_CHAR(TO_DATE(TO_CHAR(TRUNC(TO_DATE(dt, 'YYYY-MM-DD'), 'iw'), 'YYYY-MM-DD')), 'w')||'주') AS {$selectQuery}";
                break;
            case 'month':
                # month
                $selectQuery = "(TO_CHAR(TO_DATE(dt, 'YYYY-MM-DD'), 'YYYY')||'년 '||
                    TO_CHAR(TO_DATE(dt, 'YYYY-MM-DD'), 'MM')||'월') AS {$selectQuery}";
                break;
            case 'year':
                # year
                $selectQuery = "(TO_CHAR(TO_DATE(dt, 'YYYY-MM-DD'), 'YYYY')||'년') AS {$selectQuery}";
                break;
            case 'one':
            default:
                break;
        }

        $query = DB::raw("(
            SELECT * FROM 
                (
                    SELECT
                        {$selectQuery}
                    FROM (
                        SELECT B.dt, A.ITEM FROM 
                        (
                            SELECT
                                TO_CHAR(TO_DATE(substr(bt.complete_datetime, 0, 8), 'YYYY-MM-DD'), 'YYYY-MM-DD') AS complete_datetime,
                                case to_char(bc.ud_content_id)
                                    when '1' then 'origin'
                                    when '2' then 'clean'
                                    when '3' then 'master'
                                    when '4' then 'audio'
                                    when '5' then 'image'
                                    when '7' then 'clip'
                                    when '8' then 'cg'
                                    when '9' then 'news'
                                end as item
                            FROM	bc_task bt
                            LEFT JOIN bc_content bc ON (bc.CONTENT_ID = bt.SRC_CONTENT_ID)
                            WHERE	bt.status ='complete'
                            AND		bt.complete_datetime BETWEEN {$param['start_date']} AND {$param['end_date']}
                            AND		bt.TYPE = '100'
                            AND		bt.task_workflow_id IN (
                                            SELECT	task_workflow_id
                                            FROM	bc_task_workflow
                                            WHERE 	register='".$param['type']."'
                                            AND		TYPE = 'i'
                                    )
                        ) A,
                        (
                            SELECT TO_CHAR(dt, 'YYYY-MM-DD')AS dt FROM (
                                SELECT TO_DATE(substr({$param['start_date']}, 0, 8), 'YYYY-MM-DD') + LEVEL - 1 AS dt FROM dual
                                CONNECT BY LEVEL <= (TO_DATE(substr({$param['end_date']}, 0, 8), 'YYYY-MM-DD') - TO_DATE(substr({$param['start_date']}, 0, 8), 'YYYY-MM-DD') + 1)
                            )
                        ) B
                        WHERE B.dt = A.complete_datetime(+)
                    )
                ) pivot ( count(item) FOR item IN (
                                'origin' as origin,
                                'clean' as clean, 
                                'master' as master,
                                'audio' AS audio,
                                'image' AS image,
                                'clip' as clip,
                                'cg' AS cg,
                                'news' as news
                            )
                    )
                ORDER BY dt ASC
        )");

        $list = DB::table($query)->get();
        return $list;
    }

    // 콘텐츠 입수 > 부서별
    public function contentDepartmentStatistics($param) 
    {
        $selectQuery = "dt, dept";
        switch ($param['typeData']) {
            case 'week':
                # week
                $selectQuery = "(TO_CHAR(TO_DATE(dt, 'YYYY-MM-DD'), 'YYYY')||'년 '||
                    TO_CHAR(TO_DATE(dt, 'YYYY-MM-DD'), 'MM')||'월 '||
                    TO_CHAR(TO_DATE(TO_CHAR(TRUNC(TO_DATE(dt, 'YYYY-MM-DD'), 'iw'), 'YYYY-MM-DD')), 'w')||'주') AS dt, dept";
                break;
            case 'month':
                # month
                $selectQuery = "(TO_CHAR(TO_DATE(dt, 'YYYY-MM-DD'), 'YYYY')||'년 '||
                    TO_CHAR(TO_DATE(dt, 'YYYY-MM-DD'), 'MM')||'월') AS dt, dept";
                break;
            case 'year':
                # year
                $selectQuery = "(TO_CHAR(TO_DATE(dt, 'YYYY-MM-DD'), 'YYYY')||'년') AS dt, dept";
                break;
            case 'one':
            default:
                $selectQuery = "dt, dept";
                break;
        }
        $query = DB::raw("(
            SELECT * FROM (
                SELECT 
                    ${selectQuery}
                FROM (
                    SELECT 
                     b.dt, a.dept
                    FROM 
                    (
                        SELECT
                            TO_CHAR(TO_DATE(substr(bc.CREATED_DATE , 0, 8), 'YYYY-MM-DD'), 'YYYY-MM-DD') AS CREATED_DATE,
                            dci.CODE_ITM_NM AS dept
                        FROM bc_content bc 
                        INNER JOIN bc_member bm ON bm.USER_ID = bc.REG_USER_ID
                        RIGHT OUTER JOIN DD_CODE_ITEM dci ON dci.CODE_ITM_NM = bm.DEPT_NM
                        WHERE dci.CODE_SET_ID = 220 AND dci.DP = 2
                            AND bc.status NOT IN (-1, -2, -3)
                            AND bc.CREATED_DATE  BETWEEN {$param['start_date']} AND {$param['end_date']}
                    ) a,
                    (
                        SELECT TO_CHAR(dt, 'YYYY-MM-DD') AS dt FROM 
                        (
                            SELECT TO_DATE(substr({$param['start_date']}, 0, 8), 'YYYY-MM-DD') + LEVEL - 1 AS dt FROM dual
                            CONNECT BY LEVEL <= (TO_DATE(substr({$param['end_date']}, 0, 8), 'YYYY-MM-DD') - TO_DATE(substr({$param['start_date']}, 0, 8), 'YYYY-MM-DD') + 1)
                        )
                    ) b
                    WHERE b.dt = a.CREATED_DATE(+)
                ) 
            ) PIVOT ( count(dept) FOR dept IN ( 
                    '기획편성부' 기획편성부,
                    '방송보도부' 방송보도부,
                    '방송제작부' 방송제작부,
                    '운영관리부' 운영관리부,
                    '온라인콘텐츠부' 온라인콘텐츠부,
                    '방송기술부' 방송기술부,
                    '방송영상부' 방송영상부
                ))
            ORDER BY dt ASC
        )");

        $query = DB::table($query)->get();

        return $query;
    }

    /**
     * 콘텐츠 입수 > 프로그램별
     *
     * @param Array $param
     * @return void
     */
    public function contentProgramStatistics($param)
    {
        $_where = '';
        $_totalWhere = '';
        if(isset($param['category_title'])) {
            $_where = "and bc2.category_title like '%".$param['category_title']."%'";
            $_totalWhere = "and bc4.category_title like '%".$param['category_title']."%'";
        }
        $query = DB::raw("(
            SELECT *
            FROM (
                SELECT *
                FROM (
                    SELECT 	case to_char(bc.ud_content_id)
                                when '1' then 'origin'
                                when '2' then 'clean'
                                when '3' then 'master'
                                when '4' then 'audio'
                                when '5' then 'image'
                                when '7' then 'clip'
                                when '8' then 'cg'
                                when '9' then 'news'
                            end as ud_content,
                            bc2.CATEGORY_TITLE  
                    FROM 	BC_CONTENT bc
                    LEFT JOIN BC_CATEGORY bc2 ON (bc2.CATEGORY_ID = bc.CATEGORY_ID)
                    WHERE	bc.CREATED_DATE BETWEEN {$param['start_date']} AND {$param['end_date']}
                    AND 	bc.CATEGORY_FULL_PATH LIKE '%100/{$param['category_id']}%'
                    AND		bc.IS_DELETED = 'N'
                    {$_where}
                )
                pivot ( COUNT(ud_content) FOR ud_content IN (
                                        'origin' as origin,
                                        'clip' as clip,
                                        'news' as news,
                                        'master' as master,
                                        'clean' as clean, 
                                        'image' AS image,
                                        'audio' AS audio,
                                        'cg' AS cg
                                        )
                )
                UNION ALL
                SELECT *
                FROM 	(
                    SELECT 	case to_char(bc3.ud_content_id)
                                when '1' then 'origin'
                                when '2' then 'clean'
                                when '3' then 'master'
                                when '4' then 'audio'
                                when '5' then 'image'
                                when '7' then 'clip'
                                when '8' then 'cg'
                                when '9' then 'news'
                            end as ud_content,
                            'total' AS category_title
                    FROM	BC_CONTENT bc3 
                    LEFT JOIN BC_CATEGORY bc4 ON (bc4.CATEGORY_ID = bc3.CATEGORY_ID)
                    WHERE bc3.CREATED_DATE BETWEEN {$param['start_date']} AND {$param['end_date']}
                    AND 	bc3.CATEGORY_FULL_PATH LIKE '%100/{$param['category_id']}%'
                    AND		bc3.IS_DELETED = 'N'
                    {$_totalWhere}
                )
                pivot ( COUNT(ud_content) FOR ud_content IN (
                                        'origin' as origin,
                                        'clip' as clip,
                                        'news' as news,
                                        'master' as master,
                                        'clean' as clean, 
                                        'image' AS image,
                                        'audio' AS audio,
                                        'cg' AS cg
                                        )
                    )
            )
            ORDER BY (CASE WHEN category_title = 'total' THEN 1 ELSE 2 END), category_title asc
        )");

        $list = DB::table($query)->get();

        return $list;
    }

    /**
     * 콘텐츠 입수 > 프로그램별 > 콘텐츠 복원자료 통계
     *
     * @param [type] $param
     * @return void
     */
    public function contentCodeItemStatistics($param)
    {
        $_where = '';
        $_totalWhere = '';
        if(isset($param['category_title'])) {
            $_where = "and dci.CODE_ITM_NM like '%".$param['category_title']."%'";
            $_totalWhere = "and dci2.CODE_ITM_NM like '%".$param['category_title']."%'";
        }
        $query = DB::raw("(
            SELECT *
            FROM (
                SELECT *
                    FROM (
                        SELECT	case to_char(bc.ud_content_id)
                            when '1' then 'origin'
                            when '2' then 'clean'
                            when '3' then 'master'
                            when '4' then 'audio'
                            when '5' then 'image'
                            when '7' then 'clip'
                            when '8' then 'cg'
                            when '9' then 'news'
                        end as ud_content,
                        dci.CODE_ITM_NM AS category_title 
                FROM 	BC_CONTENT bc
                LEFT JOIN BC_USRMETA_CONTENT buc ON (buc.USR_CONTENT_ID = bc.content_id)
                RIGHT OUTER JOIN (
                            SELECT	code_itm_code,code_itm_nm
                            FROM	DD_CODE_ITEM 
                            WHERE	code_set_id IN (
                                                    SELECT	id 
                                                    FROM	DD_CODE_SET dcs 
                                                    WHERE	code_set_code = 'TELECINE_TY_SE'
                                                    )
                            ) dci
                            ON (dci.CODE_ITM_CODE = buc.TELECINE_TY_SE)
                WHERE	bc.IS_DELETED = 'N'
                AND bc.CATEGORY_FULL_PATH LIKE '%/{$param['category_id']}%'
                AND bc.CREATED_DATE BETWEEN {$param['start_date']} AND {$param['end_date']}
                {$_where}
                )
                pivot ( COUNT(ud_content) FOR ud_content IN (
                        'origin' as origin,
                        'clip' as clip,
                        'news' as news,
                        'master' as master,
                        'clean' as clean, 
                        'image' AS image,
                        'audio' AS audio,
                        'cg' AS cg
                        )
                )
                UNION ALL
                SELECT * FROM (
                    SELECT	case to_char(bc2.ud_content_id)
                            when '1' then 'origin'
                            when '2' then 'clean'
                            when '3' then 'master'
                            when '4' then 'audio'
                            when '5' then 'image'
                            when '7' then 'clip'
                            when '8' then 'cg'
                            when '9' then 'news'
                        end as ud_content,
                        'total' AS category_title
                    FROM 	BC_CONTENT bc2
                    LEFT JOIN BC_USRMETA_CONTENT buc2 ON (buc2.USR_CONTENT_ID = bc2.content_id)
                    RIGHT OUTER JOIN (
                                SELECT	code_itm_code,code_itm_nm
                                FROM	DD_CODE_ITEM 
                                WHERE	code_set_id IN (
                                                        SELECT	id 
                                                        FROM	DD_CODE_SET dcs 
                                                        WHERE	code_set_code = 'TELECINE_TY_SE'
                                                        )
                                ) dci2
                                ON (dci2.CODE_ITM_CODE = buc2.TELECINE_TY_SE)
                    WHERE	bc2.IS_DELETED = 'N'
                    AND bc2.CATEGORY_FULL_PATH LIKE '%/{$param['category_id']}%'
                    AND bc2.CREATED_DATE BETWEEN {$param['start_date']} AND {$param['end_date']}
                    {$_totalWhere}
                )
                pivot ( COUNT(ud_content) FOR ud_content IN (
                        'origin' as origin,
                        'clip' as clip,
                        'news' as news,
                        'master' as master,
                        'clean' as clean, 
                        'image' AS image,
                        'audio' AS audio,
                        'cg' AS cg
                    )
                )
            )
            ORDER BY (CASE WHEN category_title = 'total' THEN 1 ELSE 2 END), category_title asc
        )");

        $list = DB::table($query)->get();

        return $list;
    }

    /**
     * 콘텐츠 입수 > 프로그램별 > 나누리포털영상 통계
     *
     * @param [type] $param
     * @return void
     */
    public function contentPortalStatistics($param)
    {
        $_where = '';
        $_totalWhere = '';
        if(isset($param['category_title'])) {
            $_where = "and dci.CODE_ITM_NM like '%".$param['category_title']."%'";
            $_totalWhere = "and dci2.CODE_ITM_NM like '%".$param['category_title']."%'";
        }

        $query = DB::raw("(
            SELECT *
            FROM (
                SELECT *
                    FROM (
                        SELECT	case to_char(bc.ud_content_id)
                            when '1' then 'origin'
                            when '2' then 'clean'
                            when '3' then 'master'
                            when '4' then 'audio'
                            when '5' then 'image'
                            when '7' then 'clip'
                            when '8' then 'cg'
                            when '9' then 'news'
                        end as ud_content,
                        CASE to_char(dci.parnts_id)
                            WHEN '0' THEN dci.code_itm_nm
                            ELSE dci.parnts_nm || '/' || dci.code_itm_nm
                            END AS category_title,
                        dci.sort_ordr
                FROM 	BC_CONTENT bc
                LEFT JOIN BC_USRMETA_CONTENT buc ON (buc.USR_CONTENT_ID = bc.content_id)
                RIGHT OUTER JOIN (
                            SELECT	dc.code_itm_code,dc.code_itm_nm,dc.parnts_id,dc.sort_ordr,
                                    dci2.CODE_ITM_NM AS parnts_nm
                            FROM	DD_CODE_ITEM dc
                            LEFT JOIN DD_CODE_ITEM dci2 ON (dci2.ID = dc.PARNTS_ID)
                            WHERE	dc.code_set_id IN (
                                                    SELECT	id 
                                                    FROM	DD_CODE_SET dcs 
                                                    WHERE	code_set_code = 'INSTT'
                                                    )
                            order by dc.sort_ordr asc
                            ) dci
                ON (dci.CODE_ITM_CODE = buc.INSTT)
                WHERE	bc.IS_DELETED = 'N'
                AND bc.CATEGORY_FULL_PATH LIKE '%/{$param['category_id']}%'
                AND bc.CREATED_DATE BETWEEN {$param['start_date']} AND {$param['end_date']}
                {$_where}
                )
                pivot ( COUNT(ud_content) FOR ud_content IN (
                        'origin' as origin,
                        'clip' as clip,
                        'news' as news,
                        'master' as master,
                        'clean' as clean, 
                        'image' AS image,
                        'audio' AS audio,
                        'cg' AS cg
                        )
                )
                UNION ALL
                SELECT *
                FROM (
                    SELECT * FROM (
                        SELECT	case to_char(bc2.ud_content_id)
                                when '1' then 'origin'
                                when '2' then 'clean'
                                when '3' then 'master'
                                when '4' then 'audio'
                                when '5' then 'image'
                                when '7' then 'clip'
                                when '8' then 'cg'
                                when '9' then 'news'
                            end as ud_content,
                            'total' AS category_title,
                            1 as sort_ordr
                        FROM 	BC_CONTENT bc2
                        LEFT JOIN BC_USRMETA_CONTENT buc2 ON (buc2.USR_CONTENT_ID = bc2.content_id)
                        RIGHT OUTER JOIN (
                                    SELECT	code_itm_code,code_itm_nm
                                    FROM	DD_CODE_ITEM 
                                    WHERE	code_set_id IN (
                                                            SELECT	id 
                                                            FROM	DD_CODE_SET dcs 
                                                            WHERE	code_set_code = 'INSTT'
                                                            )
                                    ) dci2
                        ON (dci2.CODE_ITM_CODE = buc2.INSTT)
                        WHERE	bc2.IS_DELETED = 'N'
                        AND bc2.CATEGORY_FULL_PATH LIKE '%/{$param['category_id']}%'
                        AND bc2.CREATED_DATE BETWEEN {$param['start_date']} AND {$param['end_date']}
                        {$_totalWhere}
                    )
                    pivot ( COUNT(ud_content) FOR ud_content IN (
                                                'origin' as origin,
                                                'clip' as clip,
                                                'news' as news,
                                                'master' as master,
                                                'clean' as clean, 
                                                'image' AS image,
                                                'audio' AS audio,
                                                'cg' AS cg
                                            )
                    )
                )
            ) 
            order by sort_ordr asc
        )");

        $list = DB::table($query)->get();

        return $list;
    }

    /**
     * 부제별 (회차) 통계
     *
     * @param [type] $param
     * @return void
     */
    public function contentEpisodeStatistics($param)
    {
        if(empty($param['category_id'])) {
            return [];
        }
        
        $query = DB::raw("(
            SELECT *
            FROM (
                SELECT	buc.TME_NO,
                        case to_char(bc.ud_content_id)
                            when '1' then 'origin'
                            when '2' then 'clean'
                            when '3' then 'master'
                            when '4' then 'audio'
                            when '5' then 'image'
                            when '7' then 'clip'
                            when '8' then 'cg'
                            when '9' then 'news'
                        end as ud_content
                FROM 	BC_CONTENT bc 
                LEFT JOIN BC_USRMETA_CONTENT buc ON (buc.USR_CONTENT_ID = bc.CONTENT_ID)
                WHERE 	bc.CREATED_DATE BETWEEN {$param['start_date']} AND {$param['end_date']}
                AND 	bc.CATEGORY_ID = {$param['category_id']}
                AND		buc.TME_NO IS NOT NULL
            )
            pivot ( COUNT(ud_content) FOR ud_content IN (
                                    'origin' as origin,
                                    'clean' as clean, 
                                    'master' as master,
                                    'audio' AS audio,
                                    'image' AS image,
                                    'clip' as clip,
                                    'cg' AS cg,
                                    'news' as news
                                    )
                                )
            ORDER BY tme_no ASC
        )");

        $episodeList = DB::table($query)->get();
        return $episodeList;
    }

    // 콘텐츠 입수 > 포맷별
    public function contentFormatStatistics($param) 
    {
        $formats = $this->getContentFormat($param);

        if (empty($formats)) return null;

        $pivotStr = '';

        foreach ($formats as $key => $value) {
            $trimValue = trim($value);

            if ($trimValue == 'ETC' || $trimValue == 'etc') {
                $pivotStr .= "'/' as \"ETC\"";
            } else if (strlen($trimValue) > 30) {
                $trimValue = str_replace(" ", "", $trimValue);
                if (strlen($trimValue) > 30) $trimValue = substr($trimValue, 0, 30);
                $pivotStr .= "'{$value}' as \"{$trimValue}\"";
            } else {
                $pivotStr .= "'{$value}' as \"{$trimValue}\"";
            }

            if (count($formats) - 1 > $key) {
                $pivotStr = $pivotStr.',';
            }
        }

        $selectQuery = "dt, format";
        switch ($param['typeData']) {
            case 'week':
                # week
                $selectQuery = "(TO_CHAR(TO_DATE(dt, 'YYYY-MM-DD'), 'YYYY')||'년 '||
                    TO_CHAR(TO_DATE(dt, 'YYYY-MM-DD'), 'MM')||'월 '||
                    TO_CHAR(TO_DATE(TO_CHAR(TRUNC(TO_DATE(dt, 'YYYY-MM-DD'), 'iw'), 'YYYY-MM-DD')), 'w')||'주') AS dt, 
                    format";
                break;
            case 'month':
                # month
                $selectQuery = "(TO_CHAR(TO_DATE(dt, 'YYYY-MM-DD'), 'YYYY')||'년 '||
                    TO_CHAR(TO_DATE(dt, 'YYYY-MM-DD'), 'MM')||'월') AS dt, 
                    format";
                break;
            case 'year':
                # year
                $selectQuery = "(TO_CHAR(TO_DATE(dt, 'YYYY-MM-DD'), 'YYYY')||'년') AS dt, 
                    format";
                break;
            case 'one':
            default:
                $selectQuery = "dt, format";
                break;
        }

        $query = DB::raw("(
            SELECT
                *
            FROM (
                SELECT ${selectQuery} FROM (
                    SELECT b.dt, a.format FROM 
                    (
                        SELECT 
                            TO_CHAR(TO_DATE(substr(bc.CREATED_DATE, 0, 8), 'YYYY-MM-DD'), 'YYYY-MM-DD') AS CREATED_DATE,
                            CASE bc.BS_CONTENT_ID
                                WHEN 506 THEN 'VIDEO'
                                WHEN 515 THEN 'AUDIO'
                                WHEN 518 THEN 'IMAGE'
                            END AS CONTENT_TITLE,
                            CASE bc.BS_CONTENT_ID
                                WHEN 506 
                                    THEN TRIM(NVL(SYS_VIDEO_CODEC, 'ETC') || '/' || NVL(SYS_VIDEO_WRAPER, 'ETC'))
                                WHEN 515 
                                    THEN NVL(TRIM(SYS_AUDIO_CODEC), 'ETC(AUDIO)')
                                WHEN 518 
                                    THEN NVL(TRIM(SYS_IMAGE_FORMAT), 'ETC(IMAGE)')
                            END AS FORMAT
                        FROM BC_SYSMETA_MOVIE bsm
                        JOIN BC_CONTENT bc ON bc.CONTENT_ID = bsm.SYS_CONTENT_ID
                        JOIN BC_BS_CONTENT bbc ON bbc.BS_CONTENT_ID = bc.BS_CONTENT_ID
                        WHERE bc.status >= 0
                            AND bc.CREATED_DATE BETWEEN {$param['start_date']} AND {$param['end_date']}
                    ) a,
                    (
                        SELECT TO_CHAR(dt, 'YYYY-MM-DD')AS dt FROM (
                            SELECT TO_DATE(substr({$param['start_date']}, 0, 8), 'YYYY-MM-DD') + LEVEL - 1 AS dt FROM dual
                            CONNECT BY LEVEL <= (TO_DATE(substr({$param['end_date']}, 0, 8), 'YYYY-MM-DD') - TO_DATE(substr({$param['start_date']}, 0, 8), 'YYYY-MM-DD') + 1)
                        )
                    ) b
                    WHERE b.dt = a.CREATED_DATE(+)
                ) 
            )
            pivot ( count(format) FOR format IN (
                        {$pivotStr}
                    )
            )
            ORDER BY dt ASC 
        )");

        $query = DB::table($query)->get();

        return $query;
    }

    public function getContentFormat($param)
    {
        $query = DB::raw("(
            SELECT * FROM (
                SELECT
                    CASE bc.BS_CONTENT_ID
                        WHEN 506 THEN 'VIDEO'
                        WHEN 515 THEN 'AUDIO'
                        WHEN 518 THEN 'IMAGE'
                    END AS CONTENT_TITLE,
                    CASE bc.BS_CONTENT_ID
                        WHEN 506 
                            THEN TRIM(NVL(SYS_VIDEO_CODEC, 'ETC') || '/' || NVL(SYS_VIDEO_WRAPER, 'ETC'))
                        WHEN 515 
                            THEN NVL(TRIM(SYS_AUDIO_CODEC), 'ETC(AUDIO)')
                        WHEN 518 
                            THEN NVL(TRIM(SYS_IMAGE_FORMAT), 'ETC(IMAGE)')
                    END AS FORMAT
                FROM bc_sysmeta_movie bsm
                JOIN BC_CONTENT bc ON bc.CONTENT_ID = bsm.SYS_CONTENT_ID
                WHERE bc.status NOT IN (-1, -2, -3)
                    AND bc.CREATED_DATE BETWEEN {$param['start_date']} AND {$param['end_date']}
                )
            GROUP BY CONTENT_TITLE, FORMAT
            ORDER BY CONTENT_TITLE DESC
        )");

        $query = DB::table($query)->get();
        $returnArr = [];
        foreach ($query as $value) {

            $value->format = trim($value->format);
            
            if ($value->format == '/') {
                $returnArr[] = "ETC";
            } else {
                $returnArr[] = $value->format;
            }
        }

        return $returnArr;
    }

    
    /**
     * 콘텐츠 출처별 통계
     *
     * @param array $param
     * @return void
     */
    public function contentSourceStatistics($param)
    {
        $selectQuery = "dt, reg_type";
        switch ($param['typeData']) {
            case 'week':
                # week
                $selectQuery = "(TO_CHAR(TO_DATE(dt, 'YYYY-MM-DD'), 'YYYY')||'년 '||
                    TO_CHAR(TO_DATE(dt, 'YYYY-MM-DD'), 'MM')||'월 '||
                    TO_CHAR(TO_DATE(TO_CHAR(TRUNC(TO_DATE(dt, 'YYYY-MM-DD'), 'iw'), 'YYYY-MM-DD')), 'w')||'주') AS dt, 
                    reg_type";
                break;
            case 'month':
                # month
                $selectQuery = "(TO_CHAR(TO_DATE(dt, 'YYYY-MM-DD'), 'YYYY')||'년 '||
                    TO_CHAR(TO_DATE(dt, 'YYYY-MM-DD'), 'MM')||'월') AS dt, 
                    reg_type";
                break;
            case 'year':
                # year
                $selectQuery = "(TO_CHAR(TO_DATE(dt, 'YYYY-MM-DD'), 'YYYY')||'년') AS dt, 
                 reg_type";
                break;
            case 'one':
            default:
                $selectQuery = "dt, reg_type";
                break;
        }
        $query = DB::raw("(
            SELECT
                *
            FROM (
                SELECT	${selectQuery}
                FROM 	
                    (
                        SELECT	B.dt,A.reg_type
                        FROM
                            (
                                SELECT 	TO_CHAR(TO_DATE(substr(bc.CREATED_DATE , 0, 8), 'YYYY-MM-DD'), 'YYYY-MM-DD') AS CREATED_DATE ,
                                        bm.reg_type
                                FROM 	BC_CONTENT bc
                                LEFT JOIN BC_MEDIA bm ON (bm.CONTENT_ID = bc.CONTENT_ID)
                                WHERE 	bc.CREATED_DATE BETWEEN {$param['start_date']} AND {$param['end_date']}
                                AND 	bc.IS_DELETED = 'N'
                                AND		bm.MEDIA_TYPE = 'original'
                                AND		bm.DELETE_DATE IS NULL 
                                GROUP BY bc.CREATED_DATE ,bm.reg_type
                            ) A,
                            (
                                SELECT	TO_CHAR(dt, 'YYYY-MM-DD') AS dt 
                                FROM (
                                    SELECT TO_DATE(substr({$param['start_date']}, 0, 8), 'YYYY-MM-DD') + LEVEL - 1 AS dt FROM dual
                                    CONNECT BY LEVEL <= (TO_DATE(substr({$param['end_date']}, 0, 8), 'YYYY-MM-DD') - TO_DATE(substr({$param['start_date']}, 0, 8), 'YYYY-MM-DD') + 1)
                                )
                            ) B
                        WHERE B.dt = A.CREATED_DATE(+)
                    ) 
            )
            pivot ( count(reg_type) FOR reg_type IN ( 
                                            'fileingest' AS fileingest, 
                                            'fcp' AS fcp, 
                                            'fcpx' AS fcpx, 
                                            'regist_clip' AS regist_clip, 
                                            'vs2ingest' AS vs2ingest, 
                                            'regist_portal' AS regist_portal, 
                                            'fileingest_web' AS fileingest_web
                                        )
                )
            ORDER BY dt
        )");

        $list = DB::table($query)->get();
        return $list;
    }
    
    /**
     * 리스토어 통계
     *
     * @param [type] $param
     * @return void
     */
    public function restoreStatistics($param)
    {
        $_contentTypeJoin = '';
        $_contentTypeWhere = '';
        $_taskWhere = '';
        if($param['content_type'] !== 'all') {
            $_contentTypeJoin = 'LEFT JOIN bc_content bc on (bc.content_id = bt.src_content_id)';
            $_contentTypeWhere = "AND bc.ud_content_id = {$param['content_type']}";
        }
        if($param['task_status'] !== 'all') {
            $_taskWhere = "AND bt.status = '".$param['task_status']."'";
        }

        $selectQuery = "dt, item";
        switch ($param['typeData']) {
            case 'week':
                # week
                $selectQuery = "(TO_CHAR(TO_DATE(dt, 'YYYY-MM-DD'), 'YYYY')||'년 '||
                    TO_CHAR(TO_DATE(dt, 'YYYY-MM-DD'), 'MM')||'월 '||
                    TO_CHAR(TO_DATE(TO_CHAR(TRUNC(TO_DATE(dt, 'YYYY-MM-DD'), 'iw'), 'YYYY-MM-DD')), 'w')||'주') AS {$selectQuery}";
                break;
            case 'month':
                # month
                $selectQuery = "(TO_CHAR(TO_DATE(dt, 'YYYY-MM-DD'), 'YYYY')||'년 '||
                    TO_CHAR(TO_DATE(dt, 'YYYY-MM-DD'), 'MM')||'월') AS {$selectQuery}";
                break;
            case 'year':
                # year
                $selectQuery = "(TO_CHAR(TO_DATE(dt, 'YYYY-MM-DD'), 'YYYY')||'년') AS {$selectQuery}";
                break;
            case 'one':
            default:
                break;
        }

        $query = DB::raw("(
            SELECT *
            FROM 
            (
                SELECT 
                    {$selectQuery}
                FROM (
                    SELECT 	B.dt,A.item FROM
                    (
                        SELECT 	TO_CHAR(TO_DATE(substr(bt.CREATION_DATETIME, 0, 8), 'YYYY-MM-DD'), 'YYYY-MM-DD') AS CREATION_DATETIME ,
                                DESTINATION AS item
                        FROM 	BC_TASK bt 
                        {$_contentTypeJoin}
                        WHERE 	bt.CREATION_DATETIME BETWEEN {$param['start_date']} AND {$param['end_date']}
                        AND		bt.DESTINATION IN ('file_restore','file_restore_xdcam','dtl_restore','dtl_restore_copy')
                        {$_contentTypeWhere}
                        {$_taskWhere}
                        GROUP BY CREATION_DATETIME ,DESTINATION
                    ) A,
                    (
                        SELECT TO_CHAR(dt, 'YYYY-MM-DD')AS dt FROM (
                            SELECT TO_DATE(substr({$param['start_date']}, 0, 8), 'YYYY-MM-DD') + LEVEL - 1 AS dt FROM dual
                            CONNECT BY LEVEL <= (TO_DATE(substr({$param['end_date']}, 0, 8), 'YYYY-MM-DD') - TO_DATE(substr({$param['start_date']}, 0, 8), 'YYYY-MM-DD') + 1)
                        )
                    ) B
                    WHERE 	B.dt = A.CREATION_DATETIME(+)
                )
            ) pivot ( count(item) FOR item IN (
                        'file_restore' AS file_restore,
                        'file_restore_xdcam' AS file_restore_xdcam,
                        'dtl_restore' AS dtl_restore,
                        'dtl_restore_copy' AS dtl_restore_copy
                        ) 
                    )
            ORDER BY dt
        )");

        $restoreList = DB::table($query)->get();

        return $restoreList;
    }
}