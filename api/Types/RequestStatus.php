<?php

namespace Api\Types;

class RequestStatus
{
    /**
     * 요청
     */
    const READY = 'ready';
    /**
     * 진행중
     */
    const WORKING = 'working';

    /**
     * 완료
     */
    const  COMPLETE = 'complete';
}