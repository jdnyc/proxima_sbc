<?php

use Api\Types\TaskStatus;
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

function updateTrInfo($task_id, $status, $progress){
	global $db;

	if( $status == 'processing'  && $progress == 0){
		$query_t = ",REQUEST_TIME = '".date('YmdHis')."' ";
	}else if( $status == 'complete' ){
		$query_t = ",COMPLETE_TIME = '".date('YmdHis')."' ";
	}else{
		$query_t = "";
	}

	$query = "
		UPDATE	TB_ORD_TRANSMISSION	SET
			TR_STATUS = '".$status."',
			TR_PROGRESS = '".$progress."'
			".$query_t."
		WHERE	TASK_ID = ".$task_id."
	";

    $result = $db->exec($query);


    $contentInfo = \Api\Models\Task::find($task_id)->select('src_content_id')->first();
    if( $contentInfo && $contentInfo->src_content_id ){

        $contentService = new \Api\Services\ContentService( app()->getContainer() );

        $contentStatus = $contentService->findStatusMeta($contentId);
        if( $status == TaskStatus::COMPLETE ){
            $contentStatus->scr_trnsmis_end_dt = date("YmdHis");
        }
        
        if( empty($contentStatus->scr_trnsmis_begin_dt) ){
            $contentStatus->scr_trnsmis_begin_dt = date("YmdHis");
        }
        $contentStatus->save();
    }

	return $result;
}

?>