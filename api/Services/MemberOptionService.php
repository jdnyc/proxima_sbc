<?php

namespace Api\Services;

use Api\Models\User;
use Api\Services\BaseService;
use Api\Models\MemberOption;

class memberOptionService extends BaseService
{
    /**
     * 유저아이디로 조회
     *
     * @param [type] $user
     * @return void
     */
    public function findByMemberId($memberId){
        
        $query = memberOption::query();
        $query->where('member_id', $memberId);
        $columnSort = $query->first();
        return $columnSort;
    }

    /**
     * 컬럼 순서 수정 저장
     *
     * @param Array $data
     * @return \Api\Models\memberOption
     */
    public function columnSave($data, $user){
        $memberId = $user->member_id;
        
        $newColumnOrder = [];
        
        $memberOption = $this->findByMemberId($memberId);
        $oldColumnOrder = $memberOption->content_column_order;
        
        if(is_null($oldColumnOrder)){
            $newColumnOrder[$data['ud_content_id']] = $data['sort_order'];
        }else{
            $newColumnOrder = json_decode($oldColumnOrder,true);
            $newColumnOrder[$data['ud_content_id']] = $data['sort_order'];
        };
        $memberOption->content_column_order = json_encode($newColumnOrder);
        $memberOption->save();
    }

}
