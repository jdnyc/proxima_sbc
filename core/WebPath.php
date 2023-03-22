<?php

namespace Proxima\core;

class WebPath
{
    /**
     * Url 생성
     *
     * @param string $path
     * @return void
     */
    public static function makeProxyPath($path)
    {
        $alias = LOCAL_LOWRES_ROOT;
        return $alias . '/' . $path;
    }

    public static function getDefaultProtocol()
    {
        return 'http:';
    }

    public static function makeUrl($webPath, $ignorePort = false)
    {
        $rootUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
        if ($ignorePort) {
            return $rootUrl . $webPath;
        }

        $serverPort = '';
        if ($_SERVER['SERVER_PORT'] != '80') {
            $serverPort = ':' . $_SERVER['SERVER_PORT'];
        }

        return $rootUrl . $serverPort . $webPath;
    }

    public static function removeCdnRootPath($fullUrl)
    {
        $startIndex = strpos($fullUrl, '/public/confirm');
        $subUrl = substr($fullUrl, $startIndex);
        return $subUrl;
    }

    public static function dividePath($path)
    {
        if (empty($path)) {
            return [
                'path' => '',
                'name' => ''
            ];
        }
        $paths = explode('/', $path);
        $name = array_pop($paths);
        $path = implode('/', $paths);

        return [
            'path' => "{$path}/",
            'name' => $name
        ];
    }
}
