<?php
namespace Api\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'bc_category';

    protected $primaryKey = 'category_id';

    const CREATED_AT = null;//'regist_dt';
    const UPDATED_AT = null;//'updt_dt';
    const DELETED_AT = null;//'delete_dt';';
    
    protected $guarded = [];

    protected $casts = [
        'content_id' => 'integer'
    ];
    public function parent()
    {
        return $this->belongsTo(\Api\Models\Category::class, 'parent_id', 'category_id');
    }
    public function folder()
    {
        return $this->belongsTo(\Api\Models\FolderMng::class, 'category_id', 'category_id');
    }
}
