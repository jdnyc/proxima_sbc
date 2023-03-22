<?php

namespace Api\Types\Social;

/**
 * 공개상태
 */
class PrivacyStatus
{    
    /**
     * 공개
     */
    const PUBLIC = 'public';
    /**
     * 비공개
     */
    const PRIVATE = 'private';
    /**
     * 예약
     */
    const BOOK = 'book';
    /**
     * SNS 예약
     */
    const SNS_BOOK = 'sns_book';
}
