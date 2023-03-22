<?php

namespace ProximaCustom\core;

use Proxima\core\WebPath;
use ProximaCustom\core\CdnUrl;

class GetMediaListCustom
{
    public static function replacePath($media, $videoInfo)
    {
        if (!empty($media['url']) && $media['media_type'] === 'thumb') {
            $cdnUrl = CdnUrl::getUrl();
            $media['path'] = $cdnUrl . $media['url'];
        } elseif (!empty($videoInfo) && !empty($media['url'])) {
            if ($media['media_type'] === 'proxy') {
                $path = $videoInfo['result']['videoUrlLow'];
            } else {
                $path = $videoInfo['result']['videoUrlHigh'];
            }
            if (!empty($path)) {
                $media['path'] = $path;
            }
        }

        if (strpos($media['path'], 'http') === false) {
            $media['path'] = WebPath::makeUrl(WebPath::makeProxyPath($media['path']), true);
        }
        return $media;
    }
}
