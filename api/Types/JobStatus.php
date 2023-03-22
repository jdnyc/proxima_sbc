<?php

namespace Api\Types;

/**
 * 작업 상태
 */
class JobStatus
{

    /**
     * 대기중
     */
    const QUEUING = 'queuing';
    /**
     * 대기
     */
    const QUEUED = 'queued';
    /**
     * 준비완료
     */
    const STANDBY = 'standby';
    /**
     * 할당중
     */
    const ASSIGNING = 'assigning';
    /**
     * 할당됨
     */
    const ASSIGNED = 'assigned';
    /**
     * 진행중
     */
    const WORKING = 'working';
    /**
     * 완료
     */
    const FINISHED = 'finished';
    /**
     * 실패함
     */
    const FAILED = 'failed';
    /**
     * 취소중
     */
    const CANCELING = 'canceling';
    /**
     * 취소됨
     */
    const CANCELED = 'cenceled';

    /**
     * 에러
     */
    const ERROR = 'error';
}
