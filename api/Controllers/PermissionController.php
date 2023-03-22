<?php

namespace Api\Controllers;

use Api\Http\ApiRequest;
use Api\Http\ApiResponse;
use Api\Services\GroupService;
use Api\Services\PermissionService;
use Psr\Container\ContainerInterface;
use Illuminate\Database\Capsule\Manager as DB;

class PermissionController extends BaseController
{
    /**
     * 접근 권한 서비스
     *
     * @var \Api\Services\PermissionService
     */
    private $permissionService;

    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->permissionService = new PermissionService($container);
    }

    /**
     * 목록 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function index(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        $params = $input;
        $permission = $this->permissionService->list($params);     
        return $response->ok($permission);
    }

    /**
     * 테이블 단건 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function searchByPath(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        $user = auth()->user();
        $codePath = $request->input('code_path');

        $groupService = new GroupService($this->container);
        $isAdmin =   $groupService->isAdminByUser($user); 
        $groups = [];
        if($isAdmin){
            $groups = $groupService->list();
        }else{
            $groups = $groupService->listByMemberId($user->member_id);
        }
        $lists = $this->permissionService->searchByPath($codePath, $user, $groups);
        return $response->ok($lists);
    }

    public function contentGrant(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        $userId = $input['user_id'];
        $udContentId = $input['ud_content_id'];
        $grant = (int)$input['grant'];
        $grantType = 'content_grant';

        $groupsQuery = DB::table('bc_member m');
        $groupsQuery->join('bc_member_group_member mg',function($join){
            $join->on('m.member_id', '=', 'mg.member_id');
        });
        $groupsQuery->where('user_id', '=', $userId);
        $groups = $groupsQuery->get();

        foreach($groups as $group){
            $memberGroupId = $group->member_group_id;

            $groupGrant = DB::table('bc_grant');
            $groupGrant->where('ud_content_id', '=', $udContentId)->where('member_group_id', '=', $memberGroupId)->where('grant_type', '=', $grantType)->select('group_grant');
            
            $groupGrantCheck = $groupGrant->first()->group_grant;
            $groupGrantCheck = (int)$groupGrantCheck;
            
            if(!empty($groupGrantCheck)){
                if (($groupGrantCheck & $grant) == $grant)
                    return $response->ok();
            }
        }
     
        return $response->error('다운로드 권한이 없습니다.');
    }
    /**
     * 사용자 그룹 조회
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function pathList(ApiRequest $request, ApiResponse $response, array $args)
    {
        $user = auth()->user();
        $groupService = new GroupService($this->container);
        $isAdmin =   $groupService->isAdminByUser($user); 
        $groups = [];
        
        if($isAdmin){
            $groups = $groupService->list();
        }else{
            $groups = $groupService->listByMemberId($user->member_id);
        }

        $lists = $this->permissionService->pathList($groups);
        
        return $response->ok($lists);
    }
}
