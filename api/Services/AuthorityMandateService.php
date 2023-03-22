<?php

namespace Api\Services;

use Api\Models\AuthorityMandate;

class AuthorityMandateService extends BaseService
{
    /**
     * 권한 승계 수임자 목록 조회
     * @param \Api\Models\User $user 사용자 객체
     * @param bool $hasAdmin true:관리자, false:관리자 아님
     * @return \Api\Models\AuthorityMandate
     */
    public function getMandataryListByMandator($data,$user,$hasAdmin)
    {
        $userId = $user->user_id;
        $query = AuthorityMandate::query();

        $this->includeUser($query, 'mandataryInfo');
        $this->includeUser($query, 'mandatorInfo');

        if(!$hasAdmin){
            // 관리자가 아니면 관련 목록만
            $query->where('mandator', $userId)->orWhere('mandatary', $userId);
        }
    
        if(!is_null($data['start_date']) || !is_null($data['end_date'])){
            $startDate = dateToStr($data['start_date'], 'YmdHis');
            $endDate = dateToStr($data['end_date'], 'Ymd').'235959';
            $query->whereBetween('regist_dt', [$startDate,$endDate]);
        };
        
        
        $query->orderByDESC('regist_dt');

        $mandates = paginate($query);

        return $mandates;
    }
    public function getMandataryByUserId($user){
    $userId = $user->user_id;

    $query = AuthorityMandate::query();
        
    $this->includeUser($query, 'mandataryInfo');
    $this->includeUser($query, 'mandatorInfo');
    $query->where('mandatary', $userId);
    $query->where('end_dt', '>=', date('Y-m-d'));
    $query->orderByDESC('end_dt');
    $mandatary = $query->first();
   
    return $mandatary;
    }
    /**
     * 권한 승인 수임자 등록
     *
     * @param array 요청 파라메터(mandator 위임자, end_dt 만료 일자)
     * @param \Api\Models\User $user 사용자 객체
     * @return \Api\Models\AuthorityMandate 생성된 테이블 객체
     */
    public function create($data, $user)
    {
        $mandate = new AuthorityMandate();
        $mandate->mandator = $user->user_id;
        $mandate->mandatary = $data['mandatary'];
        $mandate->end_dt = $data['end_dt'];
        $mandate->authority = 'retore';
        $mandate->save();
        return $mandate;
    }
    /**
     * 권한 승계 조회
     *
     * @param integer $id
     * @return AuthorityMandate
     */
    public function find(int $id)
    {
        $query = AuthorityMandate::query();

        return $query->find($id);
    }

    /**
     * 권한 승계 조회 또는 실패 처리
     *
     * @param integer $id
     * @return AuthorityMandate
     */
    public function findOrFail(int $id)
    {
        $mandates = $this->find($id);
        if (!$mandates) {
            api_abort_404(AuthorityMandate::class);
        }
        return $mandates;
    }
    /**
     * 권한 승계 수정
     *
     * @param integer $authorityMandateId
     * @param \Api\Models\User $user 사용자 객체
     * @param array $data 요청 파라메터
     * @param bool $hasAdmin true:관리자, false:관리자 아님
     * @return \Api\Models\AuthorityMandate 수정된 권한 승계 객체
     */
    public function update(int $authorityMandateId, $data, $user, $hasAdmin)
    {
        $mandate = $this->findOrFail($authorityMandateId);

        
        // 위임자 아이디
        $mandatorId = json_decode($data['record'])->mandator_info->user_id;

        if($hasAdmin || ($mandatorId == $user->user_id)){
            // 관리자 이거나 위임자만 수정할 수 있다.
            $mandate->mandatary = $data['mandatary'];
            $mandate->end_dt = $data['end_dt'];
            $mandate->authority = 'retore';
            $mandate->save();
            return $mandate;
        }else{
            return false;
        };
    }

    /**
     * 권한 승계 삭제
     *
     * @param integer $authorityMandateId
     * @param \Api\Models\User $user 사용자 객체
     * @param bool $hasAdmin true:관리자, false:관리자 아님
     * @return bool|null 삭제 성공여부
     */
    public function delete(int $authorityMandateId, $user, $hasAdmin)
    {
        $mandates = $this->findOrFail($authorityMandateId);
        if($hasAdmin || ($mandates->mandator == $user->user_id)){
            $ret = $mandates->delete();
            return $ret;
        }else{
            return false;
        };
        
    }
}
