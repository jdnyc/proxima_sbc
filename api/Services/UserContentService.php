<?php

namespace Api\Services;

use Api\Services\BaseService;
use Api\Models\UserContent;

class UserContentService extends BaseService
{
    public function getUdContentByUdContentId($udContentId)
    {
        $query = UserContent::query();
        $udContentId = $query->find($udContentId);
        return $udContentId;
    }
}