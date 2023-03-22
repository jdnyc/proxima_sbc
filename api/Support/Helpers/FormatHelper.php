<?php

namespace Api\Support\Helpers;

/**
 * 포맷팅을 도와주는 클래스
 */
class FormatHelper
{
    /**
     * 배열 값중에 Carbon형 값이 있으면 특정 포맷의 문자열로 변환해준다.
     *
     * @param array $data
     * @param string $format
     * @return array
     */
    public static function fixDateTimeFormat($data, $format = \DateTime::ISO8601)
    {
        if (!is_array($data)) {
            return $data;
        }
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::fixDateTimeFormat($value);
                continue;
            }
            if ($value instanceof \Carbon\Carbon) {
                $data[$key] = $value->format($format);
            }
        }
        return $data;
    }
}
