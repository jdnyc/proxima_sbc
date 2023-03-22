<?php

namespace Api\Services;

use Api\Models\Group;
use Api\Models\MemberRequest;
use Api\Models\User;
use Api\Models\MemberRequestProgram;
use Api\Services\GroupService;
use Api\Types\MemberStatus;

class MemberRequestService extends BaseService
{
    /**
     * 단건 조회
     *
     * @param integer $id
     * @return MemberRequest
     */
    public function find($id)
    {
        $query = MemberRequest::query();
       
        return $query->find($id);
    }

    /**
     * 단건 조회 또는 실패 처리
     *
     * @param integer $id
     * @return MemberRequest
     */
    public function findOrFail($id)
    {
        $user = $this->find($id);
        if (!$user) {
            api_abort_404('Member');
        }
        
        return $user;
    }
        /**
     * 단건 조회
     *
     * @param integer $id
     * @return MemberRequest
     */
    public function findProgram($id)
    {
        $query = MemberRequestProgram::query();
       
        return $query->find($id);
    }

    /**
     * 단건 조회 또는 실패 처리
     *
     * @param integer $id
     * @return MemberRequest
     */
    public function findOrFailProgram($id)
    {
        $user = $this->findProgram($id);
        if (!$user) {
            api_abort_404('MemberProgram');
        }
        
        return $user;
    }
    /**
     * 사용자 아이디 조회 (존재 유무에 따라 true 와 false를 return)
     * 
     * @param [type] $memberId
     * @return boolean true 중복 false 중복 아님
     */
    public function existsUser($userId)
    {
        
        $query = MemberRequest::query();
        $userQuery = User::query();
        $exists = $query->where('user_id','=',$userId)->exists();
        $userExists = $userQuery->where('user_id','=',$userId)->exists();
        if(!$exists && !$userExists){
            return false;
        }else{
            return true;
        }
    }

    /**
     * 사용자 아이디 신청
     *
     * @param Array $data
     * @return \Api\Models\MemberRequest 사용자 테이블
     */
    public function requestUser($data){
        $user = new MemberRequest();

        $user->dept = $data['dept'];
        $user->user_id = $data['user_id'];
        $user->user_nm = $data['user_nm'];
        $user->mnfct_se = $data['mnfct_se'];
        // $user->progrm_nm = $data['progrm_nm'];
        // $user->progrm_id = $data['progrm_id'];
        $user->charger_id = $data['charger_id'];
        // $user->password =  hash('sha512', $data['password']);
        $user->password =  $data['password'];
        $user->phone = $data['phone'];
        $user->instt = $data['instt'];
        $user->lxtn_no = $data['lxtn_no'];
        $user->use_purps = $data['use_purps'];
        $user->status = MemberStatus::REQUEST;
        $user->pd_status = MemberStatus::REQUEST;
        $user->save();
        if($user->save()){          
            if(!($data['program_list'] == 'null')){
             
              $progs = json_decode($data['program_list']);
              
                foreach($progs as $prog){
                    $program = new MemberRequestProgram();
                    $program->folder_path = $prog->folder_path;
                    $program->folder_path_nm = $prog->folder_path_nm;
                    $program->pgm_id = $prog->pgm_id;
                    $program->member_request_id = $user->id;
                    $program->save();
                }
            };
        }
        return $user;
    }
    /**
     * 사용자 신청 요청 목록
     *
     * @return \Api\Models\MemberRequest
     */
    public function requestUsersList($user,$data){
        $userId = $user->user_id;
        $query = MemberRequest::query();
        $this->includeUser($query, 'charger');
        $isAdmin = $data['is_admin'];

        $hasAdmin = auth()->user()->hasAdminGroup();
            
        
        if($data['status'] != MemberStatus::ALL)
            $query->where('status', '=', $data['status']);
            
        if($data['pd_status'] != MemberStatus::ALL)
            $query->where('pd_status', '=', $data['pd_status']);
            
        if(!$hasAdmin){
            if($isAdmin != "true")
                $query->where('charger_id', '=', $userId);
        }
        
        
        $startDate = dateToStr($data['start_date'], 'YmdHis');
        $endDate = dateToStr($data['end_date'], 'Ymd').'235959';
        $query->whereBetween('regist_dt', [$startDate,$endDate]);

        
    
        $query = $query->orderBy('id','desc');
        $users = paginate($query);

        return $users;
    }

    /**
     * 사용자 신청 아이디 상태 승인 또는 반려 하기
     *
     * @param String $id pk
     * @param \Api\Types\MemberStatus $changeStatus 바꿀 상태
     * @return \Api\Models\MemberRequest
     * 
     */
    public function changeStatus($id, $changeStatus){
        $user = $this->findOrFail($id);
        if($user->status == MemberStatus::REQUEST){
            $user->status = $changeStatus;
            if ($changeStatus == MemberStatus::APPROVAL) {
                $user->compt_dt = dateToStr('now', 'YmdHis');
            };
        
            $user->save();
        }
        return $user;
    }

        /**
     * 사용자 신청 아이디 PD상태 승인 또는 반려 하기
     *
     * @param String $id pk
     * @param \Api\Types\MemberStatus $changeStatus 바꿀 상태
     * @return \Api\Models\MemberRequest
     * 
     */
    public function changePdStatus($id, $changeStatus){
        $user = $this->findOrFail($id);
        if($user->pd_status == MemberStatus::REQUEST){
            $user->pd_status = $changeStatus;
            if ($changeStatus == MemberStatus::APPROVAL) {
                $user->compt_dt = dateToStr('now', 'YmdHis');
            };
            $user->save();
        }
        return $user;
    }

    public function requestUserUpdate($id, $data)
    {
        
        $progDuplicationCheck = [];
        $user = $this->findOrFail($id);
        $user->dept = $data['dept'];
        $user->charger_id = $data['charger_id'];
        $user->mnfct_se = $data['mnfct_se'];
        $user->progrm_nm = $data['progrm_nm'];
        $user->progrm_id = $data['progrm_id'];
        $user->user_id = $data['user_id'];
        $user->phone = $data['phone'];
        $user->instt = $data['instt'];
        $user->lxtn_no = $data['lxtn_no'];
        $user->use_purps = $data['use_purps'];
        
        if(!($data['program_list'] == 'null')){
            $progs = json_decode($data['program_list']);
            
            $programs = MemberRequestProgram::query()->where('member_request_id', $id)->get();
            // if($program->pgm_id != $prog->pgm_id){
            //     $memberProgramFind = $this->findOrFailProgram($id);
            //     dump($memberProgramFind);
            // };
            /**
             * 1. [1,2,3,4,5,6,7,]
             * 2. [2,3]
             * 3. 지워야 할건 [1,4,5,6,7]
             * 
             */
            foreach($programs as $program){
                foreach($progs as $prog){
                    if($program->id == $prog->id){
                        $program->deleteCheck = true;
                    }
                }   
                if(is_null($program->deleteCheck)){
                    $p = $this->findOrFailProgram($program->id);
                    $p->delete();
                };
            };

              foreach($progs as $prog){                                                                                            
                if(is_null($prog->member_request_id)){
                    $program = new MemberRequestProgram();
                    $program->folder_path = $prog->folder_path;
                    $program->folder_path_nm = $prog->folder_path_nm;
                    $program->pgm_id = $prog->id;
                    $program->member_request_id =$id;
                    $program->save();
                };           
              }
        };

        $user->save();

        return $user;
    }
}