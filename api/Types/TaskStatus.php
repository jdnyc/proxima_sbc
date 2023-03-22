<?php

namespace Api\Types;

/**
 * 작업상태 */
class TaskStatus
{
    /**
     * 큐
     */
    const QUEUE = 'queue';
    /**
     * 할당중
     */
    const ASSIGNING = 'assigning';
    /**
     * 처리중
     */
    const PROCESSING = 'processing';
    /**
     * 작업완료
     */
    const COMPLETE = 'complete';
    /**
     * 에러
     */
    const ERROR = 'error';
    /**
     * 취소중
     */
    const CANCELING = 'canceling';
    /**
     * 취소완료
     */
    const CANCELED = 'canceled';

    public static function isWorking($status){
        switch($status)
        {        
            case self::QUEUE:
            case self::ASSIGNING:
            case self::PROCESSING:
                return true;
            break;
            default:
                return false;
            break;
        }
    }

    public static function isCompleted($status){
        switch($status)
        {        
            case self::COMPLETE:
                return true;
            break;
            default:
                return false;
            break;
        }
    }

    public static function isError($status){
        switch($status)
        {        
            case self::ERROR:
            case self::CANCELING:
            case self::CANCELED:
                return true;
            break;
            default:
                return false;
            break;
        }
    }
}
