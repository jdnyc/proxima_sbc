<?php

namespace Api\Types;

/**
 * 미디어 유형
 */
class MediaType
{
    /**
     * 원본
     */
    const ORIGINAL = 'original';

    /**
     * 프록시
     */
    const PROXY = 'proxy';

    /**
     * 썸네일
     */
    const THUMBNAIL = 'thumb';

    /**
     * 첨부파일
     */
    const ATTACH = 'attach';
}
