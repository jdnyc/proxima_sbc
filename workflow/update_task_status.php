<?PHP
require_once '../vendor/autoload.php';

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/db.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/interface.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/Master.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/soap/nusoap.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/SGL.class.php');

$response = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'.chr(10).'<Response />');

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use \ProximaCustom\models\review\Review;
use Api\Types\TaskStatus;

$bisLogger = new Logger('name');
$bisLogger->pushHandler(new RotatingFileHandler(BASEDIR . '/log/bis_update_time.log', 14));
$logger->pushHandler(new RotatingFileHandler(BASEDIR . '/log/' . substr(basename(__FILE__), 0, strrpos(basename(__FILE__), '.')) . '.log', 14));

$xml = file_get_contents('php://input');
$arr_char = array("", "", "", "", "", "", "");
$xml = str_replace($arr_char, "", $xml);
//특수문자 변환. &quot;를 "로 변환하기위해 두번필터링-20110109/16:25분이후부터정상등록됨.
//수정일 : 2011.12.18
//작성자 : 김형기
//내용 : 성용이가 주석처리 하라고 시켰음...
$xml = reConvertSpecialChar(reConvertSpecialChar($xml));

$startTime = strtotime(date("YmdHis"));
$logger->info($xml);

if(!$xml){
	$response->addChild('Success', 'false');
	$response->addChild('Message', _text('MSG02097'));//Request is empty.
	echo $response->asXML();
	exit;
}

$tail = "\r\n";
try
{
	/*
	받는 XML
	<Request><Result Action="queue"><TaskID>3074</TaskID></Result></Request>
	<Request><Result Action="queue_skip"><TaskID>3074</TaskID></Result></Request>
	<Request><Result Action="retry_skip"><TaskID>3074</TaskID></Result></Request>
	*/

	$tm_video_server_list = array(
		'TM_CONTROL_MAIN',
		'TM_CONTROL_MAIN_REWRAP',
		'TM_CONTROL_BACKUP',
		'TM_CONTROL_BACKUP_REWRAP',
		'TM_M_CONTROL',
		'TM_M_CONTROL_MXF'
	);

	libxml_use_internal_errors(true);
	$request = simplexml_load_string($xml);
	
	if ( ! $request) {
		foreach (libxml_get_errors() as $error) {
			$err_msg .= $error->message . "\t";
		}

		throw new Exception('XML '._text('MSG02098').': '.$err_msg);//Parse Error
	}

	## task_id 검사
	if ($request->TaskID) {
		$task_id = (int)$request->TaskID;
	} else if ($request->Result->TaskID) {
		$task_id = (int)$request->Result->TaskID;
	} else {
		throw new Exception("TaskID "._text('MSG02099')." ".$request->asXML());//Can not find
	}
	$task = $mdb->queryRow("select * from bc_task where task_id=".$task_id);
    if(empty($task)) throw new Exception(_text('MSG02100'));//Task does not exist.
    $content_id = $task['src_content_id'];

    
    $contentService = new \Api\Services\ContentService(app()->getContainer());
    $mediaService = new \Api\Services\MediaService(app()->getContainer());

	$now = date('YmdHis');
	$action = (String)$request->Result['Action'];
	_debug('dkkim.log', '$action : '. print_r($action, true), true);

	if ($action){
		/*
		2010-12-12 박정근
		중복된 파일이 있을 경우 파일명 변경 -> rename 기능. 2011-04-26. 펑션으로 뺌. 김성민
		*/
		//2012.02.16 중복이 안되니 필요가 없음.

		if ( ! empty($request->Result['rename'])) {
			$rename_check = doRename($request->Result['rename'], $task_id);
		}

		if ( ! empty($request->Ip)) {
			$t_ip_update_query = "update bc_task set ASSIGN_IP='".$request->Ip."' where task_id=$task_id";
			$r = $db->exec($t_ip_update_query);
		}

		if (strstr($action, 'skip')) {
			list($tmpTaskType, $tmpAction) = explode('_', $action);
			$task_query = sprintf("update bc_task set status='$tmpTaskType', progress=0, start_datetime=null, complete_datetime=null where task_id = %d", $request->Result->TaskID);
		} else if ($action == 'queue' || $action == 'assign') {
			$task_query = sprintf("update bc_task set status='processing', progress=0, start_datetime='%s' where task_id = %d", $now, $request->Result->TaskID);
			$task_log_query = sprintf("insert into bc_task_log (task_id, description, creation_date) values (%d, '%s', '%s')", $request->Result->TaskID, _text('MN00233'), $now);//Work Started
		} else if ($action == 'retry') {
			$task_query = sprintf("update bc_task set status='processing', progress=0, start_datetime='%s' where task_id = %d", $now, $request->Result->TaskID);
			$task_log_query = sprintf("insert into bc_task_log (task_id, description, creation_date) values (%d, '%s', '%s')", $request->Result->TaskID, _text('MN01079'), $now);//Restart work by User
		} else if ($action == 'cancel') {
			$task_query = sprintf("update bc_task set status='canceled' where task_id = %d", $request->Result->TaskID);
			$task_log_query = sprintf("insert into bc_task_log (task_id, description, creation_date) values (%d, '%s', '%s')", $request->Result->TaskID, _text('MN01080'), $now);//Cancel work by User
		} else if ($action == 'error') {
			$task_query = sprintf("update bc_task set status='error' where task_id = %d", $request->Result->TaskID);
			$task_log_query = sprintf("insert into bc_task_log (task_id, description, creation_date) values (%d, '%s', '%s')", $request->Result->TaskID, $db->escape($request->Result->Log), $now);
		}
	} else {
		_debug('dkkim.log', '[TEST]', true);
        $status = (string)$request->Status;
        $taskType = (int)$request->TypeCode;
		
		_debug('dkkim.log', '[TEST] $status : '. print_r($status, true), true);
		_debug('dkkim.log', '[TEST] $taskType : '. print_r($taskType, true), true);

		$task_mgr = new TaskManager($db);

		switch ($status) {
			case 'error':
				//콘텐츠 정보
				// $get_metarial = $db->queryRow("SELECT  C.UD_CONTENT_ID,
				// 									   C.BS_CONTENT_ID,
				// 									   M.CONTENT_ID,
				// 									   C.REG_USER_ID,
				// 									   C.PARENT_CONTENT_ID,
				// 									   M.MEDIA_ID,
				// 									   T.TARGET,
				// 									   T.TASK_WORKFLOW_ID,
				// 									   T.TASK_USER_ID
				// 								  FROM BC_MEDIA M, BC_TASK T, BC_CONTENT C
				// 								 WHERE T.MEDIA_ID = M.MEDIA_ID
				// 								   AND C.CONTENT_ID = M.CONTENT_ID
				// 								   AND T.TASK_ID=".$task_id);
				// print_r($db->last_query);
				// print_r($get_metarial);
				// exit;
				/*
				* get_metarial에서 구하는것이 content_id밖에 없어서 주석처리하고 src_content_id를 쓰도록 변경 - 2018.01.18 Alex
				*/
				$content_id = $task['src_content_id'];
				if($arr_sys_code['interwork_harris']['use_yn'] == 'Y') {
					$check_harris = $db->queryRow("SELECT * FROM HARRIS WHERE ARIEL_UID=".$content_id);
					if(!empty($check_harris)) {
						$db->exec("UPDATE HARRIS SET MAM_INGEST='error' WHERE ARIEL_UID=".$content_id);
					}
				}

				/*
				* 취소일 경우 Agent에서 error로 최종리턴이 오기때문에 취소상태로 유지하기 위해서 추가함 - 2018.01.18 Alex
				*/
				if (in_array($task['status'], array('canceled','cancel','canceling'))) {
					$task_query = "update bc_task set status='".$task['status']."' where task_id='".$task_id."'";
					break;
				}

				$task_query = "update bc_task set status='error' where task_id=".$task_id;
				if ($request->Progress == '100') {
					$task_query = "update bc_task set status='error', progress=99 where task_id=".$task_id;
				}

				// link 리스토어 완료 후 DAS에 알림
				if ($task['task_workflow_id'] == TASK_WORKFLOW_ARCHIVE) {
					$archive = new Archive();
					$archive->returnArchive($task['src_content_id'], 'E');
				}
				if ($task['task_workflow_id'] == TASK_WORKFLOW_RESTORE) {
					$archive = new Archive();
					$archive->returnRestore($task['src_content_id'], 'E');
				}

				if (((int)$request->TypeCode) == ARIEL_TRANSFER_FTP) {
					if($task['destination'] == 'TM_M_CONTROL') {
						$row = $db->queryRow("select * from view_bc_content where content_id=$content_id");
						$metarow = MetaDataClass::getValueInfo('usr', $row['ud_content_id'] , $content_id );
						$mtrl_id = $metarow['usr_mtrl_id'];
						$tape_id = $metarow['usr_tape_id'];
						$mtrl_nm = $metarow['usr_mtrl_nm'];
						$sys_metarow = MetaDataClass::getValueInfo('sys', $row['bs_content_id'], $content_id);
						$duration = str_replace(":","",$sys_metarow['sys_video_rt']);
						$root_task_id = $task['root_task'];
						$regr = $db->queryOne("select task_user_id from bc_task where task_id = $root_task_id");

						// $bis = new BIS();

						// if ($row['ud_content_id'] == '4000287') {
						// 	$data = $bis->Material(array(
						// 			'tape_id'=> $tape_id,
						// 			'mtrl_id'=> $mtrl_id,
						// 			'tcin' => '00000000',
						// 			'tcout'=> $duration,
						// 			'duration'=> $duration,
						// 			'clip_yn'=> 'N',
						// 			'clip_yn1'=> 'N',
						// 			'regr'=> $regr,
						// 			'action'=> 'U'
						// 	));

						// 	$APC = $bis->APC(array(
						// 			'chnl_gb'=> 'CH_B',
						// 			'tape_id'=> $tape_id,
						// 			'title'=> $mtrl_nm,
						// 			'clip_id' => $mtrl_id,
						// 			'clip_flag'=> 'N',
						// 			'clip_flag1'=> 'N',
						// 			'som'=> '00:00:00:00',
						// 			'eom'=> $sys_metarow['sys_video_rt'],
						// 			'dur'=> $sys_metarow['sys_video_rt']
						// 	));
						// } else if ($row['ud_content_id'] == '4000287') {
						// 	$params_SetMaterial = array(
						// 		'mtrl_id'=> $mtrl_id,
						// 		'tcin' => '00000000',
						// 		'tcout'=> $duration,
						// 		'duration'=> $duration,
						// 		'clip_yn'=> 'N',
						// 		'clip_yn1'=> 'N',
						// 		'regr'=> $user_id,
						// 		'action'=> 'U'
						// 	);

						// 	$result = $bis->SetMaterial($params_SetMaterial);
						// }
					}
				}
				if (((int)$request->TypeCode) == ARIEL_DELETE_JOB) {

                    if (strstr($request->Log, '(code:2)')) {
                        //삭제할 파일이 없는 경우 삭제 완료처리
                        $status = TaskStatus::COMPLETE;
                        $task_query = "update bc_task set status='".$status."', progress=100 where task_id=".$task_id;

                        $mediaService->deleteComplete($task['media_id']);
                        $contentService->deleteCompleteByTaskId($task_id);
                    }else{
                        $contentService->deleteErrorByTaskId($task_id);
                    }
				}
				if (((int)$request->TypeCode) == ARIEL_INFOVIEW) {
					$retry_cnt = $task['retry_cnt'];
					if($retry_cnt < 10) {
						$retry_cnt = $retry_cnt + 1;
						//$task_query = "update bc_task set status = 'queue', retry_cnt=".$retry_cnt." where task_id = ".$task_id." ";
					}
				}

				if (\Proxima\core\CustomHelper::customMethodExists('\ProximaCustom\core\TaskEventHandler', 'handleAfterError')) {
					\ProximaCustom\core\TaskEventHandler::handleAfterError([
						'task' => $task,
						'error_log' => (string)($request->Log ?? '')
					]);
				}
			break;

			case 'complete':
				//완료시엔 task 테이블의 작업을 삭제.
				$task_query = "update bc_task set status='complete', progress=100, complete_datetime= '$now' where task_id=".$task_id;
			
				$comp_check = $db->queryOne("select status from bc_task where task_id=".$task_id);
				if ($comp_check == 'complete') {
					break;
				}

				//콘텐츠 정보
				$get_metarial = $db->queryRow("SELECT  C.UD_CONTENT_ID,
													   C.BS_CONTENT_ID,
													   M.CONTENT_ID,
													   C.REG_USER_ID,
													   C.PARENT_CONTENT_ID,
													   M.MEDIA_ID,
													   T.TARGET,
													   T.TASK_WORKFLOW_ID,
													   T.TASK_USER_ID
												  FROM BC_MEDIA M, BC_TASK T, BC_CONTENT C
												 WHERE T.MEDIA_ID = M.MEDIA_ID
												   AND C.CONTENT_ID = M.CONTENT_ID
												   AND T.TASK_ID=".$task_id);

				$content_id = $get_metarial['content_id'];
				$ud_content_id = $get_metarial['ud_content_id'];
				$user_id = $get_metarial['task_user_id'];
				$media_id = $get_metarial['media_id'];
				$target = $get_metarial['target'];
				$task_workflow_id = $get_metarial['task_workflow_id'];
				$bs_content_id = $get_metarial['bs_content_id'];

				if ($task['task_workflow_id'] == TASK_WORKFLOW_ARCHIVE) {
					$archive = new Archive();
					$archive->returnArchive($task['src_content_id'], 'S');
				}
				if ($task['task_workflow_id'] == TASK_WORKFLOW_RESTORE) {
					$archive = new Archive();
					$archive->returnRestore($task['src_content_id'], 'S');
				}

				if ($user_id == '') {
					if ($get_metarial['content_id'] == '') {
						$user_id = 'system';
					} else {
						$user_id = 'unknown';
					}
				}
								
				switch ((int)$request->TypeCode) {
					case ARIEL_QC:

						//2011. 12. 16 이승수. 성민선배가 작업한 QC 추가
						$qc_info = $request->QualityCheck;

						//QC정보 입력받기전에 전체 지움.
						$db->exec("
								DELETE	FROM BC_MEDIA_QUALITY
								WHERE	MEDIA_ID = '".$task['media_id']."'"
						);
						
						$i = 1; 
						foreach($request->QualityCheck->position as $qc) {	
							//이상봉실장님 버전 QC type code
							$qc_type = array(
									0 => 'Black',
									1 => 'Single color',
									2 => 'Still',
									3 => 'Color bar',
									4 => 'Similar image',
									5 => 'No audio samples',
									6 => 'Mute',
									7 => 'Loudness'
							);
								
							$qc_type_str = $qc_type[(string)$qc['type']];
							if(empty($qc_type_str)) {
								$qc_type_str = 'Etc';
							}
						
							$qc_start = substr($qc['start'],0,2)*3600+substr($qc['start'],3,2)*60+substr($qc['start'],6,2);
							$qc_end = substr($qc['end'],0,2)*3600+substr($qc['end'],3,2)*60+substr($qc['end'],6,2);
						
							$new_qc_seq = getSequence('SEQ_BC_MEDIA_QUALITY_ID');
							
							$q = $db->exec("
									INSERT INTO BC_MEDIA_QUALITY
										(QUALITY_ID, MEDIA_ID, QUALITY_TYPE, START_TC, END_TC, SHOW_ORDER, SOUND_CHANNEL)
									VALUES
										($new_qc_seq, '{$task['media_id']}', '".$qc_type_str."', '{$qc_start}', '{$qc_end}', $i, '".$qc['track']."')
								");
							$i++;
						}
						
						//QC 전체에 대한 정보 넣어주는 테이블
						$idx = $i-1;
                        $hasQC = $db->queryOne("select count(content_id) from bc_media_quality_info where content_id = $content_id");
                        
                        if($idx > 0){
                            $r = $db->exec("update bc_content_status set QC_CNFIRM_AT='0' where content_id='".$content_id."' ");
                        }else{
                            $r = $db->exec("update bc_content_status set QC_CNFIRM_AT='1' where content_id='".$content_id."' ");
                        }
						
						if($idx > 0) {
							if($hasQC > 0) {
								$query = "update bc_media_quality_info set error_count = '$idx', last_modify_date = '$now' where content_id = '$content_id'";
								$db->exec($query);
							} else {
								$query = "insert into bc_media_quality_info (content_id, error_count, created_date) values ('$content_id','$idx', '$now')";
								$db->exec($query);
							}
						} else {
							/*검출된 정보가 없을 경우에도 QC를 진행한 부분을 확인하기 위해서 값 추가 / 있으면 업데이트 없으면 인서트 - 2018.03.20 Alex */
							if($hasQC > 0) {
								$db->exec("
									UPDATE	BC_MEDIA_QUALITY_INFO
									SET		ERROR_COUNT = $idx,
											LAST_MODIFY_DATE = '$now'
									WHERE	CONTENT_ID = $content_id
								");
							} else {
								$db->exec("
									INSERT INTO BC_MEDIA_QUALITY_INFO
										(CONTENT_ID, ERROR_COUNT, CREATED_DATE)
									VALUES
										($content_id, 0, '$now')
								");
							}
							$pass_add_next_job = 'true';
						}

					break;

					case ARIEL_CATALOG:
						
						if($arr_sys_code['interwork_harris']['use_yn'] == 'Y') {
							$check_harris = $db->queryRow("SELECT * FROM HARRIS WHERE ARIEL_UID=".$content_id);
							if(!empty($check_harris)) {
								$db->exec("UPDATE HARRIS SET MAM_INGEST='complete' WHERE ARIEL_UID=".$content_id);
							}
						}

						if($task['type'] == ARIEL_THUMBNAIL_CREATOR) {
							//11작업으로 넘겼는데 10으로 돌아오는 경우...
							if( !empty($request->catalog->position['size']) ){
								$task_mgr->update_filesize( $media_id , $request->catalog->position['size'] );
							}
							break;
						}

						//중복자료 있을시 디비에서 삭제
						//QC나 Loudness 이후 작업인지 판단하여 삭제대상을 다르게 지정
						$task_param = explode(' ', trim($task['parameter']));

						if($task_param[0] == '"6"') {
							switch($task_param[1]){
								case '"QCFrame"':
									$catalog_type = 'Q';
								break;
								case '"LoudFrame"' :
									$catalog_type = 'L';
								break;
							}
						} else {
							$catalog_type = 'S';
                        }
                        
                        if ($task_param[0] != '"5"') {
                            $check_img = $db->queryOne("
										SELECT	COUNT(*)
										FROM	BC_SCENE
										WHERE	MEDIA_ID = {$task['media_id']}
										AND		SCENE_TYPE = '$catalog_type'
									");
                            if ($check_img > 0) {
                                $delete_img = $db->exec("
											DELETE	FROM BC_SCENE
											WHERE	MEDIA_ID = {$task['media_id']}
											AND		SCENE_TYPE = '$catalog_type'
										");
                            }
                            
                            $i = 1;

                            foreach ($request->catalog->position as $item) {
                                $scene_id = getNextSequence();
                                $thumb = $task['target'].'/'.$i++.'.jpg';
                                if($catalog_type == 'L' || $catalog_type == 'Q') {
                                    $thumb = $task['target'].'/'.$item.'.jpg';
                                }
                                $r = $db->exec("
                                        INSERT INTO BC_SCENE
                                            (SCENE_ID, MEDIA_ID, SHOW_ORDER, PATH, START_FRAME, TITLE, FILESIZE, SCENE_TYPE)
                                        VALUES
                                            ($scene_id, {$task['media_id']}, $i, '$thumb', '$item', 'title $i', '{$item['size']}', '$catalog_type')
                                    ");
                            }
                        }
					
					break;

					case ARIEL_THUMBNAIL_CREATOR:
						if( !empty($request->catalog->position['size']) ){
							$task_mgr->update_filesize( $media_id , $request->catalog->position['size'] );
						}
					break;

					case ARIEL_IMAGE_TRANSCODER:
                    break;
					case ARIEL_TRANS_AUDIO:
                    break;
					case ARIEL_TRANSCODER_OVERLAY:
					break;
					case ARIEL_TRANSCODER:
						if (\Proxima\core\CustomHelper::customMethodExists('\ProximaCustom\core\TaskEventHandler', 'handleAfterTranscode')) {
							\ProximaCustom\core\TaskEventHandler::handleAfterTranscode([
								'task' => $task
							]);
						}
					break;
					case ARIEL_TRANSCODER_hi:
					break;
					case ARIEL_TRANSFER_FS:
						//타겟미디어 복원
						if( !empty($task['trg_media_id']) ){
							$query = "update bc_media set status = '0', flag='', DELETE_DATE='' where media_id = $task[trg_media_id] ";
							$db->exec($query);
                        }

                        //사이즈가 없거나 0인경우 에러 처리
                        if( empty($request->ProxyFilename['size']) ){
                            $status = 'error';
                            $task_query = "update bc_task set status='error', progress=100 where task_id=".$task_id;
                            $request->Log = 'filesize 0 error';
                            $pass_add_next_job = 'true';
                        }
                        
					break;
                    case ARIEL_PATIAL_FILE_RESTORE:
                    break;
					case ARIEL_TRANSFER_FTP:
                        //Harris 업로드 작업인지 체크
                        if($arr_sys_code['interwork_harris']['use_yn'] == 'Y' && strstr($task['parameter'], 'target')) {
                            $server_uid = $db->queryOne("
                                SELECT	A.SERVER_UID
                                FROM	HARRIS_FTP_LIST A
                                        INNER JOIN BC_STORAGE B ON A.STORAGE_ID=B.STORAGE_ID
                                WHERE	B.STORAGE_ID=".$task['trg_storage_id']."
                            ");
                            //Harris작업일 시
                            if(!empty($server_uid)) {
                                //CJO, Harris전송 완료 후 agency 정보 업데이트
                                //카테고리명이 Agency값이 된다
                                $category_name = $db->queryOne("select category_title from bc_category where
                                    category_id=(select category_id from bc_content where content_id=".$content_id.")");//Agency

                                $curr_datetime = date('YmdHis');
                                $field_name = 'Agency';
                                $field_number = 13;//Harris API 연계를 위한 Agency에 대한 고정 상수값
                                $field_value = $category_name;
                                $file_path_arr = explode('/', $task['target']);
                                $file_name = array_pop($file_path_arr);
                                $file_name_arr = explode('.', $file_name);
                                $xid = $file_name_arr[0];//전송지의 파일명이 XID

                                //파일이 전송시작되면 add_harris.php로 정보가 입수되어 harris테이블에 insert/update된다
                                //전송완료시 여기에 content_id정보와 작업상태를 update시켜준다.
                                $query = "update harris set ariel_uid = '$content_id', mam_ingest = 'complete'
                                    where xid = '$xid' and server_uid = '$server_uid'";
                                $db->exec($query);	

                                $content_info = $db->queryRow("select * from bc_content where content_id=".$content_id);
                                $title = $content_info['title'];
                                
                                if(CJO_UPDATE_AGENCY_AFTER_HARRIS_SEND == 'Y') {
                                    //Agency 정보를 Harris로 넘겨준다.
                                    //2017-12-30, 이승수, CJO에서 Harris로 전송시 title로 xid가 생성되어 전송된다.
                                    //전송되면 harris테이블로 정보가 들어오고, 전송완료되는 이쪽에서 xid로 조회하여 harris_id를 구하고, agency정보를 업데이트한다.
                                    //$harris_id = $db->queryOne("select id from harris where ariel_uid=".$content_id);
                                    $title = $db->escape($title);
                                    $harris_id = $db->queryOne("select id from harris where xid='".$title."' and server_uid='".$server_uid."'");
                                    $checkExists = $db->queryOne("select count(*) from harris_changed_metadata where harris_id='$harris_id' and server_uid='$server_uid' and field_name='$field_name'");
                                    if(!empty($harris_id)) {
                                        if ($checkExists) {
                                            $update_arr = array(
                                                'changed_value' => $field_value,
                                                'update_datetime' => $curr_datetime,
                                                'status' => 'queue'
                                            );
                                            $db->update('harris_changed_metadata', $update_arr, "harris_id='$harris_id' and server_uid='$server_uid' and field_name='$field_name'");
                                        } else {
                                            $insert_arr = array(
                                                'harris_id' => $harris_id,
                                                'server_uid' => $server_uid,
                                                'field_name' => $field_name,
                                                'field_number' => $field_number,
                                                'changed_value' => $field_value,
                                                'status' => 'queue',
                                                'type' => 'metadata',
                                                'regist_datetime' => $curr_datetime,
                                                'update_datetime' => $curr_datetime
                                            );
                                            $db->insert('harris_changed_metadata', $insert_arr);
                                        }
                                    }
                                }
                            }
                        }

						$bisLogger->info('FTP '._text('MSG02103'));//Transfer Complete
						$bisLogger->info($task['destination']);
						if (in_array($task['destination'], $tm_video_server_list)) {
							$row = $db->queryRow("select * from view_bc_content where content_id=$content_id");
							$metarow = MetaDataClass::getValueInfo('usr', $row['ud_content_id'] , $content_id );
							$sys_metarow = MetaDataClass::getValueInfo('sys', $row['bs_content_id'], $content_id);
							$root_task_id = $task['root_task'];
							$regr = $db->queryOne("select task_user_id from bc_task where task_id = $root_task_id");

							$bisLogger->info('metadata', $metarow);

							if ( ! empty($metarow['usr_pgm_id']) && ! empty($metarow['usr_epsd_no'])) {
								$tc_info_from_bis = getTcInfoFromBis($metarow);
								$storage_flag = array('', '');

								$bisLogger->info('$tc_info_from_bis', $tc_info_from_bis);
								$bisLogger->info('$storage_flag', $storage_flag);

								if (in_array($task['destination'], array(
										'TM_CONTROL_MAIN',
										'TM_CONTROL_MAIN_REWRAP'))) {
									$storage_flag[0] = 'Y';
								} else if (in_array($task['destination'], array(
										'TM_CONTROL_BACKUP',
										'TM_CONTROL_BACKUP_REWRAP'))) {
									$storage_flag[1] = 'Y';
								} else if (in_array($task['destination'], array(
										'TM_M_CONTROL',
										'TM_M_CONTROL_MXF'))) {
									$storage_flag[0] = 'Y';
									$storage_flag[1] = 'Y';
								}
							}

                        }
						
					break;
					case ARIEL_MXF_VALIDATE:
					case ARIEL_MOV_HEADER:
					case ARIEL_HIGH_TRANSCODER:
					case ARIEL_REWARPPING:
					break;

					case RESTORE_PFR:
						//get new_content_id from bc_archive_request
						$new_content_id = $db->queryOne("SELECT CONTENT_ID FROM BC_ARCHIVE_REQUEST WHERE TASK_ID = ". $task['task_id']);

						$insert_task = new TaskManager($db);
						$channel = 'sgl_pfr_reg';
						$insert_task->set_priority(200);
						$insert_task->insert_task_query_outside_data($new_content_id, $channel, 1, $user_id, $task['target']);
					
					break;

					case ARIEL_DELETE_JOB:
                        //미디어 테이블 작업
                        
                        $mediaService->deleteComplete($task['media_id']);
						// $query = "update bc_media set status = '1', flag='".DEL_MEDIA_COMPLETE_FLAG."', DELETE_DATE='".date('YmdHis')."' where media_id = {$task['media_id']}";
						// $db->exec($query);
						// $query_status = "
						// 	SELECT 	STATUS
						// 	FROM 	BC_CONTENT
						// 	WHERE 	CONTENT_ID = '".$content_id."'
						// ";
						// $status = $db->queryRow($query_status);
						// if ($status['status'] == CONTENT_STATUS_DELETE_APPROVE){
						// 	$chk_ori = $db->queryRow("select * from bc_media where content_id='".$content_id."' and media_type='original'");
						// 	$chk_proxy = $db->queryRow("select * from bc_media where content_id='".$content_id."' and media_type='proxy'");
						// 	if ($chk_ori['delete_date'] != '' && $chk_proxy['delete_date'] != ''){
						// 		$query = "update bc_content set status = '".CONTENT_STATUS_DELETE_COMPLETE."' where content_id = $content_id";
						// 		$r = $db->exec($query);
						// 	}
                        // }
                        
						$contentService->deleteCompleteByTaskId($task_id);
						$contentService->deleteCompleteOriginal($content_id);
					break;

					case ARIEL_EXTRACTMOVKEY:
						if( !empty($request->ProxyFilename) ){
							$task_mgr->update_key($content_id, $media_id , $request->ProxyFilename );
						}
					break;

					case ARIEL_OPENDIRECTORY:
						if( $task['destination'] == 'OD' )
						{
							$task_source = explode(' ', $task['source']);

							$task_od_type = trim( array_shift($task_source) , '"' );
							if( $task_od_type == 'CREATE_GROUP' )
							{
								$task_od_parameter_array = explode(' ', $task['parameter']);
								$od_new_last_path = trim(  array_pop( $task_od_parameter_array ) , '"' );
								$od_full_path_array = explode ( '/', trim(  array_pop( $task_od_parameter_array ) , '"' ) );

								$grant =  array_pop( $task_od_parameter_array );
								$od_last_path = trim( array_pop( $task_od_parameter_array ), '"' );
								$od_prog_path = array_pop($od_full_path_array);



								$job_infos = $db->queryAll("select r.* , tt.type from bc_task_rule r , bc_task_type tt  where r.task_type_id=tt.task_type_id and ( r.task_rule_id=84 or r.task_rule_id=85) ");

								foreach($job_infos as $job_info)
								{
									$next_task_id = getSequence('TASK_SEQ');

									$od_source = $od_prog_path.'/'.$od_last_path;
									$od_target = $od_prog_path.'/'.$od_new_last_path;
									$insert_q = " insert into bc_task ( MEDIA_ID,TASK_ID,TYPE,SOURCE,TARGET,PARAMETER,STATUS,PRIORITY,CREATION_DATETIME,DESTINATION,TASK_WORKFLOW_ID,JOB_PRIORITY,TASK_RULE_ID,ROOT_TASK,TASK_USER_ID ) values (0,$next_task_id, '{$job_info['type']}', '$od_source', '$od_target', '{$job_info['parameter']}','queue', '{$task['priority']}', '$now', '{$task['destination']}', '{$task['task_workflow_id']}', 2, {$job_info['task_rule_id']}, {$task['root_task']}, '{$task['task_user_id']}') ";
									$r = $db->exec($insert_q);


									$category_id = $db->queryOne("select category_id from CATEGORY_TASK_INFO where task_id=$task_id ");

									$path_info_row = $db->queryRow("select * from view_category where category_id=$category_id ");

									if( !empty($category_id) )
									{
										$r = $db->exec("insert into CATEGORY_TASK_INFO (CATEGORY_ID,TASK_ID) values ( $category_id,$next_task_id ) ");

										if( !empty($path_info_row['ud_storage_group_id']) ){
											$isChangeInfo = $db->queryAll("select * from bc_ud_storage_group_map where storage_group_id={$path_info_row['ud_storage_group_id']} " );

											if( !empty($isChangeInfo) ){
												$str_map_info = array();
												foreach($isChangeInfo as $info)
												{
													$str_map_info [$info['source_storage_id']] = $info['ud_storage_id'];
												}

												if( $str_map_info[$job_info['source_path']]&& $str_map_info[$job_info['target_path']] ){
													$src_storage_id = $str_map_info[$job_info['source_path']];
													$trg_storage_id = $str_map_info[$job_info['target_path']];
													$insert_q = "insert into BC_TASK_STORAGE  (TASK_ID,SRC_STORAGE_ID,TRG_STORAGE_ID )  values ($next_task_id, $src_storage_id , $trg_storage_id )";
													$r = $db->exec($insert_q);
												}
											}
										}
									}

								}

							}
						}
					break;

					case ARIEL_INFOVIEW:
						if($bs_content_id == DOCUMENT){
							$metaValues = array();
							$filenames = @pathinfo($request->Request->RegistMeta->Content->Title);
							$content_type_id = DOCUMENT;
							$metaValues['SYSMETA_DOCUMENT_FORMAT'] = $filenames['extension'];
						}else{
							//파일사이즈 있을시 미디어에 업데이트
							if( !empty($request->Request->RegistMeta->Medias->Media['filesize']) ){
								$task_mgr->update_filesize( $media_id , $request->Request->RegistMeta->Medias->Media['filesize'] );
                            }
                            
                            $mediaInfo = [];

							$Systems = 	$request->Request->RegistMeta->System;
                            $content_type_id = 	(string)$request->Request->RegistMeta->System['contentTypeID'];
                            if(empty($content_type_id)) $content_type_id = MOVIE;
							if( !empty($content_id) ){
								$metaValues = array();
								foreach ( $Systems as $System ){
									foreach ( $System as $key => $value ){
                                        $content_field_id = (string)$value['contentFieldID'];
                                        $name = str_replace(' ','_',(string)$value['name']);
										$value = (string)$value;
                                        $metaValues[$content_field_id] = $value;
                                        
                                        $mediaInfo[$name] = $value;                                       
									}
								}
							}
                        }

                        $filePath = $request->Request->RegistMeta->Medias->Media['path'];
                        $fileExt = null;
                        if( !empty($filePath) ){
                            $filePathList = explode('.', $filePath);
                            $fileExt = array_pop($filePathList);
                            $fileExt = strtoupper($fileExt);
                        }
                        if(!empty($fileExt)){
                            $metaValues['266'] = $fileExt;
                        }

                        //미디어정보 코드화
                        if( $content_type_id == MOVIE ){                               
                            $resolutionCode = resolutionCustom( $metaValues['615'], $metaValues['58172'], $fileExt);
                            $r = $db->update("BC_CONTENT_STATUS",[ 'resolution' => $resolutionCode ] , "content_id=".$content_id );                            
                        }
                        MetaDataClass::insertSysMeta($metaValues, $content_type_id , $content_id );
                        
                        //SYS_VIDEO_ASPERTO,SYS_VIDEO_WRAPER sys_rsoltn_se
                        // <Request><RegistMeta type="transcoder"><Content>
                        // <Title>20191214T01231BM.mxf</Title></Content><Medias>
                        // <Media type="original" fullpath="X:\CMS\Video\Product\program_463\20191214T01231BM.mxf" path="20191214T01231BM.mxf"
                        //  filesize="26522172" task_id="5895"/></Medias>
                        //  <System contentTypeID="506" contentTypeName="Video">
                        //  <MetaCtrl contentFieldID="507" name="Duration">00:00:03;25</MetaCtrl>
                        //  <MetaCtrl contentFieldID="508" name="Video Bitrate">50000 kb/s</MetaCtrl>
                        //  <MetaCtrl contentFieldID="615" name="Resolution">1920x1080i tff [PAR 1:1 DAR 16:9]</MetaCtrl>
                        //  <MetaCtrl contentFieldID="616" name="Framerate">29.97 Frame/s</MetaCtrl>
                        //  <MetaCtrl contentFieldID="58172" name="Video Codec">mpeg2video (4:2:2)</MetaCtrl>
                        //  <MetaCtrl contentFieldID="58173" name="Audio Codec">pcm_s24le</MetaCtrl>
                        //  <MetaCtrl contentFieldID="58192" name="Audio Bitrate">1152 kb/s</MetaCtrl>
                        //  <MetaCtrl contentFieldID="6960487" name="DF_NDF">Drop</MetaCtrl>
                        //  <MetaCtrl contentFieldID="1525" name="Start TC"> 01:55:02;15</MetaCtrl>
                        //  <MetaCtrl contentFieldID="250608" name="Audio Channel">4</MetaCtrl>
                        //  <MetaCtrl contentFieldID="0" name="Origin"></MetaCtrl>
                        //  <MetaCtrl contentFieldID="250000" name="Audio Track">4</MetaCtrl>
                        //  <MetaCtrl contentFieldID="250001" name="Audio Track1">pcm_s24le, 48000 Hz, 1 channels, s32, 1152 kb/s</MetaCtrl>
                        //  <MetaCtrl contentFieldID="250002" name="Audio Track2">pcm_s24le, 48000 Hz, 1 channels, s32, 1152 kb/s</MetaCtrl>
                        //  <MetaCtrl contentFieldID="250003" name="Audio Track3">pcm_s24le, 48000 Hz, 1 channels, s32, 1152 kb/s</MetaCtrl>
                        //  <MetaCtrl contentFieldID="250004" name="Audio Track4">pcm_s24le, 48000 Hz, 1 channels, s32, 1152 kb/s</MetaCtrl>
                        //  </System></RegistMeta></Request>
            
                        if (\Proxima\core\CustomHelper::customMethodExists('\ProximaCustom\core\TaskEventHandler', 'handleAfterInfoView')) {
							\ProximaCustom\core\TaskEventHandler::handleAfterInfoView([
								'task' => $task
							]);
						}
					break;

					case LOUDNESS_MEASUREMENT:
						$loudness_info = $db->queryRow("
											SELECT	L.*
											FROM	TB_LOUDNESS L
											WHERE	L.TASK_ID = $task_id
											AND		L.REQ_TYPE = 'M'
											AND		L.JOBUID IS NOT NULL
										");

						$pass_add_next_job = 'false';
						//검출되었고 is_correct가 Y 이면 수동 요청이므로 별도 워크플로우 진행
						if($loudness_info['measurement_state'] == 'D' && $loudness_info['is_correct'] == 'Y') {
							$adjust_task_mgr = new TaskManager($db);

							$adjust_channel = 'loudness_adjust';
							$adjust_task_id = $adjust_task_mgr->start_task_workflow($content_id, $adjust_channel, $user_id);
						} //검출되었고 is_correct가 N이면서 수동 워크플로우가 아닌 경우에만 하위 워크플로우 진행
						else if($loudness_info['measurement_state'] == 'P' && $loudness_info['is_correct'] == 'N' 
									&& !in_array($task['destination'], array('loudness_measure','loudness_adjust'))) {
							
							$pass_add_next_job = 'true';
						}

					break;

					case ARCHIVE:
						//When Flashnet job complete, get log and update.
						// $params = array(
						// 	'work_type' => 'get_log_and_volume',
						// 	'content_id' => $content_id,
						// 	'task_id' => $task_id
						// );
						// $url = 'http://'.SERVER_HOST.'/store/request/async_request.php';
						// request_async($url, $params);
					break;
					case ARCHIVE_DELETE:
					case RESTORE:
						//When Flashnet job complete, get log and update.
						// $params = array(
						// 	'work_type' => 'get_log_and_volume',
						// 	'content_id' => $content_id,
						// 	'task_id' => $task_id
						// );
						// $url = 'http://'.SERVER_HOST.'/store/request/async_request.php';
						// request_async($url, $params);
                    break;
                    
                    
					case ARIEL_LOUDNESS:
                        //@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).'_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] ARIEL_LOUDNESS ===> '.ARIEL_LOUDNESS."\r\n", FILE_APPEND);
                        $loudnessInfo = $request->LoudnessInfo;
                        $loudness_m_preset = $loudnessInfo['measurementpreset'];
                        $loudness_m_unit = $loudnessInfo['measurementunit'];
                        $loudness_integrate = $loudnessInfo['integrate'];
                        $loudness_max_momentory = $loudnessInfo['maxmomentary'];
                        $loudness_max_shotterm = $loudnessInfo['maxshortterm'];
                        $loudness_max_truepeak = $loudnessInfo['maxtruepeak'];
                        $loudness_range = $loudnessInfo['loudnessrange'];

                        //중복항목 여부 확인
                        $check_loudness = $db->queryOne("
                                            SELECT	COUNT(*)
                                            FROM	TB_LOUDNESS
                                            WHERE	CONTENT_ID = $content_id
                                        ");
                        if($check_loudness > 0){
                            $delete_loudness = $db->exec("
                                                    DELETE FROM TB_LOUDNESS WHERE CONTENT_ID = $content_id
                                                ");
                        }
                        //@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).'_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] ARIEL_LOUDNESS content_id ===> '.$content_id."\r\n", FILE_APPEND);
                        //@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).'_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] ARIEL_LOUDNESS asXML ===> '.$request->asXML()."\r\n", FILE_APPEND);
                        $r = $db->exec("
                                INSERT	INTO TB_LOUDNESS
                                    (CONTENT_ID, PRESET, UNIT, INTEGRATE, MAX_MOMENTARY, MAX_SHORTTERM, MAX_TRUEPEAK, LOUDNESSRANGE)
                                VALUES
                                    ($content_id, '$loudness_m_preset', '$loudness_m_unit', '$loudness_integrate', '$loudness_max_momentory', '$loudness_max_shotterm', '$loudness_max_truepeak', '$loudness_range')
                            ");
                        

                        /*Storage에 XML파일 추가*/
                        // $loudness_root = $db->queryOne("
                        //                     SELECT	PATH
                        //                     FROM	BC_STORAGE
                        //                     WHERE	STORAGE_ID = 127
                        //                     AND		NAME = 'Loudness XML'
                        //                 ");
                        // $loudness_filename = $content_id.'.xml';
                        // $loudness_filename = iconv('utf-8','cp949',$loudness_filename);
                        // //@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).'_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] loudness_filename ===> '.$loudness_root.'/'.$loudness_filename."\r\n", FILE_APPEND);
                        
                        // //라우드니스 로그를 xml 파일 저장 skc 2019.09.24
                        // file_put_contents($loudness_root.'/'.$loudness_filename, $request->asXML());

                    break;
				}

				if( !empty($request->ProxyFilename) ){
                    //2018-01-11 이승수, rename이라는 param이 있을때만 작업한 파일명으로 업데이트
                    //source를 리네임하지 않을건데 target명으로 바뀌어 버리는 경우 방지하기 위해서
                    if( strstr($task['parameter'], 'rename') ) {
                        $task_mgr->update_filename( $media_id , $request->ProxyFilename );
                    }
				}

				if( !empty($request->ProxyFilename) && !empty($request->ProxyFilename['size']) ){
                    //미디어에 종속된 파일이 있는경우 해당 파일 업데이트
                    if( !empty($task['trg_file_id'])  ){                        
                        $filePathInfo = new \Api\Core\FilePath($request->ProxyFilename);
                        $fileMeta = [
                            'file_name'     => $filePathInfo->filenameExt,
                            'file_size'     => (int)$request->ProxyFilename['size'],
                            'status'     => \Api\Types\TaskStatus::COMPLETE
                        ];
                        $fileService = new \Api\Services\FileService($app->getContainer());
                        $file = $fileService->update($task['trg_file_id'], $fileMeta);
                    }else{
                        $task_mgr->update_filesize( $media_id , $request->ProxyFilename['size'] );             
                    }
				}
                $logger->info($task_id.' : '.'bf add_next_job');
				if($pass_add_next_job != 'true') {
					$check_next_job = $task_mgr->add_next_job($task_id);
				}
				$task_mgr->extra_work($task , $content_id);

				if( !empty($user_id) && !empty($content_id) ){
					$result = insertLog($request->TypeCode, $user_id, $content_id, '');
				}
			break;

			case 'processing':
				if (in_array($task['status'], array('canceled','cancel','canceling'))) {
					$task_query = "update bc_task set status='".$task['status']."' where task_id='".$task_id."'";
					break;
				}
				//완료된것은 진행중으로 업데이트 안되게 12-06-10 by 이성용
				$task_query = sprintf("update bc_task set status='processing', progress = %s  where task_id = %d and status!='complete'", $request->Progress, $request->TaskID);
			break;
		}

		$log = trim(str_replace("'", "\'", str_replace('\\', '/', (string)$request->Log)));
		if($log != ''){
			//작업로그에 상태,진행률 추가 2012-09-03 이성용
			$task_log_query = sprintf("insert into bc_task_log (task_id, description, creation_date, status, progress ) values (%d, '%s', '%s', '%s', %s)", $request->TaskID, $db->escape($log), $now, $status, $request->Progress );

			if( $status == 'complete' ){
                //작업 완료시 진행 로그 삭제 2012-09-03 이성용
                //2018-10-15 이승수. 진행중 표시(xx% complete)하는 로그만 삭제. 
                //FTP의 FILE_EXISTS_ACTION=COMPLETE 기능 사용하면, processing에서 파일존재한다는 오류로그가 남는데 삭제하면 안되므로
				$r = $db->exec("delete from bc_task_log where task_id=".$request->TaskID." and status='processing'");
			}else if( $status == 'processing' ){
				//에이전트 문제로 프로세싱을 계속 보내는 경우가 있는데...
				//작업 하나에 대해 같은 로그가 있다면 업데이트 2012-09-03 이성용
				$dup_task_log_id = $db->queryOne("select task_log_id from bc_task_log where task_id=".$request->TaskID." and progress = '".$request->Progress."' ");
				if(!empty($dup_task_log_id)){
					$task_log_query = sprintf(" update bc_task_log set creation_date='%s' where task_log_id=".$dup_task_log_id." ",$now );
				}
			}
        }
        
        $logger->info($task_id.' : '.'bf TaskEventHandler');
        //FTP 작업 처리    
        if ( $taskType == ARIEL_TRANSFER_FTP ) {
            if (\Proxima\core\CustomHelper::customMethodExists('\ProximaCustom\core\TaskEventHandler', 'handleAfterFTP')) {               
                \ProximaCustom\core\TaskEventHandler::handleAfterFTP([
                    'task' => $task,
                    'request' => $request
                ]);
            }
        }
        //아카이브 작업 처리    
        if ( $taskType == ARIEL_TRANSFER_FS || $taskType == ARCHIVE || $taskType == RESTORE || $taskType == ARCHIVE_DELETE  || $taskType == 90 || $taskType == 91 ) {
            if (\Proxima\core\CustomHelper::customMethodExists('\ProximaCustom\core\TaskEventHandler', 'handleAfterArchive')) {               
                \ProximaCustom\core\TaskEventHandler::handleAfterArchive([
                    'task' => $task,
                    'request' => $request
                ]);
            }
        }

        //아카이브 작업 처리    
        if ( $taskType == ARIEL_LOUDNESS ) {
            if (\Proxima\core\CustomHelper::customMethodExists('\ProximaCustom\core\TaskEventHandler', 'handleAfterLoudness')) {               
                \ProximaCustom\core\TaskEventHandler::handleAfterLoudness([
                    'task' => $task,
                    'request' => $request
                ]);
            }
        }

        //외부연동 동기화 처리 
        if ( $taskType == ARIEL_THUMBNAIL_CREATOR || $taskType == ARIEL_CATALOG || $taskType == ARIEL_TRANSCODER ) {
            if (\Proxima\core\CustomHelper::customMethodExists('\ProximaCustom\core\TaskEventHandler', 'handleAfterSync')) {               
                \ProximaCustom\core\TaskEventHandler::handleAfterSync([
                    'task' => $task,
                    'request' => $request
                ]);
            }
        }

        //섬네일 등록 후 자동 아카이브
        if ( $task['type'] == ARIEL_THUMBNAIL_CREATOR ) {
            if (\Proxima\core\CustomHelper::customMethodExists('\ProximaCustom\core\TaskEventHandler', 'handleAfterMigration')) {               
                \ProximaCustom\core\TaskEventHandler::handleAfterMigration([
                    'task' => $task,
                    'request' => $request
                ]);
            }
        }
	}
	$result = $db->exec($task_query);
	if($task_log_query != ''){
		$result = $db->exec($task_log_query);
    }
    
    //완료시 전체 콘텐츠 상태 체크. add_nex_job에 있는 기능이었다가, bc_task상태 바꾼후 동작하도록 변경
    if($status == 'complete') {
        $logger->info($task_id.' : '.'isCompleteWorkflow');
        $next_query = " select
							task_rule_id ,TASK_RULE_PARANT_ID ,WORKFLOW_RULE_ID ,WORKFLOW_RULE_PARENT_ID
						 from
							BC_TASK_WORKFLOW_RULE
						 where
							task_workflow_id = ".$task['task_workflow_id']."
						 and WORKFLOW_RULE_PARENT_ID=".$task['workflow_rule_id']." ";

        $get_next_job_infos = $db->queryAll($next_query);
        
        //2011.03.21 by 이성용
        //다음 작업이 없을시
        if (empty($get_next_job_infos) || ( count($get_next_job_infos) == 0)) {
            //워크플로우 완료 체크
            if( $task_mgr->isCompleteWorkflow($task['task_workflow_id'] , $task['root_task'], $task_id ) ) {
                //콘텐츠 상태정보 업데이트
                $task_mgr->update_content_status( $content_id , $task , 'workflow' );
            }
        }
    }

    $response->addChild("Result", "success");
    $endTimeSec = strtotime(date("YmdHis")) - $startTime;
    $logger->info($task_id.' : '.$endTimeSec. ' : '.$response->asXML());
	die($response->asXML());
} catch(Exception $e) {
	$msg = $e->getMessage();
	switch($e->getCode()){
		case ERROR_QUERY:
			$msg .= '( '.$db->last_query.' )';
		break;
	}
	$msg .= 'Line: '.$e->getLine();
	$msg .= 'getTraceAsString: '.$e->getTraceAsString();
	$result = $response->addChild("Result", $msg);
	$result->addAttribute('success', 'false');
    $logger->error($task_id. ' : '.$response->asXML());
    die($response->asXML());
}

// 81일때 받는 xml
//<Request>
//	<TaskID>1255</TaskID>
//	<TypeCode>80</TypeCode>
//	<Progress>100</Progress>
//	<Status>complete</Status>
//	<Log>Ftp 작업 완료</Log>
//	<Content_id>123</Content_id>
//	<Xid>타이틀 이름</Xid>
//	<Id>%1234</Id>
//	<Server_name>채널 운영실</Server_name>
//</Request>


function getTcInfoFromBis($content) {
	$result = false;

	$bis = new BIS();

	$data = $bis->MaterialList(array(
		'pgm_id'=> $content['usr_pgm_id'],
		'epsd_no'=> $content['usr_epsd_no']
	));

	$data = json_decode($data, true);

	if ( ! empty($data)) {
		$result = array(
			'tcin' => $data[0]['tcin'],
			'tcout' => $data[0]['tcout'],
			'duration' => $data[0]['duration']
		);
	}

	return $result;
}

function updateMaterialInfoToBis($metadata, $tc_info, $storage_flag, $user_id) {
	$bis = new BIS();
	$result = $bis->Material(array(
		'tape_id'=> $metadata['usr_tape_id'],
		'mtrl_id'=> $metadata['usr_mtrl_id'],
		'tcin' => $tc_info['tcin'],
		'tcout'=> $tc_info['tcout'],
		'duration'=> $tc_info['duration'],
		'clip_yn'=> $storage_flag[0],
		'clip_yn1'=> $storage_flag[1],
		'clip_yn2'=> 'N',
		'clip_yn3'=> 'N',
		'arc_yn'=> '',
		'regr'=> $user_id,
		'action'=> 'U'
	));

	return $result;
}

function updateMaterialInfoToApc($metadata, $tc_info, $storage_flag) {
	$bis = new BIS();

	$result = $bis->APC(array(
		'chnl_gb'=> 'CH_B',
		'tape_id'=> $metadata['usr_tape_id'],
		'title'=> $metadata['usr_mtrl_nm'],
		'clip_id' => $metadata['usr_mtrl_id'],
		'clip_flag'=> $storage_flag[0],
		'clip_flag1'=> $storage_flag[1],
		'som'=> addColonToTimecode($tc_info['tcin']),
		'eom'=> addColonToTimecode($tc_info['tcout']),
		'dur'=> addColonToTimecode($tc_info['duration'])
	));

	return $result;
}

function addColonToTimecode($timecode) {
	if ( ! strstr($timecode, ':')) {
		$timecode = substr($timecode , 0, 2) . ':' .  substr($timecode , 2, 2) . ':' . substr($timecode , 4, 2) . ':' . substr($timecode , 6, 2);
	}

	return $timecode;
}
?>
