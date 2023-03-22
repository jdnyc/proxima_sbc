<?php

namespace Api\Types\Social;

/**
 * 게시상태
 */
class PublishStatus
{
    /**
     * 대기
     */
    const QUEUED = 'queued';
    /**
     * 변환중
     */
    const TC_WORKING = 'tc_working';
    /**
     * 변환완료
     */
    const TC_FINISHED = 'tc_finished';
    /**
     * 변환실패
     */
    const TC_FAILED = 'tc_failed';
    /**
     * 업로드 진행중
     */
    const UPLOADING = 'uploading';
    /**
     * 업로드 완료
     */
    const UPLOADED = 'uploaded';
    /**
     * 게시됨
     */
    const PUBLISHED = 'published';
    /**
     * 실패
     */
    const FAILED = 'failed';
}
