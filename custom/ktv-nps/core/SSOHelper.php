<?php

namespace ProximaCustom\core;

use Proxima\core\Request;
use Proxima\models\user\User;

class SSOHelper
{
    public static function isLoggedIn()
    {
        $headers = Request::getHeaders();
        return !empty($headers['X-AUTH-USER-ID']);
    }

    public static function createSession()
    {
        $headers = Request::getHeaders();
        $userId = $headers['X-AUTH-USER-ID'];
        $empId = $headers['X-AUTH-EMPLOYEE-ID'];

        $user = User::find($userId, true);
        if (is_null($user)) {
            // 사용자가 등록되지 않았을 때
            echo "<script>alert('등록되지 않은 사용자 입니다. 관리자에 문의하세요.'); window.location = '/_logout';</script>";
            die();
        }

        $groups = getGroups($userId);
        $super_admin = $userId === 'ptn_hkkim' ? 'Y' : 'N';
        $check_session = $userId . date("YmdHis");
        $session_time_limit = $arr_sys_code['session_time_limit']['ref1'];
        $prevent_duplicate_login = $arr_sys_code['duplicate_login']['use_yn'];

        $_SESSION['user'] = array(
            'user_id' => trim($user->get('user_id')),
            'emp_id' => $empId,
            'is_admin' => trim($user->get('is_admin')),
            'KOR_NM' => $user->get('user_nm'),
            'user_email' => $user->get('email'),
            'phone' =>  $user->get('phone'),
            'groups' => $groups,
            'lang' => $user->get('lang'),
            'super_admin' => $super_admin,
            'user_pass' => '_',
            'allow_hiddenSearch' => 'N',
            'check_session' => $check_session,
            'session_expire' => time() + ((int)$session_time_limit * 60),
            'prevent_duplicate_login' => $prevent_duplicate_login
        );
    }
}
