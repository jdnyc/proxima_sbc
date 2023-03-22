<?php

namespace Proxima\models\content;

use \Proxima\core\ModelBase;

/**
 * Media model class
 */
class Media extends ModelBase
{   
    private $content;     
    const MEDIA_TYPE_ORIGINAL = 'original';   
    const MEDIA_TYPE_PROXY = 'proxy';   
    const MEDIA_TYPE_THUMB = 'thumb';   
   
    private static $table = 'bc_media';

    /**
     * finds the media by media_id
     *
     * @param mixed $contentId media id
     * @return Media Media object
     */
    public static function find($mediaId)
    {
        global $db;
        $query = "SELECT * FROM bc_media WHERE media_id = {$mediaId}";
        $row = $db->queryRow($query);          
        return new Media($row);
    }

    /**
     * finds the media list by media_id array
     *
     * @param array $mediaIds media id array
     * @return array Media object list
     */
    public static function findMedias(array $mediaIds)
    {
        global $db;
        if(empty($mediaIds)) {
            return;
        }
        $mediaIdsStr = implode(', ', $mediaIds);
        $query = "SELECT * FROM bc_media WHERE media_id in ({$mediaIdsStr})";
        $rows = $db->queryAll($query);

        $medias = [];
        foreach($rows as $row) {
            $medias[$row['media_id']] = new Media($row);
        }
        return $medias;
    }

    /**
     * finds media list of the content by content object
     *
     * @param Content $content content object
     * @param array  $mediaTypes array of Media type ('original', 'proxy', 'thumb' etc...)
     * @param bool $ignoreDeleted ignore deleted media 
     * @return array Media object list
     */
    public static function findByContent($content, $mediaTypes = [], $ignoreDeleted = false)
    {
        global $db;        
        $query = "SELECT * FROM bc_media WHERE content_id = {$content->get('content_id')}";
        if(!empty($mediaTypes)) {
            $mediaTypesStr = implode("', '", $mediaTypes);
            $query .= " AND media_type in ('{$mediaTypesStr}')";
        }

        if($ignoreDeleted === true) {
            $query .= " AND deleted_date != '              ' AND deleted_date is not NULL";
        }
        $rows = $db->queryAll($query);

        $medias = [];
        foreach($rows as $row) {
            $medias[] = new Media($row);
        }

        return $medias;
    } 

    /**
     * finds media list of the content list by content id list
     *
     * @param array $contentIds content id list
     * @param array  $mediaTypes array of Media type ('original', 'proxy', 'thumb' etc...) 
     * @return array Media object list
     */
    public static function findByContentIds($contentIds, $mediaTypes = [])
    {
        global $db;        

        if(empty($contentIds)) {
            return;
        }
        $contentIdsStr = implode(', ', $contentIds);

        $query = "SELECT * FROM bc_media WHERE content_id in ({$contentIdsStr})";
        if(!empty($mediaTypes)) {
            $mediaTypesStr = implode("', '", $mediaTypes);
            $query .= " AND media_type in ('{$mediaTypesStr}')";
        }
        $rows = $db->queryAll($query);

        $medias = [];
        foreach($rows as $row) {
            $medias[] = new Media($row);
        }

        return $medias;
    }

    /**
     * gets content of the media
     *
     * @return Content Content object
     */
    public function content()
    {
        if(!empty($content)) {
            return $content;
        }

        $content = Content::find($this->get('content_id'));
        $this->content = $content;
        return $content;
    }

    /**
     * restore deleted media
     *
     * @return void
     */
    public function restore()
    {
        $deletedDate = trim($this->get('delete_date'));
        $flag = trim($this->get('flag'));

        if(!empty($deletedDate) && !empty($flag)) {
            $mediaId = $this->get('media_id');
            $where = "media_id={$mediaId}";        
    
            $fields = ['delete_date' => NULL, 
                        'flag' => NULL];        
    
            self::update(self::$table, $fields, $where);  
        }
    }

}