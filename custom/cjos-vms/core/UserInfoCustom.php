<?php

namespace ProximaCustom\core;

/**
 * 사용자 정보 관련 커스터마이징 클래스
 */
class UserInfoCustom
{
    public static function PasswordFieldVisible()
    {
        return false;
    }

    public static function PhoneFieldVisible()
    {
        return false;
    }

    public static function EmailFieldVisible()
    {
        return false;
    }

    public static function LanguageFieldVisible()
    {
        return false;
    }
}
