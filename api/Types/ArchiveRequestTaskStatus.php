<?php

namespace Api\Types;

class ArchiveRequestTaskStatus
{
    /**
     * 성공
     */
    const COMPLETE = 'complete';
    /**
     * 진행중
     */
    const PROCESSING = 'processing';
        /**
     * 진행중
     */
    const QUEUE = 'queue';
}