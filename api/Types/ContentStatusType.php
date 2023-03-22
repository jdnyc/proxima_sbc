<?php

namespace Api\Types;

/**
 * 콘텐츠 상태
 */
class ContentStatusType
{
    /**
     * 등록 대기
     */
    const WAITING = '0';
    /**
     * 등록중
     */
    const REGISTERING = '-3';

    /**
     * 실시간 인제스트중
     */
    const INGEST = '-1';
    /**
     * 반려
     */
    const REJECT = '-5';
    /**
     * 등록 완료
     */
    const COMPLETE = '2';

    /**
     * 리스토어
     */
    const RESTORE = '1';
    /**
     * 리스토어가 아닐때
     */
    const NOTRESTORE = '0';

    
}
