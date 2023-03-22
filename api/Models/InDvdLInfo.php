<?php
namespace Api\Models;

use Api\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;

class InDvdLInfo extends BaseModel
{
    protected $table = 'indvdlinfo';

    protected $primaryKey = 'id';

    const CREATED_AT = null;
    const UPDATED_AT = null;

    
    protected $guarded = [];

    public $sort = 'content_id';

    public $sortable = ['content_id'];

    protected $casts = [
        'content_id' => 'integer'
    ];

    protected $fillable = [        
        'content_id',
        'ACCOUNT_NUMBERS',
        'BUSINESSMAN_NUMBERS',
        'BUSSINESS_NAMES',
        'CAR_NUMBERS',
        'CELLPHONE_NUMBERS',
        'CORPORATION_NUMBERS',
        'CREDIT_CARDS',
        'DRIVER_NUMBERS',
        'EMAILS',
        'FOREIGN_NUMBERS',
        'HEALTH_INSURANCES',
        'NAMES',
        'PASSPORT_NUMBERS',
        'PREVENT_WORDS',
        'SOCIAL_NUMBERS',
        'TELEPHONE_NUMBERS'
    ];

}
