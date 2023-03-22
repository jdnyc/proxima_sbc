<?php

namespace Api\Models;

use Illuminate\Database\Eloquent\Model;

class ContentSysMeta extends Model
{
    protected $table = 'bc_sysmeta_movie';

    protected $primaryKey = 'sys_content_id';


    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $guarded = [];

    //public $sort = 'sys_content_id';

    public $sortable = ['sys_content_id'];

    protected $casts = [
        'sys_content_id' => 'integer'
    ];

    protected $fillable = [
        'sys_content_id',
        'sys_frame_rate',
        'sys_video_rt',
        'sys_filename',
        'sys_storage',
        'sys_display_size',
        'sys_video_codec',
        'sys_audio_codec',
        'sys_video_bitrate',
        'sys_audio_bitrate',
        'sys_filesize',
        'sys_audio_channel',
        'sys_video_duration',
        'sys_ori_filename',
        'sys_video_frme',
        'sys_rsoltn_se',
        'sys_video_asperto',
        'sys_video_wraper',
        'sys_audio_samplrate',
        'sys_clip_begin_time',
        'sys_clip_end_time',
        'sys_image_format',
        'sys_document_format'
    ];

    public function content()
    {
        return $this->belongsTo(\Api\Models\Content::class, 'sys_content_id', 'content_id');
    }

    public function getDurationSeconds()
    {
        if (empty($this->sys_video_rt)) {
            return 0;
        }
        return \timecode::getConvSec($this->sys_video_rt);
    }
}
