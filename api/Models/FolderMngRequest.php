<?php

namespace Api\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Api\Models\BaseModel;

class FolderMngRequest extends BaseModel
{
    use SoftDeletes;
    protected $table = 'folder_mng_request';

    protected $primaryKey = 'id';
    const CREATED_AT = 'created_at'; //'regist_dt';
    const UPDATED_AT = 'updated_at'; //'updt_dt';
    const DELETED_AT = 'deleted_at'; //'delete_dt';';

    protected $guarded = [];

    public $sortable = ['parent', 'folder_path_nm', 'folder_path', 'pgm_id', 'using_yn', 'created_at', 'updated_at'];

    protected $fillable = [
        'parent_id',
        'folder_path_nm',
        'folder_path',
        'step',
        'pgm_id',
        'owner_cd',
        'using_yn',
        'created_at',
        'updated_at',
        'category_id'
    ];
    
    /**
     * 신청자
     *
     * @return \Api\Models\User
     */
    public function registerer()
    {
        return $this->belongsTo(\Api\Models\User::class, 'regist_user_id', 'user_id');
    }
    /**
     * 승인자
     *
     * @return \Api\Models\User
     */
    public function approval()
    {
        return $this->belongsTo(\Api\Models\User::class, 'approval_user_id', 'user_id');
    }
}