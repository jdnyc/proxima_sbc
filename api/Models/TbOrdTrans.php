<?php

namespace Api\Models;


/**
 * 조디악 연동용 전송 작업
 * 
 */
class TbOrdTrans extends BaseModel
{
    protected $table = 'tb_ord_transmission';

    protected $primaryKey = 'ord_tr_id';

    const CREATED_AT = null;
    const UPDATED_AT = null;
}
