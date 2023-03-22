<?php

namespace Api\Services;

use Api\Models\User;
use Api\Models\Content;

use Api\Http\ApiRequest;
use Api\Http\ApiResponse;


use Api\Models\TbRequest;
use Api\Types\TaskStatus;

use Api\Services\BaseService;
use Api\Services\TaskService;
use Api\Services\GroupService;
use Api\Services\ContentService;


use Api\Types\ContentStatusType;
use Api\Types\ArchiveRequestType;
use Api\Services\PermissionService;
use Api\Types\ArchiveRequestStatus;
use Api\Types\ArchiveRequestTaskStatus;
use Api\Services\AuthorityMandateService;

class TbRequestService extends BaseService
{
    

    /**
     * 콘텐츠 아이디로 요청 조회
     *
     * @param integer $id
     * @return DataDicTable
     */
    public function getRequestByContentId2(int $id)
    {
        $query = TbRequest::query();
        $query->where('nps_content_id', $id);

        return $query->first();
    }
    
    /**
     * 콘텐츠 아이디로 아카이브 요청 건 조회
     *
     * @param integer $id
     * @return DataDicTable
     */
    public function getRequestTypeArchiveByContentId(int $id)
    {
        $query = TbRequest::query();
        $query->where('nps_content_id', $id)->where('req_type',ArchiveRequestType::ARCHIVE);

        return $query->get();
    }
    
    /**
     * 온라인(메인, 중앙) 미디어 삭제 여부
     * 삭제되었으면 true, 안되었으면 false 리턴
     *
     * @param mixed $contentId
     * @return boolean 
     */
    public function isOrgMediaDeleted($contentId){
        $contentService = new ContentService($this->container);
        $flagCheck = $contentService->delMediaComplete($contentId);
        return $flagCheck;
    }
    /**
     * 아카이브 상태, 요청 구분 체크
     *
     * @param integer $contentId
     * @return void
     */
    public function getRequestByContentId($contentId)
    {
        $query = TbRequest::query();
        // $query->where([
        //     ['nps_content_id','=',$contentId],
        //     ['req_type','=',ArchiveRequestType::DELETE],
        //     ['req_status','!=', ArchiveRequestStatus::REJECT]
        // ]);
        $query->where(function($q) use($contentId){
            $q->where('nps_content_id', '=', $contentId);
        });
        $request = $query->get();
        return $request;
    }

    /**
     * 요청중인 작업 조회
     *
     * @param [type] $contentId
     * @return boolean
     */
    public function isArchiveRequestByContentId($contentId){
        $query = TbRequest::query();
        // $query->where([
        //     ['nps_content_id','=',$contentId],
        //     ['req_type','=',ArchiveRequestType::DELETE],
        //     ['req_status','!=', ArchiveRequestStatus::REJECT]
        // ]);
        $query->where('nps_content_id', $contentId);
        $query->where('req_type', ArchiveRequestType::ARCHIVE);
        $requests = $query->get();
        if( !empty($requests) ){
            foreach($requests as $request)
            {
                if( $request->req_status != ArchiveRequestStatus::REJECT ){
                    return true;
                }
            }
        }
        return false;
    }
    public function contentDeleteCheck($contentId){
        $query = Content::where(function($q){
            $q->where('is_deleted', '=', 'Y')
                ->orWhere('status','!=',ContentStatusType::COMPLETE);
        });
        $content = $query->find($contentId);
        return $content;
    }
    /**
     * 아카이브 삭제 요청
     *
     * @param  Array $data
     * @param User $user
     * @return void
     */
    public function request($data, $type ,$user)
    {
        /**
         * permission auto_request.restore 권한이 있을 경우
         * appr_user_nm 승인자
         */
        $apprUserId = null;
        $archiveRequestStatus = ArchiveRequestStatus::REQUEST;

        $contentService = new ContentService($this->container);

        switch($type){
            case 'delete':
                $type = ArchiveRequestType::DELETE;
            break;
            case 'restore':
                $type = ArchiveRequestType::RESTORE;
                $isPermission= $this->isPermission('auto_request.restore', $user);
                // Array 리스토어 권한 승계자
                $authorityMandateService = new AuthorityMandateService($this->container);
                // 수임자
                $mandatary = $authorityMandateService->getMandataryByUserId($user);

                // 권한이 있는 사람
                if($isPermission){
                    $archiveRequestStatus = ArchiveRequestStatus::COMPLETE;
                    $apprUserId = $user->user_id;

                }
                    
                // 권한 승계자
                if(!is_null($mandatary)){
                    $archiveRequestStatus = ArchiveRequestStatus::COMPLETE;
                    $apprUserId = $mandatary->mandatorInfo->user_id;

                }
                    
                    
            break;
        }
        $contentIds = json_decode($data['content_ids']);
        $reqNoArray = [];
        $reqComment = $data['restore_request_comnt'];
        foreach($contentIds as $vContentId){
            $vGroupContents = Content::where([
                ['content_id', '=', $vContentId],
                ['is_deleted', '=', 'N'],
                ['status', '>=', ContentStatusType::WAITING]
            ])->get();
            foreach($vGroupContents as $vGroupContent){
                $srcContentId = $vGroupContent->content_id;
                $srcUdContentId = $vGroupContent->ud_content_id;
                $srcBsContentId = $vGroupContent->bs_content_id;
                $srcIsGroup = $vGroupContent->is_group;
               
                $r = new TbRequest();
                $r->req_no = $this->getSequence('SEQ_REQUEST_ARCHIVE');
                $r->req_time = date('YmdHis');
                $r->req_type = $type;
                $r->req_user_id = $user->user_id;
                $r->req_status = $archiveRequestStatus;
                if($archiveRequestStatus === ContentStatusType::COMPLETE){
                    $r->appr_time = date('YmdHis');                        
                    
                    $archiveService = new \Api\Services\ArchiveService($this->container);
                    $task_id = $archiveService->restore($srcContentId, $user); 
                    if(!$task_id){
                        return false;
                    }
                    $r->task_id = $task_id;
                }
                $r->das_content_id = $srcContentId;
                $r->nps_content_id = $srcContentId;
                $r->req_comment = $reqComment;

                if(!is_null($apprUserId))
                    $r->appr_user_id = $apprUserId;

                $r->save();
                array_push($reqNoArray,$r->req_no);
            }

            if(  $type == ArchiveRequestType::DELETE){
                //콘텐츠 삭제처리
                $contentService->delete($vContentId, $user);
            }
            
        }
        $requests = TbRequest::query()->whereIn('req_no', $reqNoArray)->get();

        return $requests;
    }
    /**
     * 요청시 콘텐츠 상태 체크
     *
     * @param integer $contentId
     * @return void
     */
    public function contentStatusCheck($contentId){
        $contentDeleteCheck = $this->contentDeleteCheck($contentId);
        
        if(!is_null($contentDeleteCheck)){
            if($contentDeleteCheck->is_deleted == 'Y')
                return '삭제 된 콘텐츠 입니다.';
                //승인 업무 처리전까지 임시로 등록대기도 아카이브 가능하도록
            if( $contentDeleteCheck->status != ContentStatusType::COMPLETE && $contentDeleteCheck->status != ContentStatusType::WAITING ){
                switch($contentDeleteCheck->status){
                    // case ContentStatusType::WAITING :
                    //         return '등록대기중인 콘텐츠 입니다.';      
                    //     break;
                    case ContentStatusType::REJECT :
                        return '반려 된 콘텐츠 입니다.';      
                    break;
                    case ContentStatusType::REGISTERING :
                    default:
                        return '등록중인 콘텐츠 입니다.';      
                    break;
                };
            };
        };
        return false;
    }
    /**
     * 아카이브 삭제 요청시 조건
     *
     * @param integer $contentId
     * @return void
     */
    public function archiveRequestDeleteCheck($contentId){
        $contentService = new ContentService($this->container);
        $taskService = new TaskService($this->container);
        $requests = $this->getRequestByContentId($contentId);
        $orgMediaDeleted = $this->isOrgMediaDeleted($contentId);
        $content = $contentService->getContentByContentId($contentId);

        /**
         * 온라인 상태가 아닐때 -> 다시 중앙으로 파일을 옮긴다 그러니 중앙에 파일이 없어야 한다-> 삭제되어 있어야 한다.
         * 아카이브 요청이 되어있어야한다. -> 니어라인orDTL 에 파일이 있다.-> 파일이 있어야 중앙으로 가져갈 수 있다.
         * 리스토어 되어 있지 않아야 한다. -> 리스토어는 다시 중앙에 파일이 생기게 한다.
         */

           /**
         * flagCheck true -> 파일이 없다 -> 요청 할 수 있다
         *           false -> 파일이 있다 -> 요청 할 수 없다.
         */
        // 아카이브 삭제 요청 콘텍스트 매뉴 클릭 조건
        
        // if ($orgMediaDeleted === false) {
        //     return '온라인 상태입니다. 아카이브 요청취소 할 수 없습니다.';
        // }

        //아카이브 상태
        $archiveStatus = $content->statusMeta->archive_status;

        if (!(($archiveStatus === '1') || ($archiveStatus === "2") || ($archiveStatus === '3'))){
            return '아카이브 된 상태가 아닙니다. 아카이브 삭제 요청을 할 수 없습니다.';
        }else{
            // 아카이브 상태 일때!
            foreach ($requests as $request) {
                /**
                 * $requeests -> 요청되었던 목록
                 * 1.아카이브 삭제 요청 타입
                 *  1. 아카이브(archive)
                 *      1. 아카이브 상태일때 요청 할 수 있다.
                 *      2. 요청되었던 목록들의 상태($reqeust->req_status)
                 *          1. 요청(1),승인(2),반려(3)
                 *              1. 요청 상태일 경우
                 *                  1. 아카이브 요청을 해서 아직 아카이브가 안된 경우
                 *                  3. 반려를 하지 않았다면 또 요청할수 없기 때문에 최대 0개
                 *                  
                 *              2. 반려 상태일 경우
                 *                  1.요청을 한 후 반려가 되면 다시 요청 할 수 있기 때문에 반려 상태
                 *                  2.아카이브 상태에서 반려가 많다면 반려가 많이 되고 아카이브가 승인 된 경우
                 *                  3. 반려는 여러개 일 수 있으나 아카이브 상태에서 삭제 요청 할때에 반려요청 갯수는 무시해도 된다. 결국 승인 되거기 때문에
                 *              3. 승인 상태일 경우
                 *                  1. 아카이브가 되었거나 아카이브가 삭제 되있는 상태라서 아카이브 상태에서 승인이라면  아카이브 상태이기 때문에 삭제요청을 할 수 있다.
                 *                  2. 아카이브가 안되어 있는 상태에서 아카이브 요청 승인 상태가 있다면 지금 아카이브 상태인지 알수 없음
                 *  2. 삭제(delete)
                 *  3. 정리
                 *      1. 아카이브 요청 타입이 아카이브 인것 중에서 상태가 요청 상태라면 false 그 외에는 아카이브가 된 상태기 때문에 true
                 */
                switch($request->req_type){
                    case ArchiveRequestType::DELETE:
                        switch ($request->req_status) {
                            case ArchiveRequestStatus::COMPLETE:
                                $taskId = $request->task_id;
                                if($taskId === null){
                                    return 'task_id 없음';
                                }else{
                                    $task = $taskService->findOrFail($taskId);
                                    switch($task->status){
                                        case ArchiveRequestTaskStatus::PROCESSING:
                                            return '작업이 진행중인 아카이브 삭제 요청이 있습니다.';
                                        break;
                                        case ArchiveRequestTaskStatus::COMPLETE:
                                            return '작업이 완료된 아카이브 삭제 요청이 있습니다.';
                                        break;
                                        case ArchiveRequestTaskStatus::QUEUE:
                                            return '작업 진행대기 중인 아카이브 삭제 요청이 있습니다.';
                                        break;
                                    }
                                }
                            break;
                            case ArchiveRequestStatus::REQUEST:
                                return '이미 요청된 아카이브 삭제요청이 있습니다.';
                            break;
                        }
                    break;
                }               
            }
        }
        if( empty($archiveStatus) ){
            return '아카이브된 영상이 아닙니다';
        }
        //   // 리스토어 상태
        //   $restoreAt = $content->statusMeta->restoreAt;
        //   if($restoreAt === 'Y'){
        //       return '리스토어 되어있는 상태입니다.';
        //   };




        // if ($requests->count() == 0) 
        //  return '요청된 아카이브가 없습니다.';

        // if($requests->count() != 0){
            
        //     foreach($requests as $request){
        //         $count = 1;
        //         $requestStatus = $request->req_status;
        //         $requestType = $request->req_type;

        //         if ($requestStatus == ArchiveRequestStatus::REJECT) {
        //             if ($requests->count() == $count) {
        //                 return '등록된 아카이브가 없습니다.';
        //             } else {
        //                 $count++;
        //                 continue;
        //             }
        //         };
                    
                
        //         if ($requestStatus != ArchiveRequestStatus::REJECT && (($requestStatus != ArchiveRequestStatus::COMPLETE) && ($requestType == ArchiveRequestType::ARCHIVE)))
        //                 return '아카이브 요청을 해주세요.';

        //         switch ($requestType) {
        //             case ArchiveRequestType::DELETE:
        //                 if ($requestStatus == ArchiveRequestStatus::REQUEST)
        //                     return '이미 삭제 요청중인 아카이브 입니다.';
        //                 if ($requestStatus == ArchiveRequestStatus::COMPLETE)
        //                     return '이미 삭제된 콘텐츠 입니다.';
        //             break;
        //         }
        //     }
        // };
        return false;
    }
    /**
     * 아카이브 요청 조건
     *
     * @param integer $contentId
     * @return void
     */
    public function archiveRequestCheck($contentId){
        $contentService = new ContentService($this->container);
        $taskService = new TaskService($this->container);
        /**
         * // 요청 되는 경우
         * 아카이브 요청이 없을 경우 
         * 아카이브 요청이 있을 경우 -> 반려 상태 일 때
         * 아카이브 요청 상태가 반려 상태 일 경우
         */
        $requests = $this->getRequestByContentId($contentId);
        $orgMediaDeleted = $this->isOrgMediaDeleted($contentId);

        $content = $contentService->getContentByContentId($contentId);
        /**
         * flagCheck true -> 파일이 없다 -> 요청 할 수 없다
         *           false -> 파일이 있다 -> 요청 할 수 있다.
         */
        // 아카이브 요청 콘텍스트 매뉴 클릭 조건
        
        if ($orgMediaDeleted === true) {
            return '온라인에 아카이브 요청할 파일이 삭제되어 있습니다.';
        }else{
            foreach($requests as $request){
                if ($request->req_type === ArchiveRequestType::ARCHIVE) {
                    switch ($request->req_status) {
                        case ArchiveRequestStatus::COMPLETE:
                            $taskId = $request->task_id;
                            if($taskId === null){
                                return 'task_id 없음';
                            }else{
                                $task = $taskService->findOrFail($taskId);
                                switch($task->status){
                                    case ArchiveRequestTaskStatus::PROCESSING:
                                        return '작업이 진행중인 아카이브 요청이 있습니다.';
                                    break;
                                    case ArchiveRequestTaskStatus::COMPLETE:
                                        return '작업이 완료된 아카이브 요청이 있습니다.';
                                    break;
                                    case ArchiveRequestTaskStatus::QUEUE:
                                        return '작업 진행대기 중인 아카이브 요청이 있습니다.';
                                    break;
                                }
                            }
                        break;
                        case ArchiveRequestStatus::REQUEST:
                            return '이미 요청된 아카이브가 있습니다.';
                        break;
                    }
                }
            }
        }

        //아카이브 상태
        $archiveStatus = $content->statusMeta->archive_status;
    
        if ((($archiveStatus === '1') || ($archiveStatus === "2") || ($archiveStatus === '3'))){
            return '이미 아카이브 되어 있는 상태입니다.';
        }

        if( !empty($archiveStatus) ){
            return '아카이브된 영상입니다.';
        }

          // 리스토어 상태
          $restoreAt = $content->statusMeta->restoreAt;
          if($restoreAt === '1'){
              return '리스토어 되어있는 상태입니다.';
          };




        
        // if($orgMediaDeleted)
        //     return '파일이 존재하지 않아서 아카이브 할 수 없습니다.';

        // if($requests->count() != 0){
        //     foreach($requests as $request){
        //         $originalMedia = $request->getOriginalMedia();
        //         $requestStatus = $request->req_status;
        //         $requestType = $request->req_type;

        //         if(!($requestStatus == ArchiveRequestStatus::REJECT) && ($requestType == ArchiveRequestType::ARCHIVE)){
        //             switch($requestType){
        //                 case ArchiveRequestType::ARCHIVE :
        //                     if($requestStatus == ArchiveRequestStatus::COMPLETE)
        //                         return '이미 아카이브로 등록되었습니다.';
        //                     if($requestStatus == ArchiveRequestStatus::REQUEST)
        //                         return '이미 아카이브 요청한 상태입니다.';
        //                 break;
        //             }
        //         }
        //     }
        // };

        return false;
    }
    /**
     * 리스토어 요청 조건
     *
     * @param integer $contentId
     * @return void
     */
    public function archiveRequestRestoreCheck($contentId){
        $contentService = new ContentService($this->container);
        $taskService = new TaskService($this->container);
        $content = $contentService->getContentByContentId($contentId);
        /**
         * //리스토어 요청이 되는 경우
         * 아카이브 요청 상태가 승인 상태 일 때
         * 파일의 존재르 확인 하고 파일이 없어야 가능 있으면 에러
         * 파일이 없으면 요청중인지 확인 하고 
         */
        $orgMediaDeleted = $this->isOrgMediaDeleted($contentId);
        /**
         *  리스토어
         * isOrgMediaDeleted true -> 파일이 없다 -> 요청 할 수 있다.
         *           false -> 파일이 있다 -> 요청 할 수 없다.
         */
        $requests = $this->getRequestByContentId($contentId);
        /*      
          $orgMediaDeleted === false 이면 파일이 있는 상태이므로 리스토어 요청할 수 없다.
          */

          /**
           * 매뉴 기능별 유형 조건
           */
        // 온라인 상태

        if($orgMediaDeleted === false){
            // 온라인 상태
            return '온라인 상태입니다.';
        }else{
            foreach($requests as $request){
                if($request->req_type === ArchiveRequestType::RESTORE){
                    switch($request->req_status){
                        case ArchiveRequestStatus::COMPLETE:
                            $taskId = $request->task_id;
                            if($taskId){                            
                                $task = $taskService->findOrFail($taskId);

                                if( !TaskStatus::isError($task->status) && !TaskStatus::isCompleted($task->status) ){
                                    return '작업이 진행중인 리스토어 요청이 있습니다.';
                                }
                            }
                        break;
                        case ArchiveRequestStatus::REQUEST:
                            return '이미 요청된 리스토어가 있습니다.';
                        break;
                    };
                };
            };
        }
        
        //아카이브 상태
        $archiveStatus = $content->statusMeta->archive_status;
        
        if( empty($archiveStatus) ){
            return '아카이브된 영상이 아닙니다';
        }
        
        // // 리스토어 상태
        // $restoreAt = $content->statusMeta->restoreAt;
        // if($restoreAt === 'Y'){
        //     return '이미 리스토어 되어있는 상태입니다.';
        // };
        





        //   if($orgMediaDeleted === false){
        //     if ($requests->count() == 0) {
        //         return '요청된 아카이브가 없습니다.';
        //     }else{
        //         return '콘텐츠가 이미 리스토어 되어 있어 리스토어 할 수 없습니다.';
        //     }
        //   }
        
        // if ($requests->count() == 0) 
        //     return '요청된 아카이브가 없습니다.';
            


        // if($requests->count() != 0){            
        //     foreach($requests as $request){
        //         $count = 1;
        //         $originalMedia = $request->getOriginalMedia();
        //         $requestStatus = $request->req_status;
        //         $requestType = $request->req_type;
                
        //         if ($requestStatus == ArchiveRequestStatus::REJECT) {
        //             if ($requests->count() == $count) {
        //                 return '등록된 아카이브가 없습니다.';
        //             } else {
        //                 $count++;
        //                 continue;
        //             }
        //         };

        //         if (($requestStatus != ArchiveRequestStatus::COMPLETE) && ($requestType == ArchiveRequestType::ARCHIVE))
        //             return '등록된 아카이브가 없습니다.';

        //         // flag에 DMC 가 있으면 삭제 완료 된 거기 때문에 리스토어 요청을 할 수 있고
        //         // 없으면 파일이 아직 남아있는거기 때문에 요청을 할 수 없다
        //         // if ($originalMedia->flag != 'DMC') {
        //         //     return '파일이 존재하기 때문에 리스토어 할 수 없습니다.';
        //         // }        
                
        //         switch ($requestType) {
        //             case ArchiveRequestType::ARCHIVE:
        //                 if ($requestStatus == ArchiveRequestStatus::REQUEST)
        //                     return '리스토어 요청중인 콘텐츠 입니다.';
        //             break;
        //             case ArchiveRequestType::RESTORE:
        //                 if ($requestStatus == ArchiveRequestStatus::REQUEST) 
        //                     return '리스토어 요청중인 콘텐츠 입니다.';
        //                 if ($requestStatus == ArchiveRequestStatus::COMPLETE) 
        //                     return '리스토어 된 승인된 파일이 있습니다.';
        //                     // DMC에 막힌다.
        //             break;
        //         }
        //     }
        // };
        return false;
    }
    /**
     * 권한 체크
     *
     * @param String $codePath
     * @param [type] $user
     * @return void
     */
    public function permissionCheck($codePath, $user){
        /**
         * 권한 체크
         */
        $groupService = new GroupService($this->container);
        $permissionService = new PermissionService($this->container);
        $isAdmin =   $groupService->isAdminByUser($user); 
        $groups = [];
        if($isAdmin){
            $groups = $groupService->list();
        }else{
            $groups = $groupService->listByMemberId($user->member_id);
        }

        $lists = $permissionService->searchByPath($codePath, $user, $groups);
        return $lists;
        
    }
    /**
     * 권한이 있을시 true 리턴
     *
     * @param string $codePath
     * @param [type] $user
     * @return bool
     */
    public function isPermission($codePath, $user){
        $permission = $this->permissionCheck($codePath, $user);
        $rtn = false;
        foreach($permission as $permission){
            if ($permission == '*') {
                $rtn = true;
            }else{
                $rtn = false;
            }
        }
        return $rtn;
    }
    public function updateStatus($reqNo,$data){
        
        $updateStatus = $data['update_status'];
        $request = $this->findOrFail($reqNo);
        
        $request->req_status = $updateStatus;

        $request->save();

        // \Illuminate\Database\Capsule\Manager::table('tb_request')
        //     ->where('req_no', $reqNo)
        //     ->update(['req_status' => $updateStatus]);
        // $request = $this->find($reqNo);

        return $request;
    }

    public function deleteRequest($reqNo){
        $request = $this->findOrFail($reqNo);
        $request->delete();
        return $request;
    }

    public function find($id)
    {
        $query = TbRequest::query();

        return $query->find($id);
    }

    public function findOrFail($id)
    {
        $tbRequest = $this->find($id);
        if (!$tbRequest) {
            api_abort_404('TbRequest');
        }
        return $tbRequest;
    }
}