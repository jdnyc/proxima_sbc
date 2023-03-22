<?php

namespace Api\Services;

use Api\Models\Log;
use Api\Models\Content;
use Api\Models\LogDetail;
use Api\Services\BaseService;

class LogService extends BaseService
{
    public function create($data, $user)
    {
        $log = new Log();

        $contentId = $data['content_id'] ?? null;
        if ($contentId) {
            $content = Content::where('content_id', $contentId)->first();
            $data['ud_content_id'] = $content->ud_content_id;
            $data['bs_content_id'] = $content->bs_content_id;
        }
        $log->action        = $data['action'];
        $log->user_id       = $user->user_id;
        $log->bs_content_id = $data['bs_content_id'] ?? null;
        $log->content_id    = $contentId;
        $log->created_date  = date('YmdHis');
        $log->ud_content_id = $data['ud_content_id'] ?? null;
        $log->description   = $data['description'] ?? null;
        $log->zodiac_id     = $data['zodiac_id'] ?? null;

        $log->save();

        return $log;
    }

    /**
     * 상세 로그 이력
     *
     * @param [type] $logId
     * @param [type] $data
     * @return void
     */
    public function createDetail($logId, $data)
    {
        if (!$logId) {
            return false;
        }
        $logDetail = new LogDetail();
        $logDetail->log_id   = $logId;
        $logDetail->action   = $data['action'] ?? 'edit';
        $logDetail->usr_meta_field_code     = $data['usr_meta_field_code'] ?? null;
        $logDetail->new_contents     = utf8_strcut($data['new_contents'],1000) ?? null;
        $logDetail->old_contents     = utf8_strcut($data['old_contents'],1000) ?? null;

        $logDetail->save();

        return $logDetail;
    }
}
