<?php

namespace Api\Models;
use Api\Types\MediaType;


class TbRequest extends BaseModel
{
    protected $table = 'tb_request';

    protected $primaryKey = 'req_no';

    const CREATED_AT = null;
    const UPDATED_AT = null;

    /**
     * 미디어
     *
     * @return \Api\Models\Media
     */
    public function medias()
    {
        return $this->hasMany(\Api\Models\Media::class, 'content_id', 'nps_content_id');
    }

    /**
     * 오리지날 미디어
     *
     * @return \Api\Models\Media
     */
    public function getOriginalMedia()
    {
        return $this->medias()
            ->where('media_type', MediaType::ORIGINAL)
            ->first();
    }
}
