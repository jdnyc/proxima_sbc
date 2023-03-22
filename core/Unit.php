<?php

namespace Proxima\core;

class Unit
{
    /**
     * ByteCount를 디스플레이용(KB, MB, GB와 같은...) 문자열로 변환
     *
     * @param integer $b 바이트 카운트
     * @param integer $p 표시 단위 인덱스(0~8)
     * @return void
     */
    public static function formatBytes($b, $p=null) {
        $units = array("B","KB","MB","GB","TB","PB","EB","ZB","YB");
        $c=0;
        if(!$p && $p !== 0) {
            foreach($units as $k => $u) {
                if(($b / pow(1024,$k)) >= 1) {
                    $r["bytes"] = $b / pow(1024,$k);
                    $r["units"] = $u;
                    $c++;
                }
            }
            return number_format($r["bytes"],2) . " " . $r["units"];
        } else {
            return number_format($b / pow(1024,$p)) . " " . $units[$p];
        }
    }

    /**
     * 자소분리 문자열 처리 1차배열까지만 처리됨
     *
     * @param [type] $value
     * @return void
     */
    public static function normalizeUtf8String( $value ){
        if( !function_exists('normalizer_normalize') ) return $value;
       
        if( is_array($value) ){
            foreach( $value as $key => $val ){
                if (!normalizer_is_normalized($val)) {
                    $value[$key] = normalizer_normalize($val);
                }
            }
        }else{
            if (!normalizer_is_normalized($value)) {
                $value = normalizer_normalize($value);
            }
        }
    
        return $value;
    }
}