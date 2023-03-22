<?php

namespace Proxima\models\search;

use \Proxima\core\ModelBase;

/**
 * CustomSearch model class
 */
class CustomSearch extends ModelBase
{
    private static $table = 'bc_custom_search';
    
    /**
     * 사용자의 커스텀 검색 목록을 불러옴
     *
     * @param string $userId 사용자 아이디
     * @return array CustomSearch 객체 배열
     */
    public static function findByUserId($userId)
    {
        global $db;
        $tableName = self::$table;
        // ?common은 공통 사용 항목임
        $query = "SELECT * FROM {$tableName} WHERE user_id='{$userId}' OR user_id='?common' ORDER BY show_order";
        $rows = $db->queryAll($query);

        $customSearchList = [];
        foreach($rows as $row) {
            if(!empty($row['filters'])) {
                $row['filters'] = json_decode($row['filters'], true);
            }
            $customSearchList[] = new CustomSearch($row);
        }

        return $customSearchList;
    }

    /**
     * 커스텀 검색 객체를 DB에 저장
     *
     * @return void
     */
    public function save()
    {
        $id = $this->get('id');
        if(empty($id)) {
            if(empty($this->get('show_order'))) {
                $showOrder = $this->getMaxShowOrder();
                $this->set('show_order', $showOrder === NULL ? 1 : $showOrder);
            }
            $values = $this->data;
            unset($values['id']);
            // 신규
            ModelBase::insert(self::$table, $values);            
        }        
    }

    /**
     * 사용자 별 가장 큰 정렬 순서 구하기
     *
     * @return void
     */
    public function getMaxShowOrder()
    {
        global $db;
        $tableName = self::$table;
        $userId = $this->get('user_id');
        $query = "SELECT MAX(show_order) + 1 FROM {$tableName} WHERE user_id='{$userId}' OR user_id='?common'";
        $maxShowOrder = $db->queryOne($query);

        return $maxShowOrder;
    }

    /**
     * 삭제
     *
     * @param mixed $id PK
     * @return void
     */
    public static function delete($id, $_ = null)
    {
        $where = " id = {$id}";
        ModelBase::delete(self::$table, $where);
    }
}