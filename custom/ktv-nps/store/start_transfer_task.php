<?php

use Api\Core\FilePath;
/*
우클릭 > 전송메뉴에서 여러 워크플로우를 선택하면 
이 페이지에서 각각 실행시켜준다.
*/
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

try
{
    $user_id = $_SESSION['user']['user_id'];
    $content_id_arr = json_decode( $_POST['content_list'] );
    // echo print_r($_POST, true);
    // exit;
    $contentService = new \Api\Services\ContentService(app()->getContainer());
    $mediaService = new \Api\Services\MediaService(app()->getContainer());

    foreach($_POST as $k => $v) {
        if($k == 'content_list') continue;
        if($k == 'channel'){
            $channel = $v;
            if( strstr($channel , 'transfer_to_maincontrol' ) || strstr($channel , 'transmission_zodiac' ) || strstr($channel , 'transmission_zodiac_ab' ) || strstr($channel , 'transmission_zodiac_news' ) ){
                //원본 위치 확인
                //대상 확인      
                foreach ($content_id_arr as $content_id) {
                    $targetMedia = null;
                    $medias = $mediaService->getMediaByContentId($content_id);
                    foreach ($medias as $media) {
                        if ($media->media_type == 'archive' && $media->status == 0 ) {
                            $targetMedia = $media;
                        }
                    }

                    if( empty($targetMedia) ){
                        foreach ($medias as $media) {
                            if ($media->media_type == 'original' && $media->status == 0 ) {
                                $targetMedia = $media;
                            }
                        }
                    }

                    if( !empty($targetMedia) ){
                        if( $targetMedia->media_type == 'archive'){
                            $channel = $channel.'_archive';
                            //아카이브에 있으면 아카이브에서 바로 전송
                        }else{
                            //온라인
                            $filePath = new FilePath($targetMedia->path);
                            if ($filePath->fileExt == 'mov' || $filePath->fileExt == 'mxf') {
                                if ($filePath->fileExt == 'mov') {
                                    $channel  = $channel .'_xdcam';
                                }
                            } else {
                                throw new Exception("허용 포맷이 아닙니다");
                            }
                        }
                    }else{
                        throw new Exception("영상이 없습니다. 리스토어 요청 후 전송해주세요.");
                    }
                }
            }
            foreach($content_id_arr as $content_id) {
                $arr_param_info = array();
                //2018-01-09 이승수, CJO, Haris전송이고 심의반려시 파일명에 suffix붙여서 전송
                // if(in_array($channel, array('ftp_transfer_GWR_A','ftp_transfer_GWR_B'))) {
                //     $content_info = $db->queryRow("select * from bc_content where content_id=".$content_id);
                //     if($content_info['status'] == CONTENT_STATUS_REVIEW_RETURN) {
                //         $arr_param_info = array(
                //             array(
                //                 'target_path' => 'MXFXD/'.$content_info['title'].Review::$reject_suffix.'.mxf'
                //             )
                //         );
                //     }
                // }
    
                $task = new TaskManager($db);
                $task_id = $task->start_task_workflow($content_id, $channel, $user_id, $arr_param_info);
            }
        }

    }
	
	echo json_encode(array(
		'success' => true,
		'total' => $total,
		'data' => $data,
		'msg' => $vcr_list
	));
}
catch(Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage() . '(' . $db->last_query . ')'
	));
}

?>