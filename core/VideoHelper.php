<?php

namespace Proxima\core;

class VideoDimension
{
    /**
     * 너비(픽셀)
     *
     * @var integer
     */
    public $width;
    /**
     * 높이(픽셀)
     *
     * @var integer
     */
    public $height;

    /**
     * 생성자
     *
     * @param integer $width
     * @param integer $height
     */
    public function __construct($width = 0, $height = 0)
    {
        $this->width = $width;
        $this->height = $height;
    }
}
class VideoDisplayInfo
{
    /**
     * 스캔타입 (i 또는 p)
     *
     * @var string
     */
    public $scanType;
    /**
     * 가로 해상도
     *
     * @var integer
     */
    public $width;
    /**
     * 세로 해상도
     *
     * @var integer
     */
    public $height;
    /**
     * Pixel aspect ratio(1:1)
     *
     * @var string
     */
    public $PAR;
    /**
     * Display aspect ratio(16:9)
     *
     * @var string
     */
    public $DAR;
}

class VideoHelper
{
    /**
     * 비디오 해상도를
     *
     * @param \Proxima\models\content\SystemMetadata $sysMeta
     * @param integer $destWidth
     * @param integer $destHeight
     * @return \Proxima\core\VideoDimension width, height 속성을 가진 객체
     */
    public static function checkAndFixVideoSize($sysMeta, $destWidth, $destHeight)
    {
        $orgVideoSize = $sysMeta->get('sys_display_size');
        $displayInfo = self::parseDisplayInfo($orgVideoSize);
        $fixedDimension = self::getValidDimension($destWidth, $destHeight, $displayInfo);
        return $fixedDimension;
    }

    public static function parseDisplayInfo($resolutionStr)
    {
        $res = str_replace(']', '', $resolutionStr);

        $resArray = explode(' ', $res);

        $dimensionStr = $resArray[0];
        $dimensionArray = explode('x', $dimensionStr);


        $displayInfo = new VideoDisplayInfo;
        $displayInfo->scanType = $dimensionStr[strlen($dimensionStr) - 1];
        $displayInfo->width = (int)($dimensionArray[0] ?? 0);
        $displayInfo->height = (int)($dimensionArray[1] ?? 0);
        $displayInfo->PAR = $resArray[3];
        $displayInfo->DAR = $resArray[5];
        if (empty($displayInfo->DAR)) {
            $displayInfo->DAR = self::getAspectRatio($displayInfo->width, $displayInfo->height);
        }

        return $displayInfo;
    }

    /**
     * 비율을 고려한 대상 해상도를 구한다.
     *
     * @param integer $destWidth
     * @param integer  $destHeight
     * @param \Proxima\core\DisplayInfo $displayInfo
     * @return \Proxima\core\VideoDimension
     */
    public static function getValidDimension($destWidth, $destHeight, $displayInfo)
    {
        $actualAspectRatio = $displayInfo->width / $displayInfo->height;
        // var_dump($displayInfo);
        // var_dump($actualAspectRatio);
        $destAspectRatio = $destWidth / $destHeight;

        if ($actualAspectRatio === $destAspectRatio) {
            return new VideoDimension($destWidth, $destHeight);
        }

        // 우리는 가로형 옵션을 거의 고정으로 쓴다고 생각하자
        if ($actualAspectRatio > 1) {
            // 가로형
            $destHeight = round($destWidth / $actualAspectRatio);
        } elseif ($actualAspectRatio === 1) {
            // 정방형
            // 정방형은 가로 기준(큰것) 맞춰주자
            $destHeight = $destWidth;
        } else {
            // 세로형
            // 박스 해상도가 HD이하이면 최대 1080을 넘지 않도록 한다.
            $isUnderHD = false;
            if ($destWidth <= 1920 && $destHeight <= 1080) {
                $isUnderHD = true;
            }
            $tmpHeight = $destHeight;
            // 세로형은 가로 세로를 바꿔준다.
            $destHeight = $destWidth;
            $destWidth = $tmpHeight;
            // 가로를 조절하자.
            // if ($isUnderHD && $destHeight > 1080) {
            //     $destHeight = 1080;
            // }
            $destWidth = round($destHeight * $actualAspectRatio);
        }

        if ($destHeight % 2 != 0) {
            $destHeight += 1;
        }

        if ($destWidth % 2 != 0) {
            $destWidth += 1;
        }

        return new VideoDimension($destWidth, $destHeight);
    }

    public static function getCustomAspectRatio($displayInfo)
    {
        $aspectRatio = '';
        if ($displayInfo->width > $displayInfo->height) {
            $aspectRatio = '16:9';
        } elseif ($displayInfo->width < $displayInfo->height) {
            $aspectRatio = '9:16';
        } else {
            $aspectRatio = '1:1';
        }
        return $aspectRatio;
    }

    public static function getAspectRatio($width, $height)
    {
        if (empty($width) && empty($height)) {
            return '';
        }
        $max = max($width, $height);
        $min = min($width, $height);

        $tmp = 0;
        while ($max % $min != 0) {
            $tmp = $max % $min;
            $max = $min;
            $min = $tmp;
        }

        $gcd = $min;

        $war = $width / $gcd;
        $har = $height / $gcd;
        return $war . ':' . $har;
    }
}
