<?php

/**
 * 문자열이 json인지 확인하는 함수
 *
 * @param string $str
 * @return boolean
 */
if (!function_exists('validate_json')) {
    function validate_json($str=null)
    {
        if (is_string($str)) {
            @json_decode($str);
            return (json_last_error() === JSON_ERROR_NONE);
        }
        return false;
    }
}

/**
 * 날짜 유형 파라메터에서 특수문자 제거(yyyy-mm-dd > yyyymmdd)
 *
 * @param string $date
 * @param array $stripChars
 * @return string
 */
if (!function_exists('strip_date')) {
    function strip_date($date, $stripChars = ['-'])
    {
        $striped = $date;
        foreach ($stripChars as $stripChar) {
            $striped = str_replace($stripChar, '', $striped);
        }
        return $striped;
    }
}
