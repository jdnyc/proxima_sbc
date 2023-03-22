<?php

use Api\Core\FilePath;
use Api\Types\StorageIdMap;
use Api\Services\FileService;
use Api\Services\ApiJobService;
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
$is_group = $_POST['is_group'];

try{
	if($is_group == 'Y'){
		$content_id = $_POST['content_id'];
		$scene_id = $_POST['scene_id'];
		$thumb = $mdb->queryRow("	SELECT m.content_id, s.path 
									FROM bc_media m, bc_scene s 
									WHERE s.scene_id = '{$scene_id}' 
									AND m.media_id = (
										SELECT media_id 
										FROM bc_scene 
										WHERE scene_id='{$scene_id}'
									)
								");
		$query = $mdb->exec("update bc_media set path ='{$thumb['path']}' where content_id = '{$thumb['content_id']}' and media_type='thumb'");
		$query = $mdb->exec("update bc_content set thumbnail_content_id ='{$thumb['content_id']}' where content_id = '{$content_id}'");
		
		die(json_encode(array(
				'success' => true
		)));
	}else{
        $scene = $db->queryRow("SELECT 
            m.content_id,m.storage_id,s.media_id, s.*  
        FROM BC_SCENE S 
        JOIN BC_MEDIA M 
        ON (S.MEDIA_ID=M.MEDIA_ID) 
        WHERE S.SCENE_ID='{$_POST['scene_id']}'");

        $contentId      = $scene['content_id'];
        $mediaId        = $scene['media_id'];
        $filesize       = $scene['filesize'];
        $storageId      = $scene['storage_id'];
        $path           = $scene['path'];


        $fileService = new \Api\Services\FileService(app()->getContainer());   
        $mediaService = new \Api\Services\MediaService(app()->getContainer());
        $contentService = new \Api\Services\ContentService(app()->getContainer());

        
        //이전 섬네일 미디어 정보 조회
        $thumbMedia = $mediaService->getMediaByContentIdAndType($contentId, 'thumb');
        
        $filePathInfo = new \Api\Core\FilePath($thumbMedia->path);

        //이전 섬네일 정보 저장
        $fileMeta = [
            'file_path'     => $filePathInfo->filePath,
            'file_name'     => $filePathInfo->filenameExt,
            'file_ext'      => $filePathInfo->fileExt,
            'ori_file_name' => $filePathInfo->filenameExt,
            'file_size'     => $thumbMedia->filesize,         
            'storage_id'    => $thumbMedia->storage_id,
            'media_id'      => $thumbMedia->media_id,
            'content_id'    => $contentId
        ];
        $fileService->create($fileMeta);

        $media = $mediaService->findOrFail($thumbMedia->media_id);

        $media->path = $path;
        $media->filesize = $filesize;
        $media->save();
		//$query = $db->exec("update bc_media set path ='{$path}' where content_id = '{$contentId}' and media_type='thumb'");

        //업데이트
        if ($contentId) {
            $contentMap = $contentService->getContentForPush($contentId);
            if($contentMap){
                $apiJobService = new \Api\Services\ApiJobService(app()->getContainer());
                $apiJobService->createApiJob( 'Api\Services\ContentService', 'update', $contentMap , $contentId );
            }
        }
    }
    
    		
    die(json_encode(array(
            'success' => true
    )));
}
catch(Exception $e){
	$msg = $e->getMessage();
	switch($e->getCode()){
		case ERROR_QUERY:
			$err = $db->errorInfo();

			$msg .= $err[2].'( '.$db->last_query.' )';
		break;
	}

	die(json_encode(array(
		'success' => false,
		'msg' => $msg
	)));
}
?>