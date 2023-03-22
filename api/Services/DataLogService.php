<?php

namespace Api\Services;

use Api\Models\User;
use Api\Models\DataLog;
use Api\Services\BaseService;

/**
 * 데이터 사전 표준용어 서비스
 */
class DataLogService extends BaseService
{
    public function list($params)
    {
        $targetId = $params['target_id'];
        $channel = $params['channel'];
      
        $query = DataLog::query();
        $query->where('CHANNEL', '=', $channel);   
        $query->where('target_id', '=', $targetId);
        //$query->dd();
        $data = paginate($query);

        return $data;
    }
    
    public function find($id)
    {
        $query = DataLog::query();
        return $query->find($id);
    }

    public function findOrFail($id)
    {
        $model = $this->find($id);
        if (!$model) {
            api_abort_404('DataLog');
        }
        return $model;
    }
}
