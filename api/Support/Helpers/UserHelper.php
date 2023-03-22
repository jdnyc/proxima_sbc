<?php

namespace Api\Support\Helpers;

use Api\Types\UserIdPrefix;

class UserHelper
{
    public static function portalUserId($userId)
    {
        return $userId;
        //return UserIdPrefix::PORTAL . $userId;
    }
}
