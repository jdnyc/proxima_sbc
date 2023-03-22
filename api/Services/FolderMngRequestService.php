<?php

namespace Api\Services;

use Api\Services\BaseService;
use Api\Models\User;
use Api\Models\FolderMngRequest;

use Api\Types\FolderMngRequestStatus;

use \Api\Services\DTOs\FolderMngRequestDto;


class FolderMngRequestService extends BaseService
{
    public function list($data,$user)
    {
        $hasAdmin = $user->hasAdminGroup();
        $userId = $user->user_id;
        $query = FolderMngRequest::query();
        
        // 관리자가 아니라면 관련목록만 나오기
        if(!$hasAdmin)
            $query->where('regist_user_id','=',$userId);
        
        // 일자 관련 검색
        $searchDateType = $data['search_date_type'];
        $startDate = dateToStr($data['start_date'], 'YmdHis');
        $endDate = dateToStr($data['end_date'], 'YmdHis');
        $query->whereBetween($searchDateType, [$startDate,$endDate]);
        
        $this->includeUser($query, 'registerer');
        $this->includeUser($query, 'approval');

        $requests = $query->orderBy('created_at', 'DESC')->get();
        return $requests;
    }
       /**
     * 상세 조회 또는 $실패 처리
     *
     * @param integer $id
     * @return collection
     */
    public function findOrFail($id)
    {
        $collection = $this->find($id);
        if (!$collection) {
            api_abort_404('FolderMng');
        }
        return $collection;
    }

    public function find($id)
    {
        $query = FolderMngRequest::query();
        return $query->find($id);
    }

    public function getByPgmId($pgmId)
    {
        $query = FolderMngRequest::query();
        $query->where('pgm_id', '=', $pgmId);
        $folderMngRequest = $query->first();
        return $folderMngRequest;
    }

    /**
     * 신청
     *
     * @param \Api\Services\DTOs\FolderMngRequestDto $dto
     * @param \Api\Models\User $user
     * @return \Api\Models\FolderMngRequest 생성된 제작폴더신청 테이블 객체
     */
    public function create(FolderMngRequestDto $dto,User $user)
    {
        $collection = new FolderMngRequest();

        foreach($dto->toArray() as $key => $val){
            $collection->$key = $val;
        };
        
        $collection->status = FolderMngRequestStatus::REQUEST;
        $collection->regist_user_id = $user->user_id;
        $collection->updt_user_id = $user->user_id;
        $collection->save();
        return $collection;
    }
    /**
     * 수정
     * 
     * @param integer 폴더제작관리 신청 테이블 아이디
     * @param \Api\Services\DTOs\FolderMngRequestDto $dto 
     * @param \Api\Models\User $user 사용자 객체
     * @return \Api\Models\FolderMngRequest 수테이블 객체
     */
    public function update(int $id, FolderMngRequestDto $dto, User $user)
    {
        $collection = $this->findOrFail($id);
        foreach($dto->toArray() as $key => $val){
            $collection->$key = $val;
        };
        $collection->updt_user_id = $user->user_id;
        $collection->save();
        return $collection;
    }
    /**
     * 상태 변경
     *
     * @param integer $id
     * @param [type] $data
     * @param User $user
     * @return void
     */
    public function updateStatus(int $id, $data, User $user)
    {
        $collection = $this->findOrFail($id);
        $collection->status = $data['status'];
        $collection->updt_user_id = $user->user_id;
        $collection->approval_user_id = $user->user_id;
        $collection->save();
        return $collection;
    }
    /**
     * 삭제
     *
     * @param int $id ID
     * @return bool|null 삭제 성공여부
     */
    public function delete(int $id, User $user)
    {
        $collection = $this->findOrFail($id);
        $collection->updt_user_id = $user->user_id;
        $ret = $collection->delete();
        return $ret;
    }


    
}