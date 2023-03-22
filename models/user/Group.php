<?php

namespace Proxima\models\user;

use \Proxima\core\ModelBase;

/**
 * Group model class
 */
class Group extends ModelBase
{   
    public static $tableName = 'bc_member_group';

    /**
     * 그룹 조회
     *
     * @param mixed $id 그룹 아이디
     * @return Group Group 객체
     */
    public static function find($id)
    {
        global $db;

        $tableName = self::$tableName;
        $query = "SELECT * FROM {$tableName} WHERE member_group_id = {$id}";

        $row = $db->queryRow($query);

        return new Group($row);
    }

    /**
     * 전체 그룹 조회
     *
     * @return array Group 객체 배열
     */
    public static function all()
    {
        global $db;

        $tableName = self::$tableName;
        $query = "SELECT * FROM {$tableName} ORDER BY member_group_name ASC";

        $rows = $db->queryAll($query);

        $groups = [];
        foreach($rows as $row) {
            $groups[] = new Group($row);
        }

        return $groups;
    }

    /**
     * member id로 그룹 조회
     *
     * @param mixed $memberId member id
     * @return array Group 객체 배열
     */
    public static function findByMemberId($memberId)
    {
        global $db;

        $query = "SELECT * FROM bc_member_group g, bc_member_group_member mg WHERE g.MEMBER_GROUP_ID = mg.MEMBER_GROUP_ID
                AND mg.MEMBER_ID = {$memberId} ORDER BY member_group_name";         
        
        $rows = $db->queryAll($query);
        $groups = [];
        foreach($rows as $row) {
            $groups[] = new Group($row);
        }

        return $groups;          
    }

    /**
     * 그룹의 사용자 조회
     *
     * @return array User 객체 배열
     */
    public function users()
    {
        return User::findByGroupId($this->get('member_group_id'));
    }    

}