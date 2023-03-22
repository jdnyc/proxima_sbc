<?php

namespace ProximaCustom\core;

use ProximaCustom\core\SSOHelper;

class PageHandler
{
    public static function handleBeginIndex()
    {
        // 로컬은 통합로그인 하지 않는다.
        if($_SERVER['REMOTE_ADDR'] === '::1') {
            return;
        }
        if(!SSOHelper::isLoggedIn()) {
            echo '통합로그인 실패';
            die();
        }
        SSOHelper::createSession();
        header('Location: /main.php');
        die();
    }
}