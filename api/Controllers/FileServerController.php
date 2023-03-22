<?php

namespace Api\Controllers;

use Carbon\Carbon;
use Api\Models\File;
use Api\Models\Fs\Job;
use Proxima\core\Path;
use Proxima\core\Unit;
use Api\Http\ApiRequest;
use Api\Models\Category;
use Api\Types\JobStatus;
use Api\Http\ApiResponse;
use Api\Models\Fs\JobLog;
use Api\Types\Fs\JobType;
use Api\Types\CategoryType;
use Api\Services\JobService;
use Api\Models\Fs\FileServer;
use Api\Services\FileService;
use Api\Services\TaskService;
use Api\Services\UserService;
use Api\Services\MediaService;
use Api\Types\WorkflowChannel;
use Api\Services\ZodiacService;
use Api\Services\ContentService;
use Api\Controllers\BaseController;
use Api\Services\MediaSceneService;
use Api\Support\Helpers\CodeHelper;
use Api\Support\Helpers\UserHelper;
use Psr\Container\ContainerInterface;
use Api\Support\Helpers\MetadataMapper;
use Api\Support\Helpers\SMSMessageHelper;
use Api\Support\Helpers\ValidationHelper;

class FileServerController extends BaseController
{
    /**
     * 파일작업 서비스
     *
     * @var \Api\Services\JobService
     */
    private $jobService = null;

    private $contentService = null;
    private $mediaService = null;
    private $mediaSceneService = null;
    private $mapper = null;
    private $taskService = null;
    private $userService;

    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        // db 커넥션 연결
        $this->jobService = new JobService($container);

        $this->contentService = new ContentService($container);
        $this->mediaService = new MediaService($container);
        $this->mediaSceneService = new MediaSceneService($container);
        $this->mapper = new MetadataMapper($container);
        $this->taskService = new TaskService($container);
        $this->userService = new UserService($container);
        $this->zodiacService = new ZodiacService($container);
    }

    /**
     * 작업 목록 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param Api\Http\ApiResponse $response
     * @param array $args
     * @return Api\Http\ApiResponse
     */
    public function indexJob(ApiRequest $request, ApiResponse $response, array $args)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $searchText = $request->input('search_text');
        $searchType = $request->input('search_type');
        $status = $request->input('status');
        $fileServerId = $request->input('file_server_id');
        // $start = $request->input('start');
        $start = $request->start;
        // $limit = $request->input('limit');
        $limit = $request->limit;
        $type = $request->input('type');
        

        if (empty($startDate) || empty($endDate)) {
            api_abort('Date conditions should not empty.', 'date_conditions_should_not_empty', 400);
        }

        // dto만들기 귀찮아서;;;
        $conditions = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $status,
            'file_server_id' => $fileServerId,
            'start' => $start,
            'limit' => $limit,
            'search_text'=>$searchText,
            'search_type'=>$searchType,
            'type' => $type
        ];
        $jobs = $this->jobService->index($conditions);

        $unsetKeys=[];

        foreach ($jobs as$key=> $job) {
            if (!empty($job->metadata)) {
                $meta = $job->metadata[0];
                $job->content_title = $meta['title'] ?? '';
            }

            if(empty($job->title)) {
                $job->title = $job->fs_title;
            }
            // // 파일크기
            // $b=$job->filesize; 
            // $p=null;
            //     $units = array("B","KB","MB","GB","TB","PB","EB","ZB","YB");
            //     $c=0;
            //     if(!$p && $p !== 0) {
            //         foreach($units as $k => $u) {
            //             if(($b / pow(1024,$k)) >= 1) {
            //                 $r["bytes"] = $b / pow(1024,$k);
            //                 $r["units"] = $u;
            //                 $c++;
            //             }
            //         }
            //         $job->filesize = number_format($r["bytes"],2) . " " . $r["units"];
            //         $filesize = number_format($r["bytes"], 2);
            //         $unit = $r["units"];
            //     } else {
            //         $job->filesize = number_format($b / pow(1024,$p)) . " " . $units[$p];
            //         $filesize = number_format($b / pow(1024,$p));
            //         $unit = $r[$p];
            //     }
            //     if(!is_null($filesize) && !is_null($job->time)){
            //         $job->transmission_speed = round($filesize/$job->time,2)." ".$unit."/s";
            //     }
            
            if($job->file_id){
                $mediaInfoQuery = File::where('id',$job->file_id);
                $mediaInfoQuery->join('bc_media', 'files.media_id', '=', 'bc_media.media_id');
                $mediaInfo = $mediaInfoQuery->first();
                if($mediaInfo->media_type ){
                    $job->media_type = $mediaInfo->media_type;
                }
            }
            $timeCheck = false;
            if($job->time == 0){
                $timeCheck = true;
            };
                //     // 소요시간을 시분초로
                $h = sprintf("%02d", intval($job->time) / 3600);
                $tmp = $job->time % 3600;
                $m = sprintf("%02d", $tmp / 60);
                $s = sprintf("%02d", $tmp % 60);
                $job->job_time = $h.':'.$m.':'.$s;
                $byteFileSize = $job->filesize;
                $job->filesize = Unit::formatBytes($job->filesize);
        
                $filesize = substr($job->filesize,0,-3);
                
                if($timeCheck && !is_null($job->started_at) && !is_null($job->updated_at)){
                    $job->transmission_speed = $filesize." ".substr($job->filesize, -2)."/s";
                }else{
                    // 전송속도
                    if(!is_null($filesize) && !is_null($job->time)){
                        // $job->transmission_speed = round($filesize/$job->time,2)." ".substr($job->filesize, -2)."/s";
                        $job->transmission_speed = Unit::formatBytes(round($byteFileSize/$job->time,2))."/s";
                    }
                }
              
            
            $job->transferred = Unit::formatBytes($job->transferred);
            
        
            if (!empty($job->user_id)) {
                $userService = new UserService($this->container);
                
                // 포털 유저
                if ($job->api_user_id === 'portal') {
                    $jobUser = $userService->findPortalUser($job->user_id);

                    
                    // 등록기관
                    $insttCode = $jobUser->org_id;
                    
                    $insttCodeItem = CodeHelper::findCodeItemBycodeSetCodeAndCodeItemCode('INSTT', $insttCode);
                    $job->instt_nm =$insttCodeItem->code_itm_nm ?? '';

                } else {
                    // CMS 유저
                    $jobUser = $userService->findByUserId($job->user_id);
                }
                
                $job->job_user = $userService->getMapUserField($jobUser);
            }
        }
        
        return response()->ok($jobs);
    }

    /**
     * 작업 생성
     *
     * @param \Api\Http\ApiRequest $request
     * @param Api\Http\ApiResponse $response
     * @param array $args
     * @return Api\Http\ApiResponse
     */
    public function storeJob(ApiRequest $request, ApiResponse $response, array $args)
    {
        $data = $request->all();

        ValidationHelper::emptyValidate($data, ['type', 'user_id']);
        if ($data['type'] == JobType::DOWNLOAD) {
            ValidationHelper::emptyValidate($data, ['file_id']);
            $fileService = new FileService($this->container);
            $file = $fileService->findOrFail($data['file_id']);
            $data['file_path'] = Path::fixSeparator(Path::join($file->file_path, $file->file_name), '/');
            $data['filesize'] = $file->file_size;
            $data['content_id'] = $file->content_id;
            $contentService = new ContentService($this->container);
            $content = $contentService->findOrFail($file->content_id);
            // 다운로드 시 타이틀과 재생길이 추가
            $data['title'] = $content->title;
            $sysMeta = $content->sysMeta;
            $data['duration'] = $sysMeta->getDurationSeconds();


            $isHttp = $data['is_http'] ?? false;
            if ($isHttp == true ) {
                $data['status'] = 'finished';
                $data['progress'] = 100;
            }
        }

        $apiUser = auth()->user();
        if ($apiUser && $apiUser->user_nm === 'portal') {
            // 포털 유저이면 prefix를 붙인다
            $data['user_id'] = UserHelper::portalUserId($data['user_id']);
        }
        $job = $this->jobService->create($data, $apiUser);

        return response()->ok($job);
    }

        /**
     * 일괄 작업 생성
     *
     * @param \Api\Http\ApiRequest $request
     * @param Api\Http\ApiResponse $response
     * @param array $args
     * @return Api\Http\ApiResponse
     */
    public function storeManyJob(ApiRequest $request, ApiResponse $response, array $args)
    {
        $jobs = [];
        $datas = $request->all();

        ValidationHelper::emptyValidate($datas, ['type', 'user_id']);
        $apiUser = auth()->user();

        if($datas['type'] == JobType::UPLOAD){

            $listAll = $datas['metadata'];

            if(!is_array($listAll)){
                api_abort('invalid_metadata', 'invalid_metadata', 400);
            }
            
            foreach($listAll as $metadata)
            {
                $data = [
                    'type' => $datas['type'],
                    'user_id'=> $datas['user_id'],
                    'metadata' => [$metadata]
                ];
        
                if ($apiUser && $apiUser->user_nm === 'portal') {
                    // 포털 유저이면 prefix를 붙인다
                    $data['user_id'] = UserHelper::portalUserId($datas['user_id']);
                }
        
                $job = $this->jobService->create($data, $apiUser);
                $jobs [] = $job;
            }
        }else if($datas['type'] == JobType::DOWNLOAD){
            $listAll = $datas['file_ids'];
            if(!is_array($listAll)){
                api_abort('invalid_metadata', 'invalid_metadata', 400);
            }
            foreach($listAll as $fileId)
            {
                $data = [
                    'type' => $datas['type'],
                    'user_id'=> $datas['user_id']
                ];
                if ($datas['type'] == JobType::DOWNLOAD) {
              
                    $fileService = new FileService($this->container);
                    $file = $fileService->findOrFail($fileId);
                    $data['file_path'] = Path::fixSeparator(Path::join($file->file_path, $file->file_name), '/');
                    $data['filesize'] = $file->file_size;
                    $data['content_id'] = $file->content_id;
                    $contentService = new ContentService($this->container);
                    $content = $contentService->findOrFail($file->content_id);
                    // 다운로드 시 타이틀과 재생길이 추가
                    $data['title'] = $content->title;
                    $sysMeta = $content->sysMeta;
                    $data['duration'] = $sysMeta->getDurationSeconds();        
        
                    $isHttp = $data['is_http'] ?? false;
                    if ($isHttp == true ) {
                        $data['status'] = 'finished';
                        $data['progress'] = 100;
                    }
                }
        
                if ($apiUser && $apiUser->user_nm === 'portal') {
                    // 포털 유저이면 prefix를 붙인다
                    $data['user_id'] = UserHelper::portalUserId($datas['user_id']);
                }
        
                $job = $this->jobService->create($data, $apiUser);
                $jobs [] = $job;
            }
        }

        return response()->ok($jobs);
    }

    /**
     * 일괄 작업 상세 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param Api\Http\ApiResponse $response
     * @param array $args
     * @return Api\Http\ApiResponse
     */
    public function showManyJob(ApiRequest $request, ApiResponse $response, array $args)
    {
        $jobs = [];
        $jobIds = $args['job_ids'];
        $jobIdRows = explode(',',$jobIds);
       
        foreach($jobIdRows as $jobId)
        {
            $job = $this->jobService->findOrFail($jobId);
            
            $job->file_server = $job->file_server;
            if ($job->type == JobType::DOWNLOAD) {
                $job->file_path =  config('web_upload')['root_path'].$job->file_path;
                $paths = explode('/', $job->file_path);
                $filenameExt = array_pop($paths);
                $filenames = explode('.', $filenameExt);
                $ext = array_pop($filenames);
                $filename = join('.', $filenames);
                $job->download_filename = $filename;
                $job->title = $filename;
            }else{
                if(empty($job->title)){
                    $job->title = $job->metadata[0]['title'];
                }
                //$job->download_filename = $job->title ;
            }
            $jobs [] = $job;
        }

        return response()->ok($jobs);
    }

    /**
     * 작업 상세 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param Api\Http\ApiResponse $response
     * @param array $args
     * @return Api\Http\ApiResponse
     */
    public function showJob(ApiRequest $request, ApiResponse $response, array $args)
    {
        $jobId = $args['job_id'];
        $job = $this->jobService->findOrFail($jobId);
        $job->file_server = $job->file_server;

        return response()->ok($job);
    }

    /**
     * 작업 업데이트
     *
     * @param \Api\Http\ApiRequest $request
     * @param Api\Http\ApiResponse $response
     * @param array $args
     * @return Api\Http\ApiResponse
     */
    public function updateJob(ApiRequest $request, ApiResponse $response, array $args)
    {
        $jobId = $args['job_id'];
        $job = $this->jobService->findOrFail($jobId);

        return response()->ok();
    }

    /**
     * 작업 업데이트
     *
     * @param \Api\Http\ApiRequest $request
     * @param Api\Http\ApiResponse $response
     * @param array $args
     * @return Api\Http\ApiResponse
     */
    public function updateJobPriority(ApiRequest $request, ApiResponse $response, array $args)
    {
        $jobId = $args['job_id'];
        $data = $request->all();
        
        $job = $this->jobService->findOrFail($jobId);
        $job->priority = $data['priority'];
        $job->save();
        return response()->ok();
    }

    /**
     * 작업 상태 업데이트
     *
     * @param \Api\Http\ApiRequest $request
     * @param Api\Http\ApiResponse $response
     * @param array $args
     * @return Api\Http\ApiResponse
     */
    public function updateJobStatus(ApiRequest $request, ApiResponse $response, array $args)
    {
        $jobId = $args['job_id'];
        $job = $this->jobService->findOrFail($jobId);


        $beforeStatus = $job->status ;

        $data = $request->only(['progress', 'status', 'transferred']);

        if (
            $job->status !== JobStatus::WORKING &&
            $data['status'] === JobStatus::WORKING
        ) {
            $job->started_at = \Carbon\Carbon::now();
            if ($job->type === JobType::UPLOAD) {
                // file_path 업데이트 해야 함
                if (empty($job->file_path)) {
                    $job->file_path = $request->input('file_path');
                    $job->filesize = $request->input('filesize');
                }
            }
            $job->log()->create([
                'type' => 'info',
                'message' => '작업시작'
            ]);
        }

        $job->progress = $data['progress'];
        $job->status = $data['status'];
        $job->transferred = $data['transferred'];

        if ($job->status === JobStatus::FINISHED) {
            $job->finished_at = \Carbon\Carbon::now();
        }

        $job->save();

        if ( $job->status === JobStatus::FINISHED) {
            if ($job->type === JobType::UPLOAD) {
                // 콘텐츠 등록...
                $user = auth()->user();
                if (empty($job->metadata)) {
                    $error = '작업정보에 메타데이터가 등록되어 있지 않습니다.';
                    $this->handleJobError($job, $error);
                    api_abort($error, 'no_metadata', 400);
                }
                $metadata = $job->metadata[0];
                $filename = $job->file_path;

                $regUserId = $job->user_id;

                if ($job->api_user_id == 'portal') {
                    $channel = WorkflowChannel::REGISTER_PORTAL;
                    $categoryId = CategoryType::PORTAL;
                    $metadata['prsrv_pd']= date("Ymd",strtotime('+1 days'));
                } else {
                    $error = '유효하지 않은 사용자입니다.';
                    $this->handleJobError($job, $error);
                    api_abort($error, 'invalid_user', 400);
                }

                if (!$filename) {
                    $error = '파일명이 비어있습니다.';
                    $this->handleJobError($job, $error);
                    api_abort_404($error);
                }

                $metaList = $this->contentService->getExplodeMeta($metadata);
                $listMap = [
                    'content' => [],
                    'usr_meta' => [],
                    'sys_meta' => [],
                    'status_meta' => [],
                ];
                foreach ($metaList as $key => $list) {
                    $listMap[$key] = $this->mapper->fieldMapper($list, $key, true);
                }
                $udContentMap = $this->mapper->getUdContentMap(true);
                $bsContentMap = $this->mapper->getBsContentMap(true);

                $listMap['content']['ud_content_id'] = $udContentMap[$listMap['content']['ud_content_id']];
                $listMap['content']['bs_content_id'] = $bsContentMap[$listMap['content']['bs_content_id']];
                        
                        
                if ($job->api_user_id == 'portal') {
                    $regUser = $this->userService->findPortalUser($listMap['content']['reg_user_id']);
                    if($regUser){
                        $listMap['content']['reg_user_id'] = $regUser->user_id;
                        $regUserId = $regUser->user_id;
                    }else{
                        $error = '유효하지 않은 사용자.';
                        $this->handleJobError($job, $error);
                        api_abort_404('User ID');
                    }
                }

                // if (!empty($listMap['usr_meta']['regist_instt'])) {
                //     $detailCategory = Category::where('parent_id', $categoryId)
                //         ->where('code', $listMap['usr_meta']['regist_instt'])->select('category_id')->first();
                //     if ($detailCategory) {
                //         $categoryId = $detailCategory->category_id;
                //     }
                // }
                $listMap['content']['category_id'] = $categoryId;

                $content = $this->contentService->createUsingArray($listMap['content'], $listMap['status_meta'], $listMap['sys_meta'], $listMap['usr_meta']);

                $contentId = $content->content_id;
                if (!$contentId) {
                    $error = '콘텐츠 생성 실패.';
                    $this->handleJobError($job, $error);
                    api_abort($error, 'fail_to_create_content', 500);
                }

                $job->content_id = $contentId;
                $job->title = $content->title;
                $job->save();

                $task = $this->taskService->getTaskManager();
                $taskId = $task->insert_task_query_outside_data($contentId, $channel, 1, $regUserId, $filename);

                if (!$taskId) {
                    $error = '워크플로우 실행 실패.';
                    $this->handleJobError($job, $error);
                    api_abort($error, 'fail_to_run_workflow', 500);
                }
            }

            $job->log()->create([
                'type' => 'info',
                'message' => $job->progress . ' 작업완료'
            ]);
        } else {

            if( $job->status === JobStatus::FAILED ||  $job->status === JobStatus::ERROR ){

                //오류 발생시 관리자 알림
                $isErrorCount = JobLog::where('job_id', $job->id )->where('type',  $job->status)->count();
                if( $isErrorCount < 10){
                    $job->log()->create([
                        'type' => $job->status,
                        'message' => $isErrorCount
                    ]);
                }else if($job->notify_status !='done' && $isErrorCount == 10){
                    $job->notify_status = 'done';
                    $job->save();

                    $job->log()->create([
                        'type' => $job->status,
                        'message' => $isErrorCount
                    ]);
                    $adminUsers = $this->userService->getAlertUsers();
                    if (!empty($adminUsers)) {
                        foreach ($adminUsers as $adminUser) {
                            $targetPhones [] = $adminUser->phone;
                        }
                    }
                    //중복제거
                    if( !empty($targetPhones) ){
                        $targetPhones = array_unique($targetPhones);
                        $smsMsg = SMSMessageHelper::makeMsgPortalError($job->type, $job->user_id.' : '.$job->file_path);
                        foreach ($targetPhones as $phone) {
                            $this->zodiacService->sendSMS($phone, $smsMsg);
                        }
                    }
                    $error = '처리 오류';
                    $this->handleJobError($job, $error);
                    api_abort($error, 'error', 400);
                }
            }else{
                $job->log()->create([
                    'type' => 'info',
                    'message' => $job->status.' : '.$job->progress . ' 진행중...'
                ]);
            }
        }

        return response()->ok($job);
    }

    private function handleJobError($job, $error)
    {
        $job->log()->create([
            'type' => 'error',
            'message' => $error
        ]);

        $job->status = JobStatus::FAILED;
        $job->save();
    }

    /**
     * 작업 할당
     *
     * @param \Api\Http\ApiRequest $request
     * @param Api\Http\ApiResponse $response
     * @param array $args
     * @return Api\Http\ApiResponse
     */
    public function assignJob(ApiRequest $request, ApiResponse $response, array $args)
    {
        $jobId = $args['job_id'];
        $job = $this->jobService->findOrFail($jobId);

        $data = $request->only(['client_ver', 'client_os']);

        $fileServer = null;
        if ($job->type === JobType::UPLOAD) {
            $fileServer = FileServer::where('name', 'upload')
                ->first();
        } else {
            $fileServer = FileServer::where('name', 'download')
                ->first();
        }

        $job->file_server_id = $fileServer->id;
        $job->status = JobStatus::STANDBY;
        $job->client_ip = getClientIp();
        $job->client_ver = $data['client_ver'];
        $job->client_os = $data['client_os'];
        $job->save();

        $job->file_server = $fileServer;

        return response()->ok($job);
    }
}
