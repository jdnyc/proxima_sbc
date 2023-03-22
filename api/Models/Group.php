<?php

namespace Api\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $table = 'bc_member_group';

    protected $primaryKey = 'member_group_id';

    protected $fillable = [
        
    ];

    public function users()
    {
        return $this->belongsToMany(\Api\Models\User::class, 'bc_member_group_member', 'member_group_id', 'member_id', 'member_group_id', 'member_id');
    }

    public function permissions ()
    {
        return $this->belongsToMany(\Api\Models\Permission::class, 'bc_permission_group_map', 'member_group_id', 'permission_id', 'member_group_id', 'id');
    }

    public function grants()
    {
        return $this->hasMany(\Api\Models\Grant::class, 'member_group_id', 'member_group_id');
    }
}
