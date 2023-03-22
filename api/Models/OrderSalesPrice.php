<?php

namespace Api\Models;

use Api\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 가격관리
 * IDX	SEQ
 * METHOD	규격
 * PRICE	가격
 * PROLENGTH	시간(분)
 * TAPE_PRICE	테잎가격
 * WON_PRICE	원본가격

 */
class OrderSalesPrice extends BaseModel
{
    use SoftDeletes;

    protected $table = 'order_sales_price';

    const DELETED_AT = 'delete_dt';

    protected $primaryKey = 'idx';

    // public $sort = 'idx';

    // public $dir = 'asc';

    public $sortable = ['id', 'idx'];

    protected $fillable = [
        'idx',
        'method',
        'price',
        'prolength',
        'tape_price',
        'won_price'
    ];
}
