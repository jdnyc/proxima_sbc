<?php

namespace Api\Models;

use Api\Models\BaseModel;

/**
 * 권한
 * 
 * @property int $id 아이디(pk)
 */
class Permission extends BaseModel
{
    protected $table = 'bc_permission';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * Date 포맷
     *
     * @var string
     */
    protected $dateFormat = 'YmdHis';
    /**
     * PK
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = [
        'code',
        'code_path',
        'parent_id',
        'description',
        'p_depth',
        'use',
        'show_order'
    ];

    public function groups ()
    {
        return $this->belongsToMany(\Api\Models\Group::class, 'bc_permission_group', 'permission_id', 'member_group_id',  'id', 'member_group_id');
    }
   
    // CREATE TABLE BC_PERMISSION (
    //     ID NUMBER NOT NULL ENABLE,
    //     CREATED_AT VARCHAR2(14),
    //     UPDATED_AT VARCHAR2(14),
    //     DELETED_AT VARCHAR2(14),
    //     CODE VARCHAR2(100),
    //     CODE_PATH VARCHAR2(1000),
    //     PARENT_ID NUMBER,
    //     DESCRIPTION VARCHAR2(4000),
    //     P_DEPTH NUMBER,
    //     USE NUMBER,
    //     SHOW_ORDER NUMBER,
    //     CONSTRAINT "BC_PERMISSION_PK" PRIMARY KEY ("ID")
    // );
}
