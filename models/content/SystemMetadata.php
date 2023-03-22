<?php

namespace Proxima\models\content;

use \Proxima\core\ModelBase;

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/MetaData.class.php');

/**
 * System metadata class
 */
class SystemMetadata extends ModelBase
{   
    private $content;

    /**
     * gets system metadata by content object
     *
     * @param Contnet $content content object
     * @return SystemMetadata system metadata object instance
     */
    public static function find($content)
    {
        $data = \MetaDataClass::getValueInfo(\MetaDataClass::SYS_CODE, $content->get('bs_content_id'), $content->get('content_id'));
        $sysMeta = new SystemMetadata($data);
        $sysMeta->setContent($content);

        return $sysMeta;
    }

    /**
     * gets system metadata by content id
     *
     * @param string $contentId content id
     * @return SystemMetadata system metadata object instance
     */
    public static function findByContentId($contentId)
    {
        $content = Content::find($contentId);        
        $sysMeta = $content->systemMetadata();        
        $sysMeta->setContent($content);

        return $sysMeta;
    }

    /**
     * sets content member of system metadata
     *
     * @param Content $content content object
     * @return void
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * gets content object of system metadata
     *
     * @return Content content object instance
     */
    public function content()
    {
        return $this->content;
    }
}