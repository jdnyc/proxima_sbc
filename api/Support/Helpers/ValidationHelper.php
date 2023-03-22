<?php

namespace Api\Support\Helpers;

class ValidationHelper
{
    public static function emptyValidate($values, $keys)
    {
        foreach($keys as $key) {
            if(!isset($values[$key]) || $values[$key] == null) {
                api_abort("`{$key}` field should not empty.");
            }
        }
    }
}
