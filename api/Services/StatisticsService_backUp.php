<?php

namespace Api\Services;

use Api\Services\BaseService;

use Api\Models\Content;
use Api\Models\Media;
use Api\Models\Task;
use Api\Models\UserContent;
use Illuminate\Database\Capsule\Manager as DB;

class StatisticsService extends BaseService
{
    /**
     * 아카이브 일별 통계
     *
     * @param [type] $param
     * @return void
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
            DB::raw("to_char(ROUND(sum(m.filesize) / 1024 / 1024 / 1024 ,2 ),'FM999990D99') AS filesize_gb"),
            DB::raw("ROUND(sum(m.filesize) / 1024 / 1024 / 1024 /1024 ,2) AS filesize_tb"),
            'c.ud_content_id'
        );
        $query->where('c.is_deleted', '=', 'N');
        $query->where('c.status', '>=', 0);
        $query->whereBetween('m.created_date',[$param['start_date'],$param['end_date']]);
        $query->groupBy(['ud_content_id',DB::raw('substr(m.CREATED_DATE,0,8)')]);
        $query->orderBy(DB::raw('substr(m.CREATED_DATE,0,8), UD_CONTENT_ID'));
        $statistics = $query->get();

        // 텔레시네 추가 조회
        $telecineQuery = DB::table('bc_content as c');
        $telecineQuery->join(DB::raw("(SELECT filesize, content_id, media_id,created_date FROM bc_media WHERE media_type='archive' AND filesize > 0) m"),function($join){
            $join->on('c.content_id', '=', 'm.content_id');
        });
        $telecineQuery->select(
            DB::raw("(SELECT category_title FROM BC_CATEGORY WHERE category_id = '205') as ud_content_title"),
            DB::raw("substr(m.created_date,0,8) AS created_date"),
            DB::raw('count(c.content_id) cnt'),
            DB::raw("to_char(ROUND(sum(m.filesize) / 1024 / 1024 / 1024 ,2 ),'FM999990D99') AS filesize_gb"),
            DB::raw("ROUND(sum(m.filesize) / 1024 / 1024 / 1024 /1024 ,2) AS filesize_tb"),
            DB::raw("0 AS ud_content_id")
        );
        $telecineQuery->where('c.is_deleted', '=', 'N');
        $telecineQuery->where('c.status', '>=', 0);
        $telecineQuery->where('c.category_full_path', 'like', '/0/100/205%');
        $telecineQuery->whereBetween('m.created_date',[$param['start_date'],$param['end_date']]);
        $telecineQuery->groupBy([DB::raw('substr(m.CREATED_DATE,0,8)')]);
        $telecineStatistics = $telecineQuery->get();
        $testArray = [];
        
        $mergeStatistics = $statistics->merge($telecineStatistics);
        
        return $mergeStatistics;
    }
    /**
     * 아카이브 주간 통계
     *
     * @param [type] $param
     * @return void
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
            DB::raw("to_char(ROUND(sum(m.filesize) / 1024 / 1024 / 1024 /1024 ,2),'FM99999990.00') AS filesize_tb"),
            'c.ud_content_id'
        );
        $query->where('c.is_deleted', '=', 'N');
        $query->where('c.status', '>=', 0);
        $query->whereBetween('m.created_date',[$param['start_date'],$param['end_date']]);
        $query->groupBy('ud_content_id');
        $query->orderBy('ud_content_id');
        
        $statistics = $query->get();

        // 텔레시네 통계 쿼리 따로 처리
        $telecineQuery = DB::table('bc_content as c');
        $telecineQuery->join(DB::raw("(SELECT filesize, content_id, media_id,created_date FROM bc_media WHERE media_type='archive' AND filesize > 0) m"),function($join){
            $join->on('c.content_id', '=', 'm.content_id');
        });
        $telecineQuery->select(
            DB::raw("(SELECT category_title FROM BC_CATEGORY WHERE category_id = '205') as ud_content_title"),
            DB::raw('count(c.content_id) cnt'),
            DB::raw("ROUND(sum(m.filesize) / 1024 / 1024 / 1024 ,2 ) AS filesize_gb"),
            DB::raw("to_char(ROUND(sum(m.filesize) / 1024 / 1024 / 1024 /1024 ,2),'FM99999990.00') AS filesize_tb")
        );
        $telecineQuery->where('c.is_deleted', '=', 'N');
        $telecineQuery->where('c.status', '>=', 0);
        $telecineQuery->where('c.category_full_path', 'like', '/0/100/205%');
        $telecineQuery->whereBetween('m.created_date',[$param['start_date'],$param['end_date']]);
        $telecineQuery->groupBy('category_id');
        $telecineStatistics = $telecineQuery->first();

        // 텔레시네 통계 병합
        $statistics[] = $telecineStatistics;
        $fillStatistics = $this->weekStatisticsFillInTheBlank($statistics);
        return $fillStatistics;
    }
    /**
     * 값이 없는 콘텐츠 0으로 채워주기
     *
     * @param [type] $lists
     * @return void
     */
    public function weekStatisticsFillInTheBlank($lists){
        // $udContentIdArrays = [1,2,3,7,9,0];
        $statisticsType = ['원본','클린본','마스터본','클립본','뉴스편집본','텔레시네'];
        $statistics = [];
        
        /**
         * // 기존 텔레시네 값이 없을때 쓰던 코드 다 수정된게 아니라서 참고용으로 남겨둠
         */
        // foreach($udContentIdArrays as $udContentIdArray){
        //     if($udContentIdArray === 0){
        //         $data = (object)array(
        //             'ud_content_title'=>'텔레시네',
        //             'cnt'=>'0',
        //             'filesize_gb'=>'0.00',
        //             'filesize_tb'=>'0.00',
        //             'ud_content_id'=>(string)$udContentIdArray
        //         );
        //         array_push($statistics, $data);
        //     }else{
        //         $udContent = $this->getUdContentByUdContentId($udContentIdArray);
        //         $udContentId = (string)$udContent->ud_content_id;
        //         $emptyCheck = true;
        //         foreach($lists as $list){
        //             if($list->ud_content_id === $udContentId){
        //                 array_push($statistics, $list);
        //                 $emptyCheck = false;
        //                 break;
        //             };
        //         }
        //         if($emptyCheck){
        //             // 값이 없을떄 0으로 채워준다.
        //             $data = (object)array(
        //                 'ud_content_title'=>$udContent->ud_content_title,
        //                 'cnt'=>'0',
        //                 'filesize_gb'=>'0.00',
        //                 'filesize_tb'=>'0.00',
        //                 'ud_content_id'=>(string)$udContent->ud_content_id
        //             );
        //             array_push($statistics, $data);
        //         }
        //     };
        // }
        foreach($statisticsType as $type){
            $isEmpty = true;
            foreach($lists as $list){
                if($type == $list->ud_content_title){
                    array_push($statistics, $list);
                    $isEmpty = false;
                    break;
                }
            }
            // 타입마다 반복문을 돌았지만 값이 없을때는 0으로 넣어준다
            if($isEmpty){
              $data = (object)array(
                    'ud_content_title'=>$type,
                    'cnt'=>'0',
                    'filesize_gb'=>'0.00',
                    'filesize_tb'=>'0.00',
                    'ud_content_id'=>(string)$udContent->ud_content_id
                );
                array_push($statistics, $data);
            }
        }
        return $statistics;
    }
    /**
     * ud_content_id 로 ud_content 찾기
     *
     * @param [type] $udContentId
     * @return void
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
     * @return void
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
        $archiveQuery = "(select ROOT_TASK from BC_TASK where DESTINATION like '%archive%' AND CREATION_DATETIME between {$startDate} AND {$endDate} AND status='complete' GROUP BY ROOT_TASK)";
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
}