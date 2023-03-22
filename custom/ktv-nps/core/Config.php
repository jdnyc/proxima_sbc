<?php

namespace ProximaCustom\core;

class Config
{
    public static function load()
    {
        require_once(dirname(__DIR__).'/lib/config.php');
    }
}
