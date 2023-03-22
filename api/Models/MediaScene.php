<?php
namespace Api\Models;

use Illuminate\Database\Eloquent\Model;

class MediaScene extends Model
{
    protected $table = 'bc_scene';

    protected $primaryKey = 'scene_id';

    const CREATED_AT = null;
    const UPDATED_AT = null;
    
    protected $guarded = [];

    public $sort = 'scene_id';    
    public $dir = 'asc';

    public $sortable = ['scene_id'];

    protected $casts = [
        'scene_id' => 'integer'
    ];
}
