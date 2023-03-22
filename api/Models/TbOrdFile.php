<?php

namespace Api\Models;


/**
 * 의뢰 파일
 * 
 * @property int $ord_id pk
 * @property string $file_path 파일 경로
 * @property string $file_name 파일 이름
 */
class TbOrdFile extends BaseModel
{
    protected $table = 'tb_ord_file';

    protected $primaryKey = 'id';

    const CREATED_AT = null;
    const UPDATED_AT = null;
}
