<?php

namespace Api\Types;

/**
 * 사용자 등록 신청 상태
 */
class MemberStatus
{
    /**
     *  전체
     */
    const ALL = 'all';
    /**
     * 요청
     */
    const REQUEST = 'request';
    /**
     * 승인
     */
    const APPROVAL = 'approval';
    /**
     * 반려
     */
    const REJECT = 'reject';
}