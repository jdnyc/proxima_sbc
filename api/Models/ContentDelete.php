<?php
namespace Api\Models;

use Api\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;

class ContentDelete extends BaseModel
{
    protected $table = 'bc_delete_content';

    protected $primaryKey = 'id';
    
    protected $guarded = [];

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_at';
    public $sort = 'id';
    public $dir = 'desc';

    public $sortable = ['id'];   

    public $logging = false;

    protected $casts = [
        'content_id' => 'integer',
        'id' => 'integer'
    ];

    
    protected $fillable = [
        'content_id',
        'delete_type',
        'status',
        'reason',
        'reg_user_id',
        'task_id'
    ];
}
