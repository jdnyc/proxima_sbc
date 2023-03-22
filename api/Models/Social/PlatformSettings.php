<?php

namespace Api\Models\Social;

use Api\Models\LogModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlatformSettings extends LogModel
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'sns_account_info' => 'array',
        'sns_account' => 'boolean',
    ];

    public function platform()
    {
        return $this->belongsTo('App\Models\Platform');
    }

    public function channel()
    {
        return $this->belongsTo('App\Models\Channel');
    }
}
