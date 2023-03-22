<?php
namespace Api\Models;

use Api\Types\OSType;
use Proxima\core\Path;
use Proxima\core\Directory;

class Media extends BaseModel
{
    protected $table = 'bc_media';

    protected $primaryKey = 'media_id';
    
    const CREATED_AT = 'created_date';
    const UPDATED_AT = null;

    protected $guarded = [];

    public $sort = 'media_id';    
    public $dir = 'desc';

    public $sortable = ['media_id'];

    public $logging = false;

    protected $casts = [
        'media_id' => 'integer'
    ];

    public function storage()
    {
        return $this->belongsTo(\Api\Models\Storage::class, 'storage_id', 'storage_id');
    }

    public function content()
    {
        return $this->belongsTo(\Api\Models\Content::class, 'content_id', 'content_id');
    }

    public function fileFullPath($osType = null)
    {        
        $storage = $this->storage ?? null;
        if($storage === null) {
            return $this->path;
        }

        $storagePath = Directory::getServerStoragePath($storage, $osType);
        // 스토리지 패스가 없으면 기본 패스 리턴
        if($storagePath === null) {
            $storagePath = $storage->path;
        }
        $path = Path::join($storagePath, $this->path);
        if($osType === OSType::LINUX || $osType === OSType::MAC) {
            $path = Path::fixSeparator($path, '/');
        } else {
            $path = Path::fixSeparator($path, '\\');
        }
        return $path;
    }
}

