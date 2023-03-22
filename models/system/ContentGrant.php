<?php
namespace Proxima\models\system;

use \Proxima\core\ModelBase;
use Proxima\models\user\User;

/**
 * Content grant model class
 * 콘텐츠 권한 관리 클래스
 */
class ContentGrant extends ModelBase
{
    /**
     * 16-02-11, 임찬모.
     * 함수 파라메터 $grant는 숫자이다. ex) 읽기 1, 생성 2, 수정 4 이고, 권한은 읽기/수정만 되어있다면 $grant = 5
     * 그룹이 없는 사용자는 default그룹의 기능으로, 관리자계정(is_admin이 Y인 사용자)
     *
     * @param string $userId
     * @param mixed $userContentId
     * @param mixed $grant
     * @param mixed $categoryId
     * @return boolean
     */
    public static function checkAllowGrant($userId, $userContentId, $grant , $categoryId = null) 
    {
        return checkAllowGrant($userId, $userContentId, $grant , $categoryId);
    }

    /**
     * 팝업메뉴에 대한 권한을 조회하는 함수
     * 함수 파라메터 $grant는 숫자이다.
     *
     * @param string $userId
     * @param mixed $userContentId
     * @param mixed $grant
     * @param mixed $categoryId
     * @return boolean
     */
    public static function checkAllowUdContentGrant($userId, $userContentId, $grant , $categoryId = null)
    {
        return checkAllowUdContentGrant($userId, $userContentId, $grant , $categoryId);
    }

    /**
     * 콘텐츠 유형에 관계없이 팝업메뉴 권한이 있는지 조회
     * 함수 파라메너 $grant는 숫자이다.
     *
     * @param string $userId 사용자 아이디
     * @param mixed $grant 권한코드
     * @return boolean
     */
    public static function hasContentGrant($userId, $grant)
    {
        global $db;

        $user = User::find($userId);
        $groups = $user->groups();        
        $groupIds = [];
        foreach($groups as $group) {
            $groupIds[] = $group->get('member_group_id');
        }

        $groupIdsString = implode(',', $groupIds);
        $query = "SELECT group_grant
                    FROM bc_grant
                    WHERE	
                        member_group_id in ({$groupIdsString})
                        AND	grant_type='content_grant'";
        $rows = $db->queryAll($query);
        foreach($rows as $row) {
            if(($row['group_grant'] & $grant) == $grant) {
                return true;
            }
        }

        return false;
    }
}
