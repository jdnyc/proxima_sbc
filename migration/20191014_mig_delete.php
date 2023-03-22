<?php
use \Api\Models\User;
use \Api\Models\FolderMng;
use \Api\Services\DTOs\ContentDto;
use \Api\Services\DTOs\MediaDto;
use \Api\Services\DTOs\ContentStatusDto;
use \Api\Services\DTOs\ContentSysMetaDto;
use \Api\Services\DTOs\ContentUsrMetaDto;


require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');//2011.12.17 Adding Task Manager Class
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/timecode.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/interface.class.php');

try{
    $content_id = $_GET['content_id'];
    if (strstr($content_id, ',')) {
        $contents = explode(',', $content_id);
        foreach($contents as $content_id){
            $mediaInfos = $db->queryAll("SELECT media_id FROM BC_MEDIA WHERE CONTENT_ID=$content_id ");
            $media_ids = [];
            foreach ($mediaInfos as $media) {
                $media_ids [] =  $media['media_id'];
            }
            if (!empty($media_ids)) {
                $r = $db->exec("DELETE FROM BC_MEDIA_QUALITY WHERE MEDIA_ID  IN (".join(',', $media_ids).")");
                $r = $db->exec("DELETE FROM bc_scene WHERE MEDIA_ID IN (".join(',', $media_ids).")");
                $r = $db->exec("DELETE FROM bc_task WHERE MEDIA_ID IN (".join(',', $media_ids).")");
            }
            if (!empty($content_id)) {
                $r = $db->exec("DELETE FROM BC_CONTENT_STATUS WHERE CONTENT_ID= $content_id");
                $r = $db->exec("DELETE FROM BC_USRMETA_CONTENT WHERE USR_CONTENT_ID= $content_id");
                $r = $db->exec("DELETE FROM BC_SYSMETA_MOVIE WHERE SYS_CONTENT_ID= $content_id");
                $r = $db->exec("DELETE FROM bc_task WHERE SRC_CONTENT_ID = $content_id");
                $r = $db->exec("DELETE FROM BC_MEDIA WHERE CONTENT_ID= $content_id");
                $r = $db->exec("DELETE FROM BC_CONTENT WHERE CONTENT_ID= $content_id");
            }
            echo $content_id ;
        }
    }else{
        $mediaInfos = $db->queryAll("SELECT media_id FROM BC_MEDIA WHERE CONTENT_ID=$content_id ");
        $media_ids = [];
        foreach ($mediaInfos as $media) {
            $media_ids [] =  $media['media_id'];
        }
        if (!empty($media_ids)) {
            $r = $db->exec("DELETE FROM BC_MEDIA_QUALITY WHERE MEDIA_ID  IN (".join(',', $media_ids).")");
            $r = $db->exec("DELETE FROM bc_scene WHERE MEDIA_ID IN (".join(',', $media_ids).")");
            $r = $db->exec("DELETE FROM bc_task WHERE MEDIA_ID IN (".join(',', $media_ids).")");
        }
        if (!empty($content_id)) {
            $r = $db->exec("DELETE FROM BC_CONTENT_STATUS WHERE CONTENT_ID= $content_id");
            $r = $db->exec("DELETE FROM BC_USRMETA_CONTENT WHERE USR_CONTENT_ID= $content_id");
            $r = $db->exec("DELETE FROM BC_SYSMETA_MOVIE WHERE SYS_CONTENT_ID= $content_id");
            $r = $db->exec("DELETE FROM bc_task WHERE SRC_CONTENT_ID = $content_id");
            $r = $db->exec("DELETE FROM BC_MEDIA WHERE CONTENT_ID= $content_id");
            $r = $db->exec("DELETE FROM BC_CONTENT WHERE CONTENT_ID= $content_id");
        }
        echo $content_id ;
    }
}catch(Exception $e){
    echo $e->getMessage();
}
?>