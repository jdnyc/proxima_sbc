<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/Search.class.php';
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/soap/nusoap.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/interface.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/Zodiac.class.php');

//file_put_contents(LOG_PATH.'/xml/diva/diva_update_'.date('Ymd').'.html', "\n\n".file_get_contents('php://input')."\n\n", FILE_APPEND);
/*
<response>
	<archive_list>
		<archive archive_id="2257" reqnum="1281" task_id="2257" type="archive" status="processing" progress="100" path="2010/10/16/01/0421/&#xAE40;&#xC7AC;&#xBC94; &#xD14C;&#xC2A4;&#xD2B8;.mxf"/>
		<archive archive_id="2258" reqnum="" task_id="2258" type="restore" status="processing" progress="100" path="2010/10/16/01/0421/Catalog"/>
		<archive archive_id="2259" reqnum="" task_id="2259" type="pfr_restore" status="processing" progress="100" path="2010/10/16/01/0421" start="100" end="3000"/>
	</archive_list>
</response>
*/
/*
 *<Request>
 *		<archive_list>
 *			<archive object_name="P2016052300003" reqnum="42" task_id="9" type="archive" status="complete" progress="100" filesize="" error_id="1000" error_msg="(success)"/>
 *		</archive_list>
 *</Request>
 */
try {	
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/diva_update_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] update_xml ===> '.file_get_contents('php://input')."\r\n", FILE_APPEND);

	///기본 만료일 2일후 2012-09-18 이성용
	$expired_date = date('YmdHis', strtotime("+2 days") );

	$archive_list = new SimpleXMLElement(file_get_contents('php://input'));
	
	foreach ($archive_list->archive_list->archive as $item) {
		//print_r($item);exit;

		//기본 flag 
		$default_complete_flag = true;

		$status = (string)$item['status'];
		$reqnum = (string)$item['reqnum'];
		$task_id = (string)$item['task_id'];
		$tape_id = (string)$item['tapeid'];
		$type = (string)$item['type'];
		$filesize = (string)$item['filesize'];
		$duration = (string)$item['duration'];
		$progress = (string)$item['progress'];
		$error_msg = (string)$item['error_msg'];		
		//디바아카이브 아이디
		$object_name = (string)$item['object_name'];

		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/diva_update_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] status ===> '.$status."\r\n", FILE_APPEND);
		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/diva_update_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] type ===> '.$type."\r\n", FILE_APPEND);
		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/diva_update_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] reqnum ===> '.$reqnum."\r\n", FILE_APPEND);

		if($status != 'error') {
			$result = $db->exec("
						UPDATE	ARCHIVE
						SET		REQNUM = '{$reqnum}'
						WHERE	TASK_ID = $task_id
					");

					@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/diva_update_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] result ===> '.$result."\r\n", FILE_APPEND);
		}
		$ip = $_SERVER['REMOTE_ADDR'];
		
		// diva type에 따라 task_type 변경
		switch($type) {
			case 'archive':
				$task_type = '110';
			break;
			case 'restore':
				$task_type = '160';
			break;
			case 'pfr_restore':
				$task_type = '140';
			break;
			case 'delete':
				$task_type = '150';
			break;
			case 'info':
				$task_type = '170';
			break;
		}

		// 20100106 작업중일때 프로그래스 처리를 위해 XML에 progress 속성을 추가하여 Task에 업데이트 되도록 수정함.
		// 작업자 : 김재범
		//$result = $db->exec("update task set status='{$item['status']}' where id={$item['task_id']}");
		//2011.01.12 디비 task에 statusr값 통일을 위해 progressing으로 변환처리.

		if($status == 'start'){
			//스타트 값은 없데이트 하지 않는다 2012-11-27 이성용
			continue;
		}
		if($status == 'pending'){
			//pending 값은 processing 으로
			$status = 'processing';
		}

		$task_info = $db->queryRow("
						SELECT	*
						FROM	BC_TASK
						WHERE	TASK_ID = $task_id
					");
		$media_id = $task_info['media_id'];
		$task_user_id = $task_info['task_user_id'];
		$target = $task_info['target'];
		$file_nm = basename($task_info['target']);

		//RESTORE 시 우선순의 변경하면 자동으로 다음 작업의 priority 값도 동일하게 들어간다...
		$update_task_priority = $task_info['priority'];

		$media_info = $db->queryRow("
						SELECT	M.*, C.UD_CONTENT_ID
						FROM	BC_MEDIA M
								LEFT OUTER JOIN BC_CONTENT C ON C.CONTENT_ID = M.CONTENT_ID
						WHERE	M.MEDIA_ID = $media_id
					");
		$content_id = $media_info['content_id'];
		$ud_content_id = $media_info['ud_content_id'];

		$ori_info = $db->queryRow("
						SELECT	*
						FROM	BC_MEDIA
						WHERE	CONTENT_ID = $content_id
						AND		MEDIA_TYPE = 'original'
					");

		if($status == 'complete' && $task_info['status']=='complete'){
			continue;
		}

		// Diva 작업상태에 대해서 DAS쪽으로 전달
		// 후단 작업진행을 위해서 DB업데이트가 아닌 update_task_status.php로  post 전송
// 		$result = $db->exec("
// 					UPDATE	BC_TASK
// 					SET		STATUS = '$status',
// 							PROGRESS = '$progress'
// 					WHERE	TASK_ID = $task_id
// 				");
		$request = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Request />');
		$request->addChild("TaskID", $task_id );
		$request->addChild("TypeCode", $task_type );
		$request->addChild("Progress", $progress );
		$request->addChild("Status", $status );
		$request->addChild("Ip", $ip);
		$request->addChild("Log", $log);
		$sendxml =  $request->asXML();
		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/diva_update_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] sendxml ===> '.$sendxml."\r\n", FILE_APPEND);

		$task = new TaskManager($db);
		
		$mediaInfo = $db->queryRow("select * from bc_media where media_id= {$task_info['media_id']}");
		if( empty($mediaInfo) ) throw new Exception ('not found mediaInfo', 106 );
		
		if( !empty($filesize) ){
			$task->update_filesize($mediaInfo['media_id'] , $filesize);
		}
		
		if( !empty($filename) ){
			$filename = pathinfo($filename, PATHINFO_BASENAME);
			$task->update_filename($mediaInfo['media_id'] , $filename);
		}
		
		$result = $task->Post_XML_Soket('172.30.2.20', '/workflow/update_task_status.php', $sendxml );
		$result_content = substr( $result , strpos( $result, '<'));
		$result_content_xml = InterfaceClass::checkSyntax($result_content);

		if($status == 'complete') {
			if(empty($task_info)) throw new Exception('디비에 작업 정보가 존재하지 않습니다.');
			//임시로 스토리지 아이디값.
			$storage_id = '0';
			// 성공일 경우에는 리스토어 성공만 SMS 발송

			if ($type == 'archive') {
				// 아카이브가 성공하면 BC_MEDIA에 아카이브 타입 여부 확인후 없으면 아카이브 타입 추가
				// 있으면...음....몰라...
				$ori_media_info = $db->queryRow("
									SELECT	*
									FROM	BC_MEDIA
									WHERE	CONTENT_ID = '$content_id'
									AND		MEDIA_TYPE = 'original'
								");

				$archive_media_check_q = "
					SELECT	COUNT(*)
					FROM	BC_MEDIA
					WHERE	CONTENT_ID = $content_id
					AND		MEDIA_TYPE = 'archive'
				";

				$archive_media_count = $db->queryOne($archive_media_check_q);
	
				if($archive_media_count == 0) {
					//아카이브 미디어정보 등록
					$new_media_id = getSequence('SEQ_MEDIA_ID');
					$query = "
						INSERT INTO BC_MEDIA 
							(MEDIA_ID, CONTENT_ID, STORAGE_ID, MEDIA_TYPE, PATH, FILESIZE, CREATED_DATE, REG_TYPE)	
						VALUES
							('$new_media_id','{$content_id}', '$storage_id', 'archive', '".$task_info['target']."', '$filesize', '".date('YmdHis')."', '".$task_info['destination']."')";
					$r = $db->exec($query);

					//그룹 아카이브인지 체크.
					$agi_update_query = "";
					$archive_group_info = $db->queryAll("
											SELECT	*
											FROM	ARCHIVE_GROUP_INFO
											WHERE	ARCHIVE_SEQ = 
														(
															SELECT	ARCHIVE_SEQ
															FROM	ARCHIVE
															WHERE	TASK_ID = $task_id
														)
										");
					if( !empty($archive_group_info) ) {
						//그룹 아카이브이면, archive타입으로 생성된 media_id를 업데이트 쳐 준다.
						$agi_update_query = "
								UPDATE	ARCHIVE_GROUP_INFO
								SET		MEDIA_ID = $new_media_id
								WHERE	ARCHIVE_SEQ = 
											(
												SELECT	ARCHIVE_SEQ
												FROM	ARCHIVE
												WHERE	TASK_ID = $task_id
											)
						";
						$db->exec($agi_update_query);
					}
				}

				$arc_user_id = $task_info['task_user_id']? $req_info['task_user_id'] : '';

				if(!$arc_user_id) {
					$arc_user_id = 'system';
				}

				$action = 'archive';
				insertLog($action, $arc_user_id, $content_id, 'Archive complete(task_id: '.$task_id.')');

			} else if ($type == 'restore') {
				// 리스토어 시 에는 NPS 통해서 스토리지로 바로 씀
				// 라디오리스토어 일 경우에는 상태값 업데이트를 해줘야됨
				if($task_info['destination'] == 'radio_restore'){
					$radio_restore_content_id = $db->queryOne("
													SELECT	CONTENT_ID
													FROM	TB_RADIO_REQUEST
													WHERE	TASK_ID = $task_id
												");

					$update_content_status = $db->exec("
												UPDATE	BC_CONTENT
												SET		STATUS = '2'
												WHERE	CONTENT_ID = $radio_restore_content_id
											");

					$searchEngine	= $db->queryOne("
							SELECT	C.CODE
							FROM	BC_CODE C, BC_CODE_TYPE CT
							WHERE	C.CODE_TYPE_ID = CT.ID
							AND		CT.CODE = 'SEARCHENGINE'
						");
					    searchUpdate($content_id);
				}
				
				if(INTERWORK_SMS == 'Y'){
					// 리스토어가 완료일 경우 요청자에게 SMS 발송
					$user_info = $db->queryRow("
									SELECT	*
									FROM	BC_MEMBER
									WHERE	USER_ID =
												(
													SELECT	REQ_USER_ID
													FROM	TB_REQUEST
													WHERE	TASK_ID = $task_id
												)
								");
					$content_title = $db->queryOne("
										SELECT	TITLE
										FROM	BC_CONTENT
										WHERE	CONTENT_ID = $content_id
									");
					$sub_title = mb_substr($content_title, 0, 35, "UTF-8");
					$sms_msg = '리스토어 완료-'.$sub_title;
						
					if(!empty($user_info['phone']) && !empty($sms_msg)) {
						$zodiac = new Zodiac();
						$phone = trim(str_replace('-', '', $user_info['phone']));
						$sms_param = array(
							'rcv_phn_id'		=>	$phone,
							'snd_phn_id'		=>	'023115572',
							'mesg'				=>	$sms_msg
						);
						
						//$zodiac->putRequestSms($sms_param);
					}
				}

				$action = 'restore';
				insertLog($action, $task_info['task_user_id'], $content_id, 'Restore complete(task_id: '.$task_id.')');

			} else if ($type == 'pfr_restore') {
				if(INTERWORK_SMS == 'Y'){
					// 리스토어가 완료일 경우 요청자에게 SMS 발송
					$user_info = $db->queryRow("
									SELECT	*
									FROM	BC_MEMBER
									WHERE	USER_ID =
											(
												SELECT	REQ_USER_ID
												FROM	TB_REQUEST
												WHERE	TASK_ID = $task_id
											)
								");
					$content_title = $db->queryOne("
										SELECT	TITLE
										FROM	BC_CONTENT
										WHERE	CONTENT_ID = $content_id
									");
					$sub_title = mb_substr($content_title, 0, 32, "UTF-8");
					$sms_msg = 'PFR리스토어 완료-'.$sub_title;
						
					if(!empty($user_info['phone']) && !empty($sms_msg)) {
						$zodiac = new Zodiac();
						$phone = trim(str_replace('-', '', $user_info['phone']));
						$sms_param = array(
								'rcv_phn_id'		=>	$phone,
								'snd_phn_id'		=>	'023115572',
								'mesg'				=>	$sms_msg
						);
							
						//$zodiac->putRequestSms($sms_param);
					}
				}

				$action = 'pfr_restore';
				insertLog($action, $task_info['task_user_id'], $content_id, 'PFR restore complete(task_id: '.$task_id.')');

			} else if ($type == 'delete') {
				$query = "
					UPDATE	BC_MEDIA
					SET		STATUS = '1',
							FLAG = '1',
							DELETE_STATUS = '1',
							DELETE_DATE ='".date('YmdHis')."'
					WHERE	MEDIA_ID = '".$media_id."'";
				$r = $db->exec($query);
				
				// 삭제가 성공하면 ARCHIVE_INFO 테이블에 해당 데이터도 삭제
				$del_archive_info = $db->exec("
										DELETE
										FROM	ARCHIVE_INFO
										WHERE	CONTENT_ID = $content_id
									");
				// 로그 추가
				$action = 'archive_del';
				insertLog($action, $task_user_id, $content_id, 'Archive_del complete(task_id: '.$task_id.')');

			} else if ($type == 'info') {
				$arr_diva_media = explode('/', $tape_id);
				foreach($arr_diva_media as $dm)
				{
					$arr_sub = explode(';;', $dm);
					$diva_media = $arr_sub[0];
					$diva_tapes = $arr_sub[1];
					$arr_diva_tapes = explode(';', $diva_tapes);
					foreach($arr_diva_tapes as $diva_tape)
					{
						if(!empty($diva_media) && !empty($diva_tape)) {
							$is_exist = $db->queryOne("
											SELECT	COUNT(ARCHIVE_ID)
											FROM	ARCHIVE_INFO
											WHERE	ARCHIVE_ID = '$object_name'
											AND		MEDIA = '$diva_media'
										");
							
							if($is_exist > 0) {
								continue;
							}

							// 라디오아카이브인지 미디어아카이브인지 구분자 필요 2016.10.18 임찬모
							$arc_type = 'media';
							if($ud_content_id == '4000288') {
								$arc_type = 'radio';
							}
							$query2 = "
								INSERT INTO ARCHIVE_INFO
									(CONTENT_ID, ARCHIVE_ID, MEDIA, TAPE, ARC_TYPE)
								VALUES
									('".$content_id."','".$object_name."','".$diva_media."','".$diva_tape."', '".$arc_type."')
							";
							$db->exec($query2);
						}		
					}
				}
				
				// Diva는 파일사이즈 기준이 KB이고 우리는 Byte 이기때문에 1024를 곱해줌
				$archive_filesize = (int)$filesize * 1024;
				$query = "
						UPDATE	BC_MEDIA
						SET		FILESIZE = '".$archive_filesize."'
						WHERE	MEDIA_ID = '".$media_id."'
				";
				$r = $db->exec($query);
			}

		} else if ($status == 'processing') {
			if($type == 'restore') {
				
			} else if($type == 'pfr_restore') {

			}

		} else if ($status == 'error') {
			if( !empty($error_msg) ) {
				$error_msg = $db->escape($error_msg);
				$task_log_query = "
						INSERT INTO BC_TASK_LOG
							(TASK_ID, DESCRIPTION, CREATION_DATE)
						VALUES
							($task_id, '$error_msg', '".date('YmdHis')."')
				";
				$db->exec($task_log_query);
			}

			if(INTERWORK_SMS == 'Y'){
				if($type == 'archive') {
					$type_msg = '아카이브 실패-';
					// 아카이브는 승인자에게 전달
					$user_info = $db->queryRow("
									SELECT	*
									FROM	BC_MEMBER
									WHERE	USER_ID = '$task_user_id'
								");
				} else if($type == 'restore') {
					$type_msg = '리스토어 실패-';
					// 리스토어는 요청자에게 전달
					$user_info = $db->queryRow("
									SELECT	*
									FROM	BC_MEMBER
									WHERE	USER_ID =
												(
													SELECT	REQ_USER_ID
													FROM	TB_REQUEST
													WHERE	TASK_ID = $task_id
												)
								");
				} else if($type == 'pfr_restore') {
					$type_msg = 'PFR 실패-';
					// PFR 리스토어는 요청자에게 전달
					$user_info = $db->queryRow("
									SELECT	*
									FROM	BC_MEMBER
									WHERE	USER_ID =
												(
													SELECT	REQ_USER_ID
													FROM	TB_REQUEST
													WHERE	TASK_ID = $task_id
												)
								");
				} else if($type == 'delete') {
					//우리 내부적으로 실패하면 다음날 다시 시도하거나 수동으로 다시시도. 결과는 안던짐.
				}
				$content_title = $db->queryOne("
									SELECT	TITLE
									FROM	BC_CONTENT
									WHERE	CONTENT_ID = $content_id
								");
				$sub_title = mb_substr($content_title, 0, 35, "UTF-8");
				$sms_msg = $type_msg.$sub_title;
				
				if(!empty($user_info['phone']) && !empty($sms_msg)) {
					$zodiac = new Zodiac();
					$phone = trim(str_replace('-', '', $user_info['phone']));
					$sms_param = array(
							'rcv_phn_id'		=>	$phone,
							'snd_phn_id'		=>	'023115572',
							'mesg'				=>	$sms_msg
					);
					
					//$zodiac->putRequestSms($sms_param);
				}
			}
		}
	}
	
	$xml = new SimpleXMLElement("<Response />");
	$result = $xml->addChild("Result");
	$result->addAttribute("success", "true");
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/diva_update_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] return ===> '.$xml->asXML()."\r\n", FILE_APPEND);
	echo $xml->asXML();
	
} catch (Exception $e) {
	$xml = new SimpleXMLElement('<response><archive_list /></response>');
	$xml->archive_list->addAttribute('success', 'false');
	$xml->archive_list->addAttribute('message', $e->getMessage());
	$xml->archive_list->addAttribute('query', $db->last_query);

	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/diva_update_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] error ===> '.$xml->asXML()."\r\n", FILE_APPEND);
	echo $xml->asXML();
}

/* 스토리지 아이디 값을 구해오기 위해 스토리지테이블에 디바 정의 필요.
if inside pathFromTask: select * from task where id = '3449'
if inside get_register: select register from task_workflow where task_workflow_id = '8'
if inside media_info: select content_id, filesize from media where content_id = (select content_id from media where media_id = 4009660) and type = 'original'
if inside storage_id: select storage_id from storage where mac_address = '10.10.10.121'
if inside check_nearline: select content_id from media where content_id = (select content_id from media where media_id = 4009660) and type = 'arhive'
if inside query: insert into bc_media (content_id, media_id, storage_id, type, path, filesize, created_date, register, storage, status)
values ('4008496', '4009662', '', 'archive', '29', '0', '20101214153400', '6', '', '')
*/

?>
