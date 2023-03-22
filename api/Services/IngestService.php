<?php

namespace Api\Services;

use Api\Models\User;
use Api\Services\BaseService;
use Api\Services\DTOs\CategoryDto;
use Illuminate\Database\Capsule\Manager as DB;

class IngestService extends BaseService
{
    public function list($params)
    {
        $system_host = $params['system_host'];
        $channel = $params['channel'];
        $isUse =1;
        $result = DB::table('INGESTMANAGER_SCHEDULE')     
        ->where('IS_USE', $isUse )
        ->where('INGEST_SYSTEM_IP', $system_host )
        ->where('CHANNEL', $channel )
        ->get()->all();
        return $result;
    }
}
