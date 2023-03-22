<?php

namespace Api\Services;

use Api\Models\User;
use Api\Models\DataDicColumn;
use Api\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Api\Services\DTOs\DataDicColumnDto;
use Api\Traits\DataDicFieldIncludeTrait;
use Api\Services\DTOs\DataDicColumnSearchParams;

/**
 * 데이터 사전 컬럼 서비스
 */
class DataDicColumnService extends BaseService
{
    /**
     * 데이터 사전 컬럼 목록 조회
     *
     * @param DataDicColumnSearchParams $params
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    use DataDicFieldIncludeTrait;


    public function list(DataDicColumnSearchParams $params)
    {
        $keyword = $params->keyword;
        $is_deleted = $params->is_deleted;
        //쿼리 조건 삭제여부
        $query = DataDicColumn::query();
        if ($is_deleted) {
            $query->onlyTrashed();
        }
        
        $this->includeUser($query, 'registerer');
        $this->includeUser($query, 'updater');

        $this->includeField($query);

        if (!is_null($keyword)) {
            $query->where(function ($q) use ($keyword) {
                // dd($query);
                $q->where('column_nm', 'like', "%{$keyword}%")
                    ->orWhere('column_eng_nm', 'like', "%{$keyword}%")
                    ->orWhere('dc', 'like', "%{$keyword}%");
            });
        }

        $columns = paginate($query);
        return $columns;
    }

    /**
     * 데이터 사전 컬럼 상세 조회(column id로 조회)
     *
     * @param integer $id
     * @return DataDicColumn
     */
    public function find(int $id)
    {
        $query = DataDicColumn::query();

        $this->includeUser($query, 'registerer');
        $this->includeUser($query, 'updater');
        $this->includeField($query);

        return $query->find($id);
    }

    /**
     * 테이블별 컬럼 목록 조회(table_id로 조회)
     *
     * @param integer $tableId
     * @return DataDicColumn[]
     */
    public function getColumnsByTableId(int $tableId,$request)
    {
      
        $query = DataDicColumn::query();
        $this->includeUser($query, 'registerer');
        $this->includeUser($query, 'updater');
        $this->includeField($query);
      
        // $query->where('table_id', $tableId);
        $query->where('table_id',$request->all()['table_id']);
        $query->orderBy('ordr');
        // return $query->offset($request->start)->limit($request->limit)->get();
        // return $query->get();
        return paginate($query);
    }

    /**
     * 테이블별 컬럼 삭제
     *
     * @param integer $tableId
     * @return void
     */
    public function deleteColumnsByTableId(int $tableId)
    {
        $columns = $this->getColumnsByTableId($tableId);
        foreach ($columns as $column) {
            $ret = $column->delete();
        }
        // $ret = $columns->delete();
        return $ret;
    }
    /**
     * 필드별 컬럼 목록 조회(table_id로 조회)
     *
     * @param integer $fieldId 필드아이디
     * @param string $stdYn 표준 여부
     * @return DataDicColumn[]
     */
    public function getColumnsByFieldId(int $fieldId, string $stdYn = null)
    {
        $query = DataDicColumn::where('field_id', $fieldId);
        if ($stdYn) {
            $query->where('std_yn', $stdYn);
        }

        $this->includeUser($query, 'registerer');
        $this->includeUser($query, 'updater');
        $this->includeField($query);
        $query->with('table');

        // return $query->get();
        return paginate($query);
    }


    /**
     * 데이터 사전 컬럼 상세 조회 또는 실패 처리
     *
     * @param integer $id
     * @return DataDicColumn
     */
    public function findOrFail(int $id)
    {
        $column = $this->find($id);
        if (!$column) {
            api_abort_404('DataDicColumn');
        }
        return $column;
    }

    /**
     * 데이터 사전 컬럼 생성
     *
     * @param \Api\Services\DTOs\DataDicColumnDto $dto 컬럼 생성 데이터
     * @param \Api\Models\User $user 사용자 객체
     * @return \Api\Models\DataDicColumn 생성된 컬럼 객체
     */
    public function create(DataDicColumnDto $dto, User $user)
    {
        $column = new DataDicColumn();
        $column->table_id = $dto->table_id;
        $column->std_yn = $dto->std_yn;
        $column->column_nm = $dto->column_nm;
        $column->column_eng_nm = $dto->column_eng_nm;
        $column->field_id = $dto->field_id;
        $column->data_ty = $dto->data_ty;
        $column->data_lt = $dto->data_lt;
        $column->data_dcmlpoint = $dto->data_dcmlpoint;
        $column->pk_yn = $dto->pk_yn;
        $column->nn_yn = $dto->nn_yn;
        $column->ordr = $dto->ordr;
        $column->sttus_code = $dto->sttus_code;
        $column->dc = $dto->dc;
        $column->regist_user_id = $user->user_id;
        $column->updt_user_id = $user->user_id;
        $column->save();

        return $column;
    }

    /**
     * 데이터 사전 컬럼 수정
     *
     * @param integer 수정할 컬럼 아이디
     * @param \Api\Services\DTOs\DataDicColumnDto $dto 컬럼 수정 데이터
     * @param \Api\Models\User $user 사용자 객체
     * @return \Api\Models\DataDicColumn 수정된 컬럼 객체
     */
    public function update(int $columnId, DataDicColumnDto $dto, User $user)
    {
        $column = $this->findOrFail($columnId);
        $column->table_id = $dto->table_id;
        $column->std_yn = $dto->std_yn;
        $column->column_nm = $dto->column_nm;
        $column->column_eng_nm = $dto->column_eng_nm;
        $column->field_id = $dto->field_id;
        $column->data_ty = $dto->data_ty;
        $column->data_lt = $dto->data_lt;
        $column->data_dcmlpoint = $dto->data_dcmlpoint;
        $column->pk_yn = $dto->pk_yn;
        $column->nn_yn = $dto->nn_yn;
        $column->ordr = $dto->ordr;
        $column->sttus_code = $dto->sttus_code;
        $column->dc = $dto->dc;
        $column->regist_user_id = $user->user_id;
        $column->updt_user_id = $user->user_id;
        $column->save();

        return $column;
    }

    /**
     * 데이터 사전 컬럼 삭제
     *
     * @param integer $columnId 삭제할 컬럼 아이디
     * @param User $user
     * @return bool|null 삭제 성공여부
     */
    public function delete(int $columnId, User $user)
    {
        $column = $this->findOrFail($columnId);
        $ret = $column->delete();

        return $ret;
    }

    /**
     * 데이터 사전 컬럼 복원
     *
     * @param integer $columnId 복원할 컬럼 아이디
     * @param User $user
     * @return bool|null 복원 성공여부
     */
    public function restore(int $columnId)
    {
        $column = DataDicColumn::onlyTrashed()
            ->where('id', $columnId)
            ->first();

        if (!$column) {
            api_abort_404('DataDicColumn');
        }

        $ret = $column->restore();

        return $ret;
    }
}
