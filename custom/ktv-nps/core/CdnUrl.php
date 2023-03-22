<?php

namespace ProximaCustom\core;

use Dotenv\Dotenv;

class CdnUrl
{
    protected static $cdnUrl = '';
    public static function getUrl()
    {   
        if(empty(self::$cdnUrl)) {
            $dotenv = Dotenv::create(dirname(__DIR__), '.env');
            $dotenv->load();
            self::$cdnUrl = getenv('IMG_CDS_URL');
        }
        return self::$cdnUrl;
    }
}