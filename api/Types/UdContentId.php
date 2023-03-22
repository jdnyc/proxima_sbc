<?php

namespace Api\Types;

/**
 * 사용자 정의 콘텐츠 아이디
 */
class UdContentId
{
    /**
     * 원본
     */
    const ORG = 1;
    /**
     * 클린본
     */
    const CLEAN = 2;
    /**
     * 마스터본
     */
    const MASTER = 3;
     /**
     * 뉴스편집본
     */
    const NEWS = 9;
    /**
     * 오디오
     */
    const AUDIO = 4;
    /**
     * 이미지
     */
    const IMAGE = 5;
    /**
     * 클립본
     */
    const CLIP = 7;
    /**
     * CG
     */
    const CG = 8;
}