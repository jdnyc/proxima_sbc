<?php

namespace Api\Models;

use Illuminate\Database\Eloquent\Model;

class Grant extends Model
{
    protected $table = 'bc_grant';

    protected $primaryKey = 'UD_CONTENT_ID,MEMBER_GROUP_ID,GRANT_TYPE,GROUP_GRANT';

    protected $fillable = [
        
    ];
}
