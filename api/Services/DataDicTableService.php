<?php

namespace Api\Services;

use Api\Models\User;
use Api\Models\DataDicTable;
use Api\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Api\Models\CodeType;
use Api\Models\Code;
use Api\Services\DTOs\DataDicTableDto;
use Api\Services\DTOs\DataDicTableSearchParams;

/**
 * 데이터 사전 테이블 서비스
 */
class DataDicTableService extends BaseService
{
    /**
     * 데이터 사전 테이블 목록 조회
     *
     * @param DataDicTableSearchParams $params
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(DataDicTableSearchParams $params)
    {
        $keyword = $params->keyword;
        $is_deleted = $params->is_deleted;
        //쿼리 조건 삭제여부
        $query = DataDicTable::query();
        if ($is_deleted) {
            $query->onlyTrashed();
        }

        $this->includeUser($query, 'registerer');
        $this->includeUser($query, 'updater');

        if (!is_null($keyword)) {
            $query->where(function ($q) use ($keyword) {
                // dd($query);
                $q->where('table_nm', 'like', "%{$keyword}%")
                    ->orWhere('table_eng_nm', 'like', "%{$keyword}%")
                    ->orWhere('dc', 'like', "%{$keyword}%");
            });
        }

        $tables = paginate($query);

        return $tables;
    }

    /**
     * 데이터 사전 테이블 상세 조회
     *
     * @param integer $id
     * @return DataDicTable
     */
    public function find(int $id)
    {
        $query = DataDicTable::query();

        $this->includeUser($query, 'registerer');
        $this->includeUser($query, 'updater');

        return $query->find($id);
    }


    /**
     * 데이터 사전 테이블 상세 조회 또는 실패 처리
     *
     * @param integer $id
     * @return DataDicTable
     */
    public function findOrFail(int $id)
    {
        $table = $this->find($id);
        if (!$table) {
            api_abort_404(DataDicTable::class);
        }
        return $table;
    }

    /**
     * 데이터 사전 테이블 생성
     *
     * @param \Api\Services\DTOs\DataDicTableDto $dto 테이블 생성 데이터
     * @param \Api\Models\User $user 사용자 객체
     * @return \Api\Models\DataDicTable 생성된 테이블 객체
     */
    public function create(DataDicTableDto $dto, User $user)
    {
        $table = new DataDicTable();
        $table->sys_code = $dto->sys_code;
        $table->table_nm = $dto->table_nm;
        $table->table_eng_nm = $dto->table_eng_nm;
        $table->sttus_code = $dto->sttus_code;
        $table->table_se = $dto->table_se;
        $table->dc = $dto->dc;
        $table->regist_user_id = $user->user_id;
        $table->updt_user_id = $user->user_id;
        $table->save();

        return $table;
    }

    /**
     * 데이터 사전 테이블 수정
     *
     * @param integer 수정할 테이블 아이디
     * @param \Api\Services\DTOs\DataDicTableDto $dto 테이블 수정 데이터
     * @param \Api\Models\User $user 사용자 객체
     * @return \Api\Models\DataDicTable 수정된 테이블 객체
     */
    public function update(int $tableId, DataDicTableDto $dto, User $user)
    {
        $table = $this->findOrFail($tableId);

        $table->sys_code = $dto->sys_code;
        $table->table_nm = $dto->table_nm;
        $table->table_eng_nm = $dto->table_eng_nm;
        $table->sttus_code = $dto->sttus_code;
        $table->table_se = $dto->table_se;
        $table->dc = $dto->dc;
        $table->updt_user_id = $user->user_id;
        $table->save();

        return $table;
    }

    /**
     * 데이터 사전 테이블 삭제
     *
     * @param integer $tableId 삭제할 테이블 아이디
     * @param User $user
     * @return bool|null 삭제 성공여부
     */
    public function delete(int $tableId, User $user)
    {
        $table = $this->findOrFail($tableId);
        $ret = $table->delete();

        return $ret;
    }

    /**
     * 데이터 사전 테이블 복원
     *
     * @param integer $tableId 복원할 테이블 아이디
     * @param User $user
     * @return bool|null 복원 성공여부
     */
    public function restore(int $tableId)
    {
        $table = DataDicTable::onlyTrashed()
            ->where('id', $tableId)
            ->first();

        if (!$table) {
            api_abort_404('DataDicTable');
        }

        $ret = $table->restore();

        return $ret;
    }
}
