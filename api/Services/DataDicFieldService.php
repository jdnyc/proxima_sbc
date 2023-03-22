<?php

namespace Api\Services;

use Api\Models\User;
use Api\Models\DataDicField;
use Api\Models\DataDicColumn;
use Api\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Api\Services\DTOs\DataDicFieldDto;
use Api\Traits\DataDicDomainIncludeTrait;

use Api\Services\DTOs\DataDicFieldSearchParams;

/**
 * 데이터 사전 필드 서비스
 */
class DataDicFieldService extends BaseService
{
    /**
     * 데이터 사전 필드 목록 조회
     *
     * @param DataDicFieldSearchParams $params
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    use DataDicDomainIncludeTrait;


    public function list(DataDicFieldSearchParams $params)
    {
        $keyword = $params->keyword;
        $is_deleted = $params->is_deleted;
        //쿼리 조건 삭제여부
        $query = DataDicField::query();
        if ($is_deleted) {
            $query->onlyTrashed();
        }

        $this->includeUser($query, 'registerer');
        $this->includeUser($query, 'updater');
        $this->includeDomain($query);

        if (!is_null($keyword)) {
            $query->where(function ($q) use ($keyword) {
                // dd($query);
                $q->where('field_nm', 'like', "%{$keyword}%")
                    ->orWhere('field_eng_nm', 'like', "%{$keyword}%")
                    ->orWhere('dc', 'like', "%{$keyword}%");
            });
        }

        $fields = paginate($query);
        return $fields;
    }

    /**
     * 데이터 사전 필드 상세 조회
     *
     * @param integer $id
     * @return DataDicField
     */
    public function find(int $id)
    {
        $query = DataDicField::query();

        $this->includeUser($query, 'registerer');
        $this->includeUser($query, 'updater');
        $this->includeDomain($query);

        return $query->find($id);
    }

    /**
     * 데이터 사전 필드 상세 조회
     *
     * @param integer $id
     * @return DataDicField
     */
    public function findField(int $id)
    {
        $query = DataDicField::query();
        return $query->find($id);
    }

    /**
     * 필드 field_eng_nm 으로 컬럼 조회
     * @param string $field_eng_nm
     * @return DataDicColumn
     */
    public function columns(string $field_eng_nm)
    {
        $query = DataDicColumn::query();
        $query->with('table');
        $query->where('column_eng_nm', $field_eng_nm);

        return $query->get();
    }

    /**
     * 데이터 사전 필드 상세 조회 또는 실패 처리
     *
     * @param integer $id
     * @return DataDicField
     */
    public function findOrFail(int $id)
    {
        $field = $this->find($id);
        if (!$field) {
            api_abort_404('DataDicField');
        }
        return $field;
    }

    /**
     * 데이터 사전 필드 생성
     *
     * @param \Api\Services\DTOs\DataDicFieldDto $dto 필드 생성 데이터
     * @param \Api\Models\User $user 사용자 객체
     * @return \Api\Models\DataDicField 생성된 필드 객체
     */
    public function create(DataDicFieldDto $dto, User $user)
    {
        $field = new DataDicField();
        $field->field_nm = $dto->field_nm;
        $field->field_eng_nm = $dto->field_eng_nm;
        $field->domn_id = $dto->domn_id;
        $field->sttus_code = $dto->sttus_code;
        $field->dc = $dto->dc;
        $field->regist_user_id = $user->user_id;
        $field->updt_user_id = $user->user_id;
        $field->save();
        return $field;
    }

    /**
     * 데이터 사전 필드 수정
     *
     * @param integer 수정할 필드 아이디
     * @param \Api\Services\DTOs\DataDicFieldDto $dto 필드 수정 데이터
     * @param \Api\Models\User $user 사용자 객체
     * @return \Api\Models\DataDicField 수정된 필드 객체
     */
    public function update(int $fieldId, DataDicFieldDto $dto, User $user)
    {
        $field = $this->findOrFail($fieldId);
        $field->field_nm = $dto->field_nm;
        $field->field_eng_nm = $dto->field_eng_nm;
        $field->domn_id = $dto->domn_id;
        $field->sttus_code = $dto->sttus_code;
        $field->dc = $dto->dc;
        $field->regist_user_id = $user->user_id;
        $field->updt_user_id = $user->user_id;
        $field->save();

        return $field;
    }

    /**
     * 데이터 사전 필드 삭제
     *
     * @param integer $fieldId 삭제할 필드 아이디
     * @param User $user
     * @return bool|null 삭제 성공여부
     */
    public function delete(int $fieldId, User $user)
    {
        $field = $this->findOrFail($fieldId);
        $ret = $field->delete();

        return $ret;
    }

    /**
     * 데이터 사전 필드 복원
     *
     * @param integer $fieldId 복원할 필드 아이디
     * @param User $user
     * @return bool|null 복원 성공여부
     */
    public function restore(int $fieldId)
    {
        $field = DataDicField::onlyTrashed()
            ->where('id', $fieldId)
            ->first();

        if (!$field) {
            api_abort_404('DataDicField');
        }

        $ret = $field->restore();

        return $ret;
    }

    public function searchByName($keyword, $key = 'field_nm')
    {
        $query = DataDicField::query();
        $query->where($key, '=', "{$keyword}");
        return $query->get();
    }
}
