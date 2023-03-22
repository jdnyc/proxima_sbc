<?php

namespace Api\Models;

use Api\Models\Storage;
use Api\Models\BaseModel;
use Api\Types\StorageType;

/**
 * 사용자 정의 콘텐츠
 * 
 * @property int $bs_content_id 베이스 콘텐츠 아이디
 * @property int $ud_content_id PK
 * @property int $show_order 정렬순서
 * @property string $ud_content_title 이름
 * @property string $allowed_extension 허용확장자(;구분. ex : .mov;.mxf)
 * @property string $description 설명
 * @property \Carbon\Carbon $created_date 생성일시
 * @property string $expire_date 만료일시
 * @property string $ud_content_code 코드
 */
class UserContent extends BaseModel
{
    protected $table = 'bc_ud_content';

    protected $primaryKey = 'ud_content_id';

    const CREATED_AT = 'created_date';
    const UPDATED_AT = null;

    protected $guarded = [];

    public $sort = 'show_order';
    public $dir = 'asc';

    public $sortable = ['show_order'];

    protected $casts = [
        'ud_content_id' => 'integer',
        'bs_content_id' => 'integer',
        'show_order' => 'integer'
    ];

    /**
     * 사용자 정의 콘텐츠 별 스토리지
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function storages()
    {
        return $this->belongsToMany(Storage::class, 'bc_ud_content_storage', 'ud_content_id', 'storage_id', 'ud_content_id', 'storage_id');
    }

    /**
     * 고해상도 스토리지
     *
     * @return \Api\Models\Storage
     */
    public function getHighresStorage()
    {
        return $this->getStorage(StorageType::HIGHRES);
    }

    /**
     * 저해상도 스토리지
     *
     * @return \Api\Models\Storage
     */
    public function getLowresStorage()
    {
        return $this->getStorage(StorageType::LOWRES);
    }

    /**
     * 업로드 스토리지
     *
     * @return \Api\Models\Storage
     */
    public function getUploadStorage()
    {
        return $this->getStorage(StorageType::UPLOAD);
    }

    public function getStorage($storageType)
    {
        return $this->storages()
            ->where('us_type', $storageType)
            ->first();
    }

    public function content()
    {
        return $this->belongsTo(\Api\Models\Content::class, 'usr_content_id', 'content_id');
    }
}
