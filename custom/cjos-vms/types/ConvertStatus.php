<?php
/*
 CONV_BEFORE  //변환전
 CONV_REQUEST  //변환요청
 CONV_ING   //변환중
 CONV_COMPLETE  //변환완료
 CONV_ERROR   //변환에러
 TRANS_ERROR  //전송에러
 TIME_ERROR   //재생시간 오류
 RE_CONV_REQUEST //재변환요청 
 */

namespace ProximaCustom\types;


/**
 * ConvertStatus
 */
class ConvertStatus
{
    /**
     * 변환 전
     */
    const CONV_BEFORE = 0;
    /**
     * 변환 요청
     */
    const CONV_REQUEST = 1;
    /**
     * 변환 중
     */
    const CONV_ING = 2;
    /**
     * 변환 완료
     */
    const CONV_COMPLETE = 3;
    /**
     * 변환 오류
     */
    const CONV_ERROR = 4;
    /**
     * 전송 오류
     */
    const TRANS_ERROR = 5;
    /**
     * 재생시간 오류
     */
    const TIME_ERROR = 6;
    /**
     * 재 변환 요청
     */
    const RE_CONV_REQUEST = 7;

    public static function getCasCode($convertStatus)
    {
        switch ($convertStatus) {
            case self::CONV_BEFORE:
                return 'CONV_BEFORE';
            case self::CONV_REQUEST:
                return 'CONV_REQUEST';
            case self::CONV_ING:
                return 'CONV_ING';
            case self::CONV_COMPLETE:
                return 'CONV_COMPLETE';
            case self::CONV_ERROR:
                return 'CONV_ERROR';
            case self::TRANS_ERROR:
                return 'TRANS_ERROR';
            case self::TIME_ERROR:
                return 'TIME_ERROR';
            case self::RE_CONV_REQUEST:
                return 'RE_CONV_REQUEST';
        }
    }
}
