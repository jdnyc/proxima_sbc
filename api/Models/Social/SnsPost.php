<?php

namespace Api\Models\Social;

use Api\Models\LogModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * SNS 게시
 *
 * @property int $id PK
 * @property int $content_id 콘텐츠 아이디
 * @property int $media_id 미디어 아이디
 * @property int $channel_id 채널 아이디
 * @property int $platform_id 플랫폼 아이디
 * @property string $post_id SNS 게시물 아이디
 * @property string $post_url SNS 게시물 URL
 * @property string $extra_id 플랫폼에 따라 추가적으로 필요한 id
 * @property string $thumb_url SNS 게시물 썸네일 URL
 * @property int $duration 영상 재생 길이(초)
 * @property int $category_id 카테고리 아이디
 * @property string $title 제목
 * @property string $description 설명
 * @property string $tag 태그
 * @property \Api\Types\Social\MediaType $media_type SNS 미디어 타입(video, album)
 * @property \Api\Types\Social\PublishStatus $status SNS 게시상태(booked, queued, working, finished, failed)
 * @property string $user_id 사용자 아이디
 * @property \Api\Types\Social\PrivacyStatus $privacy_status 공개상태:공개/비공개/예약(public, private, book, sns_book)
 * @property \Carbon\Carbon $published_at 게시일시
 * @property \Carbon\Carbon $booked_at 예약게시일시
 * @property \Carbon\Carbon $created_at 생성일시
 * @property \Carbon\Carbon $updated_at 수정일시
 * @property \Carbon\Carbon $deleted_at 삭제일시
 */
class SnsPost extends LogModel
{
    use SoftDeletes;

    public $logging = false;

    protected $guarded = [];

    protected $dates = ['deleted_at', 'published_at', 'booked_at'];

    public function platform()
    {
        return $this->belongsTo(\Api\Models\Social\Platform::class);
    }

    public function channel()
    {
        return $this->belongsTo(\Api\Models\Social\Channel::class);
    }

    public function content()
    {
        return $this->belongsTo(\Api\Models\Content::class);
    }

    public function media()
    {
        return $this->belongsTo(\Api\Models\Media::class, 'media_id', 'media_id');
    }
    public function thumbnail()
    {
        return $this->belongsTo(\Api\Models\Media::class, 'thumb_media_id', 'media_id');
    }

    public function user()
    {
        return $this->hasOne(\Api\Models\User::class, 'user_id', 'user_id');
    }

    public function task()
    {
        return $this->hasOne(\Api\Models\Task::class, 'task_id', 'task_id');
    }

    public function apiJobs()
    {
        return $this->hasMany(\Api\Models\ApiLog::class, 'owner_id', 'id');
    }
}
