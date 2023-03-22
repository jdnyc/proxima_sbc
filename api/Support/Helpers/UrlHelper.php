<?php

namespace Api\Support\Helpers;

class UrlHelper
{
    /**
     * URL을 base_url과 pah로 분리한다.
     *
     * @param string $url 전체 url
     * @return array base_url, path, query, info 배열
     */
    public static function parse($url)
    {
        if(empty($url)) {
            return [];
        }

        $urlInfo = parse_url($url);
        $baseUrl = $urlInfo['scheme'] .'://'.$urlInfo['host'];
        $port = $urlInfo['port'] ?? null;
        if( $port ){
            $baseUrl = $baseUrl .':'.$port;
        }
        $path = $urlInfo['path'] ?? '';
        $query = $urlInfo['query'] ?? '';
        return [
            'base_url' => $baseUrl,
            'path' => $path,
            'query' => $query,
            'info' => $urlInfo
        ];
    }

    /**
     * URL 생성
     *
     * @param string $baseUrl 기본 url(예: http://10.10.10.10:8000)
     * @param string $path
     * @param string $query
     * @return string
     */
    public static function build($baseUrl, $path, $query = '')
    {
        $url = trim($path, '/');
        if(!empty($baseUrl)) {
            $url = $baseUrl . '/' . trim($path, '/');
        }

        if(!empty($query)) {
            $url .= '?' . $query;
        }
        
        return $url;
    }
}