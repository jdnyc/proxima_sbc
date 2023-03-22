<?php

namespace Api\Types;

/**
 * 작업상태 */
class TaskType
{
    /**
     * 카달로깅
     */
    const CATALOG = '10';
    /**
     * 섬네일생성
     */
    const THUMBNAIL = '11';
    /**
     * 삭제
     */
    const DELETE = '100';

    /**
     * 트랜스코딩
     */
    const TRANSCODING = '20';

    /**
     * 리스토어
     */
    const RESTORE = '160';
    
}
