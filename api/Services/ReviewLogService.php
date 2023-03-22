<?php

namespace Api\Services;

use Api\Services\BaseService;
use Api\Models\ReviewLog;

class ReviewLogService extends BaseService
{
    public function reviewLog($data,$type){
        $user = auth()->user();
        
        $reviewLog =  new ReviewLog();
    
        $reviewLog->regist_user_id = $user->user_id;
        $reviewLog->content_id = $data['content_id'];
        $reviewLog->action =  $data['change_status'];
        $reviewLog->review_type = $type;
        $reviewLog->save();
        return $reviewLog;
    }

    public function reviewCancelLog($data,$type,$action){
        $user = auth()->user();
        $query = ReviewLog::query();
        
        $query->where('action','=',$action);
        $query->where('content_id', '=', $data['content_id']);
        $query->where('review_type','=',$type);
        $query->orderByDesc('regist_dt');
        $cancelReviewLog = $query->first();
        $ret = $cancelReviewLog->delete();

        return $ret;
        // $reviewLog =  new ReviewLog();

        // $reviewLog->regist_user_id = $user->user_id;
        // $reviewLog->content_id = $data['content_id'];
        // $reviewLog->action =  $action;
        // $reviewLog->review_type = $type;
        // $reviewLog->save();
        // return $reviewLog;
    }

    public function reviewLogCount($data,$type,$action){
        $query = ReviewLog::query();
        $query->where('action','=',$action);
        $query->where('content_id', '=', $data['content_id']);
        $query->where('review_type','=',$type);
        $count = $query->count();
        return $count;
    }
}
