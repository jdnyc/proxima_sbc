<?php

namespace Api\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * 데이터 사전에서 도메인 관련 로직을 수행하는 Trait
 */
trait DataDicFieldIncludeTrait
{
    /**
     * 데이터 사전에서 필드를 같이 조회 할 경우
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $fields
     * @return void
     */
    public function includeField(Builder $query, array $fields = null)
    {
        $defaultFields = ['id', 'field_nm', 'field_eng_nm', 'domn_id'];
        if ($fields !== null && is_array($fields)) {
            $fields = array_merge($defaultFields, $fields);
        } else {
            $fields = $defaultFields;
        }

        $query->with(['field' => function ($q) use ($fields) {
            $q->select($fields);
        }]);
    }
}
