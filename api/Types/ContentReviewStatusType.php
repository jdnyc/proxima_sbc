<?php

namespace Api\Types;

/**
 * 콘텐츠 심의 상태
 */
class ContentReviewStatusType
{
    /**
     * 심의 대기
     */
    const WAITING = '3';
   /**
    * 심의 승인
    */
    const COMPLETE = '4';
    /**
    * 심의 반려
    */
    const REJECT = '5';
    /**
    * 심의 조건부 승인
    */
    const CNDL_COMPLETE = '6';
   

    
}
