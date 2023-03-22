<?php

namespace Api\Models;

use Api\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemberOption extends BaseModel
{
    protected $table = 'bc_member_option';
    protected $primaryKey = 'member_option_id';

    const CREATED_AT = null;
    const UPDATED_AT = null;
    const DELETED_AT = null;

}
