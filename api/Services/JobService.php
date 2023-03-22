<?php

namespace Api\Services;

use Api\Models\Fs\Job;
use Api\Types\JobStatus;
use Api\Services\BaseService;
use Api\Models\DataDicCodeSet;
use Api\Models\DataDicCodeItem;
use Illuminate\Database\Capsule\Manager as DB;

class JobService extends BaseService
{
    public function find($id)
    {
        $job = Job::find($id);
        return $job;
    }

    public function findOrFail($id)
    {
        $job = Job::find($id);
        if ($job === null) {
            api_abort_404(Job::class);
        }
        return $job;
    }

    /**
     * 작업 목록 조회
     *
     * @param array $conditions
     * @return \Illuminate\Support\Collection
     */
    public function index(array $conditions)
    {
        $startDate = $conditions['start_date'];
        $endDate = $conditions['end_date'];
        $status = $conditions['status'];
        $fileServerId = $conditions['file_server_id'];
        $start = $conditions['start'] ?? null;
        $limit = $conditions['limit'] ?? null;
        $searchType = $conditions['search_type'] ?? null;
        $searchText = $conditions['search_text'] ?? null;
        $type = $conditions['type'];
        $status = $conditions['status'];

        $startDate = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $startDate.' 00:00:00');
        $endDate = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $endDate.' 23:59:59');

        $query = Job::where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate);
 
        $query->leftJoin('bc_usrmeta_content', 'fs_jobs.content_id', '=', 'bc_usrmeta_content.usr_content_id');
        $query->leftJoin('bc_content', 'fs_jobs.content_id', '=', 'bc_content.content_id');
        $query->leftJoin('bc_member', 'fs_jobs.user_id','=','bc_member.user_id');
        
        $query->select(
            'fs_jobs.updated_at',
            'fs_jobs.started_at',
            'fs_jobs.content_id',
            'fs_jobs.status',
            'fs_jobs.file_server_id',
            'fs_jobs.type',
            'fs_jobs.id',
            'fs_jobs.file_path',
            'fs_jobs.filesize',
            'fs_jobs.progress',
            'fs_jobs.priority',
            'fs_jobs.finished_at',
            'fs_jobs.user_id',
            'fs_jobs.client_ip',
            'fs_jobs.client_ver',
            'fs_jobs.client_os',
            'fs_jobs.notify_status',
            'fs_jobs.created_at',
            'fs_jobs.metadata',
            'fs_jobs.transferred',
            'fs_jobs.api_user_id',
            'fs_jobs.file_id',
            'fs_jobs.title as fs_title',
            'bc_usrmeta_content.media_id',
            'bc_usrmeta_content.instt',
            'bc_content.title',
            DB::raw("((TO_DATE(TO_CHAR(fs_jobs.updated_at,'YYYY-MM-DD HH24:MI:SS'),'YYYY-MM-DD HH24:MI:SS') - TO_DATE(TO_CHAR(fs_jobs.started_at,'YYYY-MM-DD HH24:MI:SS'),'YYYY-MM-DD HH24:MI:SS'))*24*60*60) AS time")
        );
        if (!empty($fileServerId)) {
            $query->where('fs_jobs.file_server_id', $fileServerId);
        }
        $query->where('fs_jobs.status', '!=','queued');
        if ($status != 'all') {
            if( strstr($status, ',' ) ){
                $statusIn = explode(',', $status);
                $query->whereIn('fs_jobs.status', $statusIn);
            }else{
                $query->where('fs_jobs.status', $status);
            }
        }
        if($searchText != ''){
                switch($searchType){
                    // 2022.05.16 EJ 요구사항에서 콘텐츠ID 검색 삭제, 부처명,등록자 추가
                    // case 'content_id':
                    //     $query->where('fs_jobs.content_id', 'like', "%{$searchText}%");
                    // break;
                    case 'media_id':
                        $query->where('media_id', 'like', "%{$searchText}%");
                    break;
                    case 'title':
                        $query->where('bc_content.title', 'like', "%{$searchText}%");
                    break;
                    case 'instt_nm':
                        $codeSetQuery = DataDicCodeSet::query();
                        $codeSetQuery->where('code_set_code', '=', 'INSTT');
                        $codeSet = $codeSetQuery->first();
                        $codeSetId = $codeSet->id;
                        
                        $dataDicQuery = DataDicCodeItem::query();
                        $itemCodes = $dataDicQuery->where('code_set_id', $codeSetId)
                                    ->where('code_itm_nm','like',"%{$searchText}%")
                                    ->select('code_itm_code')
                                    ->get();

                        $query->whereIn('bc_member.org_id',$itemCodes);
                        break;
                    case 'job_user':
                        $query->where('bc_member.user_nm','like',"%{$searchText}%");
                    break;
                }
        }
        
        if($type != 'all'){
            $query->where('fs_jobs.type',$type);
        }
        
        $query->orderByDesc('fs_jobs.id');
        
        $jobs = paginate($query, $start, $limit);
        
        return $jobs;
    }

    /**
     * 작업 생성
     *
     * @param array $data
     * @param \Api\Models\User $apiUser
     * @return \Api\Models\Fs\Job
     */
    public function create($data, $apiUser)
    {
        $job = new Job();
        $job->type = $data['type'] ?? null;
        $job->file_path = $data['file_path'] ?? null;
        $job->filesize = $data['filesize'] ?? 0;
        $job->user_id = $data['user_id'] ?? null;
        if ($apiUser) {
            $job->api_user_id = $apiUser->user_id;
        }
        $job->metadata = $data['metadata'] ?? null;
        $job->status = $data['status'] ?? JobStatus::QUEUED;
        $job->file_id = $data['file_id'] ?? null;
        $job->content_id = $data['content_id'] ?? null;

        $job->client_ip = $data['client_ip'] ?? null;
        $job->client_ver = $data['client_ver'] ?? null;
        $job->client_os = $data['client_os'] ?? null;

        $job->progress = $data['progress'] ?? 0;

        $job->save();
        return $job;
    }
}
