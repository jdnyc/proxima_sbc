<?php

namespace Proxima\models\system;

use \Proxima\core\ModelBase;

/**
 * Code model class
 */
class Code extends ModelBase
{   
    public static function find($id)
    {
        global $db;
        $query = "SELECT * FROM bc_code WHERE id = {$id}";
        $row = $db->queryRow($query);
        return new Code($row);
    }

    /**
     * 코드유형으로 코드 목록 조회
     *
     * @param string $codeType
     * @param array $order
     * @return array array of Code object
     */
    public static function getCodeList($codeType, $orders = [])
    {
        global $db;

        if(empty($codeType)) {
            throw new \Exception('Code type is empty.');
        }
        $codeType = strtoupper($codeType);
        
        $query = "SELECT c.* 
            FROM 
                bc_code c, bc_code_type ct
            WHERE
                c.code_type_id = ct.id
                AND ct.code = '{$codeType}'";

        if(!empty($orders)) {
            $orderArray = [];
            foreach($orders as $field => $direction) {
                $orderArray[] = "c.{$field} {$direction}";
            }
            $query .= " ORDER BY " . implode($orderArray);
        }
        
        $codes = [];
        $rows = $db->queryAll($query);
        foreach($rows as $row) {
            $codes[] = new Code($row);
        }

        return $codes;
    }

    /**
     * 코드유형과 코드로 일치하는 코드 조회
     *
     * @param string $codeType
     * @param string $code
     * @return Code Code object
     */
    public static function getCode($codeType, $code)
    {
        global $db;

        if(empty($codeType)) {
            throw new \Exception('codeType is empty.');
        }
        
        if(empty($code) && $code == '') {
            throw new \Exception('code is empty.');
        }
        $codeType = strtoupper($codeType);
        $query = "SELECT c.* 
            FROM 
                bc_code c, bc_code_type ct
            WHERE
                c.code_type_id = ct.id
                AND ct.code = '{$codeType}'
                AND c.code = '{$code}'";
        
        $row = $db->queryRow($query);

        return new Code($row);
    }
}