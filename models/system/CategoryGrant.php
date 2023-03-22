<?php
namespace Proxima\models\system;

use \Proxima\core\ModelBase;
use Proxima\models\user\User;

/**
 * Category grant model class
 * 카테고리 권한 관리 클래스
 */
class CategoryGrant extends ModelBase
{  
    
    private static $table = 'bc_category_grant';

    /**
     * 특정 카테고리에 대한 권한 조회
     *
     * @param mixed $categoryId 카테고리 아이디
     * @param array $groupIds 그룹 아이디 배열
     * @return array CategoryGrant 배열
     */
    public static function findCategoryGrantByGroup($categoryId, $groupIds = [])
    {
        global $db;

        $tableName = self::$table;
        $query = "SELECT * FROM {$tableName} a, bc_member_group g WHERE a.member_group_id = g.member_group_id AND a.category_id = {$categoryId} ";
        if(!empty($groupIds)) {
            $groupIdsString = implode(',', $groupIds);
            $query .= " AND a.member_group_id in ({$groupIdsString})";
        }

        $query .= " ORDER BY g.member_group_name ASC";

         $rows = $db->queryAll($query);
        //_text('MN02035')'권한 없음', _text('MN00035')'읽기', _text('MN00500')'읽기/생성/수정', 
        //_text('MN00501')'읽기/생성/수정/이동/삭제', _text('MN02037')'숨김', _text('MN01029')'관리자'
        $group_grant_names = array(_text('MN02035'), _text('MN00035'), _text('MN00500'), _text('MN00501'), _text('MN02037'), _text('MN01029'));
        $categoryGrants = [];
        foreach($rows as $row) {            
            $row['group_grant_name'] = $group_grant_names[$row['group_grant']];
            $categoryGrants[$row['member_group_id']] = new CategoryGrant($row);
        }        

        return $categoryGrants;
    } 

    /**
     * 저장
     *
     * @return void
     */
    public function save()
    {
        global $db;
        $categoryGrantId = $this->get('id');
        $tableName = self::$table;
        if(empty($categoryGrantId)) {
            self::insert(self::$table, $this->data);
        } else {
            if(!$this->dataChanged) {
                return;
            }
            $where = " id = {$categoryGrantId}";
            self::update(self::$table, $this->data, $where);
        }  
    }

    /**
     * 삭제
     *
     * @param mixed $id id
     * @return void
     */
    public static function delete($id, $_ = null)
    {
        $where = " id = {$id}";
        ModelBase::delete(self::$table, $where);
    }

    /**
     * 그룹에 해당하는 최고 권한 배열을 조회한다
     *
     * @param array $groups
     * @return array
     */
    public static function getCategoryGrantsByGroup($groups)
    { 
        global $db;

        $grant = [];

        $categoryGrants = $db->queryAll("SELECT * FROM bc_category_grant ORDER BY category_id, group_grant ASC");

        foreach($groups as $group)
        {
            foreach( $categoryGrants as $categoryGrant )
            {
                if( $categoryGrant['member_group_id'] == $group->get('member_group_id') )
                {
                    if( is_null( $grant[$categoryGrant['category_id']] ) )
                    {
                        $grant[$categoryGrant['category_id']] = $categoryGrant['group_grant'];
                    }
                    else if( $categoryGrant['group_grant'] > $grant[$categoryGrant['category_id']] )
                    {
                        $grant[$categoryGrant['category_id']] = $categoryGrant['group_grant'];
                    }
                }
            }

        }

        return $grant;
    }    

    /**
     * 그룹목록으로 카테고리 권한 조회
     *
     * @param mixed $categoryId
     * @param array $categoryGrants
     * @return array
     */
    public static function getCategoryGrant($categoryId, $categoryGrants, $defaultGrant)
    {           
        if(!isset($categoryGrants) || is_null($categoryGrants)) {
            $categoryGrants = array();
        }

        if( !empty($categoryGrants) )
        {
            foreach( $categoryGrants as $categoryGrantCategoryId => $grant)
            {
                if( $categoryGrantCategoryId == $categoryId )
                {
                    switch($grant)
                    {
                        case 0://권한 없음
                            $defaultGrant['read'] = 0;
                            $defaultGrant['add'] = 0;
                            $defaultGrant['edit'] = 0;
                            $defaultGrant['del'] = 0;
                            $defaultGrant['hidden'] = 0;
                            $defaultGrant['setting'] = 0;
                        break;

                        case 1://읽기 권한
                            $defaultGrant['read'] = 1;
                            $defaultGrant['add'] = 0;
                            $defaultGrant['edit'] = 0;
                            $defaultGrant['del'] = 0;
                            $defaultGrant['hidden'] = 0;
                            $defaultGrant['setting'] = 0;
                        break;

                        case 2://읽기 / 생성 / 수정 권한
                            $defaultGrant['read'] = 1;
                            $defaultGrant['add'] = 1;
                            $defaultGrant['edit'] = 1;
                            $defaultGrant['del'] = 0;
                            $defaultGrant['hidden'] = 0;
                            $defaultGrant['setting'] = 0;
                        break;

                        case 3: //읽기 / 생성 / 수정 / 삭제 및 이동 권한
                            $defaultGrant['read'] = 1;
                            $defaultGrant['add'] = 1;
                            $defaultGrant['edit'] = 1;
                            $defaultGrant['del'] = 1;
                            $defaultGrant['hidden'] = 0;
                            $defaultGrant['setting'] = 0;
                        break;                        

                        case 4: // 숨김 권한                            
                            $defaultGrant['read'] = 0;
                            $defaultGrant['add'] = 0;
                            $defaultGrant['edit'] = 0;
                            $defaultGrant['del'] = 0;
                            $defaultGrant['hidden'] = 1;
                            $defaultGrant['setting'] = 0;
                        break;

                        case 5: //관리자(읽기 / 생성 / 수정 / 삭제 및 이동 / 관리 권한)
                            $defaultGrant['read'] = 1;
                            $defaultGrant['add'] = 1;
                            $defaultGrant['edit'] = 1;
                            $defaultGrant['del'] = 1;
                            $defaultGrant['hidden'] = 0;
                            $defaultGrant['setting'] = 1;
                        break;
                    }
                }
            }
        }

        return $defaultGrant;
    }

    /**
     * 관리자 카테고리 권한 조회
     *
     * @return array
     */
    public static function getAdminGrant()
    {
        $grant = [            
            'read' => 1,
            'add' => 1,
            'edit' => 1,
            'del' => 1,
            'hidden' => 0,
            'setting' => 1
        ];
        return $grant;
    }

    /**
     * 읽기만 가능한 기본 권한 조회
     *
     * @return array
     */
    public static function getDefaultGrant()
    {
        $grant = [            
            'read' => 1,
            'add' => 0,
            'edit' => 0,
            'del' => 0,
            'hidden' => 0,
            'setting' => 0
        ];
        return $grant;
    }

    /**
     * 루트 카테고리의 권한 조회
     *
     * @param array $groups Group 객체 배열
     * @return array 루트 카테고리 권한
     */
    public static function getRootCategoryGrant($groups)
    {
        $categoryGrants = self::getCategoryGrantsByGroup($groups);
        $defaultGrant = self::getDefaultGrant();
        return self::getCategoryGrant(0, $categoryGrants, $defaultGrant);
    }
}





