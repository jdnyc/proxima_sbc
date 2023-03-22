<?php

namespace Api\Controllers;

use Api\Models\Log;
use Api\Models\User;
use Api\Http\ApiRequest;
use Api\Http\ApiResponse;
use Api\Services\UserService;
// use Api\Services\UserService;
// use Psr\Container\ContainerInterface;
use Api\Services\BisCommonService;
use Api\Controllers\BaseController;
use Api\Support\Helpers\UserHelper;
use Psr\Container\ContainerInterface;
use Illuminate\Database\Capsule\Manager as DB;

class UserController extends BaseController
{
    /**
     * 사용자 서비스
     *
     * @var \Api\Services\UserService
     */
    private $userService;
    /**
     * BIS 서비스
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

        $this->userService = new UserService($container);

        $this->bisService = new BisCommonService($container);
    }
    /**
     * 홈화면 개인설정 비밀번호 변경
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function changeMyPassword(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();

        //아이디 기준 유저 정보
        $user = auth()->user();
        // dd($user->password);
        // 현재 비밀번호
        $currentPassword = $input['current_password'];
        $encryptCurrentPassword = $this->userService->encryptPassword($currentPassword);
        // 새 비밀번호
        $newPassword = $input['new_password'];
        $encryptNewPassword = $this->userService->encryptPassword($newPassword);

        // 유저 정보의 비밀번호와 현재 비밀번호 비교
        if ($user->password == $encryptCurrentPassword) {
            // 비밀번호 변경 서비스

            $sessionConfig = config('session');
            $mode = $sessionConfig['driver'];
            if ($mode == 'sso') {
                $encUserId = $this->userService->encryptUserId($user->user_id);

                $ssoClient = $this->container->get('sso_admin');
                $result = $ssoClient->updatePassword($encUserId, $encryptNewPassword);

                if ($result['error'] != '0000') {
                    api_abort($result['error_message'], $result['error'], 400);
                    //return $response->error('비밀번호 변경 실패하였습니다.');
                }
            }

            $this->userService->updatePassword($user->user_id, $newPassword);
            $this->container['logger']->info('zodiac' . config('zodiac')['linkage']);
            //조디악 동기화
            if (config('zodiac')['linkage']) {
                $this->container['logger']->info('zodiac ' . $user->user_id);
                $r = $this->userService->syncUserZodiac($user->user_id);
                $this->container['logger']->info('zodiac ' . print_r($r, true));
            }

            //bis 연동
            if (config('bis')['user']) {
                $r = $this->bisService->changePassword($user->user_id, $newPassword);
            }

            //od 동기화
            if (config('od')['linkage']) {
                $folderAuth = new \ProximaCustom\core\FolderAuthManager();
                $folderAuth->changePasswordFromOD($user->user_id, $newPassword);
            }

        } else {
            return $response->error('현재 비밀번호가 일치 하지 않습니다.');
        };
        return $response->ok($user);
    }

    public function updateMyOption(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();

        //아이디 기준 유저 정보
        $user = auth()->user();

        $this->userService->updateOption($user, $input);

        return $response->ok();
    }

    /**
     * 목록 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function index(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        $users = $this->userService->list($input);
        return $response->ok($users);
    }

    /**
     * 단건 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function showAdmin(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        $userId = $input['user_id'];
        $user = $this->userService->findByUserId($userId);
        return $response->ok($user);
    }

    /**
     * 사용자 생성
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function create(ApiRequest $request, ApiResponse $response, array $args)
    {
        $sessionConfig = config('session');
        $mode = $sessionConfig['driver'];

        $userId = trim($request->input('user_name'));
        $password = trim($request->input('password'));
        $userRealName = trim($request->input('user_real_name'));
        $email = trim($request->input('email'));
        $hpNo = trim($request->input('hp_no'));

        $deptNm = trim($request->input('dept_nm'));
        $expiredDate = trim($request->input('expired_date'));
        $groups = trim($request->input('groups'));

        $passwordHash = $this->userService->encryptPassword($password);

        if (empty($userId) || empty($password)) {
            api_abort(_text('MSG00137'), 'invalid_input', 400);
        }

        //dump($passwordHash);
        if ($mode == 'sso') {
            $ssoClient = $this->container->get('sso_admin');
            // dump($request);
            $ssoEmail = empty($email) ? '-' : $email;
            $ssoHpNo = empty($hpNo) ? '-' : $hpNo;
            $result = $ssoClient->createUser($userId, $passwordHash, $userRealName, $ssoEmail, $ssoHpNo);
            if ($result['error'] != '0000') {
                api_abort($result['error_message'], $result['error'], 400);
            }
        }

        $userData = new \stdClass();
        $userData->user_id = $userId;
        $userData->password = $password;
        $userData->user_nm = $userRealName;
        $userData->dept_nm = $deptNm;
        $userData->expired_date = $expiredDate;
        $userData->phone = $hpNo;
        $userData->email = $email;
        $userData->groups = $groups;
        $user = $this->userService->create($userData);

        if (!$user) {
            api_abort(_text('MSG00137'), 'create failed', 400);
        }

        //조디악 동기화
        if (config('zodiac')['linkage']) {
            $this->userService->syncUserZodiac($userId);
        }

        //bis 연동
        if (config('bis')['user']) {
            $r = $this->bisService->createUser($userData);
        }

        //od 동기화
        if (config('od')['linkage']) {
            $folderAuth = new \ProximaCustom\core\FolderAuthManager();
            $folderAuth->createUserFromOD($userId, $userRealName, $user->member_id, $password);
        }

        return response()->withJson([
            'success' => true,
        ]);
    }

    public function delete(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        $this->userService->validateUserId($args);

        $userId = $args['user_id'];

        if (empty($userId)) {
            api_abort(_text('MSG00137'), 'invalid_input', 400);
        }

        $sessionConfig = config('session');
        $mode = $sessionConfig['driver'];
        if ($mode == 'sso') {

            $encUserId = $this->userService->encryptUserId($userId);
            $ssoClient = $this->container->get('sso_admin');

            $result = $ssoClient->selectUserById($encUserId);

            if (!empty($result['userInfo'])) {
                $result = $ssoClient->deleteUser($encUserId);
            }
        }
        $this->userService->delete($userId);

        //조디악 동기화
        if (config('zodiac')['linkage']) {
            $this->userService->syncUserZodiac($userId);
        }

        //bis 연동
        if (config('bis')['user']) {
            $r = $this->bisService->deleteUser($userId);
        }

        return $response->ok();
    }

    public function update(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        $this->userService->validateUserId($args);

        $userId = $args['user_id'];

        $userRealName = trim($request->input('user_real_name'));
        $email = trim($request->input('email'));
        $hpNo = trim($request->input('hp_no'));

        $deptNm = trim($request->input('dept_nm'));

        $expiredDate = trim($request->input('expired_date'));
        $groups = trim($request->input('groups'));
        $password = trim($request->input('password'));

        if (empty($userId)) {
            api_abort(_text('MSG00137'), 'invalid_input', 400);
        }

        $user = $this->userService->findByUserId($userId);

        if (empty($user)) {
            return $response->error("Not found user. ({$userId})", 'not_found_user', 404);
        }

        $sessionConfig = config('session');
        $mode = $sessionConfig['driver'];

        if ($mode == 'sso') {
            $ssoClient = $this->container->get('sso_admin');
            $encUserId = $this->userService->encryptUserId($userId);
            // dump($request);
            $ssoEmail = empty($email) ? '-' : $email;
            $ssoHpNo = empty($hpNo) ? '-' : $hpNo;
            if (!empty($password)) {
                $passwordHash = $this->userService->encryptPassword($password);
            } else {
                $passwordHash = $user->password;
                //파라미터에 없는경우 업데이트 제외
                $user->password = null;
            }

            $result = $ssoClient->updateUser($encUserId, $passwordHash, $userRealName, $ssoEmail, $ssoHpNo);
            if ($result['error'] != '0000') {
                api_abort($result['error_message'], $result['error'], 400);
            }
        }

        if (!empty($email)) {
            $user->email = $email;
        }

        if (!empty($hpNo)) {
            $user->phone = $hpNo;
        }
        if (!empty($userRealName)) {
            $user->user_nm = $userRealName;
        }
        if (!empty($deptNm)) {
            $user->dept_nm = $deptNm;
        }

        if (!empty($expiredDate)) {
            $user->expired_date = $expiredDate;
        }

        if (!empty($groups)) {
            $user->groups = $groups;
        }

        $user = $this->userService->update($user->member_id, $user);

        //조디악 동기화
        if (config('zodiac')['linkage']) {
            $this->userService->syncUserZodiac($userId);
        }

        //bis 연동
        if (config('bis')['user']) {
            $r = $this->bisService->updateUser($user);
        }

        $user->save();
        return $response->ok();
    }

    public function UserSyncAll(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();

        $type = $input['type'];

        $users = User::query()
            ->orderBy('member_id', 'asc')->get();

        foreach ($users as $key => $user) {
            $args['user_id'] = $user->user_id;
            $r = $this->UserSync($request, $response, $args);
        }
        return $response->ok();
    }

    public function UserSync(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();

        $userId = $args['user_id'];

        $type = $input['type'];

        $user = $this->userService->findByUserId($userId);

        if (empty($user)) {
            api_abort_404('user');
        }

        if (empty($type) || $type == 'sso') {
            $r = $this->userService->syncUserSSO($userId);
        }
        if (empty($type) || $type == 'cps') {
            $r = $this->userService->syncUserZodiac($userId);
        }
        if (empty($type) || $type == 'bis') {

            $bisUser = $this->bisService->getUserInfo($userId);

            if ($user->del_yn == bool_to_yn(true)) {
                //삭제됨
                if (!empty($bisUser)) { //삭제
                    $r = $this->bisService->deleteUser($userId);
                }
            } else {
                if (empty($bisUser)) { //신규
                    $r = $this->bisService->createUser($user);
                } else { //업데이트
                    $r = $this->bisService->updateUser($user);
                }
            }
        }

        return $response->ok($user);
    }

    /**
     * 인증오류 해제
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function initAuth(ApiRequest $request, ApiResponse $response, array $args)
    {
        $sessionConfig = config('session');
        $mode = $sessionConfig['driver'];

        $userId = $args['user_id'];

        if (empty($userId)) {
            api_abort(_text('MSG00137'), 'invalid_input', 400);
        }

        $encUserId = $this->userService->encryptUserId($userId);
        $ssoClient = $this->container->get('sso_admin');
        $result = $ssoClient->initAuth($encUserId);

        if ($result['error'] == '0000') {
            return response()->withJson([
                'success' => true,
            ]);
        } else {
            api_abort($result['error_message'], $result['error'], 400);
        }
    }

    /**
     * 활성화
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function active(ApiRequest $request, ApiResponse $response, array $args)
    {
        $sessionConfig = config('session');
        $mode = $sessionConfig['driver'];
        $userId = trim($request->input('user_name'));

        $encUserId = $this->userService->encryptUserId($userId);
        $ssoClient = $this->container->get('sso_admin');
        $result = $ssoClient->activeUser($encUserId);
        if ($result['error'] == '0000') {
            return response()->withJson([
                'success' => true,
            ]);
        } else {
            api_abort($result['error_message'], $result['error'], 400);
        }
    }

    //비활성화
    public function inactive(ApiRequest $request, ApiResponse $response, array $args)
    {
        $sessionConfig = config('session');
        $mode = $sessionConfig['driver'];
        $userId = trim($request->input('user_name'));

        $encUserId = $this->userService->encryptUserId($userId);
        $ssoClient = $this->container->get('sso_admin');
        $result = $ssoClient->inactiveUser($encUserId);
        if ($result['error'] == '0000') {
            return response()->withJson([
                'success' => true,
            ]);
        } else {
            api_abort($result['error_message'], $result['error'], 400);
        }
    }

    //패스워드 변경
    public function updatePassword(ApiRequest $request, ApiResponse $response, array $args)
    {
        $sessionConfig = config('session');
        $mode = $sessionConfig['driver'];
        $userId = trim($request->input('user_name'));
        $password = trim($request->input('password'));

        if ($mode == 'sso') {
            $encUserId = $this->userService->encryptUserId($userId);

            $ssoClient = $this->container->get('sso_admin');
            $result = $ssoClient->updatePassword($encUserId, $password);
            if ($result['error'] == '0000') {
                return response()->withJson([
                    'success' => true,
                ]);
            } else {
                api_abort($result['error_message'], $result['error'], 400);
            }
        }

        $this->userService->updatePassword($userId, $password);

        //조디악 동기화
        if (config('zodiac')['linkage']) {
            $r = $this->userService->syncUserZodiac($userId);
        }

        //bis 연동
        if (config('bis')['user']) {
            $r = $this->bisService->changePassword($userId, $password);
        }

        //od 동기화
        if (config('od')['linkage']) {
            $folderAuth = new \ProximaCustom\core\FolderAuthManager();
            $folderAuth->changePasswordFromOD($userId, $password);
        }

        return $response->ok();
    }

    
    /**
     * 목록 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function indexFromExternal(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        $input['external_yn']='Y';
        $users = $this->userService->list($input);
        return $response->ok($users);
    }

    public function indexFromSearch(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        $input['limit'] = $input['limit'] ?? 2000;
        $users = $this->userService->list($input);
        $result = [];
        if( !empty($users) ){

            foreach($users as $user )
            {
                $result [] = [
                    'user_nm' => $user['user_nm'],
                    'user_id' => $user['user_id'],
                    'dept_nm' => $user['dept_nm'],
                    'member_id' => $user['member_id'],
                    'external_yn' => $user['external_yn'],
                    'del_yn' => $user['del_yn']
                ];
            }
        }
        return $response->ok($result);
    }

    /**
     * 외부 사용자 추가
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function createFromExternal(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        $auth = auth()->user();
        $createUserId = $auth->member_id;

        $userId = $request->input('user_id');
        $userName = $request->input('user_nm');
        $userOrgId = $request->input('org_id');
        if (empty($userId) || empty($userName) ) {
            api_abort('Invalid input.', 400,400);
        }
        $userId = UserHelper::portalUserId($userId);
        $input['user_id'] = $userId;
        $user = $this->userService->findByUserId($userId);
        if ($user) {
            api_abort('User already exists.', 400,400);
        }
        $user = $this->userService->createFromExternal($input, $createUserId);

        //조디악 동기화
        if (config('zodiac')['linkage']) {
            $this->userService->syncUserZodiac($userId);
        }

        return $response->ok($user, 201);
    }

    /**
     * 외부 사용자 수정
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function updateFromExternal(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        $auth = auth()->user();
        $updateUserId = $auth->member_id;

        $this->userService->validateUserId($args);
        $userName = $request->input('user_nm');
        $userOrgId = $request->input('org_id');
        $externalYn = $request->input('external_yn');

        if ( empty($userName) && empty($userOrgId) && empty($externalYn) ) {
            api_abort('Invalid input.', 400, 400);
        }

        $userId = $args['user_id'];
        $userId = UserHelper::portalUserId($userId);
        $args['user_id'] = $userId;

        $user = $this->userService->findOrFailByUserId($userId);

        $user = $this->userService->updateFromExternal($user, $input, $updateUserId);

        //조디악 동기화
        if (config('zodiac')['linkage']) {
            $this->userService->syncUserZodiac($userId);
        }
        return $response->ok($user, 200);
    }

    /**
     * 외부 사용자 삭제
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function deleteFromExternal(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        $auth = auth()->user();
        $deleteUserId = $auth->member_id;

        $this->userService->validateUserId($args);

        $userId = $args['user_id'];
        $userId = UserHelper::portalUserId($userId);
        $args['user_id'] = $userId;
        $user = $this->userService->findOrFailByUserId($userId);
        $user = $this->userService->deleteFromExternal($user, $deleteUserId);

        //조디악 동기화
        if (config('zodiac')['linkage']) {
            $this->userService->syncUserZodiac($userId);
        }

        return $response->ok($user);
    }

    public function createCertificationNumber(ApiRequest $request, ApiResponse $response, array $args)
    {

        $auth = auth()->user();

        $userId = $request->input('user_id');
        $certificationNumber = $this->userService->createCertificationNumber($userId);
        return $response->ok($certificationNumber);
    }

    public function checkCertificationNumber(ApiRequest $request, ApiResponse $response, array $args)
    {

        $auth = auth()->user();
        $userId = $request->input('user_id');
        $certificationNumber = $request->input('certification_number');        
        $result = $this->userService->checkCertificationNumber($userId, $certificationNumber);
        if(!$result){
            return $response->error('인증 실패', '', 400);
        }
        return $response->ok();
    }    

    public function usersOrAdminCheck(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();

        $owners = json_decode($input['owners']);

        $user = auth()->user();
        $userId = $user->user_id;

        $ownerCheck = false;

        $hasAdmin = $user->hasAdminGroup();
        // 관리자 라면
        if ($hasAdmin) {
            $ownerCheck = true;
        } else {
            foreach ($owners as $owner) {
                if ($owner == $userId) {
                    $ownerCheck = true;
                }

            }
        }
        return $response->ok($ownerCheck);
    }

    /**
     * 핸드폰 번호, 이메일 변경
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function updateUserInfo(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();

        $userId = auth()->user()->user_id;
        $phone = $input['phone'];
        $email = $input['email'];
        
        $user = User::where('user_id',$userId)->first();
        $user->update([
            'phone' => $phone,
            'email' => $email
        ]);

        //조디악 동기화
        if (config('zodiac')['linkage']) {
            $this->userService->syncUserZodiac($userId);
        }

        //bis 연동
        if (config('bis')['user']) {
            $r = $this->bisService->updateUser($user);
        }

        return $response->ok();
    }

    /**
     * 사용자ID신청용 사용자 조회 (USER_ID MASKING처리)
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function getMaskingUserList(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        $userQuery = $input['user_query'] ?? null;
        $userIdQuery = $input['user_id'] ?? null;

        $query = User::query();
        $query->where('del_yn', '!=', 'Y');

        // 검색
        if (!is_null($userQuery)) {
            $query->where('user_nm',$userQuery);
        } else if( !is_null($userIdQuery) ){
            $query->where('user_id', '=',$userIdQuery );
        }

        $query->select('member_id','dept_nm',DB::raw("RPAD(SUBSTR(user_id,1,2),LENGTH(user_id),'*') as user_id"));

        $users = $query->get();
        
        return $response->ok($users);
    }

    /**
     * 사용자 접속이력
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function getUserLoginHistories(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        $query = Log::query();
        $query->leftJoin('bc_member as BM', 'bm.user_id' ,'=', 'bc_log.user_id');
        $query->where('bc_log.action','login');
        $query->whereBetween('bc_log.created_date',[$input['start_date'],$input['end_date']]);
        if($input['is_internal'] === 'external') {
            // 외부 사용자
            $query->leftJoin('dd_code_item as dci', 'dci.code_itm_code','=','bm.org_id');
            $query->where('dci.code_set_id',214);
            $query->where('dci.use_yn','Y');
            $query->select('dci.code_itm_nm as org_nm');
        } else {
            // 내부 사용자
            $query->select('bm.org_id as org_nm');
        }

        if(isset($input['search_value']) && !empty($input['search_value'])) {
            $searchValue = $input['search_value'];
            $query->where(function ($query) use ($searchValue) {
                $query->where('bm.user_id','like',"%{$searchValue}%")
                ->orWhere('bm.user_nm','like',"%{$searchValue}%")
                ->orWhere('bm.dept_nm','like',"%{$searchValue}%");
            });
        }
        $query->select('bc_log.created_date as login_date','bc_log.description as login_ip','bm.user_id','bm.user_nm','bm.dept_nm');
        $query->orderByDESC('bc_log.created_date');
        $loginHistories = paginate($query);
        return $response->ok($loginHistories);
    }
}
