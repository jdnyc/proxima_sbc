<?php

namespace Api\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * 데이터 사전에서 도메인 관련 로직을 수행하는 Trait
 */
trait DataDicDomainIncludeTrait
{
    /**
     * 데이터 사전에서 도메인을 같이 조회 할 경우
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $fields
     * @return void
     */
    public function includeDomain(Builder $query, array $fields = null)
    {
        $defaultFields = ['id', 'domn_nm', 'domn_eng_nm', 'data_ty', 'data_lt'];
        if ($fields !== null && is_array($fields)) {
            $fields = array_merge($defaultFields, $fields);
        } else {
            $fields = $defaultFields;
        }

        $query->with(['domain' => function ($q) use ($fields) {
            $q->select($fields);
        }]);
    }
}
