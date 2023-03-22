<?php

namespace Api\Services;

use Api\Models\User;
use Api\Models\Media;
use Api\Core\FilePath;
use Api\Models\Content;
use Api\Models\ArchiveTask;
use Api\Types\StorageIdMap;
use Api\Models\ArchiveMedia;
use Api\Models\ArchiveServer;
use Api\Models\ContentStatus;
use Api\Services\BaseService;
use Api\Services\TaskService;
use Api\Models\ContentSysMeta;
use Api\Models\ContentUsrMeta;
use Api\Services\ContentService;
use Api\Services\DTOs\ContentDto;
use Api\Services\DTOs\ContentStatusDto;
use Illuminate\Database\Capsule\Manager as DB;

class ArchiveService extends BaseService
{

    private $category = 'cms';
    private $group = 'SPM_STORAGE';
    private $destinations = 'san';

    public $priority = 300;
  
    /**
     * DTL 아카이브 여부
     *
     * @param integer $id
     * @return $collection
     */
    public function isArchived($id){
        
        $archiveMedia = ArchiveMedia::where('content_id' , $id)->first();

        if( !empty($archiveMedia) ){
            return $archiveMedia;
        }else{
            return false;
        }
    }

    public function deleteArchiveMedia($id){
        
        //$logger = new \Proxima\core\Logger('archiveDtlMig');
        //$logger->info(print_r('deleteArchiveMedia', true));
        $archiveMedia = ArchiveMedia::where('content_id' , $id)->first();
        $dumpMedia = $archiveMedia->toArray();
        //$logger->info(print_r($dumpMedia, true));
        if( !empty($archiveMedia) ){
            DB::table('ARCHIVE_TRD_MEDIAS')->insert([
                'id' => $dumpMedia['id'],
                'content_id' => $dumpMedia['content_id'],
                'media_id' => $dumpMedia['media_id'],
                'object_name' => $dumpMedia['object_name'],
                'archive_category' => $dumpMedia['archive_category'],
                'archive_group' => $dumpMedia['archive_group'],
                'qos' => $dumpMedia['qos'],
                'destinations' => $dumpMedia['destinations'],
                'user_id' => $dumpMedia['user_id'],
                'created_at' => date('YmdHis',strtotime($dumpMedia['created_at']))
            ]);
            $archiveMedia->delete();
            
            return $archiveMedia;
        }else{
            return false;
        }
    }

    /**
     * 아카이브할 아카이브아이디 생성
     *
     * @param [type] $mediaCode
     * @return void
     */
    public function getArchiveId($contentId)
    {
        $usrMeta = ContentUsrMeta::find($contentId);
        if( !empty($usrMeta) && !empty( $usrMeta->media_id) ){
            return $usrMeta->media_id;
        }
        return false;
    }

    /**
     * 아카이브 된 콘텐츠중 마이그레이션이 안된 파일은 재 아카이브 처리
     *
     * @param [type] $contentId
     * @return $newArchiveId
     */
    public function changeArchiveId($contentId)
    {
        $cid = "R";

        $usrMeta = ContentUsrMeta::find($contentId);
        if( !empty($usrMeta) && !empty( $usrMeta->media_id) ){
            $archiveId = $usrMeta->media_id;
        }

        if(empty($archiveId)) return false;

        if( substr($archiveId, 8,1) == $cid ){
            return false;
        }else{
            $newArchiveId = substr($archiveId, 0,8).$cid.substr($archiveId, 9,5);
        }

        $migInfo = $this->mediaMigInfo($contentId);

        if( $migInfo->is_change_nearline != 'Y' ||  $migInfo->is_change_dtl == 'Y' ){
            return false;
        }
        $this->deleteArchiveMedia($contentId);
        $usrMeta->media_id = $newArchiveId;
      
        $migInfo->is_change_dtl = 'Y';
        $migInfo->mediaid_new = $newArchiveId;

        $this->getMigTable()->where('content_id', $contentId)->update([
            'mediaid_new' => $newArchiveId,
            'is_change_dtl' =>'Y'
        ]);
        //$migInfo->save();
        $usrMeta->save();

        return $newArchiveId;
    }

    /**
     * 아카이브 워크플로우 수행
     *
     * @param $contentId
     * @param User $user
     * @return void
     */
    public function archive($contentId , $user){
        
        $channel = 'dtl_archive';
        $prefix = '';//운영시 제거해야함.
        
        //아카이브 옵션
        $qos = 3;
 
        //아카이브 여부
        if( $this->isArchived($contentId) ){
            return false;
        }


        //생성할 오브젝트ID 조회
        $archiveId = $this->getArchiveId($contentId);
        if(!$archiveId){
            return false;
        }
 
        $archiveId = $prefix.$archiveId;

        $contentService = new ContentService($this->container);
        $originalMedia = $contentService->getMediaByContentIdAndOriginalType($contentId);
        $sysMeta = $contentService->findContentSysMeta($contentId);
        $oriPath = new FilePath($originalMedia->path);

        if( $originalMedia->storage_id == StorageIdMap::NEAR){
            $channel = 'dtl_archive_original';
        }else if(  $oriPath->fileExt == 'mov' && $contentService->isXDCAM($sysMeta) ){ 
            //xdcam mov 인경우 변환 아카이브        
            $channel = 'dtl_archive_xdcam';
        }else{
            //그외 아카이브
        }

        $taskService = new TaskService($this->container);
        $task = $taskService->getTaskManager();

        $taskUserId = empty($user->user_id)?  'admin':  $user->user_id ;
        $task->setStatus('scheduled');
        $taskId = $task->start_task_workflow($contentId, $channel, $taskUserId,  null, null);

        if(!$taskId){
            return false;
        }

        $taskInfos = $task->get_task_list(null);
        $taskInfo = array_shift($taskInfos);  
        $mediaId = $taskInfo['trg_media_id'];
        $archiveSeq = $this->getSequence('ARCHIVE_SEQ');

        $archive = new ArchiveTask();
        $archive->archive_seq = $archiveSeq;
        $archive->media_id      = $mediaId; 
        $archive->task_id       = $taskId; 
        $archive->archive_id    = $archiveId;
        $archive->diva_category = $this->category;
        $archive->diva_group    = $this->group;
        $archive->qos           = $qos; 
        $archive->destinations  = $this->destinations;
        $archive->save();


        $contentService = new ContentService($this->container);
        $contentStatusData = [
            'archive_status' => '0',   // archive_status
            'archive_date' =>   date("YmdHis"),  // archive_date
            //'archv_end_dt' =>  '',   // archv_end_dt
            //'archv_requst_at' => 'Y',     // archv_requst_at
            //'archv_rqester' =>     // archv_rqester
            //'archv_regist_requst_ty' =>     // archv_regist_requst_ty
            'archv_begin_dt' => date("YmdHis"),   // archv_begin_dt
            'archv_sttus' =>  'queue',   // archv_sttus
            'archv_exctn' => $taskUserId    // archv_exctn
        ];
        $contentStatusDto = new \Api\Services\DTOs\ContentStatusDto($contentStatusData);
        $keys       = array_keys($contentStatusData);
        $contentStatusDto = $contentStatusDto->only(...$keys);        
        $contentService->update($contentId,null, $contentStatusDto, null,null, $user);

        return $archive;
    }

    public function archiveDtl($contentId , $user){
        
        $channel = 'dtl_archive_each';
        $prefix = '';//운영시 제거해야함.
        
        //아카이브 옵션
        $qos = 3;
 
        //아카이브 여부
        if( $this->isArchived($contentId) ){
            return false;
        }


        //생성할 오브젝트ID 조회
        $archiveId = $this->getArchiveId($contentId);
        if(!$archiveId){
            return false;
        }
 
        $archiveId = $prefix.$archiveId;

        $contentService = new ContentService($this->container);
        $originalMedia = $contentService->getMediaByContentIdAndOriginalType($contentId);
        $oriPath = new FilePath($originalMedia->path);

        $taskService = new TaskService($this->container);
        $task = $taskService->getTaskManager();

        $taskUserId = empty($user->user_id)?  'admin':  $user->user_id ;
        $task->setStatus('scheduled');
        $taskId = $task->start_task_workflow($contentId, $channel, $taskUserId,  null, null);

        if(!$taskId){
            return false;
        }

        $taskInfos = $task->get_task_list(null);
        $taskInfo = array_shift($taskInfos);  
        $mediaId = $taskInfo['trg_media_id'];
        $archiveSeq = $this->getSequence('ARCHIVE_SEQ');

        $archive = new ArchiveTask();
        $archive->archive_seq = $archiveSeq;
        $archive->media_id      = $mediaId; 
        $archive->task_id       = $taskId; 
        $archive->archive_id    = $archiveId;
        $archive->diva_category = $this->category;
        $archive->diva_group    = $this->group;
        $archive->qos           = $qos; 
        $archive->destinations  = $this->destinations;
        $archive->save();


        $contentService = new ContentService($this->container);
        $contentStatusData = [
            //'archive_status' => '0',   // archive_status
            //'archive_date' =>   date("YmdHis"),  // archive_date
            //'archv_end_dt' =>  '',   // archv_end_dt
            //'archv_requst_at' => 'Y',     // archv_requst_at
            //'archv_rqester' =>     // archv_rqester
            //'archv_regist_requst_ty' =>     // archv_regist_requst_ty
            'dtl_archv_begin_dt' => date("YmdHis"),   // archv_begin_dt
            'dtl_archv_sttus' =>  'queue'   // archv_sttus
            //'archv_exctn' => $taskUserId    // archv_exctn
        ];
        $contentStatusDto = new \Api\Services\DTOs\ContentStatusDto($contentStatusData);
        $keys       = array_keys($contentStatusData);
        $contentStatusDto = $contentStatusDto->only(...$keys);        
        $contentService->update($contentId,null, $contentStatusDto, null,null, $user);

        return $archive;
    }

    /**
     * 기존 아카이브 변환후 아카이브 대체
     *
     * @param [type] $contentId
     * @param  $user
     * @return $archive
     */
    public function archiveDtlMig($contentId , $user, $preTask = null ){
        
        $channel = 'dtl_archive_each';
        $prefix = '';//운영시 제거해야함.
        
        //아카이브 옵션
        $qos = 3;

        $logger = new \Proxima\core\Logger('archiveDtlMig');
        $logger->info(print_r('changeArchiveId', true));

        //아카이브 신규 추가 및 정보 변경
        $archiveId = $this->changeArchiveId($contentId);
        $logger->info(print_r('archiveId', true));
        if(!$archiveId){
            return false;
        }
 
        $archiveId = $prefix.$archiveId;

        $contentService = new ContentService($this->container);
        $originalMedia = $contentService->getMediaByContentIdAndOriginalType($contentId);
       
        $taskService = new TaskService($this->container);
        $task = $taskService->getTaskManager();
        
        $taskUserId = empty($user->user_id)?  'admin':  $user->user_id ;

        $arrayParamInfo = [];
        if(!empty($preTask)){
            $arrayParamInfo = [
                [
                    //'root_task' => $preTask['root_task'],
                    'priority' => $preTask['priority']
                ]
            ];
            $taskUserId = $preTask['task_user_id'] ;
        }
        $taskId = $task->start_task_workflow($contentId, $channel, $taskUserId,  $arrayParamInfo, null);

        if(!$taskId){
            return false;
        }

        $taskInfos = $task->get_task_list(null);
        $taskInfo = array_shift($taskInfos);  
        $mediaId = $taskInfo['trg_media_id'];
        $archiveSeq = $this->getSequence('ARCHIVE_SEQ');

        $archive = new ArchiveTask();
        $archive->archive_seq = $archiveSeq;
        $archive->media_id      = $mediaId; 
        $archive->task_id       = $taskId; 
        $archive->archive_id    = $archiveId;
        $archive->diva_category = $this->category;
        $archive->diva_group    = $this->group;
        $archive->qos           = $qos; 
        $archive->destinations  = $this->destinations;
        $archive->save();


        $contentService = new ContentService($this->container);
        $contentStatusData = [
            //'archive_status' => '0',   // archive_status
            //'archive_date' =>   date("YmdHis"),  // archive_date
            //'archv_end_dt' =>  '',   // archv_end_dt
            //'archv_requst_at' => 'Y',     // archv_requst_at
            //'archv_rqester' =>     // archv_rqester
            //'archv_regist_requst_ty' =>     // archv_regist_requst_ty
            'dtl_archv_begin_dt' => date("YmdHis"),   // archv_begin_dt
            'dtl_archv_sttus' =>  'queue'   // archv_sttus
            //'archv_exctn' => $taskUserId    // archv_exctn
        ];
        $contentStatusDto = new \Api\Services\DTOs\ContentStatusDto($contentStatusData);
        $keys       = array_keys($contentStatusData);
        $contentStatusDto = $contentStatusDto->only(...$keys);        
        $contentService->update($contentId,null, $contentStatusDto, null,null, $user);
        $this->container['searcher']->update($contentId);
        return $archive;
    }

    /**
     * 리스토어 
     *
     * @param $contentId
     * @param User $user
     * @return void
     */
    public function restore($contentId , $user){
        //아카이브 스토리지 확인
        //중앙 스토리지 복사
        //DTL -> 중앙 스토리지 복사

        $originalMedia = Media::where('media_type', 'original')->where('content_id', $contentId)->first();

        //원본이 있으면 실패
        if( $originalMedia->status != 1 ){
            return false;
        }

        $archiveMedia = Media::where('media_type', 'archive')->where('content_id', $contentId)->first();
        $contentService = new ContentService($this->container);
        $statusMeta = $contentService->findStatusMeta($contentId);  
        //니어라인 확인
        if (!empty($archiveMedia) && empty($archiveMedia->status)) {
            //니어라인 존재
            $channel = 'file_restore';         
            //ASIS 이관 메타데이터 
            if ( !empty($statusMeta->bfe_video_id) && !$this->isChangeMigNear($contentId) ) {
                $channel = 'file_restore_xdcam';
            }
            
            $taskService = new TaskService($this->container);
            $task = $taskService->getTaskManager();
            $taskUserId = empty($user->user_id)?  'admin':  $user->user_id ;
            $task->set_priority(200);
            $taskId = $task->start_task_workflow($contentId, $channel, $taskUserId, null, null);


            //리스토어 요청시 만료일 갱신
            if( $archiveMedia->expired_date < date('YmdHis', strtotime("+28 days")) ){

                $archiveMedia->expired_date = date('YmdHis', strtotime("+28 days"));
                $archiveMedia->save();
            }
        }else{
          
            //as is 변환안된건 변환 후 재아카이브하도록
            if (!empty($statusMeta->bfe_video_id) && !$this->isChangeMigNear($contentId)) {
                //$channel = 'dtl_restore_mig'; 추후 변경
                $channel = 'dtl_restore';
            }else{
                //운영반영하면 워크플로우 변경
                //변환없이 리스토어하는 작업으로
                $channel = 'dtl_restore_copy';                
            }
	    	    
            //아카이브 옵션
            $qos = 4;
            //아카이브 여부
            $archiveInfo = $this->isArchived($contentId);          

            if (!$archiveInfo) {
                return false;
            }
            //리스토어할 오브젝트ID 조회
            $archiveId = $archiveInfo->object_name;

            $taskService = new TaskService($this->container);
            $task = $taskService->getTaskManager();
            $taskUserId = empty($user->user_id)?  'admin':  $user->user_id ;
            $task->set_priority(200);
            $taskId = $task->start_task_workflow($contentId, $channel, $taskUserId, null, null);

            if (!$taskId) {
                return false;
            }

            $taskInfos = $task->get_task_list(null);
            $taskInfo = array_shift($taskInfos);
            $mediaId = $taskInfo['trg_media_id'];
            $archiveSeq = $this->getSequence('ARCHIVE_SEQ');
            $archive = new ArchiveTask();
            $archive->archive_seq = $archiveSeq;
            $archive->media_id      = $mediaId;
            $archive->task_id       = $taskId;
            $archive->archive_id    = $archiveId;
            $archive->diva_category = $this->category;
            $archive->diva_group    = $this->group;
            $archive->qos           = $qos;
            $archive->destinations  = $this->destinations;
            $archive->save();

            //리스토어 요청시 만료일 갱신
            $archiveMedia->expired_date = date('YmdHis', strtotime("+28 days"));
            $archiveMedia->save();
        }

        $contentService = new ContentService($this->container);
        $contentStatusData = [
            'restore_date' => date("YmdHis"),
            'restore_at' => '0',
            'restore_begin_dt' =>date("YmdHis"),
            'restore_sttus' =>  'queue'
        ];
        $contentStatusDto = new \Api\Services\DTOs\ContentStatusDto($contentStatusData);
        $keys       = array_keys($contentStatusData);
        $contentStatusDto = $contentStatusDto->only(...$keys);        
        $contentService->update($contentId,null, $contentStatusDto, null,null, $user);

        return $taskId;
    }

    /**
     * 니어라인까지만 리스토어 원본 변환
     *
      * @param $contentId
     * @param User $user
     * @return $taskId 
     */
    public function restoreNear($contentId , $user){
       // global $db;
       // $archiveMedia = Media::where('media_type', 'archive')->where('content_id', $contentId)->first();
      
        //니어라인 확인
        //if (!empty($archiveMedia) && empty($archiveMedia->status)) {
            //니어라인 존재
        //    return false;
        //}else{
          
            if( !$this->isChangeMigNear($contentId) ){
                $channel = 'dtl_restore_near_mig';

                $this->getMigTable()->where('content_id', $contentId)->update([
                    'is_change_nearline' => 'Y'
                ]);
            }else{
                $channel = 'dtl_restore_near_mig';
                $this->getMigTable()->where('content_id', $contentId)->update([
                    'is_change_nearline' => 'Y'
                ]);
            }
            //아카이브 옵션
            $qos = 4;
            //아카이브 여부
            $archiveInfo = $this->isArchived($contentId);          
            //dump($archiveInfo);
            if (!$archiveInfo) {
                return false;
            }

         
                //리스토어할 오브젝트ID 조회
                $archiveId = $archiveInfo->object_name;
                //dump($channel);
                $taskService = new \Api\Services\TaskService($this->container);
                $task = $taskService->getTaskManager();
                $taskUserId = empty($user->user_id)?  'admin':  $user->user_id ;
                $task->set_priority(400);
                //dump($task);
                $taskId = $task->start_task_workflow($contentId, $channel, $taskUserId, null, null);
                //dump($taskId);
                if (!$taskId) {
                    return false;
                }
           
            $taskInfos = $task->get_task_list(null);
            $taskInfo = array_shift($taskInfos);
            $mediaId = $taskInfo['trg_media_id'];
            $archiveSeq = $this->getSequence('ARCHIVE_SEQ');
            $archive = new ArchiveTask();
            $archive->archive_seq = $archiveSeq;
            $archive->media_id      = $mediaId;
            $archive->task_id       = $taskId;
            $archive->archive_id    = $archiveId;
            $archive->diva_category = $this->category;
            $archive->diva_group    = $this->group;
            $archive->qos           = $qos;
            $archive->destinations  = $this->destinations;
            $archive->save();
           // dump($archive);
        //}
        return $taskId;
    }

    /**
     * 이관 자료 마이그레이션 정보
     * 이관자료인지 체크한다
     *
     * @param $contentId
     * @return $collection
     */
    public function mediaMigInfo($contentId){

        $contentService = new ContentService($this->container);
        $statusMeta = $contentService->findStatusMeta($contentId);
        $bfeVideoId    = $statusMeta->bfe_video_id;

        if( empty($bfeVideoId) ){
            return false;
        }

        $migInfo = $this->getMigTable()->where('content_id', $contentId )->first();
        if( empty($migInfo) ){
            $contentService = new ContentService($this->container);
            $usrMeta = $contentService->findContentUsrMeta($contentId);
            $mediaId = $usrMeta->media_id;

            $this->getMigTable()->insert([
                'CONTENT_ID' =>  $contentId,
                'MEDIAID' => $mediaId,
                'CREATED_AT' => date("YmdHis")
            ]);
            $migInfo = $this->getMigTable()->where('content_id', $contentId )->first();
        }
        return $migInfo;
    }

    public function getMigTable(){
        return DB::table('Z_MIG_CMS');
    }

    /**
     * 니어라인 파일이 코난MXF이면 false 대상이 아니거나 변환된거면 true
     *
     * @param [type] $contentId
     * @return boolean
     */
    public function isChangeMigNear($contentId){

        $migInfo = $this->mediaMigInfo($contentId);

         //마이그레이션 대상이 아니면 스킵
         if(!$migInfo){
            return true;
         }

         if($migInfo->is_change_nearline == 'Y'){
            return true;
         }
         return false;
    }

    public function isChangeMigDtl($contentId){
        $migInfo = $this->mediaMigInfo($contentId);

        //마이그레이션 대상이 아니면 스킵
        if(!$migInfo){
            return true;
         }
        if($migInfo->is_change_dtl == 'Y'){
           return true;
        }

        return false;
   }

    public function delete($contentId , $user){
        //아카이브 여부
        if( !$this->isArchived($contentId) ){
            return false;
        }

        //생성할 오브젝트ID 조회
        $archiveId = $this->getArchiveId($contentId);
        if(!$archiveId){
            return false;
        }

                  
        $channel = 'dtl_delete';
        //아카이브 옵션
        $qos = 4;
        //아카이브 여부
        $archiveInfo = $this->isArchived($contentId);          

        if (!$archiveInfo) {
            return false;
        }
        //리스토어할 오브젝트ID 조회
        $archiveId = $archiveInfo->object_name;
        
        $archiveMedia = Media::where('media_type', 'archive')->where('content_id', $contentId)->first();
        if(!empty($archiveMedia)){
            $srcMediaId = $archiveMedia->media_id;
        }else{
            $originalMedia = Media::where('media_type', 'original')->where('content_id', $contentId)->first();
            $srcMediaId = $originalMedia->media_id;
        }
               
        $taskService = new TaskService($this->container);
        $task = $taskService->getTaskManager();

        $taskUserId = empty($user->user_id)?  'admin':  $user->user_id ;
        $task->setStatus('scheduled');
        $task->set_source_media_id($srcMediaId);
        $taskId = $task->start_task_workflow($contentId, $channel, $taskUserId,  null, null);

        if(!$taskId){
            return false;
        }

        $taskInfos = $task->get_task_list(null);
        $taskInfo = array_shift($taskInfos);  
        $mediaId = $srcMediaId;
        $archiveSeq = $this->getSequence('ARCHIVE_SEQ');

        $archive = new ArchiveTask();
        $archive->archive_seq = $archiveSeq;
        $archive->media_id      = $mediaId; 
        $archive->task_id       = $taskId; 
        $archive->archive_id    = $archiveId;
        $archive->diva_category = $this->category;
        $archive->diva_group    = $this->group;
        $archive->qos           = $qos; 
        $archive->destinations  = $this->destinations;
        $archive->save();



        return true;
    }

    public function serverCheck($server_ip)
    {
        $server = ArchiveServer::where('server_ip',$server_ip)->first();

        if(!$server){
            return false;
        }

        if( $server->is_active == '1' ){
            //active인경우
            $server->access_at =  \Carbon\Carbon::now();
            $server->save();
            return true;
        }else{
            //active 아닌경우
            //메인이 업데이트 시간이 지난경우
            //백업 access 시간이 시간내일때

            if($server->type == 'backup'){
                $mainServer = ArchiveServer::where('type','main')->first();
                if( $mainServer ){
                    $now = \Carbon\Carbon::now();
                    $accessAt = \Carbon\Carbon::parse($mainServer->access_at);
                    $diff = $now->diffInSeconds($accessAt);
                    if( $diff > 300 ){//5분이상 업데이트가 없는경우 절체
                        
                        $mainServer->is_active = 0;
                        $mainServer->save();

                        $server->access_at =  \Carbon\Carbon::now();
                        $server->is_active = 1;
                        $server->save();
                    }
                }
            }
        }

        return false;
    }
}
