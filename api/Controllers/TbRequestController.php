<?php

namespace Api\Controllers;

use Api\Http\ApiRequest;
use Api\Http\ApiResponse;
use Psr\Container\ContainerInterface;
use Api\Services\TbRequestService;
use Api\Models\ContentDelete;
use Api\Services\GroupService;
use Api\Services\PermissionService;
use Api\Services\AuthorityMandateService;

use Api\Types\ArchiveRequestType;
use Api\Types\ArchiveRequestStatus;
use Api\Types\ContentStatusType;

class TbRequestController extends BaseController
{
    /**
     * tb_request service
     *
     * @var \Api\Services\TbRequestService
     */
    private $tbRequestService;


    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->tbRequestService = new TbRequestService($container);
        $this->permissionService = new PermissionService($container);
    }
    /**
     * 아카이브 요청이 되어 있는지 되어있다면 파일 삭제가 가능한 상태인지
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function archiveRequestFileDeleteCheck(ApiRequest $request, ApiResponse $response, array $args)
    {
        $contentId = $args['content_id'];
        $input = $request->all();
        $contentDelete = ContentDelete::where('delete_type','=','CONTENT')->where('content_id', $contentId)->get();
        if(count($contentDelete) !== 0 ){
            return $response->error('파일삭제 요청된 이력이 있습니다.');
        };
        /**
         * 온라인에
         * 삭제되었으면 true , 안되었으면 false
         */
    
        // if($break){
        //     return $response->error('아카이브 상태가 요청 또는 반려일때만 파일삭제할 수 있습니다.');
        // }else{
        //     return $response->ok();
        // }
    }
    /**
     * 아카이브 삭제 요청
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function requestDelete(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        $user = auth()->user();

        /**
         * 삭제요청 체크
         */
        $contentIds = json_decode($input['content_ids']);
        foreach($contentIds as $contentId){
            // $getRequestDeleteCheck = $this->tbRequestService->requestDeleteCheck($contentId);
            // $contentDeleteCheck = $this->tbRequestService->contentDeleteCheck($contentId);
            
            // if($getRequestDeleteCheck->count() != 0){
            //     foreach($getRequestDeleteCheck as $requestDeleteCheck){
            //         $requestStatus = $requestDeleteCheck->req_status;
            //         switch($requestDeleteCheck->req_type){
            //             case ArchiveRequestType::ARCHIVE :
            //                     if($requestStatus == ArchiveRequestStatus::REJECT)
            //                         return $response->error('아카이브 반려된 컨텐츠 입니다.'); 

            //                     if($requestStatus == ArchiveRequestStatus::REQUEST)
            //                         return $response->error('아카이브 요청중인 컨텐츠 입니다.');
            //                 break;
            //             case ArchiveRequestType::RESTORE :
            //                     if($requestStatus == ArchiveRequestStatus::REQUEST)
            //                         return $response->error('리스토어 요청중인 콘텐츠 입니다.');      
            //                 break;
            //             case ArchiveRequestType::DELETE :
            //                     if($requestStatus == ArchiveRequestStatus::REQUEST)
            //                         return $response->error('아카이브 삭제 요청된 콘텐츠 입니다.');      

            //                     if($requestStatus == ArchiveRequestStatus::COMPLETE)
            //                         return $response->error('아카이브 삭제 요청 승인 된 콘텐츠 입니다.');      
            //                 break;
            //         };
            //     };
            // };
            $contentStatusCheck = $this->tbRequestService->contentStatusCheck($contentId);
            if(!empty($contentStatusCheck))
                return $response->error($contentStatusCheck);

            $archiveRequestDeleteCheck = $this->tbRequestService->archiveRequestDeleteCheck($contentId);
            if(!empty($archiveRequestDeleteCheck))
                return $response->error($archiveRequestDeleteCheck);
            
       
            
                
            // if(!is_null($contentDeleteCheck)){
            //     if($contentDeleteCheck->is_deleted == 'Y')
            //         return $response->error('삭제 된 콘텐츠 입니다.');
            //     if($contentDeleteCheck->status != ContentStatusType::COMPLETE){
            //         switch($contentDeleteCheck->status){
            //             case ContentStatusType::WAITING :
            //                     return $response->error('등록대기중인 콘텐츠 입니다.');      
            //                 break;
            //             case ContentStatusType::REGISTERING :
            //                     return $response->error('등록중인 콘텐츠 입니다.');      
            //                 break;
            //             case ContentStatusType::REJECT :
            //                     return $response->error('반려 된 콘텐츠 입니다.');      
            //              ;   break;
            //         };
            //     };
            // };
        };
        
        $archiveRequestDelete = $this->tbRequestService->request($input,'delete',$user);
    
        return $response->ok($archiveRequestDelete, 201);
    }
    public function requestRestore(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        $user = auth()->user();
        $codePath1 = 'authority_mandate.restore';
        // $codePath2 = 'auto_request.restore';
        $groupService = new GroupService($this->container);
     
        $permissionCheck = false;

        $isAdmin =   $groupService->isAdminByUser($user); 
        if($isAdmin){
            $groups = $groupService->list();
        }else{
            $groups = $groupService->listByMemberId($user->member_id);
        }
        /**
         * 권한이 있는 사람
         * isPermission return -> true or false
         */
        // $autoRestorePermission = $this->tbRequestService->isPermission($codePath2, $user);
        // if($autoRestorePermission){
        //     $permissionCheck = true;
        // };
   
        $restorePermission = $this->tbRequestService->isPermission($codePath1, $user);
        if($restorePermission){
            $permissionCheck  = true;
        }

        // 권한 승계자
        $authorityMandateService = new AuthorityMandateService($this->container);
        $mandatary = $authorityMandateService->getMandataryByUserId($user);
        if(!is_null($mandatary)){
            $permissionCheck = true;
        };
      
        if(!$permissionCheck){
            return $response->error('리스토어 권한이 없습니다.');
        }
       
        /**
         * 리스토어 요청 체크
         */
        $contentIds = json_decode($input['content_ids']);
        foreach($contentIds as $contentId){
            $contentStatusCheck = $this->tbRequestService->contentStatusCheck($contentId);
            if(!empty($contentStatusCheck))
                return $response->error($contentStatusCheck);

            $archiveRequestRestoreCheck = $this->tbRequestService->archiveRequestRestoreCheck($contentId);
            if(!empty($archiveRequestRestoreCheck))
                return $response->error($archiveRequestRestoreCheck);
        }

        $archiveRequestDelete = $this->tbRequestService->request($input,'restore',$user);
        if(empty($archiveRequestDelete))
            return $response->error('요청 실패');

        return $response->ok($archiveRequestDelete, 201);
    }
    /**
     * 아카이브 상태 변경
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function updateStatus(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        $user = auth()->user();
        $reqNo = $args['req_no'];

        $updateStatus = $this->tbRequestService->updateStatus($reqNo,$input);
        return $response->ok($updateStatus);
    }

    public function deleteRequest(ApiRequest $request, ApiResponse $response, array $args)
    {
        $reqNo = $args['req_no'];
        $delete = $this->tbRequestService->deleteRequest($reqNo);
        return $response->ok($delete);
    }


}