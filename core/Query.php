<?php

/**
 * 쿼리 도움 클래스
 */
namespace Proxima\core;

class Query
{
    /**
     * 쿼리 문자열
     *
     * @var string
     */
    private $query;
    /**
     * where 조건 배열(key/value 형식)
     *
     * @var array
     */
    private $conditions;
    const VALID_OPERATORS = ['=', '!=', '<=', '>=', '>', '<', 'like', 'not like'];
    public function __construct($query)
    {
        $this->query = $query;
    }

    /**
     * where 조건 추가(operator 생략 시 =로 처리)
     *
     * @param array $condition
     * @return void
     */
    public function addWhere($field, $value, $operator = '=')
    {
        if (!in_array($operator, self::VALID_OPERATORS)) {
            $operator = '=';
        }
        $this->conditions[] = [$field, $operator, $value];
    }

    /**
     * AND 쿼리 생성
     *
     * @return string 생성된 쿼리
     */
    public function getAndQuery()
    {
        return $this->makeQuery();
    }

    /**
     * OR 쿼리 생성
     *
     * @return string 생성된 쿼리
     */
    public function getOrQuery()
    {
        return $this->makeQuery('OR');
    }

    /**
     * 쿼리 생성
     *
     * @param string $conditionType 조건문 유형(AND 또는 OR)
     * @return string 생성된 쿼리
     */
    private function makeQuery($conditionType = 'AND')
    {
        $conditionType = strtoupper($conditionType);
        if ($conditionType != 'AND' && $conditionType != 'OR') {
            $conditionType = 'AND';
        }
        if (empty($this->conditions)) {
            return $this->query;
        }
        $wheres = [];        
        foreach($this->conditions as $condition) {
            if (empty($condition)) {
                continue;
            }
            $wheres[] = "{$condition[0]} {$condition[1]} '{$condition[2]}'";
        }

        if (strpos(strtoupper($this->query), 'WHERE') === false) {
            $query = $this->query . ' WHERE ' . implode(" {$conditionType} ", $wheres);
        } else {
            $query = $this->query . " $conditionType (" . implode(" {$conditionType} ", $wheres) . ')';
        }
        

        return $query;
    }
}

