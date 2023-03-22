<?php

namespace Proxima\models\user;

use \Proxima\core\ModelBase;

/**
 * User model class
 */
class User extends ModelBase
{   
    public static $tableName = 'bc_member';

    /**
     * 사용자 아이디로 조회
     *
     * @param mixed $userId 사용자 아이디
     * @return User
     */
    public static function find($userId)
    {
        if(empty($userId))
            throw new \Exception('userId is empty.');
        global $db;
        $tableName = self::$tableName;
        $query = "SELECT * FROM {$tableName} WHERE user_id = '{$userId}' and del_yn='N'";
        $row = $db->queryRow($query);       
        return new User($row);
    }    

    /**
     * 사용자 아이디로 여러 사용자 조회
     *
     * @param array $userIds 사용자 아이디 배열
     * @return array User object array
     */
    public static function findUsers($userIds)
    {
        if(empty($userIds) || !is_array($userIds))
            throw new \Exception('userIds is empty.');

        $userIdsString = "'" . implode("', '", $userIds) . "'";
        global $db;
        $tableName = self::$tableName;        
        $query = "SELECT * FROM {$tableName} WHERE user_id in ({$userIdsString}) and del_yn='N'";
        $rows = $db->queryAll($query);       

        $users = [];
        foreach($rows as $row) {         
            $users[] = new User($row);
        }

        return $users;
    }

    /**
     * 멤버 아이디로 사용자 찾기
     *
     * @param mixed $memberId
     * @return object User object
     */
    public static function findByMemberId($memberId) 
    {
        global $db;
        $tableName = self::$tableName;
        $query = "SELECT * FROM {$tableName} WHERE member_id = '{$memberId}' and del_yn='N'";
        $row = $db->queryRow($query);        
        return new User($row);
    }

    /**
     * 그룹 아이디로 사용자 찾기
     *
     * @param mixed $groupId 그룹 아이디
     * @return array User 객체 배열
     */
    public static function findByGroupId($groupId)
    {
        global $db;

        $query = "SELECT * FROM bc_member m, bc_member_group_member mg WHERE m.MEMBER_ID = mg.MEMBER_ID
                AND mg.member_group_id={$groupId} ORDER BY user_nm";
        
        $rows = $db->queryAll($query);
        $users = [];        
        foreach($rows as $row) {
            $users[] = new User($row); 
        }
        return $users;
    }

    /**
     * 이메일로 사용자 찾기
     *
     * @param array $emails array of email address
     * @return array array of User object
     */
    public static function findByEmail($emails)
    {
        global $db;
        $tableName = self::$tableName;
        $emailsString = implode("', '", $emails);
        $query = "SELECT * FROM {$tableName} WHERE email in ('$emailsString') and del_yn='N'";   

        $rows = $db->queryAll($query);

        $users = [];
        foreach($rows as $row) {
            $users[] = new User($row);
        }

        return $users;
    }

    /**
     * 변경된 사용자 정보 저장
     *
     * @return void
     */
    public function save()
    {
        global $db;
        $memberId = $this->get('member_id');
        if(empty($memberId)) {
            $memberId = $db->queryOne("SELECT MAX(member_id) + 1 FROM bc_member");
            $this->set('member_id', $memberId);
            self::insert(self::$tableName, $this->data);
            $db->exec("insert into bc_member_group_member values ($memberId, 2)");
        } else {
            if(!$this->dataChanged) {
                return;
            }
            $where = " member_id = {$memberId}";
            self::update(self::$tableName, $this->data, $where);
        }       
    }

    /**
     * 사용자가 속한 그룹 목록 조회
     *
     * @return array Array of Group object
     */
    public function groups()
    {
        global $db;
        $query = "SELECT member_group_id FROM bc_member_group_member WHERE member_id = {$this->get('member_id')}";
        $rows = $db->queryAll($query);

        $groups = [];
        foreach($rows as $row) {
            $groups[] = Group::find($row['member_group_id']);
        }

        return $groups;
    }


    /**
     * search users
     *
     * @param string $keyword user name or user id   
     * @return array array of user
     */
    public static function search($keyword)
    {
        global $db;
        $tableName = self::$tableName;

        $where = " user_nm like '{$keyword}%' OR user_nm like '%{$keyword}' OR user_id like '{$keyword}'";

        $query = "SELECT * FROM {$tableName} WHERE {$where} and del_yn='N'";        
        
        $rows = $db->queryAll($query);

        $users = [];
        foreach($rows as $row) {
            $users[] = new User($row);
        }
        return $users;
    }    
}