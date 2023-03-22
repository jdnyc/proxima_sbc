<?php

namespace Proxima\models\content;

use \Proxima\core\ModelBase;

/**
 * Content model class
 */
class Content extends ModelBase
{   
    private $userContent;
    private $category;
    private $userMetadata;
    private $systemMetadata;

    private static $table = 'bc_content';
    
    /**
     * finds the content by content_id
     *
     * @param mixed $contentId content id
     * @return Content Content object
     */
    public static function find($contentId)
    {
        global $db;
        $query = "SELECT * FROM bc_content WHERE content_id = {$contentId}";
        $row = $db->queryRow($query);        
        return new Content($row);
    }

    /**
     * finds the content list by content_id array
     *
     * @param array $contentIds content id array
     * @return array Content object list
     */
    public static function findContents(array $contentIds)
    {
        global $db;
        if(empty($contentIds)) {
            return;
        }
        $contentIdsStr = implode(', ', $contentIds);
        $query = "SELECT * FROM bc_content WHERE content_id in ({$contentIdsStr})";
        $rows = $db->queryAll($query);

        $contents = [];
        foreach($rows as $row) {
            $contents[$row['content_id']] = new Content($row);
        }
        return $contents;
    }

    /**
     * finds the content by title
     *
     * @param string $title title
     * @param bool $includeDeletedContent 삭제된 콘텐츠를 조회할지 여부
     * @param bool $includeHiddenContent 숨김 처리된 콘텐츠를 조회할지 여부
     * @return Content Content object
     */
    public static function getContentsByTitle($title, $includeDeletedContent = false, $includeHiddenContent = false)
    {
        global $db;
        $query = "SELECT * FROM bc_content WHERE title = '{$title}'";
        if(!$includeDeletedContent) {
            $query .= " AND is_deleted != 'Y'";
        }
        if(!$includeHiddenContent) {
            $query .= " AND is_hidden != 'Y'";
        }
        $rows = $db->queryAll($query); 

        $contents = [];
        foreach($rows as $row) {
            $contents[] = new Content($row);
        }
        return $contents;
    }

    /**
     * checks the content exists.
     *
     * @param mixed $contentId content id
     * @param bool $includeDeletedContent 삭제된 콘텐츠를 조회할지 여부
     * @param bool $includeHiddenContent 숨김 처리된 콘텐츠를 조회할지 여부
     * @return bool if return is ture, the content exists
     */
    public static function exists($contentId, $includeDeletedContent = false, $includeHiddenContent = false)
    {
        global $db;
        $query = "SELECT count(*) FROM bc_content WHERE content_id = {$contentId}";
        if(!$includeDeletedContent) {
            $query .= " AND is_deleted != 'Y'";
        }
        if(!$includeHiddenContent) {
            $query .= " AND is_hidden != 'Y'";
        }
        $count = $db->queryOne($query);        
        return ($count > 0);
    }

    /**
     * finds the content list by category id
     *
     * @param mixed $categoryId category id
     * @param bool $ignoreDeleted ignore deleted content
     * @return array found content list array
     */
    public static function getContentsByCategoryId($categoryId, $ignoreDeleted = false)
    {
        global $db;
        $query = "SELECT * FROM bc_content WHERE category_id = {$categoryId}";
        if($ignoreDeleted === true) {
            $query .= " AND is_deleted = 'N'";
        }
        $rows = $db->queryAll($query);

        $contents = [];
        foreach($rows as $row) {
            $contents[] = new Content($row);
        }
        return $contents;
    }

    /**
     * gets user metadata of the content
     *
     * @return UserMetadata user metadata object
     */
    public function userMetadata()
    {
        if (empty($uerMetadata)) {
            $userMetadata = UserMetadata::find($this);
        }
        return $userMetadata;
    }

    /**
     * gets category of the content
     *
     * @return Category category object
     */
    public function category()
    {        
        if (empty($category)) {
            $category = Category::find($this->get('category_id'));
        }
        return $category; 
    }

    /**
     * gets user content of the content
     *
     * @return UserContent user content object
     */
    public function userContent()
    {
        if (empty($uerMetadata)) {
            $userContent = UserContent::find($this->get('ud_content_id'));
        }
        return $userContent;        
    }

    /**
     * gets user content name of the content
     *
     * @return string
     */    
    public function userContentName()
    {
        return $this->userContent()->get('ud_content_title');
    }

    /**
     * gets system metadata of the content
     *
     * @return SystemMetadata
     */
    public function systemMetadata()
    {
        if (empty($systemMetadata)) {
            $systemMetadata = SystemMetadata::find($this);
        }
        return $systemMetadata;
    }

    /**
     * gets register this content
     *
     * @return User user object
     */
    public function user()
    {
        return User::find($this->get('reg_user_id'));
    }

    /**
     * gets sub contents of the content
     *
     * @return array array of Content object
     */
    public function subContents()
    {
        global $db;

        $parentContentId = $this->get('content_id');
        $query = "SELECT * FROM bc_content WHERE parent_content_id={$parentContentId}";

        $rows = $db->queryAll($query);

        $subContents = [];
        foreach($rows as $row) {
            $subContents[] = new Content($row);
        }

        return $subContents;
    }

    /**
     * gets medias of the content
     *
     * @param array $mediaTypes array of media type 'original', 'proxy', 'thumb', etc..
     * @return array array of Media object
     */
    public function medias($mediaTypes = [])
    {        
        return Media::findByContent($this, $mediaTypes);
    }

    /**
     * changes status of the content
     *
     * @param mixed $contentId content id
     * @param string $status content status
     * @return void
     */
    public static function changeStatus($contentId, $status)
    {        
        //$where = "content_id={$contentId}";        
        //self::update(self::$table, ['status' => $status], $where);
        // 위 함수가 작동안해서 아래와 같이 하지만 언젠가 손봐야 함
        global $db;
        $db->exec("UPDATE bc_content SET status={$status} where content_id={$contentId}");

        searchUpdate($contentId);
    }

    /**
     * bulk changes status of the content
     *
     * @param array $contentIds content id array
     * @param mixed $oldStatus old status(if $oldStatus empty, it doesn't use status condition.)
     * @param mixed $newStatus new status to change
     * @return void
     */
    public static function bulkChangeStatus($contentIds, $oldStatus, $newStatus)
    {
        global $db;

        if(!empty($newStatus)) {
            throw new \Exception('$newStatus is empty.');
        }

        if(!is_array($contentIds)) {
            throw new \Exception('$contentIds must be array.');
        }

        $contentIdsString = implode(',', $contentIds);
        $query = "UPDATE bc_content SET status={$newStatus} 
                    WHERE content_id IN ({$contentIdsString})";        
        $statusCondition = '';
        if(!empty($oldStatus)) {
            $statusCondition =  $query . " AND status = {$oldStatus}";
        }
        $db->exec($query);

        foreach($contentIds as $contentId) {
            searchUpdate($contentId);
        }
    }

    /**
     * changes user content id of the content
     *
     * @param mixed $contentId content id
     * @param mixed $newUserContentId new user content id
     * @param mixed $newCategoryId new category id
     * @return void
     */
    public function changeUserContentId($newUserContentId, $newCategoryId)
    {
        $contentId = $this->get('content_id');
        $where = "content_id={$contentId}";        

        $fields = ['ud_content_id' => $newUserContentId];
        // change root category
        if(!empty($newCategoryId)) {
            if($this->get('status') == CONTENT_STATUS_COMPLETE) {
                $fields['status'] = CONTENT_STATUS_REG_READY;
            }            
            $fields['category_id'] = $newCategoryId;
            $fields['category_full_path'] = Category::getPath($newCategoryId);
        }

        self::update(self::$table, $fields, $where);

        searchUpdate($contentId);
    }

    /**
     * change title of the content
     *
     * @param string $newTitle content title
     * @return void
     */
    public function rename($newTitle)
    {        
        $contentId = $this->get('content_id');
        $where = "content_id={$contentId}";        

        $fields = ['title' => $newTitle];        

        self::update(self::$table, $fields, $where);

        searchUpdate($contentId);
    }

    /**
     * 콘텐츠 상태값 조회
     *
     * @return ContentStatus object of ContentStatus
     */
    public function contentStatus()
    {
        $status = $this->get('status');
        return ContentStatus::findByStatus($status);
    }

    /**
     * 콘텐츠 상태값 조회(전역함수)
     *
     * @param string $status content status
     * @return ContentStatus object of ContentStatus
     */
    public static function getContentStatus($status)
    {
        if($status == '') {
            return null;
        }
        return ContentStatus::findByStatus($status);
    }

    /**
     * 삭제된 콘텐츠 복구
     *
     * @return void
     */
    public function restore()
    {   
        // 콘텐츠 복구
        if($this->get('is_deleted') == 'Y') {
            $contentId = $this->get('content_id');
            $where = "content_id={$contentId}";        
    
            $fields = ['is_deleted' => 'N'];        
    
            self::update(self::$table, $fields, $where);   
            
            searchUpdate($contentId);    
        }

        // 미디어 복구(원본, 썸네일, 프록시만 복구)
        $medias = $this->medias([Media::MEDIA_TYPE_ORIGINAL, Media::MEDIA_TYPE_PROXY, Media::MEDIA_TYPE_THUMB]);
        foreach($medias as $media) {
            $media->restore();
        }
    }

    /**
     * 자식 카테고리 별 콘텐츠 개수 반환
     *
     * @param int $parentCategoryId
     * @return array
     */
    public static function getChildCategoryContentCount($parentCategoryId)
    {
        global $db;
        $query = "SELECT cat.category_id, count(c.content_id) count FROM 
                (SELECT CATEGORY_ID, CATEGORY_TITLE 
                FROM BC_CATEGORY
                WHERE parent_id = {$parentCategoryId} AND NO_CHILDREN = '1') cat LEFT JOIN
                bc_content c
                on cat.category_id = c.category_id AND c.is_deleted = 'N' GROUP BY cat.category_id";
        
        $rows = $db->queryAll($query);
        $data = [];
        foreach($rows as $row) {
            $categoryId = (string)$row['category_id'];
            $data[$categoryId] = (int)$row['count'];
        }
        return $data;
    }
}
