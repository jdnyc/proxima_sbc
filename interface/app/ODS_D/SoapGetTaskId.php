<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
/**
 * Created by PhpStorm.
 * User: cerori
 * Date: 2015-01-15
 */

function SoapGetTaskId($content_id) {
    global $server;
    global $db;
	
    @file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ODA_ODS_D_SoapGetTaskId'.date('Ym').'.log', date('Y-m-d H:i:s').'$SoapGetTaskId start'.")\n", FILE_APPEND);
    
    try{
//    	$v_type = 'archive';
//    	
//    	$media_info = $db->queryRow("
//    		SELECT	MEDIA_ID
//    				,PATH
//    		FROM	BC_MEDIA
//			WHERE	CONTENT_ID	= ".$content_id."
//			AND		MEDIA_TYPE	= 'original'
//    	");
//    	$media_id = $media_info['media_id'];
//    	$media_path = $media_info['path'];
//    	
//    	if(empty($media_id)){
//    		return array(
//    				'code' => '1',
//    				'msg' => 'Content id not found.['.$content_id.']'
//    		);
//    	}
//    	
//    	$task_id = getSequence('TASK_SEQ');
//    	$insert_task = "INSERT INTO BC_TASK
//				(TASK_ID, MEDIA_ID, TYPE, SRC_CONTENT_ID, PARAMETER, STATUS, PRIORITY, CREATION_DATETIME, TASK_USER_ID)
//				VALUES
//				(".$task_id.", ".$media_id.", '".$v_type."', ".$content_id.", ' ', 'queue', 300, '".date('YmdHis')."','ODA Achive')";
//    	$db->exec($insert_task);
//    	
//    	//TASK ID and archive status change
//	    $stt_update_q = "
//	    		UPDATE	BC_ARCHIVE_REQUEST
//	    		SET		STATUS		= 'PROCESSING'
//	    				,TASK_ID	= ".$task_id."
//				WHERE	REQUEST_SYSTEM	= 'ODS_D'
//	    		AND		REQUEST_TYPE	= 'ARCHIVE'
//				AND		CONTENT_ID		= ".$content_id
//		;
//	    $db->exec($stt_update_q);
//	    
//	    @file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ODA_ODS_D_SoapGetTaskId'.date('Ym').'.log', date('Y-m-d H:i:s').'$stt_update_q:::'.$stt_update_q."\n", FILE_APPEND);


		$task_info = $db->queryRow("
			SELECT	*
			FROM	BC_TASK
			WHERE	SRC_CONTENT_ID=".$content_id."
			AND		TYPE='".ARCHIVE."'
			ORDER BY TASK_ID DESC");
		if(empty($task_info)){
    		return array(
    				'code' => '1',
    				'msg' => 'Content id not found.['.$content_id.']'
    		);
    	}
		$task_id = $task_info['task_id'];

		//TASK ID and archive status change
	    $stt_update_q = "
	    		UPDATE	BC_ARCHIVE_REQUEST
	    		SET		STATUS		= 'PROCESSING'
				WHERE	REQUEST_SYSTEM	= 'ODS_D'
	    		AND		REQUEST_TYPE	= 'ARCHIVE'
				AND		CONTENT_ID		= ".$content_id
		;
	    $db->exec($stt_update_q);
	    
	    @file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ODA_ODS_D_SoapGetTaskId'.date('Ym').'.log', date('Y-m-d H:i:s').'$stt_update_q:::'.$stt_update_q."\n", FILE_APPEND);
    }
    catch(Exception $e){
    	$msg = $e->getMessage();
    	switch($e->getCode()){
    		case ERROR_QUERY:
    			$msg .= '( '.$db->last_query.' )';
    			break;
    	}
    
    	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ODA_ODS_D_SoapGetTaskId'.date('Ym').'.log', date('Y-m-d H:i:s').'$msg:::'.$msg."\n", FILE_APPEND);
    
    	return array(
    			'code' => '1',
    			'msg' => $msg
    	);
    }
    
    return array(
        'code' => '0', 
        'msg' => $task_id
    );
}
