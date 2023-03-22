<?php

namespace Api\Services;

use Api\Models\User;
use Api\Types\ApiJobType;
use Api\Services\BaseService;
use Api\Models\DataDicCodeSet;
use Api\Services\ApiJobService;
use Illuminate\Support\Facades\DB;
use Api\Services\DTOs\DataDicCodeSetDto;
use Api\Services\DTOs\DataDicCodeSetSearchParams;

/**
 * 데이터 사전 코드셋 서비스
 */
class DataDicCodeSetService extends BaseService
{
    /**
     * 데이터 사전 코드셋 목록 조회
     *
     * @param DataDicCodeSetSearchParams $params
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(DataDicCodeSetSearchParams $params, $includes = null )
    {
        $keyword = $params->keyword;
        $is_deleted = $params->is_deleted;
       
        //쿼리 조건 삭제여부
        $query = DataDicCodeSet::query();
        if ($is_deleted) {
            $query->onlyTrashed();
        }
        $this->includeUser($query, 'registerer');
        $this->includeUser($query, 'updater');
        if( !empty($includes) ){
            foreach($includes as $include){                
                $this->includes($query, $include);
            }
        }

        if (!is_null($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('code_set_nm', 'like', "%{$keyword}%")
                    ->orWhere('code_set_code', 'like', "%{$keyword}%")
                    ->orWhere('dc', 'like', "%{$keyword}%");
            });
        }

        $codeSets = paginate($query);

        return $codeSets;
    }

    /**
     * 데이터 사전 코드셋 상세 조회
     *
     * @param integer $id
     * @return DataDicCodeSet
     */
    public function find(int $id)
    {
        $query = DataDicCodeSet::query();

        $this->includeUser($query, 'registerer');
        $this->includeUser($query, 'updater');

        return $query->find($id);
    }

    /**
     * 데이터 사전 코드셋 상세 조회 또는 실패 처리
     *
     * @param integer $id
     * @return DataDicCodeSet
     */
    public function findOrFail(int $id)
    {
        $codeSet = $this->find($id);
        if (!$codeSet) {
            api_abort_404('DataDicCodeSet');
        }
        return $codeSet;
    }

    /**
     * 코드셋 코드로 코드셋 조회(없을 시 실패)
     *
     * @param string $codeSetCode
     * @return DataDicCodeSet
     */
    public function findByCodeOrFail(string $codeSetCode)
    {
        if (empty($codeSetCode)) {
            api_abort_404('DataDicCodeSet');
        }
        $codeSet = $this->findByCode($codeSetCode);
        if (!$codeSet) {
            api_abort_404('DataDicCodeSet');
        }
        return $codeSet;
    }

    /**
     * 코드셋 코드로 코드셋 조회
     *
     * @param string $codeSetCode
     * @return DataDicCodeSet
     */
    public function findByCode(string $codeSetCode)
    {
        if (empty($codeSetCode)) {
            return null;
        }
        $codeSet = DataDicCodeSet::where('code_set_code', $codeSetCode)->first();
        return $codeSet;
    }

    /**
     * 데이터 사전 코드셋 생성
     *
     * @param \Api\Services\DTOs\DataDicCodeSetDto $dto 코드셋 생성 데이터
     * @param \Api\Models\User $user 사용자 객체
     * @return \Api\Models\DataDicCodeSet 생성된 코드셋 객체
     */
    public function create(DataDicCodeSetDto $dto, User $user)
    {

        $codeSet = new DataDicCodeSet();
        $codeSet->code_set_nm = $dto->code_set_nm;
        $codeSet->code_set_code = $dto->code_set_code;
        $codeSet->dc = $dto->dc;
        $codeSet->code_set_cl = $dto->code_set_cl;
        $codeSet->regist_user_id = $user->user_id;
        $codeSet->updt_user_id = $user->user_id;
        $codeSet->save();

        $apiJobService = new ApiJobService();
        $apiJobService->createApiJob( __CLASS__, __FUNCTION__, $codeSet->toArray() , $codeSet->id );

        return $codeSet;
    }

    /**
     * 데이터 사전 코드셋 수정
     *
     * @param integer 수정할 코드셋 아이디
     * @param \Api\Services\DTOs\DataDicCodeSetDto $dto 코드셋 수정 데이터
     * @param \Api\Models\User $user 사용자 객체
     * @return \Api\Models\DataDicCodeSet 수정된 코드셋 객체
     */
    public function update(int $codeSetId, DataDicCodeSetDto $dto, User $user)
    {
        $codeSet = $this->findOrFail($codeSetId);
        $codeSet->code_set_nm = $dto->code_set_nm;
        $codeSet->code_set_code = $dto->code_set_code;
        $codeSet->dc = $dto->dc;
        $codeSet->code_set_cl = $dto->code_set_cl;
        $codeSet->regist_user_id = $user->user_id;
        $codeSet->updt_user_id = $user->user_id;
        $codeSet->save();

        $apiJobService = new ApiJobService();
        $apiJobService->createApiJob( __CLASS__, __FUNCTION__, $codeSet->toArray() , $codeSet->id );

        return $codeSet;
    }

    /**
     * 데이터 사전 코드셋 삭제
     *
     * @param integer $codeSetId 삭제할 코드셋 아이디
     * @param User $user
     * @return bool|null 삭제 성공여부
     */
    public function delete(int $codeSetId, User $user)
    {
        $codeSet = $this->findOrFail($codeSetId);
        $ret = $codeSet->delete();

        $apiJobService = new ApiJobService();
        $apiJobService->createApiJob( __CLASS__, 'update', $codeSet->toArray() , $codeSet->id );

        return $ret;
    }

    /**
     * 데이터 사전 코드셋 복원
     *
     * @param integer $codeSetId 복원할 코드셋 아이디
     * @param User $user
     * @return bool|null 복원 성공여부
     */
    public function restore(int $codeSetId)
    {
        $codeSet = DataDicCodeSet::onlyTrashed()
            ->where('id', $codeSetId)
            ->first();

        if (!$codeSet) {
            api_abort_404('DataDicCodeSet');
        }

        $ret = $codeSet->restore();

        return $ret;
    }
}
