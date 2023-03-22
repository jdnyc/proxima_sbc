<?php

namespace Api\Types;

/**
 * 아카이브상태 */
class ArchiveStatus
{
    /**
     * 니어라인
     */
    const NEARLINE = 1;
    /**
     * DTL
     */
    const DTL = 2;
    /**
     * 니어라인 및 DTL
     */
    const NEARLINE_AND_DTL = 3;
}
