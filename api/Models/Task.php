<?php

namespace Api\Models;

use Api\Models\BaseModel;

/**
 * 작업 모듈
 * 
 * @property int $task_id 작업 아이디(pk)
 * @property int $media_id 미디어아이디
 * @property string $type 작업유형코드
 * @property string $source 소스
 * @property string $source_id 소스 아이디
 * @property string $source_pw 소스 암호
 * @property string $target 대상
 * @property string $target_id 대상 아이디
 * @property string $target_pw 대상 암호
 * @property string $parameter 파라메터
 * @property int $progress 진행률
 * @property string $status 작업 상태
 * @property int $priority 우선순위
 * @property Carbon $작업 시작시간
 * @property mixed $name
 */
class Task extends BaseModel
{
    protected $table = 'bc_task';

    protected $primaryKey = 'task_id';

    protected $guarded = [];

    /**
     * 소스 미디어 관계
     */
    public function sourceMedia()
    {
        return $this->belongsTo(\Api\Models\Media::class, 'src_media_id', 'media_id');
    }

    /**
     * 대상 미디어 관계
     */
    public function targetMedia()
    {
        return $this->belongsTo(\Api\Models\Media::class, 'trg_media_id', 'media_id');
    }

    /**
     * 대상 스토리지 관계
     */
    public function targetStorage()
    {
        return $this->belongsTo(\Api\Models\Storage::class, 'trg_storage_id', 'storage_id');
    }

    /**
     * 소스 스토리지 관계
     */
    public function sourceStorage()
    {
        return $this->belongsTo(\Api\Models\Storage::class, 'src_storage_id', 'storage_id');
    }

    /**
     * 소스 파일 관계
     */
    public function sourceFile()
    {
        return $this->belongsTo(\Api\Models\File::class, 'src_file_id', 'id');
    }

    /**
     * 대상 파일 관계
     */
    public function targetFile()
    {
        return $this->belongsTo(\Api\Models\File::class, 'trg_file_id', 'id');
    }
}
