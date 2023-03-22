<?php

namespace Proxima\models\content;

use \Proxima\core\ModelBase;

/**
 * User content class
 */
class UserContent extends ModelBase
{   
    public static function find($userContentId)
    {
        global $db;
        $query = "SELECT *
                    FROM 
                        bc_ud_content
                    WHERE 
                        ud_content_id = {$userContentId}";
        $row = $db->queryRow($query);        
        return new UserContent($row);
    }

    /**
     * 사용자 정의 콘텐츠 목록 조회
     *
     * @return array UserContent 객체 배열
     */
    public static function all()
    {
        $db = ModelBase::getDatabase();
        $query = "SELECT *
                FROM 
                    bc_ud_content ORDER BY show_order ASC";
        
        $rows = $db->queryAll($query);        

        $userContents = [];
        foreach($rows as $row) {
            $userContents[$row['ud_content_id']] = new UserContent($row);
        }
        return $userContents;
    }

    /**
     * 사용자정의 콘텐츠와 해당되는 루트 카테고리 정보를 모두 조회
     *
     * @param string $order 정렬 순서 ('user_content'와 'root_category' 두가지 옵션만 있음)
     * @return array UserContent 배열
     */
    public static function allWithRootCategory($order = 'user_content')
    {
        $db = ModelBase::getDatabase();
        $query = "SELECT  
                u.ud_content_id,
                u.bs_content_id,
                u.ud_content_code,
                u.allowed_extension,
                u.description,
                u.created_date,
                u.show_order AS ud_show_order,
                c.category_id,
                c.parent_id,
                c.category_title,
                c.is_deleted,
                c.no_children,
                c.show_order AS cat_show_order
            FROM bc_category_mapping m
                LEFT OUTER JOIN 
                    bc_category c ON c.category_id = m.category_id
                    left outer JOIN
                    bc_ud_content u ON u.ud_content_id = m.ud_content_id
                ORDER BY u.show_order ASC";

        $rows = $db->queryAll($query);        

        $userContents = [];
        foreach($rows as $row) {
            $userContent = [
                'ud_content_id' => $row['ud_content_id'],
                'ud_content_code' => $row['ud_content_code'],
                'bs_content_id' => $row['bs_content_id'],
                'ud_content_title' => $row['ud_content_title'],
                'allowed_extension' => $row['allowed_extension'],
                'description' => $row['description'],
                'show_order' => $row['ud_show_order'],
                'created_date' => $row['created_date']
            ];

            $userContent['root_category'] = new \Proxima\models\content\Category([
                'category_id' => $row['category_id'],
                'parent_id' => $row['parent_id'],
                'category_title' => $row['category_title'],
                'is_deleted' => $row['is_deleted'],
                'no_children' => $row['no_children'],
                'show_order' => $row['cat_show_order']
            ]);
            $userContents[$row['ud_content_id']] = new UserContent($userContent);
        }
        return $userContents;
    }

    public static function first()
    {
        global $db;

        $query = "SELECT *
                FROM 
                    bc_ud_content ORDER BY show_order ASC";        
        $row = $db->queryRow($query);        

        return new UserContent($row);
    }

    public static function findUserContentsByBsContentId($bsContentId)
    {
        global $db;
        
        $query = "SELECT *
                FROM 
                    bc_ud_content 
                WHERE
                    bs_content_id = {$bsContentId}
                ORDER BY show_order ASC";
        $rows = $db->queryAll($query);        

        $userContents = [];
        foreach($rows as $row) {
            $userContents[$row['ud_content_id']] = new UserContent($row);
        }
        return $userContents;
    }

    public function rootCategory()
    {
        $rootCategory = $this->get('root_category');
        if (isset($rootCategory) && $rootCategory !== NULL) {
            return $rootCategory;
        }
        global $db;
        
        $query = "SELECT category_id
                FROM 
                    bc_category_mapping 
                WHERE
                    ud_content_id = {$this->get('ud_content_id')}";
        $categoryId = $db->queryOne($query);

        return Category::find($categoryId);
    }
}