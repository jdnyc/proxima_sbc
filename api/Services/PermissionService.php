<?php
namespace Api\Services;

use Api\Models\Permission;
use Api\Models\User;
use Api\Models\Group;
use Api\Services\BaseService;
use Illuminate\Support\Facades\DB;

class PermissionService extends BaseService
{
    public function list($params)
    {      
        //쿼리 조건 삭제여부
        $query = Permission::query();
          
        if (!is_null($params->code_path)) {
            $query->where('code_path', '=', "{$keyword}");
        }

        $lists = $query->get();
        //dd($lists);
        return $lists;
    }

    public function create($id){
        $data = new Permission();
        $data->id = $id;
        $data->code = 'add';
        $data->code_path = 'data_dic.word.add';
        $data->parent_id = 2;
        $data->description = 'word';
        $data->p_depth = 1;
        $data->use = 1;
        $data->show_order = $id;
        $r = $data->save();
        return $r;
    }

    public function searchByPath( $codePath , $user, $groups){
        $memberGroupIds = [];
        foreach($groups as $group){
            $memberGroupIds[] =  (int)$group->member_group_id;
        }

        $lists = Permission::with('groups')
        ->where('use', 1)
        ->where("code_path", 'like', ''.$codePath.'%' )
        ->whereHas('groups', function($q) use ($memberGroupIds){         
            $q->whereIn('bc_permission_group.member_group_id', $memberGroupIds );
        })->get();

        //일치하면 전체 권한
        $permissions = [];
        foreach($lists as $list){
            if( $list['code_path'] == $codePath ){
                $permissions  = ['*'];
                break;
            }else{
                $permissions [] = $list['code'];
            }
        }
        return $permissions;
    }

    public function pathList($groups){
        $memberGroupIds = [];
        foreach($groups as $group){
            $memberGroupIds[] =  (int)$group->member_group_id;
        }
        
        $lists = Permission::with('groups')
            ->where('use', 1)
            ->whereHas('groups', function($q) use ($memberGroupIds){         
                $q->whereIn('bc_permission_group.member_group_id', $memberGroupIds );
            })->get();

        
        $groupLists = [];
        foreach($lists as $list){
            $groupLists[] = $list['code_path'];
        }
        
        return $groupLists;
    }
}
