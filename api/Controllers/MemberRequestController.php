<?php

namespace Api\Controllers;

use Api\Models\User;
use Api\Http\ApiRequest;

use Api\Http\ApiResponse;

use Api\Types\MemberStatus;
use Api\Services\UserService;
use Api\Services\GroupService;
use Api\Services\ZodiacService;
use Api\Services\BisCommonService;
use Psr\Container\ContainerInterface;
use Api\Services\MemberRequestService;
use Api\Services\DataDicCodeItemService;
use Api\Support\Helpers\SMSMessageHelper;

class MemberRequestController extends BaseController
{
    /**
     * 사용자 등록 요청 서비스
     *
     * @var \Api\Services\MemberRequestService
     */
    private $memberRequestService;
    /**
     * 사용자 관리 서비스
     *
     * @var \Api\Services\UserService
     */
    private $userService;
    /**
     * 조디악 서비스
     *
     * @var \Api\Services\ZodiacService
     */
    private $zodiacService;
    /**
     * 데이터사전 코드 서비스
     *
     * @var \Api\Services\DataDicCodeItemService
     */
    private $dataDicCodeItemService;
    /**
     * BIS 연동 서비스
     *
     * @var \Api\Services\BisCommonService
     */
    private $bisService;

    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->memberRequestService = new MemberRequestService($container);
        $this->userService = new UserService($container);
        $this->zodiacService = new ZodiacService($container);
        $this->dataDicCodeItemService = new DataDicCodeItemService($container);

        if( config('bis')['user'] ) {
            $this->bisService = new BisCommonService($container);
        }
        
    }

    /**
     * 사용자 ID 신청
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function requestUser(ApiRequest $request, ApiResponse $response, array $args)
    {
        $data = $request->all();

        // IP 내부 및 외부 체크하여 내부IP라면 401 에러 리턴
        if (checkInternalIp()) {
            return $response->error('Unauthorized Error', 401);
        }
        
        // charger_id가 member_id 값을 갖고 있어서 문자전송을 위해 담당자의 user_id를 찾아줌
        $charger = $this->userService->findByMemberId($data['charger_id']);
        $data['charger_id'] = $charger->user_id;
        
        $memberRequest = $this->memberRequestService->requestUser($data);
        // 담당자에게 문자
        $smsMsg = SMSMessageHelper::makeMsgMemberRegRequested($memberRequest);
        $charger = $memberRequest->charger;
        $this->zodiacService->sendSMS($charger->phone, $smsMsg);
   
        return $response->ok($memberRequest, 201);
    }

    /**
     * 사용자 아이디 중복 existsuser함수에서 true false 반환
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function existsUser(ApiRequest $request, ApiResponse $response, array $args)
    {
        $data = $request->all();
        $userId = $data['user_id'];
        $exists = $this->memberRequestService->existsUser($userId);
        return $response->ok($exists, 201);
    }

    /**
     * requestUsersList 사용자 신청 유저 목록
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function requestUsersList(ApiRequest $request, ApiResponse $response, array $args)
    { 
        $data = $request->all();
        
        $user = auth()->user();
        $groupService = new GroupService($this->container);
        $isAdmin =   $groupService->isAdminByUser($user); 
        
        $users = $this->memberRequestService->requestUsersList($user,$data);
        $dataDicCodeItemService = new DataDicCodeItemService($this->container);
        foreach($users as $user){
            //코드 아이템 부서 코드
            $deptCode = $user->dept;
            $deptCodeItem = $dataDicCodeItemService->findCodeItemByCodeItemCode($deptCode);
            $user->dept_info = $deptCodeItem;
        };
 
        return $response->ok($users);
    }
    /**
     * 요청된 상태를 승인 또는 반려 한다.
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function changeStatus(ApiRequest $request, ApiResponse $response, array $args)
    {
        $id = $args['id'];
        $data = $request->all();
        /** @var \Api\Types\MemberStatus $changeStatus */
        $changeStatus = $data['change_status'];
        /** @var \Api\Types\MemberStatus $changePdStatus */
        $changePdStatus = $data['change_pd_status'];
        $user = null;
        if(!is_null($changePdStatus)){
            $memberRequest = $this->memberRequestService->changePdStatus($id, $changePdStatus);
            /**
             * 담당자 승인상태가 승인이면 관리자에게 문자를 보내고
             * 반려이면 신청자에게 문자를 보낸다.
             */
            $smsMsg = SMSMessageHelper::makeMsgMemberRegPdStatusChanged($memberRequest);
            if($memberRequest->pd_status === MemberStatus::APPROVAL) {
                $adminUsers = $this->userService->getAdminUsers();
                foreach($adminUsers as $adminUser) {                  
                    $this->zodiacService->sendSMS($adminUser->phone, $smsMsg);                 
                }
            } else {
                $this->zodiacService->sendSMS($memberRequest->phone, $smsMsg);
            }
            
            $user = $memberRequest;
        }else if(!is_null($changeStatus)){
            $memberRequest = $this->memberRequestService->changeStatus($id, $changeStatus);
            // chageStatus 결과가 반려일 때 나머지 로직 안타게 수정 - 220718
            if ( $memberRequest->status == MemberStatus::REJECT ) {
                // 패스워드 unset
                unset($memberRequest->password);
                return $response->ok($memberRequest);
            }
            //사용자 생성
            //그룹생성 
            //권한 생성
            $isExist = $this->userService->findByUserId($memberRequest->user_id);
            if( !empty($isExist) ){
                return $response->error('중복 아이디 존재');
            }

            $sessionConfig = config('session');
            $mode = $sessionConfig['driver'];

            $userId = $memberRequest->user_id;
            $password = $memberRequest->password;
                      
            if($mode == 'sso'){
               $ssoClient = $this->container->get('sso_admin');
               $encUserId = $this->userService->encryptUserId( $memberRequest->user_id ); 
               // dump($request);
               $userRealName = $memberRequest->user_nm;
               $ssoEmail = '-';
               $ssoHpNo = empty($memberRequest->phone) ? '-' :$memberRequest->phone;
               if( !empty($password ) ){
                    $passwordHash = $this->userService->encryptPassword( $password );
               }
    
                $result = $ssoClient->updateUser($encUserId, $passwordHash, $userRealName, $ssoEmail, $ssoHpNo);    
                if($result['error'] != '0000'){
                    api_abort($result['error_message'], $result['error'], 400);
                }
            }
            $deptNm = $this->dataDicCodeItemService->findCodeItemByCodeItemCode($memberRequest->dept);
            
            $data = [
                'user_id' => $memberRequest->user_id,
                'dept_nm' => $deptNm->code_itm_nm,
                'password' => $memberRequest->password,
                'phone' => $memberRequest->phone,
                'user_nm' => $memberRequest->user_nm,               
                'org_id' => $memberRequest->instt,
                'groups' => 3
            ];
            $userData = (object)$data;        
            $user = $this->userService->create($userData);  

                            
            //조디악 동기화
            if( config('zodiac')['linkage'] ){
                $this->userService->syncUserZodiac( $userId );
            }

             //bis 연동
            if( config('bis')['user'] ){            
                $r  = $this->bisService->createUser( $userData );
            }
                
            //od 동기화
            if( config('od')['linkage'] ){
                $folderAuth = new \ProximaCustom\core\FolderAuthManager();
                $folderAuth->createUserFromOD($userId, $userRealName, $user->member_id, $password );
            }

            /**
             * 관리자 등록/반려에 관계 없이 신청자에게 문자를 보낸다.
             */
            $smsMsg = SMSMessageHelper::makeMsgMemberRegAdminStatusChanged($memberRequest);
            $this->zodiacService->sendSMS($user->phone, $smsMsg);
        };
        
        return $response->ok($user);
    }
    /**
     * 사용자 ID 신청 수정
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function requestUserUpdate(ApiRequest $request, ApiResponse $response, array $args)
    {
        $id = $args['id'];
        $data = $request->all();
        $findUser = $this->memberRequestService->findOrFail($id);

        if($findUser->status === MemberStatus::APPROVAL){
            return $response->okMsg(null, '승인완료된 목록은 수정할 수 없습니다.');
        }
            
        $user = $this->memberRequestService->requestUserUpdate($id, $data);
        return $response->ok($user);
    }
}