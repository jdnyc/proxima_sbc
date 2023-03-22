<?php

namespace Api\Controllers;

use Api\Http\ApiRequest;
use Api\Http\ApiResponse;
use Api\Types\ReviewType;
use Api\Types\ReviewStatus;
use Api\Models\ContentStatus;
use Api\Services\UserService;
use Api\Services\ReviewService;
use Api\Services\ContentService;
use Api\Types\ContentStatusType;
use Api\Services\ReviewLogService;
use Api\Controllers\BaseController;
use Psr\Container\ContainerInterface;
use Api\Types\ContentReviewStatusType;
use Api\Services\DataDicCodeSetService;
use Api\Services\DataDicCodeItemService;
use Api\Support\Helpers\SMSMessageHelper;
use Api\Services\ZodiacService;


class ReviewController extends BaseController
{
    /**
     * 조디악 서비스
     *
     * @var \Api\Services\ZodiacService
     */
    private $zodiacService;
    /**
     * 콘텐츠등록 방송심의 서비스
     *
     * @var \Api\Services\ReviewService;
     */
    private $reviewService;
    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->reviewService = new ReviewService($container);
        $this->userService = new UserService($container);
        $this->reviewLogService = new ReviewLogService($container);
        $this->contentService = new ContentService($container);
        $this->zodiacService = new ZodiacService($container);
    }

    /**
     * 심의 목록 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function index(ApiRequest $request, ApiResponse $response, array $args)
    {

        $input = $request->all();
        $user = auth()->user();
        $isAdmin = $user->hasAdminGroup();
        /**
         * 심의 리스트 조회
         */

        $reviews = $this->reviewService->getByReviewList($input, $user);

        // $reviews = $this->reviewService->list();


        /**
         * 심의 의뢰 상태 코드 값
         */
        $dataDicCodeSetService = new DataDicCodeSetService($this->container);

        $reviewStatusCodeItems = $dataDicCodeSetService->findByCodeOrFail('REVIEW_REQEST_STTUS')
            ->codeItems()
            ->get(['id', 'code_itm_code', 'code_itm_nm']);

            // dd($reviews);
        
        foreach ($reviews as $review) {
            $reviewStatusCode = $review->review_reqest_sttus;
            $review->review_st_code = DataDicCodeItemService::getCodeItemByCode($reviewStatusCodeItems, $reviewStatusCode);
            // $review->review_user = UserService::findOrFailByUserIdQuery('admin')->get(['member_id','user_id','user_nm']);
            
        };
        
        
        // return $response->ok($reviews);
        $data = json_decode($response->ok($reviews)->getBody());
        $data->groups = [];
        if($isAdmin){
            $data->groups['ingest_group'] = true;
        }else{
            $data->groups['ingest_group'] = $user->hasIngestGroup();
        }
        
        
    
        return $response->withJson($data)->withStatus(200);

    }
    /**
     * 의뢰 담당자 변경
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function updateCharger(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();

        $id = $args['id'];
        $updateCharger = $input['updateCharger'];

        $charger = $this->reviewService->updateCharger($id, $input);
        return $response->ok($charger);
    }
    /**
     * 의뢰 진행상태 변경
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function statusUpdate(ApiRequest $request, ApiResponse $response, array $args)
    {
        // 로그인유저
        $user = auth()->user();
        $userId = $user->user_id;

        $input = $request->all();
        $id = $args['id'];
        
        $changeStatus = $input['change_status'];
        // 등록자 아이디
        $registUserId = $input['regist_user'];
        
        $review = $this->reviewService->findOrFail($id);
        $reviewType = $review->review_ty_se;
        $reviewUserId = $review->review_user_id;
        if($changeStatus == ReviewStatus::REQUEST){
            // 상태값을 요청상태로 바꿀때 .. -> 재승인 버튼을 눌렀을 때
            if(!is_null($input['type'])){
                if($input['type'] == 're'){
                    if($userId == $registUserId){
                        // 등록자와 현재 로그인 유저가 같은 아이디 일때 재승인 버튼을 누를 수 있다.
                        $status = $this->reviewService->updateStatus($id, $input,$user);
                        $this->reviewLogService->reviewLog($input,$reviewType);
                     
                        return $response->ok($status);
                    }else{
                        // return $response->error('심의 요청자가 아닙니다.');
                        api_abort('심의요청자만 재 승인 버튼을 눌러주세요..', 400);
                    }
                }
                if($input['type'] == 'cancel'){
                    if($userId == $reviewUserId){

                        $beforeChangeStatus = $review->review_reqest_sttus;
                        $action = $beforeChangeStatus;
                        
                        $status = $this->reviewService->updateStatus($id, $input,$user);
                        $this->reviewService->reviewCnNull($id);
                        $this->reviewLogService->reviewCancelLog($input,$reviewType,$action);
                        
                        if($status->review_ty_se == ReviewType::CONTENT){
                            $usrMeta = [];
                            switch($status->review_reqest_sttus){
                                case ReviewStatus::REQUEST:
                                    $contentMeta = [
                                        'status'=> ContentStatusType::WAITING
                                    ];
                                    $content = $this->contentService->find($status->content_id);
                                    $content->status = $contentMeta['status'];
                                    $usrMetaInfo = $this->contentService->findContentUsrMeta($status->content_id);
                                    
                                    // //승인취소시에 한번 변경
                                    $usrMeta['othbc_at'] = 'N' ;//공개여부
                                    $usrMeta['reviv_posbl_at'] = 'N';//재생가능여부 
                                    $usrMeta = $this->contentService->changePortalMeta( $content, $usrMetaInfo, $usrMeta );                        
                                break;
                            }
                            $this->contentService->updateUsingArray($status->content_id, $contentMeta, [],[],$usrMeta, $user);
                        }
                        return $response->ok($status);

                    }else{
                        api_abort('심의자만 상태취소 버튼을 눌러주세요.', 400);
                    }
                }
            };
            
        }else{
            
            $status = $this->reviewService->updateStatus($id, $input,$user);
            $this->reviewLogService->reviewLog($input,$reviewType);
            $reviewRejectCount = $this->reviewLogService->reviewLogCount($input,$reviewType,'reject');
            if(($changeStatus === ReviewStatus::REJECT) && ($status->review_reqest_sttus === ReviewStatus::REJECT)){
                if($status->review_ty_se === ReviewType::CONTENT){
                    // 반려시 문자 등록자에게 문자
                    $reviewUserInfo = $this->userService->findByUserId($status->review_user_id);
                    $contentInfo = $this->contentService->getContentByContentId($status->content_id);

                    $smsMsg = SMSMessageHelper::makeMsgReviewStatusChangeReject($status,$contentInfo,$reviewUserInfo,$reviewRejectCount);

                    $regUserId = $status->regist_user_id;
                    $userInfo = $this->userService->findByUserId($regUserId);
                    $this->zodiacService->sendSMS($userInfo->phone, $smsMsg);
                    
                };
            }
            
            if($status->review_ty_se == ReviewType::CONTENT){
                $usrMeta = [];
                
                switch($status->review_reqest_sttus)
                {
                    case ReviewStatus::APPROVAL:
                        $contentMeta = [
                            'status'=> ContentStatusType::COMPLETE
                        ];
                        
                        $content = $this->contentService->find($status->content_id);
                        $content->status = $contentMeta['status'];
                        $usrMetaInfo = $this->contentService->findContentUsrMeta($status->content_id);
                        
                        //승인시에 한번 변경
                        $usrMeta['othbc_at'] = 'Y' ;//공개여부
                        $usrMeta['reviv_posbl_at'] = 'Y';//재생가능여부 
                        $usrMeta = $this->contentService->changePortalMeta( $content, $usrMetaInfo, $usrMeta );
                    break;
                    case ReviewStatus::REJECT:
                        $contentMeta = [
                            'status'=> ContentStatusType::REJECT
                        ];
                    break;
                }
              
                $this->contentService->updateUsingArray($status->content_id, $contentMeta, [],[],$usrMeta, $user);
            };
           
            return $response->ok($status);
        };
        
        
    }
    /**
     * 심의 수정(반려 내용 추가)
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function updateRejectCn(ApiRequest $request, ApiResponse $response, array $args){
        $input = $request->all();
        // 로그인유저
        $userId = auth()->user()->user_id;
        
        $id = $args['id'];
        
        $updateRejectCn = $input['reject_cn'];
        // 심의자 유저
        $reviewUserId = $input['review_user'];

        // if($userId == $reviewUserId){
        if(true){
            $rejectCn = $this->reviewService->updateReviewCn($id, $input, $userId);
            return $response->ok($rejectCn);
        }else{
            switch($input['change_status']){
                case ReviewStatus::APPROVAL :
                $msg = '등록된 심의자만 승인 할 수 있습니다.';
                break;
                case ReviewStatus::REJECT :
                $msg = '등록된 심의자만 반려 할 수 있습니다.';
                break;
                default:
                $msg = '수행할 수 없습니다.';
                break;
            };

            return $response->error($msg);
        }
        
        
        
       
    }
    public function create(ApiRequest $request, ApiResponse $response, array $args)
    {
        $data = $request->all();
        $user = auth()->user();

        $reviewData = json_decode($data['reviewsData']);

        //방송심의만 상태 업데이트
        if( $reviewData->review_ty_se == 'ingest' ){
            // content status
            $contentStatus = $this->contentService->findStatusMeta($reviewData->content_id);           
            if(is_null($contentStatus)){
                $contentStatus = new ContentStatus();
                $contentStatus->content_id = $reviewData->content_id;
            };
            // 3:심의 대기, 4:심의 승인, 5:심의 반려, 6:심의 조건부 승인            
            $contentStatus->review_status = ContentReviewStatusType::WAITING;            
            $contentStatus->save();
        }

        $reviews = $this->reviewService->create($reviewData, $user);

        return $response->ok($reviews, 201);
    }
}
