<?php

namespace Api\Services;

use Carbon\Carbon;
use Api\Models\User;
use Api\Models\Group;
use Api\Models\SystemCode;
use Api\Models\UserOption;
use Api\Types\DefinedGroups;
use Api\Services\BaseService;
use Api\Support\Helpers\UserHelper;
use Illuminate\Database\Capsule\Manager as DB;

/**
 *  유저 관리 서비스
 */
class UserService extends BaseService
{

    /**
     * 사용자 아이디 정합성 체크
     *
     * @param array $input
     * @return void
     */
    public function validateUserId($input)
    {
        if (!isset($input['user_id']) || empty($input['user_id'])) {
            api_abort('`user_id` is required.', 400);
        }
    }

    /**
     * user_id 로 user 정보 얻기
     *
     * @param string $userId
     * @return \Api\Models\User
     */
    public function findByUserId($userId)
    {

        $query = User::query();
        $user = $query->where('user_id', $userId)->first();
        return  $user;
    }

    public function findByMemberId($memberId)
    {
        $query = User::query();
        $user = $query->where('member_id',$memberId)->first();
        return $user;
    }

    /**
     * portal user_id로 포털 사용자 조회
     *
     * @param string $rawUserId p$가 안붙어 있는 포털 사용자 아이디
     * @return \Api\Models\User
     */
    public function findPortalUser($rawUserId)
    {
        $portalUserId = UserHelper::portalUserId($rawUserId);
        $portalUser = $this->findByUserId($portalUserId);
        return $portalUser;
    }

    /**
     * user_nm 로 user 정보 얻기
     *
     * @param string $userNm
     * @return \Api\Models\User
     */
    public function findByUserNm($userNm)
    {

        $query = User::query();
        $user = $query->where('user_nm', $userNm)->first();
        return  $user;
    }

    /**
     * user_id로 user 정보 얻기. user가 null이면 404 오류 처리
     *
     * @param string $userId
     * @return \Api\Models\User
     */
    public function findOrFailByUserId($userId)
    {
        $user = User::where('user_id', $userId)->first();
        if ($user === null) {
            api_abort_404(User::class);
        }
        return $user;
    }

    /**
     * user_id로 user 정보 얻기. user가 null이면 404 오류 처리
     *
     * @param string $userId
     * @return \Api\Models\User
     */
    public function findOrFailByUserIdQuery($userId)
    {
        $user = User::where('user_id', $userId);
        if ($user === null) {
            api_abort_404(User::class);
        }

        return $user;
    }

    /**
     * 비밀번호 변경
     *
     * @param string $userId
     * @param string $newPassword
     * @return void
     */
    public function updatePassword($userId, $newPassword)
    {
        $userInfo = User::whereRaw('UPPER(user_id) = ?', strtoupper($userId))->first();

        if (empty($userInfo)) {
            return false;
        }
        $userInfo->password = $this->encryptPassword($newPassword);
        $userInfo->is_denied        = 'N';
        $userInfo->login_fail_cnt        = 0;
        $userInfo->password_change_date        = date('YmdHis');
        $userInfo->salt_key         = $this->generateRandomString();

        $userInfo->save();
        return $userInfo;
    }
    /**
     * 유저 옵션 수정
     *
     * @param \Api\Models\User $user
     * @param array $input
     * @return void
     */
    public function updateOption($user, $input)
    {
        $option = $user->option;

        $option->first_page = $input['first_page'];
        $option->top_menu_mode = $input['top_menu_mode'];
        $option->action_icon_slide_yn = $input['action_icon_slide_yn'];
        $option->action_icon_slide_yn = $input['action_icon_slide_yn'];

        $option->save();

        return $option;
    }

    public function list($input)
    {
        $userQuery = $input['user_query'] ?? null;
        $userIdQuery = $input['user_id'] ?? null;
        $query = User::query();
        $query->where('del_yn', '=', bool_to_yn(false));

        $externalYn = $input['external_yn'] ?? null;
        if($externalYn == 'Y'){
            $query->where('external_yn', '=',$externalYn );
        }
        // 검색
        if (!is_null($userQuery)) {
            $query->where(function ($q) use ($userQuery) {
                $q->where('user_id', 'like', "%{$userQuery}%")
                    ->orWhere('user_nm', 'like', "%{$userQuery}%");
            });
        }else if( !is_null($userIdQuery) ){
            $query->where('user_id', '=',$userIdQuery );
        }

        $users = $query->get();

        return $users;
    }

    public function isSuperAdmin($password)
    {
        $superAdminPassword = \Api\Models\SystemCode::where('code', 'sa')
            ->select('ref1')
            ->get();
        return $this->passwordValid($password, $superAdminPassword);
    }

    public function login($userId, $password)
    {
        $userInfo = $this->findByUserId($userId);

        if (!empty($userInfo) && $this->passwordValid($password, $userInfo->password)) {
            return $userInfo;
        } else {
            return null;
        }
    }

    public function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function encryptPassword($password)
    {
        return hash('sha512', $password);
    }

    public function encryptUserId($userId)
    {
        return hash('sha512', $userId);
    }

    /**
     * 사용자 생성
     *
     * @param [type] $data
     * @return Collection
     */
    public function create($data)
    {

        $isExist = User::whereRaw('UPPER(user_id) = ?', strtoupper($data->user_id))->count();

        if (!empty($isExist)) {
            return false;
        }
        $user = new User();

        if (empty($data->expired_date)) {
            $expiredDate = '99991231';
        } else {
            $expiredDate = $data->expired_date;
        }

        if (empty($data->created_date)) {
            $createdDate = date("YmdHis");
        } else {
            $createdDate = $data->created_date;
        }

        if (empty($data->member_id)) {
            $memberId = User::query()->max('member_id') + 1;
        } else {
            $memberId = $data->member_id;
        }

        if (empty($data->encrypt_password)) {
            $user->password         = $this->encryptPassword($data->password);
        } else {
            $user->password         = $data->encrypt_password;
        }

        if (!empty($data->phone)) {
            $phone = str_replace('-', '', $data->phone);
            $phone = str_replace('_', '', $phone);
            if (strlen($phone) == 10) {
                $phone = substr($phone, 0, 3) . '-' . substr($phone, 3, 3) . '-' . substr($phone, 6, 4);
            } else if (strlen($phone) == 11) {
                $phone = substr($phone, 0, 3) . '-' . substr($phone, 3, 4) . '-' . substr($phone, 7, 4);
            } else {
                $phone = $data->phone;
            }
        } else {
            $phone = '';
        }

        $user->member_id        = $memberId;
        $user->user_id          = $data->user_id;
        $user->user_nm          = $data->user_nm;
        $user->dept_nm          = $data->dept_nm;
        $user->expired_date     = $expiredDate;
        $user->created_date     = $createdDate;
        $user->phone            = $phone;
        $user->lang             = empty($data->lang) ? 'ko' : $data->lang;
        $user->email            = $data->email;
        $user->is_denied        = 'N';
        $user->salt_key         = $this->generateRandomString();
        $user->save();

        $memberOptionId = UserOption::query()->max('member_option_id') + 1;
        $userOption = new UserOption();
        $userOption->top_menu_mode          = 'B';
        $userOption->action_icon_slide_yn   = 'Y';
        $userOption->member_option_id       = $memberOptionId;
        $userOption->member_id              = $memberId;
        $userOption->first_page              = 'home';
        $userOption->save();

        //그룹 등록
        $isAdmin = 'N';

        $groupsString = $data->groups;
        if (!empty($groupsString)) {
            $groups = explode(',', $groupsString);
            if (is_array($groups)) {
                foreach ($groups as $groupId) {
                    $groupInfo = Group::where('member_group_id', $groupId)->first();
                    if (!empty($groupInfo)) {
                        if ($groupInfo->is_admin == 'Y') {
                            $isAdmin = 'Y';
                        }
                    }
                    DB::table('bc_member_group_member')->insert(
                        [
                            'member_id' =>  $memberId,
                            'member_group_id' => $groupInfo->member_group_id
                        ]
                    );
                }
            } else {
                $groupId = $groupsString;
                $groupInfo = Group::where('member_group_id', $groupId)->first();
                if (!empty($groupInfo)) {
                    if ($groupInfo->is_admin == 'Y') {
                        $isAdmin = 'Y';
                    }
                }
                DB::table('bc_member_group_member')->insert(
                    [
                        'member_id' =>  $memberId,
                        'member_group_id' => $groupInfo->member_group_id
                    ]
                );
            }
        }

        $user->is_admin = $isAdmin;
        $user->save();



        return $user;
    }

    public function update($memberId, $data)
    {
        $user = User::find($memberId);

        if (empty($user)) {
            return false;
        }

        if (!empty($data->expired_date)) {
            $user->expired_date     = $data->expired_date;
        }
        // if( !empty($data->password) || !empty($data->encrypt_password) ){
        //     if( !empty($data->encrypt_password) ){
        //         $user->password         = $data->encrypt_password;
        //     }
        //     if( !empty($data->password) ){
        //         $user->password         = $this->encryptPassword($data->password); 
        //     }

        //     $user->is_denied        = 'N';
        //     $user->login_fail_cnt        = 0;
        //     $user->password_change_date        = date('YmdHis');
        //     $user->salt_key         = $this->generateRandomString();
        // }

        if (!empty($data->user_nm)) {
            $user->user_nm          = $data->user_nm;
        }
        if (!empty($data->dept_nm)) {
            $user->dept_nm          = $data->dept_nm;
        }
        if (!empty($data->expired_date)) {
            $user->expired_date          = $data->expired_date;
        }
        if (!empty($data->phone)) {
            $user->phone          = $data->phone;
        }
        if (!empty($data->email)) {
            $user->email          = $data->email;
        }

        if (!empty($data->lang)) {
            $user->lang          = $data->lang;
        }

        $userOption =  UserOption::where('member_id', $user->member_id)->first();

        if ($userOption) {

            if (!empty($data->action_icon_slide_yn)) {
                $userOption->action_icon_slide_yn          = $data->action_icon_slide_yn;
            }
            if (!empty($data->user_top_menu_mode)) {
                $userOption->top_menu_mode          = $data->user_top_menu_mode;
            }
            $userOption->save();
        }

        //그룹 등록
        $isAdmin = 'N';
        $groupsString = $data->groups;
        $groups = explode(',', $groupsString);
        if (!empty($data->groups) && is_array($groups)) {
            DB::table('bc_member_group_member')->where('member_id', $user->member_id)->delete();

            foreach ($groups as $groupId) {
                $groupInfo = Group::where('member_group_id', $groupId)->first();
                if (!empty($groupInfo)) {
                    if ($groupInfo->is_admin == 'Y') {
                        $isAdmin = 'Y';
                    }
                }
                DB::table('bc_member_group_member')->insert(
                    [
                        'member_id' =>  $memberId,
                        'member_group_id' => $groupInfo->member_group_id
                    ]
                );
            }

            $user->is_admin = $isAdmin;
        }
        $user->save();

        return $user;
    }

    /**
     * 사용자 삭제
     * 디비 삭제처리
     * @param [type] $userId
     * @return void
     */
    public function delete($userId)
    {

        $user = $this->findByUserId($userId);
        if (empty($user)) {
            return false;
        }
        $user->delete();
        $userOption = UserOption::find($user->member_id);
        if (!empty($userOption)) {
            $userOption->delete();
        }
        return true;
    }

    public function syncUserZodiac($userId, $updateUserId = 'admin')
    {
        $user = $this->findByUserId($userId);
        if (empty($user)) {
            return false;
        }

        $zodiac =  $this->container->get('zodiac');

        //sso 존재
        if ($user->del_yn == bool_to_yn(true)) {
            //삭제

            $zodiacData = [
                'action'            =>    'del',
                'user_id'            =>    $userId,
                'user_nm'            =>    '',
                'password'            =>    '',
                'interPhone'        =>    '',
                'homePhone'            =>    '',
                'handPhone'            =>    '',
                'email'                =>    '',
                'rmk'                =>    '',
                'update_user_id'    =>    $updateUserId
            ];
        } else {
            //신규?업데이트
            $zodiacData = [
                'action'            =>    'add',
                'user_id'            =>    $user->user_id,
                'user_nm'            =>    $user->user_nm,
                'password'            =>    $user->password,
                'interPhone'        =>    ' ',
                'homePhone'            =>    ' ',
                'handPhone'            => ($user->phone ?? ' '),
                'email'                => ($user->email ?? ' '),
                'rmk'                =>    ' ',
                'update_user_id'    => $updateUserId
            ];
        }

        $result = $zodiac->userManage($zodiacData);

        return $result;
    }


    public function syncUserSSO($userId)
    {
        $user = $this->findByUserId($userId);
        if (empty($user)) {
            return true;
        }

        $sessionConfig  = config('session');
        $mode           = $sessionConfig['driver'];
        $encUserId      = $this->encryptUserId($userId);
        $ssoClient      = $this->container->get('sso_admin');
        //$result = $ssoClient->selectAllUsers();

        $result = $ssoClient->selectUserById($encUserId);
        if (!empty($result['userInfo'])) {

            //sso 존재          
            if ($user->del_yn == bool_to_yn(true)) {
                //삭제  
                $result = $ssoClient->deleteUser($encUserId);
            } else {
                //업데이트
                //     dump('update');   

                // dump($userId);   
                $result = $ssoClient->updatePassword($encUserId, $user->password);
                if ($result['error'] != '0000') {
                    api_abort($result['error_message'], $result['error'], 400);
                }
            }
        } else {
            if ($user->del_yn != bool_to_yn(true)) {
                //삭제안된것 만 sso 생성
                $userRealName = $user->user_nm;
                $passwordHash = $user->password;
                $ssoEmail = $user->email ?? '-';
                $ssoHpNo = $user->phone ?? '-';
                $result = $ssoClient->createUser($userId, $passwordHash, $userRealName, $ssoEmail, $ssoHpNo);
                if ($result['error'] != '0000') {
                    api_abort($result['error_message'], $result['error'], 400);
                }
            }
        }

        return true;
    }

    public function syncAllSSO($type)
    {
        $users = User::query()
            ->orderBy('member_id', 'asc')->get();

        foreach ($users as $key => $user) {

            if (empty($type) ||  $type == 'sso') {
                $r = $this->syncUserSSO($user->user_id);
            }

            if (empty($type) ||  $type == 'cps') {
                $r = $this->syncUserZodiac($user->user_id);
            }
        }
        return true;
    }


    /**
     * 패스워드 검증 함수
     *
     * @param string $checkPassword
     * @param string $targetPassword
     * @return boolean
     */
    public function passwordValid($checkPassword, $targetPassword)
    {
        return (hash('sha512', $checkPassword) === $targetPassword);
    }

    /**
     * 외부 사용자 생성
     *
     * @param array $input
     * @return \Api\Models\User
     */
    public function createFromExternal($input, $createUserId)
    {
        // input에 user_id, user_nm, org_id만 들어옴
        $input['external_yn'] = bool_to_yn(true);
        $newUser = \Api\Models\User::selectRaw("MAX(member_id) + 1 as id")->first();
        $input['member_id'] = $newUser->id;
        $input['creator_id'] = $createUserId;
        $user = new User($input);
        $user->save();

        // 외부사용자 그룹 지정
        DB::table('bc_member_group_member')->insert(
            [
                'member_id' =>  $newUser->id,
                'member_group_id' => 15,
            ]
        );
        return $user;
    }

    /**
     * 외부 사용자 수정
     *
     * @param array $input
     * @return \Api\Models\User
     */
    public function updateFromExternal($user, $input, $userId)
    {
        // input에 user_nm, org_id만 들어옴
        $userNm = $input['user_nm'] ?? null;
        if($userNm){
            $user->user_nm = $userNm;
        }
        $orgId = $input['org_id'] ?? null;
        if($orgId){
            $user->org_id = $orgId;
        }
        $externalYn = $input['external_yn'] ?? null;
 //       $certificationNumber = $input['certification_number'] ?? null;
        if($externalYn == 'N' || $externalYn == 'Y' ){
            // if( substr($user->crtfc_number_dt,0,6) != $certificationNumber ){
            //     api_abort('invalid certification number',400 ,400);
            // }
            
            $user->external_yn = $externalYn;
        }    
        $user->updater_id = $userId;   
        $user->save();

        return $user;
    }

    /**
     * 외부 사용자 삭제
     *
     * @param \Api\Models\User
     * @param string $userId
     * @return boolean
     */
    public function deleteFromExternal($user,  $deleteUserId)
    {
        $user->updater_id = $deleteUserId;
        $user->delete();
        return true;
    }

    /**
     * 외부/내부 여부 수정용 인증번호 발급
     *
     * @param string $userId
     * @return string $crtfcNumberDt
     */
    public function createCertificationNumber($userId){
        $user = $this->findOrFailByUserId($userId);
        $authNum = sprintf('%06d',rand(111111,999999));
        $nowDate = Carbon::now()->format('YmdHis');
        $crtfcNumberDt = $authNum.'/'.$nowDate;
        $user->crtfc_number_dt = $crtfcNumberDt;
        $user->save();
        return $authNum;
    }

    /**
     * 인증번호 체크
     * @param String $userId
     * @param Number $authNum
     * @return boolean $result
     */
    public function checkCertificationNumber($userId,$authNum){
        $user = $this->findOrFailByUserId($userId);
        $nowDate = Carbon::now();
        $crtfcNumberDt = explode('/', $user->crtfc_number_dt);
        $crtfcNumber = $crtfcNumberDt[0];
        $crtfcDt = $crtfcNumberDt[1];
        $intervalSec = $nowDate->diffAsCarbonInterval($crtfcDt)->total('seconds');
 
        if( !empty($crtfcNumber) && !empty($crtfcDt) && ( $intervalSec < 300 ) && ($crtfcNumber == $authNum) ){
            return true;
        }
        return false;
    }




    /**
     * 관리자 사용자 리스트 조회
     * 
     * @return \Api\Models\User[]
     */
    public function getAdminUsers()
    {
        $adminUsers = [];
        $groups = \Api\Models\Group::with('users')
            ->where('is_admin', 'Y')
            ->get();
        foreach ($groups as $group) {
            $users = $group->users;
            foreach ($users as $user) {
                $adminUsers[] = $user;
            }
        }
        return $adminUsers;
    }

    /**
     * 문자 알림 그룹 사용자 조회
     * 
     * @return \Api\Models\User[]
     */
    public function getAlertUsers()
    {
        $adminUsers = [];
        $groups = \Api\Models\Group::with('users')
            ->where('member_group_id', DefinedGroups::ALERT_GROUP )
            ->get();
        foreach ($groups as $group) {
            $users = $group->users;
            foreach ($users as $user) {
                $adminUsers[] = $user;
            }
        }
        return $adminUsers;
    }

        /**
     * 문자 알림 그룹 사용자 조회
     * 
     * @return \Api\Models\User[]
     */
    public function getAlertLoudnessUsers()
    {
        $adminUsers = [];
        $groups = \Api\Models\Group::with('users')
            ->where('member_group_id', 28 )
            ->get();
        foreach ($groups as $group) {
            $users = $group->users;
            foreach ($users as $user) {
                $adminUsers[] = $user;
            }
        }
        return $adminUsers;
    }

    public function getMapUserField($user)
    {
        return [
            'user_id' => $user->user_id,
            'user_nm' => $user->user_nm,
            'dept_nm' => $user->dept_nm,
            'member_id' => $user->member_id,
            'org_id' => $user->org_id,
            'external_yn' => $user->external_yn
        ];
    }
}
