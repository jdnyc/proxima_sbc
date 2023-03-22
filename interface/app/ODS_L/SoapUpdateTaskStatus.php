<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');
/**
 * Created by PhpStorm.
 * User: g.c.Shin
 * Date: 2015-04-03
 */

function SoapUpdateTaskStatus($task_id, $cartridge_id, $content_id, $progress, $status) {
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/nakan_ODA_ODS_L_SoapUpdateTaskStatus'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] task_id:'.$task_id.':::cartridge_id:'.$cartridge_id.'::::content_id:'.$content_id.':::progress:'.$progress.':::status:'.$status."\r\n", FILE_APPEND);
	//task_id:3241:::cartridge_id:1111::::content_id:100977:::progress:20:::status:processing
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ODA_ODS_L_SoapUpdateTaskStatus'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] task_id ===> '.$task_id."\r\n", FILE_APPEND);
	global $server;
	global $db;
	$user_id = $_SESSION['user']['user_id'];
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ODA_ODS_L_SoapUpdateTaskStatus'.date('Ymd').'.log', date('Y-m-d H:i:s').'SoapUpdateTaskStatus START:::'."\n", FILE_APPEND);

	try{
		$v_query = "select * from bc_task where task_id=".$task_id;

		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ODA_ODS_L_SoapUpdateTaskStatus'.date('Ymd').'.log', date('Y-m-d H:i:s').'$v_query:::'.$v_query."\n", FILE_APPEND);

		$task = $db->queryRow($v_query);

		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ODA_ODS_L_SoapUpdateTaskStatus'.date('Ymd').'.log', date('Y-m-d H:i:s').'$task:::'.print_r($task, true)."\n", FILE_APPEND);

		if(!empty($task)){
			$v_task_log_id = getSequence('TASK_LOG_SEQ');

			@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ODA_ODS_L_SoapUpdateTaskStatus'.date('Ymd').'.log', date('Y-m-d H:i:s').'$v_task_log_id:::'.$v_task_log_id."\n", FILE_APPEND);

			$v_bc_log_cnt = $db->queryOne("SELECT COUNT(*) AS BC_LOG_CNT FROM BC_TASK_LOG WHERE TASK_ID = $task_id");

			//Status change to start
			if($v_bc_log_cnt == 0){
				$v_query = "
					UPDATE	BC_TASK
					SET		STATUS			= '".$status."'
							,START_DATETIME	= '".date('YmdHis')."'
					WHERE	TASK_ID			= $task_id
				";
				@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ODA_ODS_L_SoapUpdateTaskStatus'.date('Ymd').'.log', date('Y-m-d H:i:s').'$v_query:::'.$v_query."\n", FILE_APPEND);
				$q = $db->exec($v_query);
			}

			if($status == 'processing'){
				$v_msg = $progress. ' % completed.';

				$v_query = "
					UPDATE	BC_TASK
					SET		STATUS		= '".$status."'
							,PROGRESS	= '".$progress."'
					WHERE	TASK_ID		= $task_id
				";
				@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ODA_ODS_L_SoapUpdateTaskStatus'.date('Ymd').'.log', date('Y-m-d H:i:s').'$v_query:::'.$v_query."\n", FILE_APPEND);
				$q = $db->exec($v_query);
				
				$v_query = "
					UPDATE	BC_ARCHIVE_REQUEST
					SET		TAPE_ID		= '".$cartridge_id."'
							,PROGRESS	= ".$progress."
							,STATUS		= 'PROCESSING'
					WHERE	TASK_ID		= $task_id
				";
				
				$q = $db->exec($v_query);
			}else if($status == 'complete'){
				$v_msg = 'process completed';
				@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ODA_ODS_L_SoapUpdateTaskStatus'.date('Ymd').'.log', date('Y-m-d H:i:s').'task type:::'.$task['type']."\n", FILE_APPEND);
				if($task['type'] == 'restore' || $task['type'] == RESTORE){
					$v_query = "
						UPDATE	BC_CONTENT_STATUS
						SET		RESTORE_DATE 	= '".date('YmdHis')."'
						WHERE	CONTENT_ID		= $content_id
					";
					@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ODA_ODS_L_SoapUpdateTaskStatus'.date('Ymd').'.log', date('Y-m-d H:i:s').'$v_query:::'.$v_query."\n", FILE_APPEND);
					$db->exec($v_query);

					$v_query = "
						UPDATE	BC_MEDIA
						SET		STATUS=0
								,DELETE_DATE=null
								,FLAG=null
						WHERE	CONTENT_ID=$content_id
						AND		MEDIA_TYPE='original'
					";
					@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ODA_ODS_L_SoapUpdateTaskStatus'.date('Ymd').'.log', date('Y-m-d H:i:s').'$v_query:::'.$v_query."\n", FILE_APPEND);
					$db->exec($v_query);
				} else if($task['type'] == 'pfr_restore' || $task['type'] == RESTORE_PFR){
					//When pfr finish, start workflow

					$pfr_info = $db->queryRow("
						SELECT	*
						FROM	BC_ARCHIVE_REQUEST
						WHERE	TASK_ID=".$task_id."
					");
					$pfr_content_id = $pfr_info['content_id'];

					//filepath can get from original content
					$ori_info = $db->queryRow("
						SELECT	*
						FROM	BC_MEDIA
						WHERE	CONTENT_ID=".$pfr_info['ori_content_id']."
						AND		MEDIA_TYPE='original'
					");
					$filepath = $ori_info['path'];
					$filepath_array = explode('/', $filepath);
					$filename = array_pop($filepath_array);
					$filename_array = explode('.',$filename);
					$file_ext = array_pop($filename_array);

					$pfr_task_mgr = new TaskManager($db);

					$channel = 'oda_pfr_reg';
					$job_priority = 1;
					$pfr_task_id = $pfr_task_mgr->insert_task_query_outside_data($pfr_content_id, $channel, $job_priority, $pfr_info['request_user_id'], $pfr_content_id.'.'.$file_ext);
				}else{
					//bc_media archive data insert. 2015.04.05 g.c.Shin
					$media_id = getSequence('SEQ_MEDIA_ID');
						
					$v_query = "
						insert into bc_media
						(media_id, content_id, storage_id, media_type,
						path, filesize, created_date, reg_type, STATUS)
						select	".$media_id.", content_id, storage_id, 'archive',
								path, filesize, '".date('YmdHis')."', 'ODS_L', 0
						from	bc_media
						where	content_id	= ".$content_id."
						and		media_type	= 'original'
					";
					@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ODA_ODS_L_SoapUpdateTaskStatus'.date('Ymd').'.log', date('Y-m-d H:i:s').'$v_query:::'.$v_query."\n", FILE_APPEND);
					$q = $db->exec($v_query);


					@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ODA_ODS_L_SoapUpdateTaskStatus'.date('Ymd').'.log', date('Y-m-d H:i:s').'$v_query:::'.$v_query."\n", FILE_APPEND);
					
					$v_query = "
						UPDATE	BC_CONTENT_STATUS
						SET		ARCHIVE_STATUS	= 'Y'
								,ARCHIVE_DATE	= '".date('YmdHis')."'
						WHERE	CONTENT_ID		= $content_id
					";

					$db->exec($v_query);
				}

				$v_query = "
					UPDATE	BC_TASK
					SET		STATUS				= '".$status."'
							,PROGRESS			= '".$progress."'
							,COMPLETE_DATETIME	= '".date('YmdHis')."'
					WHERE	TASK_ID				= $task_id
				";
				@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ODA_ODS_L_SoapUpdateTaskStatus'.date('Ymd').'.log', date('Y-m-d H:i:s').'$v_query:::'.$v_query."\n", FILE_APPEND);
				$q = $db->exec($v_query);

				$v_query = "
					UPDATE	BC_ARCHIVE_REQUEST
					SET		TAPE_ID				= '".$cartridge_id."'
							,PROGRESS			= 100
							,STATUS				= 'COMPLETE'
							,COMPLETE_DATETIME	= '".date('YmdHis')."'
					WHERE	TASK_ID				= $task_id
				";
				
				$db->exec($v_query);

				@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ODA_ODS_L_SoapUpdateTaskStatus'.date('Ymd').'.log', date('Y-m-d H:i:s').'$v_query:::'.$v_query."\n", FILE_APPEND);
			} else if($status == 'error'){
				$v_msg = 'error';

				$v_query = "
					UPDATE	BC_TASK
					SET		STATUS				= '".$status."'
							,PROGRESS			= '".$progress."'
							,COMPLETE_DATETIME	= '".date('YmdHis')."'
					WHERE	TASK_ID				= $task_id
				";

				@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ODA_ODS_L_SoapUpdateTaskStatus'.date('Ymd').'.log', date('Y-m-d H:i:s').'$v_query:::'.$v_query."\n", FILE_APPEND);

				$q = $db->exec($v_query);

				$v_query = "
					UPDATE	BC_ARCHIVE_REQUEST
					SET		TAPE_ID				= '".$cartridge_id."'
							,PROGRESS			= ".$progress."
							,STATUS				= 'FAILED'
							,COMPLETE_DATETIME	= '".date('YmdHis')."'
					WHERE	TASK_ID				= $task_id
				";
				$db->exec($v_query);
				
				$v_query = "
					UPDATE	BC_CONTENT_STATUS
					SET		ARCHIVE_STATUS	= 'E'
					WHERE	CONTENT_ID		= $content_id
				";
				$db->exec($v_query);
				
				@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ODA_ODS_L_SoapUpdateTaskStatus'.date('Ymd').'.log', date('Y-m-d H:i:s').'$v_query:::'.$v_query."\n", FILE_APPEND);
			}
			$v_query = "
				INSERT INTO BC_TASK_LOG
					(TASK_LOG_ID, TASK_ID, DESCRIPTION, CREATION_DATE, STATUS, PROGRESS)
				values
					(".$v_task_log_id.", ".$task_id.", '".$v_msg."', '".date('YmdHis')."', '".$status."', ".$progress.")
			";

			@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ODA_ODS_L_SoapUpdateTaskStatus'.date('Ymd').'.log', date('Y-m-d H:i:s').'$v_query:::'.$v_query."\n", FILE_APPEND);

			$q = $db->exec($v_query);
		}
	}
	catch(Exception $e){
		$msg = $e->getMessage();
		switch($e->getCode()){
			case ERROR_QUERY:
				$msg .= '( '.$db->last_query.' )';
			break;
		}

		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ODA_ODS_L_SoapUpdateTaskStatus'.date('Ymd').'.log', date('Y-m-d H:i:s').'$msg:::'.$msg."\n", FILE_APPEND);

		return array(
				'code' => '1',
				'msg' => $msg
		);
	}

	return array(
		'code' => '0',
		'msg' => 'success'
	);
}
