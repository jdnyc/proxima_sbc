<?php
namespace Api\Services;

use Api\Models\User;
use Api\Models\Group;
use Api\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Api\Traits\DataDicDomainIncludeTrait;

class GroupService extends BaseService
{
    use DataDicDomainIncludeTrait;

    public function list($params = null)
    {

        if( empty($params) ){
            $lists = Group::all();
        }
        return $lists;
    }

    public function create($id){      
    }

    /**
     * 그룹 조회 BY 사용자의 MEMBER ID로 조회
     *
     * @param [type] $memberId
     * @return $lists
     */
    public function listByMemberId($memberId){
        $lists = Group::with("users")->whereHas("users", function($q) use ($memberId){
            $q->where('bc_member_group_member.member_id', $memberId);
        })->get();

        return $lists;
    }

    /**
     * 그룹의 관리자그룹 여부 체크
     *
     * @param [type] $user
     * @return boolean
     */
    public function isAdminByUser($user){
        
        $groups = self::listByMemberId($user->member_id);
  
        $isAdmin = false;
        $memberGroupIds = [];
        foreach($groups as $group){
            if( $group->is_admin == 'Y'){
                $isAdmin = true;
            }
        }
        return $isAdmin;
    }
}
