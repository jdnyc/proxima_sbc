<?php

namespace ProximaCustom\core;

use ProximaCustom\core\CdnUrl;

class GetContentListCustom
{
    public static function replaceThumbPath($media, $metadata)
    {   
        if(!empty($media['url'])) {
            $cdnUrl = CdnUrl::getUrl();
            $metadata['thumb'] = $cdnUrl . str_replace('#', '%23', $media['url']);
        } else {
            $metadata['thumb'] = LOCAL_LOWRES_ROOT . '/' . $metadata['thumb'];
        }
        return $metadata;
    }
}