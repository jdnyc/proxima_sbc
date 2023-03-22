<?php

namespace Api\Models;

use Api\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 주문관리 아이템
 * AMOUNT	수량
 * DATANUM	자료번호
 * HOMEPAGE_KEY	홈페이지키
 * IDX	SEQ
 * METHOD	규격
 * ORDER_NUM	주문번호
 * PRICE	금액
 * PROGDATE	제작(본방)일
 * PROGLENGTH	길이
 * PROGNM	프로그램명
 * PROGNUM	제호(횟수)
 * PROGSECTION	구분
 * PROGTITLE	제목
 * PROLENGTH	길이(초)
 */
class OrderItem extends BaseModel
{
    use SoftDeletes;

    protected $table = 'order_item';

    const DELETED_AT = 'delete_dt';

    protected $primaryKey = 'idx';

    public $sortable = ['id'];

    protected $fillable = [
        'amount',
        'datanum',
        'homepage_key',
        'idx',
        'method',
        'order_num',
        'price',
        'progdate',
        'proglength',
        'prognm',
        'prognum',
        'progsection',
        'progtitle',
        'prolength',
        'status'
    ];

    public function content()
    {
        return $this->belongsTo(\Api\Models\Content::class, 'content_id', 'content_id');
    }
}
