<?php

namespace Api\Controllers;

use Api\Models\User;
use Api\Http\ApiRequest;
use Api\Http\ApiResponse;
use Proxima\core\Session;
use Api\Services\LogService;
use Api\Services\UserService;
use Api\Services\AuthNumberService;
use Api\Controllers\BaseController;
use Psr\Container\ContainerInterface;
use Api\Support\Helpers\SMSMessageHelper;
use Api\Services\ZodiacService;

class AuthController extends BaseController
{
    /**
     * 접근 권한 서비스
     *
     * @var \Api\Services\UserService
     */
    private $userService;

    /**
     * 로그 서비스
     *
     * @var \Api\Services\LogService
     */
    private $logService;
    /**
     * 로그인 인증번호 서비스
     * 
     * @var \Api\Services\AuthNumberService
     */
    private $authNumberService;
    /**
     * 조디악 서비스
     *
     * @var \Api\Services\ZodiacService
     */
    private $zodiacService;
    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->userService = new UserService($container);
        $this->logService = new LogService($container);
        $this->zodiacService = new ZodiacService($container);
        $this->authNumberService = new AuthNumberService($container);
    }

    /**
     * 로그인
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function login(ApiRequest $request, ApiResponse $response, array $args)
    {
        //로그인 처리 분기
        $sessionConfig = config('session');
        $mode = $sessionConfig['driver'];

        $userId = trim($request->input('user_name'));
        $password = trim($request->input('password'));
        $flag = trim($request->input('flag'));
        $authNumber = $request->input('auth_number');
        if (empty($userId) || empty($password)) {
            api_abort(_text('MSG00137'), 'invalid_input', 400);
        }

        if ($mode == 'sso') {
            // SSO 로그인
            /** @var \Api\Core\Session\lib\bandiSSO */
            $ssoClient = $this->container->get('sso');
            $result = $ssoClient->login($userId, $password);
            if ($result['error'] == '0000' || $result['error'] == 'VL-3130') {

                $user = $this->userService->findByUserId($userId);
                if ($user === null) {
                    api_abort_404(User::class);
                }
            } else {
                // 로그인 실패
                api_abort($result['error_message'], $result['error'], 400);
            }
        } else {
            $user = $this->userService->login($userId, $password);
            if ($user === null) {
                api_abort_404(User::class);
            }
        }


        /**
         * 외부사용자 $phoneAuth false
         * 내부사용자 $phoneAuth true
         */
        $phoneAuth = true;
        $serverHost = get_server_param('HTTP_HOST');
        
        //대상 도메인
        $servers = explode(",", config('sms_auth')['domain']);
        
        //예외 사용자
        $allowUsers = explode(",", config('sms_auth')['allow_user']);
        
        if( in_array( $user->user_id, $allowUsers )  ){
            $isAllowUser = true;
        }else{
            $isAllowUser = false;
        }
        
        if(!$isAllowUser && in_array($serverHost, $servers)){
            if(is_null($authNumber)){
                $authNum = sprintf('%04d',rand(1111,9999));
                $this->authNumberService->create($user->user_id,$authNum);
                $findAuthNumber = $this->authNumberService->findByUserId($user->user_id);
                $loginAuthNumber = $findAuthNumber->auth_number;
                $smsMsg = SMSMessageHelper::makeMsgAuthNumber($loginAuthNumber);
                $this->zodiacService->sendSMS($user->phone, $smsMsg);
               
                
                
                $phoneAuth = false;
            }else{
                if($request->input('limit_time') <= 0 ){
                    api_abort('인증번호가 만료되었습다 재발급 버튼을 눌러주세요.', 'invalid_input', 400);
                };

                if($authNumber == ""){
                    api_abort('인증번호를 입력해주세요.', 'invalid_input', 400);
                };
                $authCheck = $this->authNumberService->getByAuthNumberOrUserId($user->user_id, trim($authNumber));
                if(is_null($authCheck)){
                    api_abort('정확하지 않은 인증번호 입니다.', 'invalid_input', 400);
                }
            }    
        }
     
  
        if ($phoneAuth) {
            // 세션 생성
            // 로그인 성공
            Session::init();
            if ($mode == 'sso') {
                Session::set('access_token', $result['access_token']);
                Session::set('refresh_token', $result['refresh_token']);
            }
            Session::set(config('app_auth_id'), $userId);

            $groupIds = $user->groups->pluck('member_group_id')->toArray();
            // 레거시 호환용 세션변수
            $userSession = [
            'user_id' => $userId,
            'is_admin' => bool_to_yn($user->hasAdminGroup()),
            'KOR_NM' => $user->user_nm,
            'user_email' => $user->email,
            'phone' =>  $user->phone,
            'groups' => $groupIds,
            'lang' => $user->lang,
            'super_admin' => $this->userService->isSuperAdmin($password),
            'user_pass' => hash('sha512', $password)
            ];
            Session::set('user', $userSession);

            $redirection = 'main.php';
            if (!empty($flag) && in_array($flag, config('plugin')['allow_flag'])) {
                $redirection = 'interface/app/plugin/regist_form/index.php?flag=' . $flag;
            }

            if ($user->user_id) {
                $description = getClientIp();
                $logData = [
                'action' => 'login',
                'description' => $description
            ];
                $log = $this->logService->create($logData, $user);
            }

            // 로그인 완료후 인증번호 삭제
            $this->authNumberService->deleteByUserId($user->user_id);
        }
        
       
        return response()->withJson([
            'success' => true,
            'redirection' => $redirection,
            'auth_phone' => $phoneAuth
        ]);
    }

    /**
     * 로그아웃
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function logout(ApiRequest $request, ApiResponse $response, array $args)
    {
        $token = Session::get('token');
        // SSO 로그아웃
        Session::destroy();
    }

    public function authNumberReSend(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        $userId = $input['user_id'];
 
        $user = $this->userService->findByUserId($userId);
        $authNum = sprintf('%04d',rand(1111,9999));
        $this->authNumberService->create($user->user_id,$authNum);


        $findAuthNumber = $this->authNumberService->findByUserId($user->user_id);
        $loginAuthNumber = $findAuthNumber->auth_number;
        $smsMsg = SMSMessageHelper::makeMsgAuthNumber($loginAuthNumber);
        $this->zodiacService->sendSMS($user->phone, $smsMsg);

        return $response->ok();
    }
}
