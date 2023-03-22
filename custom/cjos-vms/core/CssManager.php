<?php

namespace ProximaCustom\core;

require_once($_SERVER['DOCUMENT_ROOT'] . '/custom/cjos/lib/config.php');

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
            background-image: url('" . CUSTOM_ROOT_WEBPATH . "/images/vms_logo.png');
            margin: 0 auto;
            height: 170px;            
            width: 350px;
            background-repeat: no-repeat;            
        }
        </style>";

        return $style;
    }

    public static function getMainLogoPath()
    {
        return CUSTOM_ROOT_WEBPATH . '/images/vms_logo.png';
    }

    public static function getFaviconPath()
    {
        return CUSTOM_ROOT_WEBPATH . '/images/favicon_32x32.ico';
    }
}
