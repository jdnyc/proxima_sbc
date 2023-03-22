<?php

namespace Proxima\models\content;

use \Proxima\core\ModelBase;

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/MetaData.class.php');

/**
 * User metadata class
 */
class UserMetadata extends ModelBase
{   
    private $content;
    private $tableName;
    public static function find($content)
    {        
        $data = \MetaDataClass::getValueInfo(\MetaDataClass::USR_CODE, $content->get('ud_content_id'), $content->get('content_id'));        
        $userMeta = new UserMetadata($data);
        $userMeta->setContent($content);        

        return $userMeta;
    }

    /**
     * gets user metadata by content id
     *
     * @param string $contentId content id
     * @return UserMetadata user metadata object instance
     */
    public static function findByContentId($contentId)
    {
        $content = Content::find($contentId);    
        $userMeta = self::find($content);

        return $userMeta;
    }
    
    /**
     * gets content object of user metadata
     *
     * @return Content content object instance
     */
    public function content()
    {
        if(empty($this->content)) {
            $contentId = $this->get('usr_content_id');
            $content = Content::find($contentId);  
            $this->setContent($content);
        }
        return $this->content;
    }

    /**
     * sets content member of user metadata
     *
     * @param Content $content content object
     * @return void
     */
    public function setContent($content)
    {
        $this->content = $content;
        $this->tableName = \MetaDataClass::getTableName(\MetaDataClass::USR_CODE, $content->get('ud_content_id'));
    }

    /**
     * updates user metadata
     *
     * @return void
     */
    public function save()
    {
        global $db;
        $this->tableName = \MetaDataClass::getTableName(\MetaDataClass::USR_CODE, $this->content->get('ud_content_id'));

        $contentId = $this->content->get('content_id');
        $where = " usr_content_id = {$contentId}";

        $query = "SELECT count(*) FROM {$this->tableName} WHERE {$where}";
        $count = $db->queryOne($query);
        if(empty($count)) {
            self::insert($this->tableName, $this->data);
        } else {
            self::update($this->tableName, $this->data, $where);
        }

        searchUpdate($contentId);
        
    }

    /**
     * saveAs user metadata
     *
     * @param mixed $userContentId user content id
     * @return void
     */
    public function saveAs($userContentId)
    {
        $newTableName = \MetaDataClass::getTableName(\MetaDataClass::USR_CODE, $userContentId);

        $fields = \MetadataClass::getMetaFieldInfo(\MetaDataClass::USR_CODE, $userContentId);

        $newData = [];
        // 기본값 설정
        $newData['usr_content_id'] = $this->content->get('content_id');
        foreach ($fields as $field ) {

            if($field['usr_meta_field_type'] == 'container')
                continue;

            $fieldName = \MetaDataClass::USR_CODE .'_'. strtolower($field['usr_meta_field_code']);

            // 기본값 설정
            $defaultValue = self::getDefaultValue($field);            
            $newData[$fieldName] = $defaultValue;

            $fieldValue = $this->get($fieldName);
            
            if(!empty($fieldValue)) {
                $newData[$fieldName] = $fieldValue;                
            }
        }  
      
        // 새 테이블에 사용자 메타데이터 입력
        self::insert($newTableName, $newData);

        $contentId = $this->content->get('content_id');
        // 기존 테이블 데이터 삭제
        $where = " usr_content_id = {$contentId}";
        self::delete($this->tableName, $where);

        searchUpdate($contentId);

        $this->data = $newData;
    }

    /**
     * create new user metadata
     *
     * @param mixed $contentId content id
     * @param mixed $userContentId user content id
     * @return UserMetadata user metadata object
     */
    public static function create($contentId, $userContentId) {

        $newTableName = \MetaDataClass::getTableName(\MetaDataClass::USR_CODE, $userContentId);
        
        $fields = \MetadataClass::getMetaFieldInfo(\MetaDataClass::USR_CODE, $userContentId);

        $newData = [];
        // 기본값 설정
        $newData['usr_content_id'] = $contentId;
        foreach ($fields as $field ) {

            if($field['usr_meta_field_type'] == 'container')
                continue;

            $fieldName = \MetaDataClass::USR_CODE .'_'. strtolower($field['usr_meta_field_code']);

            // 기본값 설정
            $defaultValue = self::getDefaultValue($field);            
            $newData[$fieldName] = $defaultValue;
        }  

        $userMeta = new UserMetadata($newData);
        $contentId = $userMeta->get('usr_content_id');
        $content = Content::find($contentId);  
        $userMeta->content($content);
        return $userMeta;
    }

    /**
     * gets default metadata value
     *
     * @param array $userMetaField user metadata field array
     * @return mixed default metadata value
     */
    public static function getDefaultValue($userMetaField)
    {
        $result = '';
        $defualtValue = trim($userMetaField['default_value']);
        if(empty($defualtValue)) {
            return $result;
        }
        switch($userMetaField['usr_meta_field_type']) {
            case 'textfield':
            case 'textarea':
                $result = $defualtValue;
            break;
            case 'combo':
                $defaultValueArray = explode('(default)', $defualtValue);
                $result = $defaultValueArray[0];
            break;
            case 'datefield':
                try {
                    $result = date('Y-m-d', strtotime("{$defualtValue} day"));
                } catch(\Exception $e) {
                    $result = '';
                }
                
            break;
        }

        return $result;
    }
}