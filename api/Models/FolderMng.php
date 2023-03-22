<?php

namespace Api\Models;

use Api\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FolderMng extends BaseModel
{
    use SoftDeletes;
    protected $table = 'folder_mng';

    protected $primaryKey = 'id';
    const CREATED_AT = 'created_at'; //'regist_dt';
    const UPDATED_AT = 'updated_at'; //'updt_dt';
    const DELETED_AT = 'deleted_at'; //'delete_dt';';

    protected $guarded = [];

    public $sortable = ['parent', 'folder_path_nm','group_cd', 'folder_path', 'pgm_id', 'using_yn','quota','cursize','cursize_num', 'created_at', 'updated_at'];

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
    protected $casts = [];

    public function users()
    {
        return $this->hasMany(\Api\Models\FolderMngUser::class, 'folder_id', 'id');
    }

    public function userInfos()
    {
        return $this->belongsToMany(\Api\Models\User::class, 'folder_mng_user', 'folder_id', 'user_id',  'id', 'user_id');
    }

    public function parent()
    {
        return $this->belongsTo(\Api\Models\FolderMng::class, 'parent_id', 'id');
    }
    public function category()
    {
        return $this->belongsTo(\Api\Models\Category::class, 'category_id', 'category_id');
    }

    public function owners()
    {
        return $this->hasMany(\Api\Models\FolderMngOwnerUser::class, 'folder_id', 'id');
    }

    public function ownerInfo()
    {
        return $this->belongsToMany(\Api\Models\User::class,'folder_mng_owner_user', 'folder_id', 'user_id',  'id', 'user_id')->select('bc_member.user_id','bc_member.user_nm');
    }
}
