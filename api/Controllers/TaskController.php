<?php

namespace Api\Controllers;

use Api\Models\Task;
use Api\Models\User;
use Api\Http\ApiRequest;
use Api\Types\MediaType;
use Api\Http\ApiResponse;
use Api\Types\TaskStatus;
use Api\Types\StorageIdMap;
use Api\Services\FileService;
use Api\Services\TaskService;
use Api\Services\MediaService;
use Api\Types\WorkflowChannel;
use Api\Services\ArchiveService;
use Api\Services\ContentService;
use Api\Types\ContentStatusType;
use Api\Controllers\BaseController;
use Psr\Container\ContainerInterface;

class TaskController extends BaseController
{
    /**
     * 작업 서비스
     *
     * @var \Api\Services\TaskService
     */
    private $taskService;
    private $fileService;
    private $contentService;
    private $mediaService;
    private $archiveService;

    //http://nps.ktv.go.kr/media/CMS/2019/12/04/20191204T00666_proxy2m1080_proxy2m1080.mp4
    private $downloadUrl = 'https://send.g.ktv.go.kr/download?path=CMS';
    /**
     * 생성자
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->taskService = new TaskService($container);
        $this->fileService = new FileService($container);
        $this->contentService = new ContentService($container);
        $this->mediaService = new MediaService($container);
        $this->archiveService = new ArchiveService($container);

        $publish = config('publish');
        $this->downloadUrl = $publish['api_download_url'];

    }

    public function list(ApiRequest $request, ApiResponse $response, array $args)
    {
    }

    /**
     * FS 작업으로 포털 콘텐츠 등록 상태를 조회
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return ApiResponse
     */
    public function getStatusByFsJob(ApiRequest $request, ApiResponse $response, array $args)
    {
        $fsJobId = $args['fs_job_id'];

        $fsJob = \Api\Models\Fs\Job::find($fsJobId);
        if ($fsJob === null) {
            api_abort_404(\Api\Models\Fs\Job::class);
        }
        if (empty($fsJob->content_id)) {
            api_abort('콘텐츠가 등록되지 않았습니다.', 'no_content_id', 400);
        }
        $tasks = $this->taskService->getByRootIdByContentId($fsJob->content_id, WorkflowChannel::REGISTER_PORTAL);

        $data = $this->getTaskStatus($tasks);

        return $response->ok($data);
    }

    public function getStatus(ApiRequest $request, ApiResponse $response, array $args)
    {
        $taskId = $args['task_id'];

        $tasks = $this->taskService->getByRootId($taskId);
        $data = $this->getTaskStatus($tasks);

        return $response->ok($data);
    }

    /**
     * Task id로 전체 등록 워크플로우 진행상황 조회
     *
     * @param \Api\Models\Task[] $tasks
     * @return array
     */
    private function getTaskStatus($tasks)
    {
        $status = 'processing';
        $contentId = '';
        foreach ($tasks as $task) {
            if (!$contentId) {
                $contentId = $task['src_content_id'];
            }
            if ($task['status'] == 'error') {
                $status = 'error';
            }

            if ($task['type'] == '11') {
                $status = $task['status'];
            }
        }
        $content = $this->contentService->find($contentId);

        $data = [
            'task_sttus' => $status,
            'cntnts_sttus' =>  $content->status,
            'cntnts_id' =>  $contentId
        ];

        return $data;
    }

    public function getDownloadStatus(ApiRequest $request, ApiResponse $response, array $args)
    {

        $taskId = $args['task_id'];

        //전체 작업에서 타겟 스토리지로 전송 작업 상태 확인 
        $tasks = $this->taskService->getByRootId($taskId);
        if (empty($tasks)) {
            api_abort_404('task');
        }

        foreach ($tasks as $task) {
            if ($task->trg_storage_id == StorageIdMap::CACHE) {
                $targetTask = $task;
            }

            if (TaskStatus::isError($task->status)) {
                $errorTask = $task;
            }
        }
        //작업 오류
        if (!empty($errorTask)) {
            return $response->error('task error');
        }

        if (!empty($targetTask) && TaskStatus::isCompleted($targetTask->status)) {
            //대상 작업 완료
            $file = $targetTask->targetFile;
            if (empty($file)) {
                api_abort_404('file');
            }
            $targetFilePath = $file->file_path . '/' . $file->file_name;
            $data = [
                'file_id' => $file->id,
                'status' => $targetTask->status,
                'progress' => $targetTask->progress,
                'download_url' => $this->downloadUrl . '/' . $targetFilePath,
                'expired_at' => $file->expired_at
            ];
        } else {
            //진행중
            if (!empty($targetTask)) {
                $data = [
                    'status' => TaskStatus::PROCESSING,
                    'progress' => $targetTask->progress
                ];
            } else {
                $data = [
                    'status' => TaskStatus::PROCESSING,
                    'progress' => 5
                ];
            }
        }


        return $response->ok($data);
    }

    public function show(ApiRequest $request, ApiResponse $response, array $args)
    {
        $taskId = $args['task_id'];
        $task = $this->taskService->find($taskId);

        return $response->ok($task);
    }

    /**
     * 수동 워크플로우 등록
     */
    public function createWorkflow(ApiRequest $request, ApiResponse $response, array $args)
    {
        $user = auth()->user();
        $data = $request->all();


        $channel = $data['channel'];
        $detail = $data['detail'];
        $contentId = $data['content_id'];
        $priority = empty($data['priority']) ? 300 : $data['priority'];

        if (empty($contentId)) {
            api_abort('not found content_id', 404);
        }
        if (empty($channel)) {
            api_abort('not found channel', 404);
        }
        $arrayParamInfo = [];

        $paramInfo = $this->validBeforeJob($contentId, $data);

        if (!empty($data['array_param_info'])) {
            $arrayParamInfo = json_decode($data['array_param_info'], true);
        } else {
            if (is_array($paramInfo)) {
                $arrayParamInfo[] = $paramInfo;
            }
        }
        $task = $this->taskService->getTaskManager();

        if (!is_array($contentId)) {
            $contentIds = [$contentId];
        } else {
            $contentIds = $contentId;
        }
        foreach ($contentIds as $contentId) {
            if ($detail == 'archive_dtl') {
                $tasks = $this->archiveService->archiveDtl($contentId, $user);
            } else if ($detail == 'archive') {
                $tasks = $this->archiveService->archive($contentId, $user);
            }else if($detail == 'restore'){
                $this->archiveService->priority = $priority;
                $tasks = $this->archiveService->restore($contentId, $user);
            }else if($detail == 'restore_near'){
                $this->archiveService->priority = $priority;
                $tasks = $this->archiveService->restoreNear($contentId, $user);
            }else{           
                $task->set_priority($priority);
                $task->start_task_workflow($contentId, $channel, $user->user_id, $arrayParamInfo);

                $tasks = $task->get_task_list(null);
            }
        }

        return $response->ok($tasks);
    }


    /**
     * 수동 서비스영상 등록
     */
    public function createProxyMedia(ApiRequest $request, ApiResponse $response, array $args)
    {
        $user = auth()->user();
        $data = $request->all();


        $channel = $data['channel'];
        $contentId = $data['content_id'];
        $priority = empty($data['priority']) ? 300 : $data['priority'];

        if (empty($contentId)) {
            api_abort('not found content_id', 404);
        }
        if (empty($channel)) {
            api_abort('not found channel', 404);
        }
        $bfContent = $this->contentService->getContentByContentId($contentId);
        $bfContentData = $bfContent->toArray();
        
        $usrMeta = $bfContentData['usr_meta'];

        if( $bfContentData['stataus'] == ContentStatusType::REGISTERING || $bfContentData['stataus'] == ContentStatusType::INGEST  ){
            api_abort('입수중인 콘텐츠 입니다. 입수완료후 요청 가능합니다.', 500);
        }

        // 사용금지 여부
        if($usrMeta['use_prhibt_at'] == 'Y'){
            api_abort('사용금지 된 콘텐츠 입니다. 아카이브팀에게 문의 바랍니다', 500);
        };
         
        // // 엠바고 해제일시
        if(!is_null($usrMeta['embg_relis_dt']) && ($usrMeta['embg_at'] == 'Y')){
            $embargoDateTimeStamp = strtotime($usrMeta['embg_relis_dt']);
            $nowDateTimeStamp = \Carbon\Carbon::now()->timestamp;
            $embargoDateTimeStamp = \Carbon\Carbon::createFromTimestamp($embargoDateTimeStamp);
            $nowDateTimeStamp = \Carbon\Carbon::createFromTimestamp($nowDateTimeStamp);
            if($embargoDateTimeStamp > $nowDateTimeStamp){
                api_abort('엠바고 해제일시가 지난 후 사용해주세요.', 500);
            }
        };
        $contentTaskList = $this->taskService->taskListByContentId($contentId);
        
        foreach($contentTaskList as $contentTask){
            // 원본파일에 영향을 주는 작업 코드
            $contentTypeArray = ['60','80','91','69'];

            $contentTaskType = $contentTask['type'];
            $contentTaskStatus = $contentTask['status'];
            $contentTaskId = $contentTask['task_id'];
            $contentTaskTargetMediaId = $contentTask['trg_media_id'];

            $contentTaskTargetMedia = $this->mediaService->find($contentTaskTargetMediaId);
            $contentTaskTargetMediaType = $contentTaskTargetMedia['media_type'];
            
            if(in_array($contentTaskType,$contentTypeArray)){
                if($contentTaskTargetMediaType == MediaType::ORIGINAL){
                    if((TaskStatus::isWorking($contentTaskStatus))){
                        api_abort('원본파일이 입수 및 작업중입니다.완료 후 요청해주세요.', 500);
                    }
                }
            }
        };

        $paramInfo = $this->validBeforeJob($contentId, $data);

        $arrayParamInfo = [];
        if (is_array($paramInfo)) {
            $arrayParamInfo[] = $paramInfo;
        }

        $task = $this->taskService->getTaskManager();

        if (!is_array($contentId)) {
            $contentIds = [$contentId];
        } else {
            $contentIds = $contentId;
        }
        foreach ($contentIds as $contentId) {
            $task->set_priority($priority);
            $task->start_task_workflow($contentId, $channel, $user->user_id, $arrayParamInfo);
            $tasks = $task->get_task_list(null);
        }
        return $response->ok($tasks);
    }

    public function sendToMedia(ApiRequest $request, ApiResponse $response, array $args)
    {
        $user = auth()->user();
        $data = $request->all();


        $channel = $data['channel'];
        $mediaType = $data['media_type'];
        $contentId = $data['content_id'];
        $priority = empty($data['priority']) ? 300 : $data['priority'];

        if (empty($contentId)) {
            api_abort('not found content_id', 404);
        }
        if (empty($channel)) {
            api_abort('not found channel', 404);
        }
        $bfContent = $this->contentService->getContentByContentId($contentId);
        $bfContentData = $bfContent->toArray();
        
        $usrMeta = $bfContentData['usr_meta'];

        // 사용금지 여부
        if($usrMeta['use_prhibt_at'] == 'Y'){
            api_abort('사용금지 된 콘텐츠 입니다. 아카이브팀에게 문의 바랍니다', 500);
        };
         
        // // 엠바고 해제일시
        if(!is_null($usrMeta['embg_relis_dt']) && ($usrMeta['embg_at'] == 'Y')){
            $embargoDateTimeStamp = strtotime($usrMeta['embg_relis_dt']);
            $nowDateTimeStamp = \Carbon\Carbon::now()->timestamp;
            $embargoDateTimeStamp = \Carbon\Carbon::createFromTimestamp($embargoDateTimeStamp);
            $nowDateTimeStamp = \Carbon\Carbon::createFromTimestamp($nowDateTimeStamp);
            if($embargoDateTimeStamp > $nowDateTimeStamp){
                api_abort('엠바고 해제일시가 지난 후 사용해주세요.', 500);
            }
        };

        $medias = $this->mediaService->getMediaByContentId($contentId);
        $isExist = false;
        foreach($medias as $media)
        {
            if( $media->media_type == $mediaType ){
                $isExist = $media;
            }
        }
        if(!$isExist){
            api_abort('전송할 미디어('.$mediaType.')가 존재하지 않습니다. 생성 후 사용해주세요.', 500);
        }else{
            if (empty($isExist->filesize)) {
                api_abort('전송할 미디어('.$mediaType.')가 생성 중입니다. 잠시 후 사용해주세요.', 500);
            }
        }
        // $contentTaskList = $this->taskService->taskListByContentId($contentId);
        
        // foreach($contentTaskList as $contentTask){
        //     // 원본파일에 영향을 주는 작업 코드
        //     $contentTypeArray = ['60','80','91'];

        //     $contentTaskType = $contentTask['type'];
        //     $contentTaskStatus = $contentTask['status'];
        //     $contentTaskId = $contentTask['task_id'];
        //     $contentTaskTargetMediaId = $contentTask['trg_media_id'];

        //     $contentTaskTargetMedia = $this->mediaService->find($contentTaskTargetMediaId);
        //     $contentTaskTargetMediaType = $contentTaskTargetMedia['media_type'];
            
        //     if(in_array($contentTaskType,$contentTypeArray)){
        //         if($contentTaskTargetMediaType == MediaType::ORIGINAL){
        //             if((TaskStatus::isWorking($contentTaskStatus))){
        //                 api_abort('작업 진행중인 원본파일이 있습니다 진행이 끝난후 시작해주세요.', 500);
        //             }
        //         }
        //     }
        // };

        $paramInfo = $this->validBeforeJob($contentId, $data);

        $arrayParamInfo = [];
        if (is_array($paramInfo)) {
            $arrayParamInfo[] = $paramInfo;
        }

        $task = $this->taskService->getTaskManager();

        if (!is_array($contentId)) {
            $contentIds = [$contentId];
        } else {
            $contentIds = $contentId;
        }
        foreach ($contentIds as $contentId) {
            $task->set_priority($priority);
            $task->start_task_workflow($contentId, $channel, $user->user_id, $arrayParamInfo);
            $tasks = $task->get_task_list(null);
        }
        return $response->ok($tasks);
    }

    /**
     * 작업등록전 조건 확인
     *
     * @param [type] $contentId
     * @param [type] $params
     * @return void
     */
    public function validBeforeJob($contentId, $params)
    {

        $channel = $params['channel'];
        $mediaType = $params['media_type'];
        $contentId = $params['content_id'];

        if (strstr($channel, 'create_proxy')) {
            //저해상도 생성작업이므로 원본 존재여부 확인필요

            $medias = $this->mediaService->getMediaByContentId($contentId);

            foreach ($medias as $media) {
                if ($media->media_type == $mediaType) {
                    api_abort('서비스영상이 생성중이거나 존재합니다.', -105);
                }
                if ($media->media_type == 'original') {
                    $targetMedia = $media;
                }
            }

            if (empty($targetMedia)) {
                api_abort('원본이 없습니다.', -106);
            }

            return [
                'force_src_media_id' => $targetMedia->media_id
            ];
        } else if (strstr($channel, 'create_thumb')) {

            $medias = $this->mediaService->getMediaByContentId($contentId);

            foreach ($medias as $media) {
                if (!empty($mediaType)) {
                    if ($media->media_type == $mediaType) {
                        api_abort('서비스영상이 생성중이거나 존재합니다.', -105);
                    }
                }
                if ($media->media_type == 'proxy') {
                    $targetMedia = $media;
                }
            }

            if (empty($targetMedia)) {
                api_abort('소스파일이 없습니다.', -106);
            }

            return [
                'force_src_media_id' => $targetMedia->media_id
            ];
        } else if (strstr($channel, 'delete_media_archive') || strstr($channel, 'file_restore')) {
            $medias = $this->mediaService->getMediaByContentId($contentId);

            foreach ($medias as $media) {
                if ($media->media_type == 'archive') {
                    $targetMedia = $media;
                }
            }

            if (empty($targetMedia)) {
                api_abort('소스파일이 없습니다.', -106);
            }
        } else if (strstr($channel, 'delete_media_original')) {
            //원본파일만 삭제하므로 아카이브된 미디어를 확인해야함.
            $medias = $this->mediaService->getMediaByContentId($contentId);

            foreach ($medias as $media) {
                if ($media->media_type == 'archive') {
                    $targetMedia = $media;
                }
            }

            if (empty($targetMedia)) {
                api_abort('아카이브 파일이 없습니다.<br />재 리스토어를 위한 용도인지 확인해주세요.', -106);
            }
        }

        return true;
    }
}
