<?php

namespace Api\Services;

use Api\Models\User;
use Api\Models\DataDicDomain;
use Api\Models\DataDicCodeSet;
use Api\Models\DataDicCodeItem;
use Api\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Api\Services\DTOs\DataDicDomainDto;
use Api\Services\DTOs\DataDicDomainSearchParams;

/**
 * 데이터 사전 도메인 서비스
 */
class DataDicDomainService extends BaseService
{
    /**
     * 데이터 사전 도메인 목록 조회
     *
     * @param DataDicDomainSearchParams $params
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(DataDicDomainSearchParams $params)
    {
        $keyword = $params->keyword;
        $is_deleted = $params->is_deleted;
        //쿼리 조건 삭제여부
        $query = DataDicDomain::query();
        if ($is_deleted) {
            $query->onlyTrashed();
        }

        $this->includeUser($query, 'registerer');
        $this->includeUser($query, 'updater');

        if (!is_null($keyword)) {
            $query->where(function ($q) use ($keyword) {
                // dd($query);
                $q->where('domn_nm', 'like', "%{$keyword}%")
                    ->orWhere('domn_eng_nm', 'like', "%{$keyword}%")
                    ->orWhere('dc', 'like', "%{$keyword}%");
            });
        }

        $domains = paginate($query);
        return $domains;
    }

    /**
     * 데이터 사전 도메인 상세 조회
     *
     * @param integer $id
     * @return DataDicDomain
     */
    public function find(int $id)
    {
        $query = DataDicDomain::query();

        $this->includeUser($query, 'registerer');
        $this->includeUser($query, 'updater');

        return $query->find($id);
    }

    /**
     * 데이터 사전 도메인 상세 조회 또는 실패 처리
     *
     * @param integer $id
     * @return DataDicDomain
     */
    public function findOrFail(int $id)
    {
        $domain = $this->find($id);
        if (!$domain) {
            api_abort_404('DataDicDomain');
        }
        return $domain;
    }

    /**
     * 데이터 사전 도메인 생성
     *
     * @param \Api\Services\DTOs\DataDicDomainDto $dto 도메인 생성 데이터
     * @param \Api\Models\User $user 사용자 객체
     * @return \Api\Models\DataDicDomain 생성된 도메인 객체
     */
    public function create(DataDicDomainDto $dto, User $user)
    {
        $domain = new DataDicDomain();
        $domain->domn_mlsfc = $dto->domn_mlsfc;
        $domain->domn_sclas = $dto->domn_sclas;
        $domain->domn_ty = $dto->domn_ty;
        $domain->domn_eng_nm = $dto->domn_eng_nm;
        $domain->data_ty = $dto->data_ty;
        $domain->data_lt = $dto->data_lt;
        $domain->data_dcmlpoint = $dto->data_dcmlpoint;
        $domain->sttus_code = $dto->sttus_code;
        $domain->domn_nm = $dto->domn_nm;
        $domain->dc = $dto->dc;
        $domain->code_set_id = $dto->code_set_id;
        $domain->regist_user_id = $user->user_id;
        $domain->updt_user_id = $user->user_id;
        $domain->save();

        return $domain;
    }

    /**
     * 데이터 사전 도메인 수정
     *
     * @param integer 수정할 도메인 아이디
     * @param \Api\Services\DTOs\DataDicDomainDto $dto 도메인 수정 데이터
     * @param \Api\Models\User $user 사용자 객체
     * @return \Api\Models\DataDicDomain 수정된 도메인 객체
     */
    public function update(int $domainId, DataDicDomainDto $dto, User $user)
    {
        $domain = $this->findOrFail($domainId);
        $domain->domn_mlsfc = $dto->domn_mlsfc;
        $domain->domn_sclas = $dto->domn_sclas;
        $domain->domn_ty = $dto->domn_ty;
        $domain->domn_eng_nm = $dto->domn_eng_nm;
        $domain->domn_nm = $dto->domn_nm;
        $domain->data_ty = $dto->data_ty;
        $domain->data_lt = $dto->data_lt;
        $domain->data_dcmlpoint = $dto->data_dcmlpoint;
        $domain->sttus_code = $dto->sttus_code;
        $domain->dc = $dto->dc;
        $domain->code_set_id = $dto->code_set_id;
        $domain->regist_user_id = $user->user_id;
        $domain->updt_user_id = $user->user_id;
        $domain->save();
        return $domain;
    }

    /**
     * 데이터 사전 도메인 삭제
     *
     * @param integer $domainId 삭제할 도메인 아이디
     * @param User $user
     * @return bool|null 삭제 성공여부
     */
    public function delete(int $domainId, User $user)
    {
        $domain = $this->findOrFail($domainId);
        $ret = $domain->delete();

        return $ret;
    }

    /**
     * 데이터 사전 도메인 복원
     *
     * @param integer $domainId 복원할 도메인 아이디
     * @param User $user
     * @return bool|null 복원 성공여부
     */
    public function restore(int $domainId)
    {
        $domain = DataDicDomain::onlyTrashed()
            ->where('id', $domainId)
            ->first();

        if (!$domain) {
            api_abort_404('DataDicDomain');
        }

        $ret = $domain->restore();

        return $ret;
    }

    /**
     * 컬럼별 도메인 목록 조회(domain_id로 조회)
     *
     * @param integer $domainId 필드아이디
     * @return DataDicDomain[]
     */
    public function getColumnsByDomainId(int $domainId)
    {
        $query = DataDicDomain::find($domainId);

        // $this->includeUser($query, 'registerer');
        // $this->includeUser($query, 'updater');

        return $query;
    }
}
