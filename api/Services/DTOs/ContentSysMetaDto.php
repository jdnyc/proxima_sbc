<?php

namespace Api\Services\DTOs;

use Spatie\DataTransferObject\DataTransferObject;
use Respect\Validation\Validator as v;

/**
 * 콘텐츠 DTO
 * 
 * @property int $content_id id
 */
final class ContentSysMetaDto extends DataTransferObject
{
    public $sys_content_id;
    public $sys_frame_rate;
    public $sys_video_rt;
    public $sys_filename;
    public $sys_storage;
    public $sys_display_size;
    public $sys_video_codec;
    public $sys_audio_codec;
    public $sys_video_bitrate;
    public $sys_audio_bitrate;
    public $sys_filesize;
    public $sys_audio_channel;
    public $sys_video_duration;
    public $sys_ori_filename;
    public $sys_video_frme;
    public $sys_rsoltn_se;
    public $sys_video_asperto;
    public $sys_video_wraper;
    public $sys_audio_samplrate;
    public $sys_clip_begin_time;
    public $sys_clip_end_time;
    public $sys_image_format;
    public $sys_document_format;
}