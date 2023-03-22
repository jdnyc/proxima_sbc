<?php

/**
 * Session class
 */

namespace Proxima\core;

class Session
{
    public static function init()
    {
        if (empty($_SERVER['DOCUMENT_ROOT'])) {
            return;
        }

        if (session_id() == '') {            
            session_start();
        }
    }

    public static function checkUserAuth()
    {
        $user = self::get('user');
        if (empty($user) || empty($user['user_id']) || $user['user_id'] == 'temp') {
            echo '<script type="text/javascript">
                alert("권한이 없습니다.");
                window.location = "/";
            </script>';
            die();
        }
    }

    public static function isEmpty()
    {
        return empty($_SESSION);
    }

    public static function get($key)
    {
        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }
    }

    public static function getUser($key){
        if ( isset($_SESSION['user'])  && isset($_SESSION['user'][$key]) ) {
            return $_SESSION['user'][$key];
        }
    }

    public static function exists($key)
    {
        $value = self::get($key);
        return !is_null($value);
    }

    public static function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public static function destroy()
    {
        session_destroy();
    }

}
