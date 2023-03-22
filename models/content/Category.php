<?php

namespace Proxima\models\content;

use Proxima\core\Unit;
use Proxima\core\ModelBase;
use Proxima\models\content\Media;
use Proxima\models\content\Content;
use Api\Services\DataDicCodeSetService;
use Api\Services\DataDicCodeItemService;

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php'); 

/**
 * Category model class
 */
class Category extends ModelBase
{       
    const ROOT_ID = 0; // 카테고리 루트
    private static $mappedUserContents = [];

    public static function find($categoryId)
    {
        global $db;
        $query = "SELECT * FROM bc_category WHERE category_id = {$categoryId}";
        $row = $db->queryRow($query);
        return new Category($row);
    }

    public static function search($keyword)
    {
        global $db;
        $query = "SELECT * FROM bc_category WHERE category_title like '%{$keyword}%'";
        $rows = $db->queryAll($query);

        $categories = [];
        foreach($rows as $row) {
            $categories[] = new Category($row);
        }
        return $categories;
    }
    public static function getDep($categoryId){
        
        $category = self::find($categoryId);
        $dep = $category->data['dep'];
        return $dep;
    }
    /**
     * 현재 카테고리에 속한 콘텐츠 목록 조회
     *
     * @return array Array of Content object
     */
    public function contents($ignoreDeleted = false)
    {
        return Content::getContentsByCategoryId($this->get('category_id'), $ignoreDeleted);
    }

    /**
     * 카테고리아이디에 해당하는 전체 경로를 조회한다.
     *
     * @param mixed $categoryId 조회 할 카테고리 아이디
     * @return string 카테고리 전체 경로(/0/1/2/3 과 같이 카테고리 아이디로 된 경로)
     */
    public static function getPath($categoryId)
    {
        return getCategoryFullPath($categoryId);
    }

    /**
     * 카테고리 아이디로 구성된 전체 경로에 대해 이름으로 된 경로를 조회한다.
     *
     * @param string $categoryPath 조회 할 카테고리 전체 경로(/0/1/2/3 과 같이 카테고리 아이디로 된 경로)
     * @return string 카테고리 전체 경로(/루트/할아버지/아버지/아들 과 같이 카테고리 이름으로 된 경로)
     */
    public static function getNamePath($categoryPath)
    {
        return getCategoryPathTitle($categoryPath);
    }

    /**
     * 카테고리 아이디로 카테고리 이름으로 된 경로를 조회한다.
     *
     * @param mixed $categoryId 조회 할 카테고리 아이디
     * @return string 카테고리 전체 경로(/루트/할아버지/아버지/아들 과 같이 카테고리 이름으로 된 경로)
     */
    public static function getNamePathById($categoryId)
    {
        return getCategoryPathTitle(self::getPath($categoryId));
    }

    /**
     * 카테고리의 하위 카테고리 조회
     *
     * @return array 카테고리 객체의 배열
     */
    public function children()
    {        
        $parentId = $this->get('category_id');
        
        return self::getChildrenByCategoryId($parentId);
    }

    /**
     * 현재 카테고리에 맵핑된 사용자 정의 콘텐츠 목록 조회
     *
     * @return array Array of UserContent object
     */
    public function userContents()
    {
        global $db;

        $categoryId = $this->get('category_id');

        // 최초 실행 시 맵 테이블 전체를 로드 해 놓고 재사용 하자. DB 부하 절감...
        if(empty(self::$mappedUserContents)) {
            $query = "SELECT cm.category_id, u.* 
                            FROM bc_category_mapping cm, bc_ud_content u 
                            WHERE cm.ud_content_id = u.ud_content_id order by u.ud_content_id ";
            $rows = $db->queryAll($query);

            foreach($rows as $row) {
                self::$mappedUserContents[] = new UserContent($row);
            }            
        }        

        $userContents = $this->getMappedUserContent($categoryId);        

        return $userContents;
    }

    /**
     * 맵핑 된 사용자 정의 콘텐츠 찾기
     *
     * @return array Array of UserContent
     */
    private function getMappedUserContent($categoryId)
    {        
        if(empty(self::$mappedUserContents))
            return [];
        
        $userContents = [];
        foreach(self::$mappedUserContents as $mappedUserContent) {
            if($mappedUserContent->get('category_id') == $categoryId)
                $userContents[] = $mappedUserContent;
        }
        return $userContents;
    }

    /**
     * 카테고리아이디로 하위 카테고리 조회
     *
     * @param mixed $parentCategoryId
     * @return array
     */
    public static function getChildrenByCategoryId($parentCategoryId)
    {
        // 루트 카테고리는 0이기 때문에 empty로 체크 하지 않음
        if(is_null($parentCategoryId)) {
            return [];
        }
        global $db;

        $order = "show_order";
        if($parentCategoryId == 200){
            $order = "category_title";
        }
        $query = "SELECT * FROM bc_category WHERE parent_id = {$parentCategoryId} ORDER BY {$order}";
        
        $children = [];
        $rows = $db->queryAll($query);
        foreach($rows as $row) {
            $children[] = new Category($row);
        }
        
        return $children;
    }    

    /**
     * 카테고리아이디로 하위 카테고리 조회 검색
     *
     * @param mixed $parentCategoryId
     * @param string $keyword 검색어
     * @return array
     */
    public static function getSearchChildrenByCategoryId($parentCategoryId,$keyword)
    {
        // 루트 카테고리는 0이기 때문에 empty로 체크 하지 않음
        if(is_null($parentCategoryId)) {
            return [];
        }
        global $db;   
        $order = "show_order";
        if($parentCategoryId == 200){
            $order = "category_title";
        }     
        $query = "SELECT * FROM bc_category WHERE parent_id = {$parentCategoryId} AND category_title like '%{$keyword}%' ORDER BY {$order}";
      
        $children = [];
        $rows = $db->queryAll($query);
        foreach($rows as $row) {
            $children[] = new Category($row);
        }
        
        return $children;
    }    

    public function getContentsSize()
    {
        $contents = $this->contents();
        $contentIds = [];
        foreach($contents as $content) {
            $contentIds[] = $content->get('content_id');        
        }

        $medias = Media::findByContentIds($contentIds, [Media::MEDIA_TYPE_ORIGINAL], true);
        
        $fileSize = 0;
        if(!empty($medias)) {
            foreach($medias as $media) {
                $fileSize = (int)$media->get('filesize');
            }
        }

        return Unit::formatBytes($fileSize);    
    }

    public static function getCodeCategory($code,$parentId=0){
        //HIERARCHY
        // $codeSetQuery = "SELECT ID FROM DD_CODE_SET WHERE CODE_SET_CODE = '".$code."'";
        // $codeSetId = $db->queryOne($codeSetQuery);
    
        $container = app()->getContainer();
        $dataDicCodeSetService =  new DataDicCodeSetService($container);
        $dataDicCodeItemService =  new DataDicCodeItemService($container);
        $codeSet = $dataDicCodeSetService->findByCode($code);
        
        $codeSetId = $codeSet->id;
        $codes = $dataDicCodeItemService->getCodeItemsByCodeSetId($codeSetId);

        
        $childrenConfig = [];
        $childrenConfig['expanded'] = false;
        $childrenConfig['ud_contents'] = [1,2,3,4,5,7,9];
        $childrenConfig['empty'] = true;
        $childrenConfig['read'] = 1;
        $childrenConfig['add'] = 0;
        $childrenConfig['edit'] = 0;
        $childrenConfig['del'] = 0;
        $childrenConfig['move'] = null;
        $childrenConfig['hidden'] = 0;
        $childrenConfig['setting'] = 0;

        $codeItems = $dataDicCodeItemService->makeNodes($codes, null, $childrenConfig );

        return $codeItems;
    }
    
    public static function getPgmByBisPgmAndBrdcstStleSe($brdcstStleSe){
        global $db;   
        $query = "SELECT bc.category_id,bc.category_title 
                    FROM bc_category bc
                    JOIN folder_mng fm on bc.category_id = fm.category_id
                    JOIN bis_scpgmmst bs on bs.pgm_id = fm.pgm_id
                    where bs.brd_form = '{$brdcstStleSe}' AND fm.using_yn = 'Y'
                    ORDER BY bc.category_title";
                            
        
        $children = [];
        $rows = $db->queryAll($query);
        foreach($rows as $row) {
            $findNode = new Category($row);
            $oriCategoryId = $findNode->get('category_id');
            $findNode->set('category_id',$oriCategoryId.'c');
            $findNode->set('original_category_id',$oriCategoryId);
            $children[] = $findNode;
        }
        
        return $children;
    }
    
}

