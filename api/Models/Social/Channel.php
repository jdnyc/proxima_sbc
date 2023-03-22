<?php

namespace Api\Models\Social;

use Api\Models\LogModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Channel extends LogModel
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'active' => 'boolean'
    ];

    /**
     * 채널에 속한 플랫폼들
     * 
     * @return \Api\Models\Social\Platform[]
     */
    public function platforms()
    {
        return $this->belongsToMany('Api\Models\Social\Platform', 'platform_settings');
    }

    /**
     * 플랫폼 설정
     *
     * @return \Api\Models\Social\PlatformSettings[]
     */
    public function platformSettings()
    {
        return $this->belongsToMany('Api\Models\Social\PlatformSettings', 'platform_settings', 'channel_id', 'platform_id');
    }

    /**
     * 채널에 속한 플랫폼들인데 설정정보도 같이 조회
     *
     * @return \Api\Models\Social\PlatformSettings[]
     */
    public function platforms_with_setting()
    {
        return $this->belongsToMany('Api\Models\Social\Platform', 'platform_settings', 'channel_id', 'platform_id')
            ->as('setting')
            ->withPivot('sns_account', 'sns_account_info', 'category_id');
    }
}
