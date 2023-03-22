<?php

namespace Api\Types;

/**
 * 사용자 졍의 콘텐츠 유형
 */
class UdContentType
{
    /**
     * 원본
     */
    const INGEST = '1';
    /**
     * 클립
     */
    const CLIP = '7';
    /**
     * 클린
     */
    const CLEAN = '2';
      /**
     * 마스터
     */
    const MASTER = '3';
    /**
     * 뉴스편집본
     */
    const NEWS = '9';
      /**
     * 이미지
     */
    const IMAGE = '5';
      /**
     * 오디오
     */
    const AUDIO = '4';
      /**
     * CG
     */
    const CG = '8';
}
