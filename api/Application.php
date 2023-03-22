<?php

namespace Api;

class Application
{
    private static $app;

    public static function create($settings)
    {
        if (!is_null(self::$app)) {
            return self::$app;
        }
        self::$app = new \Slim\App($settings);
        return self::$app;
    }

    public static function getApp()
    {
        return self::$app;
    }

    public static function request()
    {
        $app = self::getApp();
        if (is_null(self::$app)) {
            return null;
        }
        return $app->getContainer()->get('request');
    }

    public static function response()
    {
        $app = self::getApp();
        if (is_null(self::$app)) {
            return null;
        }
        return $app->getContainer()->get('response');
    }

    public static function container()
    {
        if (is_null(self::$app)) {
            return null;
        }
        return self::$app->getContainer();
    }

    public static function run()
    {
        if (is_null(self::$app)) {
            return null;
        }
        self::$app->run();
    }

    /**
     * 클라이언트가 접속한 지점이 public인지 여부 확인
     *
     * @return bool
     */
    public static function isPublicZone()
    {
        $httpHost = $_SERVER['HTTP_HOST'];
        $isPublic = \Illuminate\Support\Str::contains($httpHost, config('app.domain'));
        return $isPublic;
    }
}
