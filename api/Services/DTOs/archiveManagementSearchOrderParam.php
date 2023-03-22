<?php

namespace Api\Services\DTOs;

use Spatie\DataTransferObject\DataTransferObject;

/**
 * @property string $start_date 검색 시작할 날짜
 * @property string $end_date 검색 마지막 날짜
 * @property int $search_order_num 주문번호 검색
 * @property string $search_cust_nm 이름 검색
 * @property string $status 진행상태
 * @property string $receipt 입금상태
 */
final class archiveManagementSearchOrderParam extends DataTransferObject
{
    public $start_date;
    public $end_date;
    public $search_order_num;
    public $search_cust_nm;
    public $status;
    public $receipt;
}
