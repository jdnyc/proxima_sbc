<?php

namespace Api\Types;

/**
 * 주문상태
 */
class ReceiptStatus
{
    /**
     * 전체
     */
    const TOTAL = '1';
    /**
     * 입금전
     */
    const BEFORE = '2';
    /**
     * 입금후
     */
    const AFTER = '3';
}
