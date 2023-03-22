<?php

namespace Api\Types;

class ArchiveRequestStatus
{
    /**
     * 요청
     */
    const REQUEST = '1';
    /**
     * 반려
     */
    const REJECT = '3';

    /**
     * 승인
     */
    const  COMPLETE = '2';
}