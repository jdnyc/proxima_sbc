<?php

namespace Api\Models\Social;

use Api\Models\LogModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Platform extends LogModel
{
    use SoftDeletes;

    protected $guarded = [];

    /**
     * 플랫폼에 속한 채널들
     */
    public function channels()
    {
        return $this->belongsToMany('Api\Models\Social\Channel', 'platform_settings');
    }

    /**
     * 플랫폼에 속한 카테고리들
     */
    public function categories()
    {
        return $this->hasMany('Api\Models\Social\Category');
    }

    public function channels_with_setting()
    {
        return $this->belongsToMany('Api\Models\Social\Channel', 'platform_settings', 'platform_id', 'channel_id')
            ->as('setting')
            ->withPivot('sns_account', 'sns_account_info', 'category_id');
    }
}
