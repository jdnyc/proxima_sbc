<?php

namespace Api\Auth;

use Api\Models\User;
use Proxima\core\Session;

class Auth
{
    /**
     * 사용자 객체
     *
     * @var \Api\Models\User
     */
    protected $user;

    public function __construct()
    {
        Session::init();
    }

    public function check()
    {
        if(Session::isEmpty()) {
            return false;
        }
        return Session::exists(config('app_auth_id'));
    }

    public function user()
    {        
        if(Session::isEmpty()) {
            return null;
        }
        if(!$this->user) {
            $userId = Session::get(config('app_auth_id'));
            $this->user = User::where('user_id', $userId)->first();
        }
        return $this->user;
    }

    public function setUser($userId)
    {
        Session::set(config('app_auth_id'), $userId);
    }
}
