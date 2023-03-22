<?php

namespace ProximaCustom\core;

class ModelUrlCustom
{
    public static function modifyUrl($model)
    {   
        $url = $model->get('url');
        if(empty($url)) {
            return;
        }
        $cdnUrl = CdnUrl::getUrl();
        $model->set('url', $cdnUrl . $url);
    }
}