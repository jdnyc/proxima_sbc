<?php

namespace ProximaCustom\core;

require_once(dirname(__DIR__) . '/lib/config.php');

/**
 * 커스텀 css 로드를 위한 클래스
 */
class CssManager
{
    public static $Styles = [
        'custom_style'
    ];

    /**
     * 스타일 시트 로드 구문을 반환한다.(html)
     * main.php에서 사용
     *
     * @return array 스타일 로드 구문(html)
     */
    public static function getCustomStyles(): array
    {
        $styles = [];
        foreach (self::$Styles as $style) {
            $styles[] = '<link rel="stylesheet" type="text/css" href="' .
                CUSTOM_ROOT_WEBPATH . '/css/' . $style . '.css" />' . "\n";
        }
        return $styles;
    }

    public static function getLoginLogoStyle()
    {
        $style = "
        <style type=\"text/css\">
        .proxima_logo_image_login_form{
            background-image: url('" . CUSTOM_ROOT_WEBPATH . "/images/logo_main.png');
            background-size: 400px;
            margin: 0 auto;
            height: 153px;            
            width: 400px;
            background-repeat: no-repeat;            
        }
        </style>";

        return $style;
    }

    public static function getMainLogoPath($topMenuMode)
    {
        if(strtoupper($topMenuMode) === 'S') {
            return CUSTOM_ROOT_WEBPATH . '/images/logo_small.png?_ver=20191216001';
        }
        return CUSTOM_ROOT_WEBPATH . '/images/logo_large.png?_ver=20191216001';
    }

    public static function getFaviconPath()
    {
        return CUSTOM_ROOT_WEBPATH . '/images/favicon_32x32.ico';
    }

    public static function getMainLogoStyle($topMenuMode)
    {
        if(strtoupper($topMenuMode) === 'S') {
            return "style=\"margin-left: 15px; width: 208px;margin-top: 8px;\"";
        } else {
            return "style=\"margin-left: 15px; width: 165px;margin-top: 15px;\"";
        }
    }

    public static function getTopMenuStyle($topMenuMode)
    {
        if(strtoupper($topMenuMode) === 'S') {
            return "style=\"margin-left: 250px;\"";
        }
    }
}
