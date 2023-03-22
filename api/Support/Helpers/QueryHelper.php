<?php

namespace Api\Support\Helpers;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Builder;
use Interop\Container\ContainerInterface;

class QueryHelper
{
    /**
     * 쿼리 빌더
     *
     * @var \Illuminate\Database\Eloquent\Builder
     */
    private $query;

    /**
     * 최대 페이지 당 결과 수
     */
    const MAX_RESULTS = 1000;

    /**
     * PaginateHelper constructor.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    public function __construct(Builder $query)
    {
        $this->query = $query;       
        // sort처리
        $request = request();
        $sorted = false;
        $model = $this->query->make();
        
        if ($request->sort) {
            if (in_array($request->sort, $model->sortable)) {
                $this->query->orderBy($request->sort, $request->dir);
                $sorted = true;
            }
        }

        // default sort 처리
        if (!$sorted && $model->sort && $model->dir ) {
            $this->query->orderBy($model->sort, $model->dir);
        }
    }

    /**
     * request에서 페이징 관련 파라메터를 받아서 쿼리빌더에 조건을 추가해주는 함수
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($start = null, $limit = null)
    {
        $request = request();
        if ($start === null && $limit === null) {
            $start = $request->start;
            $limit = $request->limit;
        }

        $limit = $limit > self::MAX_RESULTS ? self::MAX_RESULTS : $limit;

        $page = 1;
        if ($start > 0) {
            $page = (int) ($start / $limit) + 1;
        }
        $paginator = $this->query
            ->paginate($limit, ['*'], 'page.number', $page)
            ->setPageName('page[number]')
            ->appends(Arr::except($request->input(), 'page.number'));

        return $paginator;
    }

    public function query()
    {
        return $this->query;
    }
}
