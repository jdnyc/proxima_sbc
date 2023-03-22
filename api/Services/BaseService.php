<?php

namespace Api\Services;

use Illuminate\Database\Eloquent\Builder;
use Interop\Container\ContainerInterface;
use \Illuminate\Database\Capsule\Manager as DB;

abstract class BaseService
{
    /**
     * @var \Interop\Container\ContainerInterface
     */
    protected $container;

    protected $db;

    /**
     * BaseService constructor.
     *
     * @param \Interop\Container\ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->db = $container->get('db');
    }

    /**
     * 데이터 조회 시 사용자 필드에 대한 객체를 같이 조회 할때 사용
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $foreignKey
     * @return void
     */
    protected function includeUser(Builder $query, string $foreignKey, array $fields = null)
    {
        $defaultFields = ['member_id', 'user_id', 'user_nm', 'dept_nm'];
        if ($fields !== null && is_array($fields)) {
            $fields = array_merge($defaultFields, $fields);
        } else {
            $fields = $defaultFields;
        }

        $query->with([$foreignKey => function ($q) use ($fields) {
            $q->select($fields);
        }]);
    }

    /**
     * 코드 아이템 배열에서 코드로 코드아이템을 찾아서 리턴
     *
     * @param array|\Illuminate\Database\Eloquent\Collection $codeItems
     * @param string $code
     * @return \Api\Models\DataDicCodeItem|null
     */
    public static function getCodeItemByCode($codeItems, $code)
    {
        foreach ($codeItems as $codeItem) {
            if ($codeItem->code_itm_code === $code) {
                return $codeItem;
            };
        }
        return null;
    }
    /**
     * 코드 아이템 배열에서 코드로 코드아이템을 찾아서 리턴 + dp = 1인 값을 찾아서 중분류 값으로
     *
     * @param array|\Illuminate\Database\Eloquent\Collection $codeItems
     * @param string $code
     * @return \Api\Models\DataDicCodeItem|null
     */
    public static function getCodeItemByCodeMlsfc($codeItems, string $code)
    {
        foreach ($codeItems as $codeItem) {
            if ($codeItem->code_itm_code === $code && $codeItem->dp == 1) {
                return $codeItem;
            };
        }
        return null;
    }
    /**
     * 코드 아이템 배열에서 코드로 코드아이템을 찾아서 리턴 + dp = 2인 값을 찾아서 소분류 값으로
     *
     * @param array|\Illuminate\Database\Eloquent\Collection $codeItems
     * @param string $code
     * @return \Api\Models\DataDicCodeItem|null
     */
    public static function getCodeItemByCodeSclas($codeItems, string $code)
    {
        foreach ($codeItems as $codeItem) {
            if ($codeItem->code_itm_code === $code && $codeItem->dp == 2) {
                return $codeItem;
            };
        }
        return null;
    }

    
    public static function getSequence($seq_name)
	{
        $seq_name = trim($seq_name);
        $id = DB::selectOne("select $seq_name.nextval from dual");
        return $id->nextval;
    }
    
    
    
    /**
     * 데이터 조회 시 include 처리 함수
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $foreignKey
     * @return void
     */
    public static function includes(Builder $query, string $foreignKey, array $fields = null)
    {
        $foreignKey = camel_case($foreignKey);
        $query->with([$foreignKey => function ($q) use ($fields) {
            if($fields){
                $q->select($fields);
            }
        }]);
    }
}
