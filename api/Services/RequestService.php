<?php

namespace Api\Services;

use Api\Services\BaseService;

use Api\Models\Request;
use Api\Models\TbOrdFile;
use Api\Models\User;

use Api\Types\RequestType;
use Api\Types\RequestStatus;

class RequestService extends BaseService
{
    /**
     * TB_ORD query
     *
     * @return void
     */
    // public function query()
    // {

    //     $query = Request::query();

    //     return  $query;
    // }

    public function find($id)
    {
        $query = Request::query();
        return $query->find($id);
    }

    public function findUser($userNm)
    {
        $query = User::query();
        $user = $query->where('user_nm', $userNm)->first();
        return  $user;
    }

    public function findWithUser($id)
    {
        $query = Request::query();
        $this->includeUser($query, 'inputr');
        $this->includeUser($query, 'workUser');
        return $query->find($id);
    }

    public function findOrFail($id)
    {
        $request = $this->find($id);
        if (!$request) {
            api_abort_404('Request');
        }
        return $request;
    }
    /**
     * 의뢰 추가
     *
     * @param [type] $requestData
     * @param [type] $typeSe
     * @param [type] $user
     * @param [type] $workUser
     * @return \Api\Models\Request
     */
    public function create($requestData, $typeSe, $user, $workUser, $graphicReqestTy)
    {
      
        $request = new Request();
        $request->ord_id = $this->getSequence(('SEQ_TB_ORD'));
        $request->title = $requestData->title;
        $request->ord_ctt = $requestData->ord_ctt;
        $request->inputr_id = $user->user_id;
        $request->dept_cd = $typeSe->dept_cd;
        $request->ord_meta_cd = $typeSe->ord_meta_cd;
        $request->ord_work_id = $workUser->user_id;
        $request->ord_status = RequestStatus::READY;
        if($typeSe->ord_meta_cd === "graphic"){
            $request->graphic_reqest_ty = $graphicReqestTy;
        };
        $request->save();
        return $request;
    }

    /**
     * Undocumented function
     *
     * @param [type] $ordId
     * @return void
     */
    public function delete($ordId)
    {

        $request = $this->findOrFail($ordId);
        if (empty($request)) {
            return false;
        }

        $request->delete();
 
        return $request;
    }

    /**
     * 의뢰 수정
     *
     * @param String $ordId
     * @param Array $data
     * @return \Api\Models\Request
     */
    public function update($ordId, $data)
    {
        $requestData = json_decode($data['request_data']);
        $userData = json_decode($data['user_data']);
        $workUser = $this->findUser($userData->user_nm);

        $request = $this->findOrFail($ordId);

        $request->title = $requestData->title;
        $request->ord_ctt = $requestData->ord_ctt;
        $request->ord_work_id = $workUser->user_id;
        $request->save();

        return $request;
    }
    public function memberGroup($groupId, $user)
    {
        // $userGroups = User::query()->find('member_id', $user->member_id);
        // $userGroups = User::query()->with('groups')->where('member_id', $user->member_id)->first();
        $userGroups = User::where('member_id', $user->member_id)->first()->groups;
        foreach ($userGroups as $userGroup) {
            if ($userGroup->member_group_id == $groupId) {
                dd($userGroup);
            };
        }
        return $userGroups;
    }

    /**
     * 의뢰 타입별 리스트
     * ord_status update
     *
     * @param array $input 요청 파라메터(type, status, my_request 사용)
     * @param \Api\Models\User $user 현재 세션 사용자 객체
     * @return \Api\Models\Request[]
     */
    public function getRequestList($input, $user)
    {
        $query = Request::query();

        $this->includeUser($query, 'inputr');
        $this->includeUser($query, 'workUser');
        /**
         * 영상편집 인지 그래픽 인지 유형을 확인한다.
         * 
         * 그 유형에 맞게 권한이 있다면..
         * 1. 조회는 전체 조회 -> 중복
         * 2. 진행 상태별 조회 -> 중복
         * 3. 내 심의 체크시
         * 3-1. 의뢰자 이거나 담당자 일 수 있다.
         * 
         * 그 유형에 맞게 권한이 없다면..
         * 1. 조회는 전체 조회  -> 중복
         * 2. 진행 상태별 조회  -> 중복
         * 3. 내 심의 체크시 의뢰자 만 조회
         */
        switch ($input['type']) {
            case RequestType::VIDEO_EDIT:
                $groupPermissions = $user->hasEditGroup();
                break;
            case RequestType::GRAPHIC:
                $groupPermissions = $user->hasCgGroup();
                break;
                // case RequestType::All:
                //     $groupPermissions = $user->hasCgGroup();
                //     break;
            default:
                $groupPermissions = false;
                break;
        };

        if (!($input['type'] == 'All')) {
            $query->where('ord_meta_cd', $input['type']);
        }
        if (!($input['graphic_reqest_ty'] == 'All')) {
            $query->where('graphic_reqest_ty', $input['graphic_reqest_ty']);
        }


        if ($input['my_request'] == 'true') {
            if ($groupPermissions) {
                // 담당자
                // 담당자가 나인 목록
                $query->where(function ($q) use ($user) {
                    $q->where('ord_work_id', $user->user_id)
                        ->orWhere('inputr_id', $user->user_id);
                });
            } else {
                // 의뢰자
                // 의뢰자가 나인 목록
                $query->where('inputr_id', $user->user_id);
            }
        }

        // 진행상태 전체가 아니면 ..
        if (!($input['status'] == 'all')) {
            $query->where('ord_status', $input['status']);
        }

        $startDate = dateToStr($input['start_date'], 'YmdHis');
        $endDate = dateToStr($input['end_date'], 'Ymd').'235959';
        $query->whereBetween('input_dtm', [$startDate,$endDate]);

        $query->orderByDesc('input_dtm');
        // $requests = $query->get();
        

        $requests = paginate($query);

        return $requests;
    }
    /**
     * ord_status update
     *
     * @param String $ordId 의뢰 아이디
     * @param String $changeStatus 바꿀 진행 상태
     * @return \Api\Models\Request
     */
    public function updateStatus($ordId, $changeStatus)
    {

        $request = $this->findOrFail($ordId);

        $request->ord_status = $changeStatus;
        if ($changeStatus == "complete") {
            $request->completed_dtm = dateToStr('now', 'YmdHis');
        };
        $request->save();

        return $request;
    }
    public function updateStatusCancel($ordId)
    {
        $request = $this->findOrFail($ordId);
        $request->ord_status = 'cancel';
        $request->save();
        return $request;
    }
    /**
     * 의뢰 등록자 배정
     *
     * @param String $ordId 의뢰 아이디
     * @param String $updateCharger 배정할 등록자
     * @return \Api\Models\Request
     */
    public function updateCharger($ordId, $updateCharger)
    {

        $request = $this->findOrFail($ordId);

        $request->ord_work_id = $updateCharger;
        $request->save();

        return $request;
    }



    public function getRequestByStatus($type, $status)
    {
        $query = $this->getRequestByType($type);
    }

    public function deleteAttach($ordId)
    {
        $request = $this->findOrFail($ordId);
        dd($request);
        // $ret = $request->delete();
        // return $ret;
    }
}
