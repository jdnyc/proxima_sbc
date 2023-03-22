<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/MetaData.class.php');

try
{
	/*	
		interwork_oda_ods_d	:	ODA ODS_D, Sony
		interwork_oda_ods_l	:	ODA ODS_L, Sony
		interwork_flashnet	:	Flashnet, SGL
		request_system: request:::approve:::reject
	*/
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/insert_archive_request'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'POST:::'.print_r($_POST, true)."\n", FILE_APPEND);
	$case_management = $_POST['case_management'];
	$request_system = 'ArchiveSystem';
	if( $arr_sys_code['interwork_oda_ods_d']['use_yn'] == 'Y' ) {
		$request_system = 'ODS_D';
	} else if ( $arr_sys_code['interwork_oda_ods_l']['use_yn'] == 'Y' ) {
		$request_system = 'ODS_L';
	} else if ( $arr_sys_code['interwork_flashnet']['use_yn'] == 'Y' ) {
		$request_system = 'FLASHNET';
	}

	$user_id = $_SESSION['user']['user_id'];
	$current_datetime = date('YmdHis');

	if($request_system == 'ODS_L'){
		if($case_management == 'request'){
			//request
			$content_ids = json_decode($_POST['contents'], true);
			$type_job = $_POST['job_type'];
			$creation_datetime = date('YmdHis');
			$comment = $_POST['comment'];
			$pfr_start = $_POST['start'];
			$pfr_end = $_POST['end'];
			$pfr_new_title = $_POST['new_title'];

			if(empty($type_job)) throw new Exception(_text('MSG02097'));//Request is empty
			
			foreach ($content_ids as $content_id) {
				$query = "
					SELECT	A.*
					FROM	BC_ARCHIVE_REQUEST A
					WHERE	A.REQUEST_ID = (
											SELECT 	MAX(REQUEST_ID)
											FROM 	BC_ARCHIVE_REQUEST
											WHERE 	CONTENT_ID = ".$content_id."
											AND		REQUEST_TYPE='ARCHIVE')
				";
				$archive_request = $db->queryRow($query);
				$archive_request_id = $archive_request['request_id'];
				$archive_status = $archive_request['status'];

				if($archive_request_id != '' && $type_job == 'archive') {
					//Only archive case, if previous exists, update to bc_archive_request.
					$request_query = "
						UPDATE BC_ARCHIVE_REQUEST
						SET	STATUS='REQUEST'
							,CREATED_DATETIME='".$creation_datetime."'
							,REQUEST_USER_ID='".$user_id."'
							,COMMENTS='".$comment."'
						WHERE REQUEST_ID=".$archive_request_id."
					";
				} else {
					$request_id = getSequence('SEQ_ARCHIVE_SEQ');
					if($type_job == 'pfr') {
						$request_query = "
							INSERT INTO BC_ARCHIVE_REQUEST
							(REQUEST_ID, REQUEST_SYSTEM, REQUEST_TYPE, CREATED_DATETIME, COMMENTS, STATUS, REQUEST_USER_ID, ORI_CONTENT_ID, START_FRAME, END_FRAME, IF_KEY2)
							VALUES
							(".$request_id.",'".$request_system."', '".strtoupper($type_job)."','".$creation_datetime."', '".$comment."', 'REQUEST', '".$user_id."',".$content_id.", ".$pfr_start.", ".$pfr_end.", '".$pfr_new_title."')
						";
					} else {
						$request_query = "
							INSERT INTO BC_ARCHIVE_REQUEST
							(CONTENT_ID, REQUEST_ID, REQUEST_SYSTEM, REQUEST_TYPE, CREATED_DATETIME, COMMENTS, STATUS, REQUEST_USER_ID)
							VALUES
							(".$content_id.", ".$request_id.",'".$request_system."', '".strtoupper($type_job)."','".$creation_datetime."', '".$comment."', 'REQUEST', '".$user_id."')
						";
					}

					if($type_job =='archive'){
						$is_content_status_exists = $db->queryRow("SELECT * FROM BC_CONTENT_STATUS WHERE CONTENT_ID=".$content_id);
						if(!empty($is_content_status_exists)) {
							$content_status_query = "
								UPDATE	BC_CONTENT_STATUS
								SET		ARCHIVE_STATUS	= 'P'
								WHERE	CONTENT_ID	= ".$content_id
							;
						} else {
							$content_status_query = "
								INSERT INTO BC_CONTENT_STATUS
								(CONTENT_ID, ARCHIVE_STATUS)
								VALUES
								(".$content_id.", 'P')
							";
						}
						$db->exec($content_status_query);
					}
					
				}
				$db->exec($request_query);

			}
			echo (json_encode(array(
				'success' => true,
				'msg'	=>	 _text('MSG01009')
			)));

		}else if($case_management == 'approve'){
			$request_ids = json_decode($_POST['request_ids'], true);
			$content_ids = json_decode($_POST['content_ids'], true);
			$approve_reason = $_POST['approve_reason'];

			$creation_datetime = date('YmdHis');
			$v_return = array();
			$v_reslt_arr =	array();
			$results =	array();

			
			
			//All media info
			$query_media = "
				SELECT 	S.PATH AS STORAGE_PATH,
						M.*
				FROM 	BC_MEDIA M
						LEFT OUTER JOIN BC_STORAGE S ON(S.STORAGE_ID = M.STORAGE_ID)
				WHERE 	M.CONTENT_ID IN (".join(',',$content_ids).")
				AND 	M.MEDIA_TYPE = 'original'
			";
			$media_info = $db->queryAll($query_media);
			$media = array();
			foreach($media_info as $mi)
			{
				$media[$mi['content_id']] = $mi;
			}

			foreach ($request_ids as $request_id) {
				$query_info = "
					SELECT *
					FROM BC_ARCHIVE_REQUEST
					WHERE REQUEST_ID = ".$request_id."
				";
				$request_info = $db->queryRow($query_info);
				$type_job = strtolower($request_info['request_type']);

				if($type_job == 'pfr'){
					$content_id = $request_info['ori_content_id'];
					$pfr_start = $request_info['start_frame'];
					$pfr_end = $request_info['end_frame'];
					$pfr_title =  $request_info['if_key2'];
				}else{
					$content_id = $request_info['content_id'];
				}
				//Check current status
				$query = "
					SELECT	A.*
					FROM	BC_ARCHIVE_REQUEST A
					WHERE	A.REQUEST_ID = (
											SELECT 	MAX(REQUEST_ID)
											FROM 	BC_ARCHIVE_REQUEST
											WHERE 	CONTENT_ID = ".$content_id."
											AND		REQUEST_TYPE='ARCHIVE')
				";
				$archive_request = $db->queryRow($query);
				$archive_request_id = $archive_request['request_id'];
				$archive_status = $archive_request['status'];
				$archive_tape_id = $archive_request['tape_id'];
				$creation_datetime = date('YmdHis');

				$p_con = $db->queryRow("SELECT * FROM BC_CONTENT WHERE CONTENT_ID=".$content_id);
				$sys_metarow = MetaDataClass::getValueInfo('sys', $p_con['bs_content_id'], $content_id);
				$duration = $sys_metarow['sys_video_rt'];

				//If status is not fit to start task, exit.
				switch($type_job) {
					case 'archive':
					break;
					case 'restore':
						//If archive is not completed, exit
						if($archive_status != 'COMPLETE') {
							continue 2; // Switch is roop. So continue upper two depth.
						}
					break;
					case 'pfr':
						//If archive is not completed, exit
						if($archive_status != 'COMPLETE') {
							continue 2; // Switch is roop. So continue upper two depth.
						}
						
						//Insert new content
						if($pfr_title == '') $pfr_title = $p_con['title'];
						$new_content_id = getSequence('SEQ_CONTENT_ID');
						$insert_content_query = "
							INSERT INTO BC_CONTENT
							(CATEGORY_ID, CATEGORY_FULL_PATH, BS_CONTENT_ID, UD_CONTENT_ID,
							CONTENT_ID, TITLE, REG_USER_ID, CREATED_DATE, STATUS, EXPIRED_DATE, PARENT_CONTENT_ID)
							VALUES
							(".$p_con['category_id'].",'".$p_con['category_full_path']."',".$p_con['bs_content_id'].",'".$p_con['ud_content_id']."',
							".$new_content_id.",'".$db->escape($pfr_title)."','".$user_id."','".$creation_datetime."','".CONTENT_STATUS_REG_READY."','99991231235959', ".$content_id.")
						";
						$db->exec($insert_content_query);

						//Metadata insert
						$tablename = MetaDataClass::getTableName('usr', $p_con['ud_content_id'] );
						$fieldKey = array();
						$fieldValue = array();
						$ori_usr_meta = $db->queryRow("SELECT * FROM ".$tablename." WHERE USR_CONTENT_ID=".$content_id);
						$ori_usr_meta['usr_content_id'] = $new_content_id;
						foreach($ori_usr_meta as $key=>$val) {
							array_push($fieldKey, $key);
							array_push($fieldValue, $val);
						}
						$query_metadata = $db->InsertQuery($tablename ,$fieldKey, $fieldValue);
						$r = $db->exec($query_metadata);
					break;
				}

				//Insert into BC_TASK
				$channel = 'oda_'.$type_job;
				
				@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/insert_archive_request'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'channel:::'.$channel."\n", FILE_APPEND);
				
				if($type_job == 'pfr') {
					$task_content_id = $new_content_id;
				} else {
					$task_content_id = $content_id;
				}
				$ori_file = $media[$content_id]['path'];
				$ori_ext = array_pop(explode('.', $ori_file));
				$array_filepath_media = explode('/',str_replace('//','/',$ori_file));
				$filename = array_pop($array_filepath_media);
				$task = new TaskManager($db);
				$task_id = $task->insert_task_query_outside_data($task_content_id, $channel, '1',  $user_id, $filename);
				$task_log_query = "
					INSERT INTO BC_TASK_LOG
					(TASK_ID, DESCRIPTION, CREATION_DATE, STATUS, PROGRESS)
					VALUES
					(".$task_id.", '"._text('MN00233')."', '".date('YmdHis')."', 'queue', 0)
				";
				$db->exec($task_log_query);
				$approve_datetime = date('YmdHis');

				switch($type_job) {
					case 'archive':
						//update task_id
						$archive_update_query = "
							UPDATE BC_ARCHIVE_REQUEST
							SET	TASK_ID=".$task_id."
								,APPROVE_USER_ID = '".$user_id."'
								,APPROVE_DATETIME = '".$approve_datetime."'
								,APPROVE_REASON = '".$approve_reason."'
								,STATUS = 'APPROVE'
							WHERE REQUEST_ID=".$request_id."
						";
						$db->exec($archive_update_query);
					break;
					case 'restore':
						$restore_update_query = "
							UPDATE BC_ARCHIVE_REQUEST
							SET	TASK_ID=".$task_id."
								,TAPE_ID='".$archive_tape_id."'
								,APPROVE_USER_ID = '".$user_id."'
								,APPROVE_DATETIME = '".$approve_datetime."'
								,APPROVE_REASON = '".$approve_reason."'
								,STATUS = 'APPROVE'
							WHERE REQUEST_ID=".$request_id."
						";
						$db->exec($restore_update_query);
					break;
					case 'pfr':
						//update task_id, tape_id for restore.
						$restore_pfr_update_query = "
							UPDATE BC_ARCHIVE_REQUEST
							SET	TAPE_ID='".$archive_tape_id."'
								,TASK_ID=".$task_id."
								,APPROVE_USER_ID = '".$user_id."'
								,APPROVE_DATETIME = '".$approve_datetime."'
								,APPROVE_REASON = '".$approve_reason."'
								,STATUS = 'APPROVE'
								,CONTENT_ID = ".$new_content_id."
							WHERE REQUEST_ID=".$request_id."
						";
						$db->exec($restore_pfr_update_query);
					break;
				}
				
				//Main task start. This part, diff by each archive system.
				if ( $arr_sys_code['interwork_oda_ods_l']['use_yn'] == 'Y' ) {

					require_once($_SERVER['DOCUMENT_ROOT'].'/interface/app/ODS_L/client/ExecuteTaskODA.php');
					$data = array();

					$task_info = $db->queryRow("SELECT * FROM BC_TASK WHERE TASK_ID = '".$task_id."' ");
					$target_path = $task_info['target'];
					$filepath = pathinfo($target_path, PATHINFO_DIRNAME);
					$array_filepath_task = explode('/',str_replace('//','/',$target_path));
					$filename = array_pop($array_filepath_task);
					if($filepath == '.') $filepath = '';

					$filepath_full = str_replace('//', '/',$media[$content_id]['storage_path'].'/'.$filepath);

					$priority =100;

					if($type_job == 'archive'){
						$data = array(
							'objectID' 		=> $content_id,
							'objectCategory' 	=> 'AAA',
							'filesPathRoot' 	=> $filepath_full,
							'filename' 			=> $filename,
							'priority' 			=> $priority,
							'title' 			=> $p_con['title'],
							'comments' 			=> '',
							'task_id'			=> $task_id
						);
					}else if($type_job == 'restore'){
						$data = array(
							'objectID' 		=> $content_id,
							'objectCategory' 	=> 'AAA',
							'filesPathRoot' 	=> $filepath_full,
							'filename' 			=> $filename,
							'priority' 			=> $priority,
							'task_id'			=> $task_id
						);

					}else if($type_job=='pfr'){

						$pfr_storage_path = $arr_sys_code['interwork_oda_ods_l']['ref3'];
						$pfr_filepath = str_replace("\\", "/", $pfr_storage_path);
						$pfr_filename = $new_content_id.'.'.$ori_ext;
						$frame_rate = getFrameRate($content_id);
						$pfrMarkIn	= round($pfr_start * $frame_rate);
						$pfrMarkOut		= round($pfr_end  * $frame_rate);

						$data = array(
							'objectID' 		=> $content_id,
							'objectCategory' 	=> 'AAA',
							'filesPathRoot' 	=> str_replace('//', '/',$pfr_filepath),
							'priority' 			=> $priority,
							'destFile'			=> $pfr_filename,
							'sourceFile' 		=> $filename,
							'pfrMarkIn' 		=> $pfrMarkIn,
							'pfrMarkOut' 		=> $pfrMarkOut,
							'task_id'			=> $task_id
						);
					}
					
					$v_return = ExecuteTaskODA($data,$type_job,$content_id);
					@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/insert_archive_request'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'$result:::'.print_r($v_return, true)."\n", FILE_APPEND);

					if ($v_return['status'] == '0') {
						$v_temp = _text('MN00273').' : '.$p_con['title'].'<br />'._text('MSG02145').' : '.$v_return['message'];
						@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/insert_archive_request'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'$v_temp1:::'.$v_temp."\n", FILE_APPEND);
						array_push($v_reslt_arr, $v_temp);
						@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/insert_archive_request'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'$v_reslt_arr1:::'.print_r($v_reslt_arr, true)."\n", FILE_APPEND);
					}
					array_push($results, $result);
				}
			} // END foreach ($request_ids as $request_id)

			// Return message
			if( $arr_sys_code['interwork_oda_ods_l']['use_yn'] == 'Y' ) {
				if ( count($v_reslt_arr) > 0) {
					$v_temp = _text('MSG02127').' : '.count($content_ids).'<br />'._text('MSG02144').' : '.count($v_reslt_arr).'<br />';
					@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/insert_archive_request'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'$v_temp2:::'.$v_temp."\n", FILE_APPEND);
					echo (json_encode(array(
							'success' => false,
							'msg'	=>	 _text('MSG00088').'<br /><br />'.$v_temp.'<br />'.join($v_reslt_arr, '<br />'),
							'result' => $results
					)));
				}else{
					echo (json_encode(array(
						'success' => true,
						'msg'	=>	 _text('MSG01009'),//요청이 완료되었습니다
						'result' => $results
					)));
				}

			}
		}else if($case_management == 'reject'){
			$request_ids = json_decode($_POST['request_ids'], true);
			$reject_reason = $_POST['reject_reason'];
			
			foreach ($request_ids as $request_id) {
				$reject_datetime = date('YmdHis');

				$query_info = "
					SELECT *
					FROM BC_ARCHIVE_REQUEST
					WHERE REQUEST_ID = ".$request_id."
				";
				$request_info = $db->queryRow($query_info);
				$type_job = strtolower($request_info['request_type']);
				if($type_job == 'archive'){
					$content_id = $request_info['content_id'];
					$content_status_query = "
						UPDATE	BC_CONTENT_STATUS
						SET		ARCHIVE_STATUS	= 'N'
						WHERE	CONTENT_ID	= ".$content_id
					;
					$db->exec($content_status_query);

					$archive_update_query = "
						UPDATE BC_ARCHIVE_REQUEST
						SET	APPROVE_USER_ID = '".$user_id."'
							,REJECT_DATETIME = '".$reject_datetime."'
							,REJECT_REASON = '".$reject_reason."'
							,STATUS = 'REJECT'
						WHERE REQUEST_ID=".$request_id."
					";
					$db->exec($archive_update_query);

				}else{
					$archive_update_query = "
						UPDATE BC_ARCHIVE_REQUEST
						SET	APPROVE_USER_ID = '".$user_id."'
							,REJECT_DATETIME = '".$reject_datetime."'
							,REJECT_REASON = '".$reject_reason."'
							,STATUS = 'REJECT'
						WHERE REQUEST_ID=".$request_id."
					";
					$db->exec($archive_update_query);
				}

			}
			echo (json_encode(array(
				'success' => true,
				'msg'	=>	 _text('MSG02185')
			)));	
		}

	}else if ($request_system == 'ODS_D') {
		
		if($case_management == 'request'){
			//request
			$content_ids = json_decode($_POST['contents'], true);
			$type_job = $_POST['job_type'];
			$creation_datetime = date('YmdHis');
			$comment = $_POST['comment'];
			$pfr_start = $_POST['start'];
			$pfr_end = $_POST['end'];
			$pfr_new_title = $_POST['new_title'];

			if(empty($type_job)) throw new Exception(_text('MSG02097'));//Request is empty
			
			foreach ($content_ids as $content_id) {
				$query_info = "
					SELECT	IS_GROUP
					FROM	BC_CONTENT
					WHERE	CONTENT_ID =".$content_id;
				$is_group = $db->queryOne($query_info);

				if($is_group == 'G'){

					if($type_job != 'pfr'){

						$list_id = array();
						array_push($list_id, $content_id);

						$query_info = "
							SELECT	CONTENT_ID
							FROM	BC_CONTENT
							WHERE	PARENT_CONTENT_ID =".$content_id."
							AND	IS_DELETED = 'N'
						";
						$children = $db->queryAll($query_info);
						foreach ($children as $child) {
							array_push($list_id, $child['content_id']);
						}

						foreach ($list_id as $id) {
							$query = "
							SELECT	A.*
							FROM	BC_ARCHIVE_REQUEST A
							WHERE	A.REQUEST_ID = (
													SELECT 	MAX(REQUEST_ID)
													FROM 	BC_ARCHIVE_REQUEST
													WHERE 	CONTENT_ID = ".$id."
													AND		REQUEST_TYPE='ARCHIVE')
							";

							$archive_request = $db->queryRow($query);
							$archive_request_id = $archive_request['request_id'];
							$archive_status = $archive_request['status'];

							if($archive_request_id != '' && $type_job == 'archive') {
								//Only archive case, if previous exists, update to bc_archive_request.
								$request_query = "
									UPDATE BC_ARCHIVE_REQUEST
									SET	STATUS='REQUEST'
										,CREATED_DATETIME='".$creation_datetime."'
										,REQUEST_USER_ID='".$user_id."'
										,COMMENTS='".$comment."'
									WHERE REQUEST_ID=".$archive_request_id."
								";

								$content_status_query = "
									UPDATE	BC_CONTENT_STATUS
									SET		ARCHIVE_STATUS	= 'P'
									WHERE	CONTENT_ID	= ".$id
								;
								$db->exec($content_status_query);

							} else {
								$request_id = getSequence('SEQ_ARCHIVE_SEQ');
								
								$request_query = "
									INSERT INTO BC_ARCHIVE_REQUEST
									(CONTENT_ID, REQUEST_ID, REQUEST_SYSTEM, REQUEST_TYPE, CREATED_DATETIME, COMMENTS, STATUS, REQUEST_USER_ID)
									VALUES
									(".$id.", ".$request_id.",'".$request_system."', '".strtoupper($type_job)."','".$creation_datetime."', '".$comment."', 'REQUEST', '".$user_id."')
								";
								
								if($type_job =='archive'){
									$is_content_status_exists = $db->queryRow("SELECT * FROM BC_CONTENT_STATUS WHERE CONTENT_ID=".$id);
									if(!empty($is_content_status_exists)) {
										$content_status_query = "
											UPDATE	BC_CONTENT_STATUS
											SET		ARCHIVE_STATUS	= 'P'
											WHERE	CONTENT_ID	= ".$id
										;
									} else {
										$content_status_query = "
											INSERT INTO BC_CONTENT_STATUS
											(CONTENT_ID, ARCHIVE_STATUS)
											VALUES
											(".$id.", 'P')
										";
									}
									$db->exec($content_status_query);
								}
								
							}

							$db->exec($request_query);

						} // END foreach ($list_id as $id)

					}else{ // $type_job == 'pfr'

						$query = "
							SELECT	A.*
							FROM	BC_ARCHIVE_REQUEST A
							WHERE	A.REQUEST_ID = (
													SELECT 	MAX(REQUEST_ID)
													FROM 	BC_ARCHIVE_REQUEST
													WHERE 	CONTENT_ID = ".$content_id."
													AND		REQUEST_TYPE='ARCHIVE')
							";

						$archive_request = $db->queryRow($query);
						$archive_request_id = $archive_request['request_id'];
						$archive_status = $archive_request['status'];
						$request_id = getSequence('SEQ_ARCHIVE_SEQ');
						$request_query = "
							INSERT INTO BC_ARCHIVE_REQUEST
							(REQUEST_ID, REQUEST_SYSTEM, REQUEST_TYPE, CREATED_DATETIME, COMMENTS, STATUS, REQUEST_USER_ID, ORI_CONTENT_ID, START_FRAME, END_FRAME, IF_KEY2)
							VALUES
							(".$request_id.",'".$request_system."', '".strtoupper($type_job)."','".$creation_datetime."', '".$comment."', 'REQUEST', '".$user_id."',".$content_id.", ".$pfr_start.", ".$pfr_end.", '".$pfr_new_title."')
						";
						$db->exec($request_query);
					}

				}else if($is_group == 'I' || $is_group == 'C'){
					$query = "
					SELECT	A.*
					FROM	BC_ARCHIVE_REQUEST A
					WHERE	A.REQUEST_ID = (
											SELECT 	MAX(REQUEST_ID)
											FROM 	BC_ARCHIVE_REQUEST
											WHERE 	CONTENT_ID = ".$content_id."
											AND		REQUEST_TYPE='ARCHIVE')
					";

					$archive_request = $db->queryRow($query);
					$archive_request_id = $archive_request['request_id'];
					$archive_status = $archive_request['status'];

					if($archive_request_id != '' && $type_job == 'archive') {
						//Only archive case, if previous exists, update to bc_archive_request.
						$request_query = "
							UPDATE BC_ARCHIVE_REQUEST
							SET	STATUS='REQUEST'
								,CREATED_DATETIME='".$creation_datetime."'
								,REQUEST_USER_ID='".$user_id."'
								,COMMENTS='".$comment."'
							WHERE REQUEST_ID=".$archive_request_id."
						";
						$content_status_query = "
							UPDATE	BC_CONTENT_STATUS
							SET		ARCHIVE_STATUS	= 'P'
							WHERE	CONTENT_ID	= ".$content_id
						;
						$db->exec($content_status_query);
					} else {
						$request_id = getSequence('SEQ_ARCHIVE_SEQ');
						if($type_job == 'pfr') {
							$request_query = "
								INSERT INTO BC_ARCHIVE_REQUEST
								(REQUEST_ID, REQUEST_SYSTEM, REQUEST_TYPE, CREATED_DATETIME, COMMENTS, STATUS, REQUEST_USER_ID, ORI_CONTENT_ID, START_FRAME, END_FRAME, IF_KEY2)
								VALUES
								(".$request_id.",'".$request_system."', '".strtoupper($type_job)."','".$creation_datetime."', '".$comment."', 'REQUEST', '".$user_id."',".$content_id.", ".$pfr_start.", ".$pfr_end.", '".$pfr_new_title."')
							";
						} else {
							$request_query = "
								INSERT INTO BC_ARCHIVE_REQUEST
								(CONTENT_ID, REQUEST_ID, REQUEST_SYSTEM, REQUEST_TYPE, CREATED_DATETIME, COMMENTS, STATUS, REQUEST_USER_ID)
								VALUES
								(".$content_id.", ".$request_id.",'".$request_system."', '".strtoupper($type_job)."','".$creation_datetime."', '".$comment."', 'REQUEST', '".$user_id."')
							";
						}

						if($type_job =='archive'){
							$is_content_status_exists = $db->queryRow("SELECT * FROM BC_CONTENT_STATUS WHERE CONTENT_ID=".$content_id);
							if(!empty($is_content_status_exists)) {
								$content_status_query = "
									UPDATE	BC_CONTENT_STATUS
									SET		ARCHIVE_STATUS	= 'P'
									WHERE	CONTENT_ID	= ".$content_id
								;
							} else {
								$content_status_query = "
									INSERT INTO BC_CONTENT_STATUS
									(CONTENT_ID, ARCHIVE_STATUS)
									VALUES
									(".$content_id.", 'P')
								";
							}
							$db->exec($content_status_query);
						}
						
					}

					$db->exec($request_query);
				}else{

				}
				

			}
			echo (json_encode(array(
				'success' => true,
				'msg'	=>	 _text('MSG01009')
			)));

		}else if($case_management == 'approve'){
			$request_ids = json_decode($_POST['request_ids'], true);
			$content_ids = json_decode($_POST['content_ids'], true);
			$approve_reason = $_POST['approve_reason'];

			$total_request_ids = $request_ids;
			$total_content_ids = $content_ids;

			foreach ($request_ids as $id) {
				$query = "
					SELECT	REQUEST_ID
							,CONTENT_ID
					FROM	BC_ARCHIVE_REQUEST
					WHERE	CONTENT_ID IN (
								SELECT	C.CONTENT_ID
								FROM	(
									SELECT	A.CONTENT_ID, A.REQUEST_ID
									FROM	BC_ARCHIVE_REQUEST A
									WHERE	A.REQUEST_ID IN (".$id.")
									AND		A.REQUEST_TYPE IN ('ARCHIVE', 'RESTORE')
									AND		A.STATUS = 'REQUEST'
								) A
								LEFT OUTER JOIN BC_CONTENT C ON(C.PARENT_CONTENT_ID = A.CONTENT_ID)
					)
					AND		REQUEST_TYPE IN ('ARCHIVE', 'RESTORE')
					AND		STATUS = 'REQUEST'
				";
				$childrent_content = $db->queryAll($query);
				if(!empty($childrent_content)){
					foreach ($childrent_content as $child) {
						array_push($total_request_ids, $child['request_id']);
						array_push($total_content_ids, $child['content_id']);
					}
				}
			}

			$creation_datetime = date('YmdHis');
			$v_return = array();
			$v_reslt_arr =	array();
			$results =	array();

			
			
			//All media info
			$query_media = "
				SELECT 	S.PATH AS STORAGE_PATH,
						M.*
				FROM 	BC_MEDIA M
						LEFT OUTER JOIN BC_STORAGE S ON(S.STORAGE_ID = M.STORAGE_ID)
				WHERE 	M.CONTENT_ID IN (".join(',',$total_content_ids).")
				AND 	M.MEDIA_TYPE = 'original'
			";
			$media_info = $db->queryAll($query_media);
			$media = array();
			foreach($media_info as $mi)
			{
				$media[$mi['content_id']] = $mi;
			}
			foreach ($total_request_ids as $request_id) {
				$query_info = "
					SELECT *
					FROM BC_ARCHIVE_REQUEST
					WHERE REQUEST_ID = ".$request_id."
				";
				$request_info = $db->queryRow($query_info);
				$type_job = strtolower($request_info['request_type']);

				if($type_job == 'pfr'){
					$content_id = $request_info['ori_content_id'];
					$pfr_start = $request_info['start_frame'];
					$pfr_end = $request_info['end_frame'];
					$pfr_title =  $request_info['if_key2'];
				}else{
					$content_id = $request_info['content_id'];
				}
				//Check current status
				$query = "
					SELECT	A.*
					FROM	BC_ARCHIVE_REQUEST A
					WHERE	A.REQUEST_ID = (
											SELECT 	MAX(REQUEST_ID)
											FROM 	BC_ARCHIVE_REQUEST
											WHERE 	CONTENT_ID = ".$content_id."
											AND		REQUEST_TYPE='ARCHIVE')
				";
				$archive_request = $db->queryRow($query);
				$archive_request_id = $archive_request['request_id'];
				$archive_status = $archive_request['status'];
				$archive_tape_id = $archive_request['tape_id'];
				$creation_datetime = date('YmdHis');

				$p_con = $db->queryRow("SELECT * FROM BC_CONTENT WHERE CONTENT_ID=".$content_id);
				$sys_metarow = MetaDataClass::getValueInfo('sys', $p_con['bs_content_id'], $content_id);
				$duration = $sys_metarow['sys_video_rt'];

				//If status is not fit to start task, exit.
				switch($type_job) {
					case 'archive':
					break;
					case 'restore':
						//If archive is not completed, exit
						if($archive_status != 'COMPLETE') {
							continue 2; // Switch is roop. So continue upper two depth.
						}
					break;
					case 'pfr':
						//If archive is not completed, exit
						if($archive_status != 'COMPLETE') {
							continue 2; // Switch is roop. So continue upper two depth.
						}
						
						//Insert new content
						if($pfr_title == '') $pfr_title = $p_con['title'];
						$new_content_id = getSequence('SEQ_CONTENT_ID');
						$insert_content_query = "
							INSERT INTO BC_CONTENT
							(CATEGORY_ID, CATEGORY_FULL_PATH, BS_CONTENT_ID, UD_CONTENT_ID,
							CONTENT_ID, TITLE, REG_USER_ID, CREATED_DATE, STATUS, EXPIRED_DATE, PARENT_CONTENT_ID)
							VALUES
							(".$p_con['category_id'].",'".$p_con['category_full_path']."',".$p_con['bs_content_id'].",'".$p_con['ud_content_id']."',
							".$new_content_id.",'".$db->escape($pfr_title)."','".$user_id."','".$creation_datetime."','".CONTENT_STATUS_REG_READY."','99991231235959', ".$content_id.")
						";
						$db->exec($insert_content_query);

						//Metadata insert
						$tablename = MetaDataClass::getTableName('usr', $p_con['ud_content_id'] );
						$fieldKey = array();
						$fieldValue = array();
						$ori_usr_meta = $db->queryRow("SELECT * FROM ".$tablename." WHERE USR_CONTENT_ID=".$content_id);
						$ori_usr_meta['usr_content_id'] = $new_content_id;
						foreach($ori_usr_meta as $key=>$val) {
							array_push($fieldKey, $key);
							array_push($fieldValue, $val);
						}
						$query_metadata = $db->InsertQuery($tablename ,$fieldKey, $fieldValue);
						$r = $db->exec($query_metadata);
					break;
				}

				//Insert into BC_TASK
				$channel = 'oda_'.$type_job;
				
				@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/insert_archive_request'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'channel:::'.$channel."\n", FILE_APPEND);
				
				if($type_job == 'pfr') {
					$task_content_id = $new_content_id;
				} else {
					$task_content_id = $content_id;
				}
				$ori_file = $media[$content_id]['path'];
				$ori_ext = array_pop(explode('.', $ori_file));
				$array_filepath_media = explode('/',str_replace('//','/',$ori_file));
				$filename = array_pop($array_filepath_media);
				$task = new TaskManager($db);
				$task_id = $task->insert_task_query_outside_data($task_content_id, $channel, '1',  $user_id, $filename);
				$task_log_query = "
					INSERT INTO BC_TASK_LOG
					(TASK_ID, DESCRIPTION, CREATION_DATE, STATUS, PROGRESS)
					VALUES
					(".$task_id.", '"._text('MN00233')."', '".date('YmdHis')."', 'queue', 0)
				";
				$db->exec($task_log_query);
				$approve_datetime = date('YmdHis');

				switch($type_job) {
					case 'archive':
						//update task_id
						$archive_update_query = "
							UPDATE BC_ARCHIVE_REQUEST
							SET	TASK_ID=".$task_id."
								,APPROVE_USER_ID = '".$user_id."'
								,APPROVE_DATETIME = '".$approve_datetime."'
								,APPROVE_REASON = '".$approve_reason."'
								,STATUS = 'APPROVE'
							WHERE REQUEST_ID=".$request_id."
						";
						$db->exec($archive_update_query);
					break;
					case 'restore':
						$restore_update_query = "
							UPDATE BC_ARCHIVE_REQUEST
							SET	TASK_ID=".$task_id."
								,TAPE_ID='".$archive_tape_id."'
								,APPROVE_USER_ID = '".$user_id."'
								,APPROVE_DATETIME = '".$approve_datetime."'
								,APPROVE_REASON = '".$approve_reason."'
								,STATUS = 'APPROVE'
							WHERE REQUEST_ID=".$request_id."
						";
						$db->exec($restore_update_query);
					break;
					case 'pfr':
						//update task_id, tape_id for restore.
						$restore_pfr_update_query = "
							UPDATE BC_ARCHIVE_REQUEST
							SET	TAPE_ID='".$archive_tape_id."'
								,TASK_ID=".$task_id."
								,APPROVE_USER_ID = '".$user_id."'
								,APPROVE_DATETIME = '".$approve_datetime."'
								,APPROVE_REASON = '".$approve_reason."'
								,STATUS = 'APPROVE'
								,CONTENT_ID = ".$new_content_id."
							WHERE REQUEST_ID=".$request_id."
						";
						$db->exec($restore_pfr_update_query);
					break;
				}
				
				//Main task start. This part, diff by each archive system.
				if( $arr_sys_code['interwork_oda_ods_d']['use_yn'] == 'Y' ) {
					require_once($_SERVER['DOCUMENT_ROOT'].'/interface/app/ODS_D/xml_d77u.php');
					require_once($_SERVER['DOCUMENT_ROOT'].'/interface/app/ODS_D/client/ExecuteTaskODA.php');
					$pfr_storage_path = $arr_sys_code['interwork_oda_ods_d']['ref4'];
					$v_return['status'] = '0';
					switch($type_job) {
						case 'archive':
							fn_create_xml_d77u($content_id);
						break;
						case 'restore':
							$data = array(
								'RestoreMode' => 'C',
								'TaskID' => $task_id,
								'CartridgeID' => $archive_tape_id,
								'ContentID' => $content_id,
								'MarkIn' => '',
								'MarkOut' => '',
								'TargetPath' => ''
								);
							$v_return = ExecuteTaskODA($data);
						break;
						case 'pfr':
							$frame_rate = getFrameRate($content_id);
							$tc_start	= round($pfr_start * $frame_rate);
							$tc_end		= round($pfr_end * $frame_rate);
							$data = array(
								'RestoreMode' => 'P',
								'TaskID' => $task_id,
								'CartridgeID' => $archive_tape_id,
								'ContentID' => $content_id,
								'MarkIn' => $tc_start,
								'MarkOut' => $tc_end,
								'TargetPath' => $pfr_storage_path.'\\'.$new_content_id.'.'.$ori_ext
								);
							$v_return = ExecuteTaskODA($data);
						break;
					}
					
					if ($v_return['status'] != '0') {
						$v_temp = _text('MN00273').' : '.$p_con['title'].'<br />'._text('MSG02145').' : '.$v_return['message'];
						@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/insert_archive_request'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'$v_temp1:::'.$v_temp."\n", FILE_APPEND);
						array_push($v_reslt_arr, $v_temp);
						@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/insert_archive_request'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'$v_reslt_arr1:::'.print_r($v_reslt_arr, true)."\n", FILE_APPEND);
					}
				} else if ( $arr_sys_code['interwork_oda_ods_l']['use_yn'] == 'Y' ) {

					require_once($_SERVER['DOCUMENT_ROOT'].'/interface/app/ODS_L/client/ExecuteTaskODA.php');
					$data = array();

					$task_info = $db->queryRow("SELECT * FROM BC_TASK WHERE TASK_ID = '".$task_id."' ");
					$target_path = $task_info['target'];
					$filepath = pathinfo($target_path, PATHINFO_DIRNAME);
					$array_filepath_task = explode('/',str_replace('//','/',$target_path));
					$filename = array_pop($array_filepath_task);
					if($filepath == '.') $filepath = '';

					$filepath_full = str_replace('//', '/',$media[$content_id]['storage_path'].'/'.$filepath);

					$priority =100;

					if($type_job == 'archive'){
						$data = array(
							'objectID' 		=> $content_id,
							'objectCategory' 	=> 'AAA',
							'filesPathRoot' 	=> $filepath_full,
							'filename' 			=> $filename,
							'priority' 			=> $priority,
							'title' 			=> $p_con['title'],
							'comments' 			=> '',
							'task_id'			=> $task_id
						);
					}else if($type_job == 'restore'){
						$data = array(
							'objectID' 		=> $content_id,
							'objectCategory' 	=> 'AAA',
							'filesPathRoot' 	=> $filepath_full,
							'filename' 			=> $filename,
							'priority' 			=> $priority,
							'task_id'			=> $task_id
						);

					}else if($type_job=='pfr'){

						$pfr_storage_path = $arr_sys_code['interwork_oda_ods_l']['ref3'];
						$pfr_filepath = str_replace("\\", "/", $pfr_storage_path);
						$pfr_filename = $new_content_id.'.'.$ori_ext;
						$frame_rate = getFrameRate($content_id);
						$pfrMarkIn	= round($pfr_start * $frame_rate);
						$pfrMarkOut		= round($pfr_end  * $frame_rate);

						$data = array(
							'objectID' 		=> $content_id,
							'objectCategory' 	=> 'AAA',
							'filesPathRoot' 	=> str_replace('//', '/',$pfr_filepath),
							'priority' 			=> $priority,
							'destFile'			=> $pfr_filename,
							'sourceFile' 		=> $filename,
							'pfrMarkIn' 		=> $pfrMarkIn,
							'pfrMarkOut' 		=> $pfrMarkOut,
							'task_id'			=> $task_id
						);
					}
					
					$v_return = ExecuteTaskODA($data,$type_job,$content_id);
					@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/insert_archive_request'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'$result:::'.print_r($v_return, true)."\n", FILE_APPEND);

					if ($v_return['status'] == '0') {
						$v_temp = _text('MN00273').' : '.$p_con['title'].'<br />'._text('MSG02145').' : '.$v_return['message'];
						@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/insert_archive_request'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'$v_temp1:::'.$v_temp."\n", FILE_APPEND);
						array_push($v_reslt_arr, $v_temp);
						@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/insert_archive_request'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'$v_reslt_arr1:::'.print_r($v_reslt_arr, true)."\n", FILE_APPEND);
					}
					array_push($results, $result);
				}
			}
			if( $arr_sys_code['interwork_oda_ods_d']['use_yn'] == 'Y' ) {
				@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/insert_archive_request'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'$v_reslt_arr2:::'.print_r($v_reslt_arr, true)."\n", FILE_APPEND);
				if ( count($v_reslt_arr) > 0) {
					$v_temp = _text('MSG02127').' : '.count($total_content_ids).'<br />'._text('MSG02144').' : '.count($v_reslt_arr).'<br />';
					@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/insert_archive_request'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'$v_temp2:::'.$v_temp."\n", FILE_APPEND);
					//throw new Exception(_text('MSG00088')."<br />.$v_temp.
					//		<br />".join($v_reslt_arr, "<br />"));
					//전체 요청 건수 count($content_ids)
					//요청 오류 건수 count($v_reslt_arr)
					//오류 메세지
					echo (json_encode(array(
							'success' => false,
							'msg'	=>	 _text('MSG00088').'<br /><br />'.$v_temp.'<br />'.join($v_reslt_arr, '<br />'),
							'result' => $results
					)));
				}else{
					echo (json_encode(array(
						'success' => true,
						'msg'	=>	 _text('MSG01009'),//요청이 완료되었습니다
						'result' => $results
					)));
				}
			}
			
			if( $arr_sys_code['interwork_oda_ods_l']['use_yn'] == 'Y' ) {
				if ( count($v_reslt_arr) > 0) {
					$v_temp = _text('MSG02127').' : '.count($content_ids).'<br />'._text('MSG02144').' : '.count($v_reslt_arr).'<br />';
					@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/insert_archive_request'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'$v_temp2:::'.$v_temp."\n", FILE_APPEND);
					echo (json_encode(array(
							'success' => false,
							'msg'	=>	 _text('MSG00088').'<br /><br />'.$v_temp.'<br />'.join($v_reslt_arr, '<br />'),
							'result' => $results
					)));
				}else{
					echo (json_encode(array(
						'success' => true,
						'msg'	=>	 _text('MSG01009'),//요청이 완료되었습니다
						'result' => $results
					)));
				}

			}
		}else if($case_management == 'reject'){
			$request_ids = json_decode($_POST['request_ids'], true);
			$reject_reason = $_POST['reject_reason'];
			
			$total_request_ids = $request_ids;

			foreach ($request_ids as $id) {
				$query = "
					SELECT	REQUEST_ID
							,CONTENT_ID
					FROM	BC_ARCHIVE_REQUEST
					WHERE	CONTENT_ID IN (
								SELECT	C.CONTENT_ID
								FROM	(
									SELECT	A.CONTENT_ID, A.REQUEST_ID
									FROM	BC_ARCHIVE_REQUEST A
									WHERE	A.REQUEST_ID IN (".$id.")
									AND		A.REQUEST_TYPE IN ('ARCHIVE', 'RESTORE')
									AND		A.STATUS = 'REQUEST'
								) A
								LEFT OUTER JOIN BC_CONTENT C ON(C.PARENT_CONTENT_ID = A.CONTENT_ID)
					)
					AND		REQUEST_TYPE IN ('ARCHIVE', 'RESTORE')
					AND		STATUS = 'REQUEST'
				";
				$childrent_content = $db->queryAll($query);
				if(!empty($childrent_content)){
					foreach ($childrent_content as $child) {
						array_push($total_request_ids, $child['request_id']);
					}
				}
			}

			foreach ($total_request_ids as $request_id) {
				$reject_datetime = date('YmdHis');

				$query_info = "
					SELECT *
					FROM BC_ARCHIVE_REQUEST
					WHERE REQUEST_ID = ".$request_id."
				";
				$request_info = $db->queryRow($query_info);
				$type_job = strtolower($request_info['request_type']);
				if($type_job == 'archive'){
					$content_id = $request_info['content_id'];
					$content_status_query = "
						UPDATE	BC_CONTENT_STATUS
						SET		ARCHIVE_STATUS	= 'N'
						WHERE	CONTENT_ID	= ".$content_id
					;
					$db->exec($content_status_query);

					$archive_update_query = "
						UPDATE BC_ARCHIVE_REQUEST
						SET	APPROVE_USER_ID = '".$user_id."'
							,REJECT_DATETIME = '".$reject_datetime."'
							,REJECT_REASON = '".$reject_reason."'
							,STATUS = 'REJECT'
						WHERE REQUEST_ID=".$request_id."
					";
					$db->exec($archive_update_query);

				}else{
					$archive_update_query = "
						UPDATE BC_ARCHIVE_REQUEST
						SET	APPROVE_USER_ID = '".$user_id."'
							,REJECT_DATETIME = '".$reject_datetime."'
							,REJECT_REASON = '".$reject_reason."'
							,STATUS = 'REJECT'
						WHERE REQUEST_ID=".$request_id."
					";
					$db->exec($archive_update_query);
				}

			}
			echo (json_encode(array(
				'success' => true,
				'msg'	=>	 _text('MSG02185')
			)));	
		}

	} else if($request_system == 'FLASHNET'){
		if($case_management == 'request'){
			$content_ids = json_decode($_POST['contents'], true);
			$type_job = $_POST['job_type'];
			$creation_datetime = date('YmdHis');
			$comment = $_POST['comment'];
			$pfr_start = $_POST['start'];
			$pfr_end = $_POST['end'];
			$pfr_new_title = $_POST['new_title'];
			$archive_group = $_POST['archive_group'];

			if(empty($type_job)) throw new Exception(_text('MSG02097'));//Request is empty

			foreach ($content_ids as $content_id) {
				$query = "
					SELECT	A.*
					FROM	BC_ARCHIVE_REQUEST A
					WHERE	A.REQUEST_ID = (
											SELECT 	MAX(REQUEST_ID)
											FROM 	BC_ARCHIVE_REQUEST
											WHERE 	CONTENT_ID = ".$content_id."
											AND		REQUEST_TYPE='ARCHIVE')
				";
				$archive_request = $db->queryRow($query);
				$archive_request_id = $archive_request['request_id'];
				$archive_status = $archive_request['status'];

				if($archive_request_id != '' && $type_job == 'archive') {
				}else{
					$request_id = getSequence('SEQ_ARCHIVE_SEQ');
					if($type_job == 'archive'){
						$request_query = "
							INSERT INTO BC_ARCHIVE_REQUEST
							(
								CONTENT_ID, 
								REQUEST_ID, 
								REQUEST_SYSTEM, 
								REQUEST_TYPE, 
								CREATED_DATETIME, 
								COMMENTS, 
								STATUS, 
								REQUEST_USER_ID,
								IF_KEY1
							)
							VALUES
							(
								".$content_id.", 
								".$request_id.",
								'".$request_system."', 
								'".strtoupper($type_job)."',
								'".$creation_datetime."', 
								'".$comment."',
								'REQUEST', 
								'".$user_id."',
								'".$archive_group."'
							)
						";
						$db->exec($request_query);

						$is_content_status_exists = $db->queryRow("SELECT * FROM BC_CONTENT_STATUS WHERE CONTENT_ID=".$content_id);
						if(!empty($is_content_status_exists)) {
						} else {
							$content_status_query = "
								INSERT INTO BC_CONTENT_STATUS
								(
									CONTENT_ID, 
									ARCHIVE_STATUS
								)
								VALUES
								(
									".$content_id.", 
									'P'
								)
							";

							$db->exec($content_status_query);
						}
						
					}else{
						if($archive_status == "COMPLETE"){
							if($type_job == 'restore'){
								$request_query = "
									INSERT INTO BC_ARCHIVE_REQUEST
									(
										CONTENT_ID, 
										REQUEST_ID, 
										REQUEST_SYSTEM, 
										REQUEST_TYPE, 
										CREATED_DATETIME, 
										COMMENTS, 
										STATUS, 
										REQUEST_USER_ID
									)
									VALUES
									(
										".$content_id.", 
										".$request_id.",
										'".$request_system."', 
										'".strtoupper($type_job)."',
										'".$creation_datetime."', 
										'".$comment."', 
										'REQUEST', 
										'".$user_id."'
									)
								";
							}else if($type_job == 'pfr'){
								$request_query = "
									INSERT INTO BC_ARCHIVE_REQUEST
									(
										REQUEST_ID, 
										REQUEST_SYSTEM, 
										REQUEST_TYPE, 
										CREATED_DATETIME, 
										COMMENTS, 
										STATUS, 
										REQUEST_USER_ID, 
										ORI_CONTENT_ID, 
										START_FRAME, 
										END_FRAME, 
										IF_KEY2
									)
									VALUES
									(
										".$request_id.",
										'".$request_system."', 
										'".strtoupper($type_job)."',
										'".$creation_datetime."', 
										'".$comment."', 
										'REQUEST', 
										'".$user_id."',
										".$content_id.", 
										".$pfr_start.", 
										".$pfr_end.", 
										'".$pfr_new_title."'
									)
								";
							}

							$db->exec($request_query);
						}else{
						}
					}
				}
			}
			
			echo (json_encode(array(
				'success' => true,
				'msg'	=>	 _text('MSG01009')
			)));

		}else if($case_management == 'approve'){
			$request_ids = json_decode($_POST['request_ids'], true);
			$content_ids = json_decode($_POST['content_ids'], true);
			$approve_reason = $_POST['approve_reason'];
			$approve_datetime = date('YmdHis');
			//All media info
			$query_media = "
				SELECT 	S.PATH AS STORAGE_PATH,
						M.*
				FROM 	BC_MEDIA M
						LEFT OUTER JOIN BC_STORAGE S ON(S.STORAGE_ID = M.STORAGE_ID)
				WHERE 	M.CONTENT_ID IN (".join(',',$content_ids).")
				AND 	M.MEDIA_TYPE = 'original'
			";
			$media_info = $db->queryAll($query_media);
			$media = array();
			foreach($media_info as $mi){
				$media[$mi['content_id']] = $mi;
			}

			foreach ($request_ids as $request_id) {
				$query_info = "
					SELECT *
					FROM BC_ARCHIVE_REQUEST
					WHERE REQUEST_ID = ".$request_id."
				";
				$request_info = $db->queryRow($query_info);
				$type_job = strtolower($request_info['request_type']);
				$archive_group = $request_info['if_key1'];

				if($type_job == 'pfr'){
					$content_id = $request_info['ori_content_id'];
					$pfr_start = $request_info['start_frame'];
					$pfr_end = $request_info['end_frame'];
					$pfr_title =  $request_info['if_key2'];
				}else{
					$content_id = $request_info['content_id'];
				}

				$p_con = $db->queryRow("SELECT * FROM BC_CONTENT WHERE CONTENT_ID=".$content_id);
				$bs_content_id = $p_con['bs_content_id'];
				$is_group = $p_con['is_group'];


				require_once ($_SERVER['DOCUMENT_ROOT'] . '/lib/SGL.class.php');
				$insert_task = new TaskManager($db);

				if($type_job == 'archive'){
					if($bs_content_id == SEQUENCE) {
						$channel = 'sgl_archive_seq';
					} else {
						if($is_group == 'G') {
							$channel = 'sgl_archive_group';
						} else {
							$channel = 'sgl_archive';
						}
					}

					@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sgl_archive_request_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] before insert task ===> '."\r\n", FILE_APPEND);
					$insert_task->start_task_workflow($content_id, $channel, $user_id);
					@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sgl_archive_request_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] after insert task ===> '."\r\n", FILE_APPEND);
					$task_id = $insert_task->get_task_id();
					@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sgl_archive_request_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] task_id ===> '.$task_id."\r\n", FILE_APPEND);

					//update task_id
					$archive_update_query = "
						UPDATE BC_ARCHIVE_REQUEST
						SET	TASK_ID=".$task_id.",
							STATUS = 'APPROVE',
							APPROVE_USER_ID = '".$user_id."',
							APPROVE_DATETIME = '".$approve_datetime."',
							APPROVE_REASON = '".$approve_reason."'
						WHERE REQUEST_ID=".$request_id."
					";
					$db->exec($archive_update_query);

					$task_info = $db->queryRow("
								SELECT *
								FROM BC_TASK
								WHERE TASK_ID = '$task_id'
							");

					$sgl = new SGL();
					$sgl_filepath = SGL_ROOT.'/'.$task_info['source'];

					$display_name = $p_con['title'];
					$priority = $p_con['archive_priority'];
					$strFileCount = $p_con['group_count'];

					if(empty($priority)) {
						$priority = 5;
					}

					if($bs_content_id == SEQUENCE) {
						$v_return = $sgl->FlashNetSequnceArchive($content_id, $sgl_filepath, $archive_group, $display_name, $priority, $strFileCount);
					} else {
						if($is_group == 'G') {
							$arr_filepath_q = "
								SELECT	*
								FROM	BC_MEDIA
								WHERE	CONTENT_ID IN (
											SELECT	CONTENT_ID
											FROM	BC_CONTENT 
											WHERE	CONTENT_ID=".$content_id."
											OR		PARENT_CONTENT_ID=".$content_id."
										)
								AND		MEDIA_TYPE='original'
							";
							$arr_filepath = $db->queryAll($arr_filepath_q);
							$sgl_arr_filepath = array();
							foreach($arr_filepath as $fp) {
								$sgl_arr_filepath[] = SGL_ROOT.'/'.$fp['path'];
							}
							$v_return = $sgl->FlashNetGroupArchive($content_id, $sgl_arr_filepath, $archive_group, $display_name, $priority);
						} else {
							$v_return = $sgl->FlashNetArchive($content_id, $sgl_filepath, $archive_group, $display_name, $priority);
						}
					}

					if(!$v_return['success']) {
						if($v_return['msg'] == '') 
							$v_return['msg'] = 'error';

						$db->exec("
							UPDATE BC_TASK
							SET STATUS = 'error'
							WHERE TASK_ID = '$task_id'
						");
						$now = date('YmdHis');
						$db->exec("
							INSERT INTO BC_TASK_LOG
								(TASK_ID, DESCRIPTION, CREATION_DATE, STATUS)
							VALUES
								('$task_id', '".$v_return['msg']."', '$now', 'error')
						");
						array_push($v_reslt_arr, $v_return['msg']);
						//throw new Exception ();
					} else {
						$db->exec("
							UPDATE SGL_ARCHIVE
							SET	SESSION_ID = '".$v_return['request_id']."'
							WHERE TASK_ID = '$task_id'
						");
						$db->exec("
							UPDATE BC_CONTENT
							SET	UAN = '".$v_return['uan']."'
							WHERE CONTENT_ID = '$content_id'
						");
					}

				}else if($type_job == 'restore'){
					
					if($bs_content_id == SEQUENCE) {
						$channel = 'sgl_restore_seq';
					} else {
						if($is_group == 'G') {
							$channel = 'sgl_restore_group';
						} else {
							$channel = 'sgl_restore';
						}
					}

					$query = "
							SELECT 	* 
							FROM 	bc_content
							WHERE 	content_id = $content_id
						";
					$infos = $db->queryRow($query);
					$del_yn = $infos['del_yn'];
					$del_status = $infos['del_status'];
					$displayname = $infos['title'];
					$bs_content_id = $infos['bs_content_id'];
					$sgl_uan = $infos['uan'];
					$is_group = $infos['is_group'];

					$query = "
							SELECT 	ud_content_id,
									category_id,
									archive_date 
							FROM 	bc_content
							WHERE 	content_id = $content_id
						";
					$content_info = $db->queryRow($query);

					$check_ud_content = $content_info['ud_content_id'];
					$category_id = $content_info['category_id'];
					$archive_date = $content_info['archive_date'];

					$query = "
							SELECT 	path
							FROM 	bc_media
							WHERE 	media_id = (
										SELECT 	max(media_id)
										FROM 	bc_media 
										WHERE 	content_id = $content_id 
										AND 	media_type = 'archive'
									)
						";
					$full_path =$db->queryOne($query);
					$arr_full_path = explode('/', $full_path);
					$filename = array_pop($arr_full_path);
					$filename_arr = explode('.', $filename);
					$archive_type = array_pop($filename_arr);
					if($content_id < 43709 && $check_ud_content == '358' && $user_id != 'alex2207' && $user_id != 'bkjeong' && $user_id != 'reidar') {
						$ask_admin = true;
					} else {

						$insert_task->set_priority(200);
						$insert_task->start_task_workflow($content_id, $channel, $user_id);
						$task_id = $insert_task->get_task_id();

						//update task_id in bc_archive_request
						$query = "
							UPDATE BC_ARCHIVE_REQUEST
							SET	TASK_ID=".$task_id.",
								STATUS = 'APPROVE',
								APPROVE_USER_ID = '".$user_id."',
								APPROVE_DATETIME = '".$approve_datetime."',
								APPROVE_REASON = '".$approve_reason."'
							WHERE REQUEST_ID=".$request_id."
						";
						$db->exec($query);

						// BC_TASK 테이블에 작업 추가 후 SGL Webservice로 작업 요청

						// 추후 작업개수 제한 등의 작업은 필요함
						// 작업요청 후 성공이면 SGL_ARCHIVE 테이블 업데이트 아니면 에러 메세지 처리
						// SGL 서버가 Active 일 경우에만 진행
						$is_active = $db->queryOne("
										SELECT CODE
										FROM BC_CODE
										WHERE CODE_TYPE_ID =
											(SELECT ID
											FROM BC_CODE_TYPE
											WHERE CODE = 'sgl_active_check')
										AND NAME = 'Active Code'
									");

						if($is_active == 'Y') {
							// 작업요청에 필요한 정보를 얻기 위해서 BC_TASK 테이블 조회
							$task_info = $db->queryRow("
											SELECT *
											FROM BC_TASK
											WHERE TASK_ID = $task_id
										");
							$sgl = new SGL();
							$sgl_filepath = SGL_ROOT.'/'.$task_info['target'];
							//priority 정보는 설정된 값에서 가져옴 없으면 default
							$priority = $db->queryOne("
										SELECT E.RESTORE_PRIORITY
										FROM BC_CONTENT C, BC_CATEGORY_ENV E
										WHERE C.CONTENT_ID = $content_id
										AND C.CATEGORY_ID = E.CATEGORY_ID
									");
							if(!empty($priority)) {
								$priority = 4;
							}

							$pfr_start = '';
							$pfr_end = '';
							//$return = $sgl->FlashNetRestoreUAN($sgl_uan, '', $displayname, $priority);
							if($bs_content_id == SEQUENCE) {
								//UAN 리스토어시엔 filepath를 null로 줘야 원래위치에 들어감
								$return = $sgl->FlashNetRestoreUAN($sgl_uan, '', $displayname, $priority);
							} else {
								if($is_group == 'G') {
									$return = $sgl->FlashNetRestoreUAN($sgl_uan, '', $displayname, $priority);
								} else {
									$return = $sgl->FlashNetRestore($content_id, $sgl_filepath, $displayname, $priority, $pfr_start, $pfr_end);
								}
							}


							if(!$return['success']) {
								// BC_TASK는 error로 업데이트
								$db->exec("
									UPDATE BC_TASK
									SET STATUS = 'error'
									WHERE TASK_ID = $task_id
								");
								$now = date('YmdHis');
								$db->exec("
									INSERT INTO BC_TASK_LOG
										(TASK_ID, DESCRIPTION, CREATION_DATE)
									VALUES
										($task_id, '".$return['msg']."', '$now')
								");

								throw new Exception ($return['msg']);
							} else {
								$db->exec("
									UPDATE SGL_ARCHIVE
									SET SESSION_ID = ".$return['request_id']."
									WHERE TASK_ID = $task_id
								");
							}
						}
					}

					if($ask_admin) {
						print_r('222');
						//'msg' => '해당 영상은 관리자에게 문의 바랍니다' MSG01011
						throw new exception(_text('MSG01011'));
						//				echo json_encode(array(
						//					'success' => true,
						//					//'msg' => '해당 영상은 관리자에게 문의 바랍니다' MSG01011
						//					'msg' => _text('MSG01011')
						//				));
					} else {
						//리스토어가 진행중인것을 표기하기 위해 bc_restore_ing에 해당 content_id를 입력함 (2014.01.17 임찬모)
						$query = "insert into bc_restore_ing (content_id) values ($content_id)";
						$db->exec($query);
						//리스토어 실패등으로 인해서 찌꺼기가 남을 경우 자동으로 삭제하기 위해서 restore_date를 업데이트 함 (2014.01.17 임찬모)
						//restore_date 업데이트는 아카이브가 되어 있고 원본이 삭제된 경우에 한해서만 작동하도록 조건을 둠
						//위 작업 이후에는 원래 로직을 따름
						$restore_time = date('YmdHis');

						$query = "update bc_media set status = 0, delete_date = null  where content_id = $content_id and media_type = 'original' and status = '1' ";
						$db->exec($query);
						$query = "update bc_content set del_status = '0', del_yn = 'N', restore_date = '$restore_time' where content_id = $content_id and del_status = '1' and del_yn='Y'";
						$db->exec($query);
					}
				}else if($type_job == 'pfr'){
					$query = "
						UPDATE BC_ARCHIVE_REQUEST
						SET 	STATUS = 'APPROVE',
								APPROVE_USER_ID = '".$user_id."',
								APPROVE_DATETIME = '".$approve_datetime."',
								APPROVE_REASON = '".$approve_reason."'
						WHERE REQUEST_ID=".$request_id."
					";
					$db->exec($query);
					//Insert new content
					if($pfr_title == '') $pfr_title = $p_con['title'];
					$new_content_id = getSequence('SEQ_CONTENT_ID');
					$creation_datetime = date('YmdHis');
					$insert_content_query = "
						INSERT INTO BC_CONTENT
						(CATEGORY_ID, CATEGORY_FULL_PATH, BS_CONTENT_ID, UD_CONTENT_ID,
						CONTENT_ID, TITLE, REG_USER_ID, CREATED_DATE, STATUS, EXPIRED_DATE, PARENT_CONTENT_ID)
						VALUES
						(".$p_con['category_id'].",'".$p_con['category_full_path']."',".$p_con['bs_content_id'].",'".$p_con['ud_content_id']."',
						".$new_content_id.",'".$db->escape($pfr_title)."','".$user_id."','".$creation_datetime."','".CONTENT_STATUS_REG_READY."','99991231235959', ".$content_id.")
					";
					$db->exec($insert_content_query);
					//Metadata insert
					$tablename = MetaDataClass::getTableName('usr', $p_con['ud_content_id'] );
					$fieldKey = array();
					$fieldValue = array();
					$ori_usr_meta = $db->queryRow("SELECT * FROM ".$tablename." WHERE USR_CONTENT_ID=".$content_id);
					$ori_usr_meta['usr_content_id'] = $new_content_id;
					foreach($ori_usr_meta as $key=>$val) {
						array_push($fieldKey, $key);
						array_push($fieldValue, $val);
					}
					$query_metadata = $db->InsertQuery($tablename ,$fieldKey, $fieldValue);
					$r = $db->exec($query_metadata);

					$ori_file = $media[$content_id]['path'];
					$ori_ext = array_pop(explode('.', $ori_file));
					$target = $new_content_id.".".$ori_ext;
					$sgl_filepath = SGL_PFR_ROOT.'/'.$target;
					
					$channel = 'sgl_pfr_restore';
					$insert_task->set_priority(200);
					$arr_source_path = array('source_path' => '', 'target_path' => $target);
					$arr_param_info = array($arr_source_path);
					$insert_task->start_task_workflow($content_id, $channel, $user_id, $arr_param_info);
					$task_id = $insert_task->get_task_id();
					$displayname = $pfr_title;

					//update task_id in bc_archive_request
					$query = "
						UPDATE BC_ARCHIVE_REQUEST
						SET	TASK_ID=".$task_id.",
							CONTENT_ID = ".$new_content_id."
						WHERE REQUEST_ID=".$request_id."
					";
					$db->exec($query);
					$priority = $db->queryOne("
							SELECT E.RESTORE_PRIORITY
							FROM BC_CONTENT C, BC_CATEGORY_ENV E
							WHERE C.CONTENT_ID = '$content_id'
							AND C.CATEGORY_ID = E.CATEGORY_ID
						");

					if(!empty($priority)) {
						$priority = 4;
					}

					$frame_rate = getFrameRate($content_id);
					$pfr_start_frame	= round($pfr_start * $frame_rate);
					$pfr_end_frame		= round($pfr_end * $frame_rate);

					$sgl = new SGL();
					$return = $sgl->FlashNetRestore($content_id, $sgl_filepath, $displayname, $priority, $pfr_start_frame, $pfr_end_frame);
					if(!$return['success']) {
						// BC_TASK는 error로 업데이트
						$db->exec("
							UPDATE BC_TASK
							SET STATUS = 'error'
							WHERE TASK_ID = '$task_id'
						");
						$now = date('YmdHis');
						$db->exec("
							INSERT INTO BC_TASK_LOG
								(TASK_ID, DESCRIPTION, CREATION_DATE, TASK_LOG_TYPE)
							VALUES
								('$task_id', '".$return['msg']."', '$now', 'error')
						");

						throw new Exception ($return['msg']);
					} else {
						$db->exec("
							UPDATE SGL_ARCHIVE
							SET SESSION_ID = '".$return['request_id']."'
							WHERE TASK_ID = '$task_id'
						");
					}
				}

			}

			echo (json_encode(array(
				'success' => true,
				'msg'	=>	 _text('MSG02185')
			)));

		}else if($case_management == 'reject'){
			$request_ids = json_decode($_POST['request_ids'], true);
			$reject_reason = $_POST['reject_reason'];
			
			foreach ($request_ids as $request_id) {
				$reject_datetime = date('YmdHis');

				$query_info = "
					SELECT *
					FROM BC_ARCHIVE_REQUEST
					WHERE REQUEST_ID = ".$request_id."
				";
				$request_info = $db->queryRow($query_info);
				$type_job = strtolower($request_info['request_type']);
				if($type_job == 'archive'){
					$content_id = $request_info['content_id'];
					$content_status_query = "
						UPDATE	BC_CONTENT_STATUS
						SET		ARCHIVE_STATUS	= 'N'
						WHERE	CONTENT_ID	= ".$content_id
					;
					$db->exec($content_status_query);

					$archive_update_query = "
						UPDATE BC_ARCHIVE_REQUEST
						SET	APPROVE_USER_ID = '".$user_id."'
							,REJECT_DATETIME = '".$reject_datetime."'
							,REJECT_REASON = '".$reject_reason."'
							,STATUS = 'REJECT'
						WHERE REQUEST_ID=".$request_id."
					";
					$db->exec($archive_update_query);

				}else{
					$archive_update_query = "
						UPDATE BC_ARCHIVE_REQUEST
						SET	APPROVE_USER_ID = '".$user_id."'
							,REJECT_DATETIME = '".$reject_datetime."'
							,REJECT_REASON = '".$reject_reason."'
							,STATUS = 'REJECT'
						WHERE REQUEST_ID=".$request_id."
					";
					$db->exec($archive_update_query);
				}

			}
			echo (json_encode(array(
				'success' => true,
				'msg'	=>	 _text('MSG02185')
			)));
		}
	}
}
catch (Exception $e)
{
	switch($e->getCode())
	{
		case ERROR_QUERY:
			$msg = $e->getMessage().'( '.$db->last_query . ' )';
		break;

		default:
			$msg = $e->getMessage();
		break;
	}

	die(json_encode(array(
		'success' => false,
		'msg' => $msg
	)));
}

?>