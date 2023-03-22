<?php

namespace Proxima\core;

class CustomHelper
{
    public static function customClassExists($className)
    {
        return (defined('CUSTOM_ROOT') && class_exists($className));
    }

    public static function customMethodExists($className, $methodName)
    {
        if(defined('CUSTOM_ROOT') && class_exists($className) && 
            method_exists($className, $methodName)) {
				
			return true;

        }
        return false;
    }
}
