<?php

use Proxima\core\Session;
use Api\Types\ArchiveRequestType;
/// 아카이브 관련 작업
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/archive.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/Search.class.php';
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/Zodiac.class.php');
Session::init();
/*
	아카이브/ 리스토어 요청관리에 대한 행위 처리 (Action)
	req_no => Key 값
	2가지 행위로 나눔
	accept	=> 승인 (archive / restore)
	decline	=> 반려 (archive / restore)
	동일작업 진행여부 확인 필요
	// 아카이브 상태
	// 1: 요청 , 2: 자동승인, 3: 승인, 4: 반려 
	define('ARCHIVE_REQUEST',			1);
	define('ARCHIVE_APPROVE',			2);
	define('ARCHIVE_REJECT',			3);
*/
define('ARCHIVE_REQUEST',			1);
define('ARCHIVE_APPROVE',			2);
define('ARCHIVE_REJECT',			3);

try {

	$user_id = $_SESSION['user']['user_id'];
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] request ===> '.print_r($_POST,true)."\r\n", FILE_APPEND);
	if(empty($user_id) || $user_id=='temp') {
		throw new Exception('다시 로그인 해 주시기 바랍니다.');
	}

	$action		= $_POST['action'];
	$arr_req_no	= $_POST['req_no'];
	if(!is_array($arr_req_no)) $arr_req_no = array($arr_req_no);
	
	$appr_comment = $_POST['appr_comment'];
	$now = date('YmdHis');

	$dup_job_flag = false;

	if(!empty($appr_comment)) {
		$appr_comment = $db->escape($appr_comment);
	}
	
	$req_str = join(',', $arr_req_no);
	
	$archive = new Archive();
	$task_mgr = new TaskManager($db);

	switch($action) {
		case 'approve' :			
			$req_lists = $db->queryAll("
							SELECT	*
							FROM	TB_REQUEST
							WHERE	REQ_NO IN ($req_str)
						");
			$query = "SELECT	*
							FROM	TB_REQUEST
							WHERE	REQ_NO IN ($req_str)";
			@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] query ===> '.$query."\r\n", FILE_APPEND);
			foreach($req_lists as $req) {
				$req_no = $req['req_no'];
                $content_id = $req['das_content_id'];
                $nps_content_id = $req['nps_content_id'];
				$type = $req['req_type'];
				$content_info = $db->queryRow('SELECT * FROM BC_CONTENT WHERE CONTENT_ID = ' . $content_id);
                
				switch($type) {
					case 'archive' :
						// task_id가 있으면 진행중인 작업이 있는 걸로 판단
						if(!empty($req['task_id'])) {
							@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] archive duplicate ===> '.$req_no."\r\n"."\r\n", FILE_APPEND);
							$dup_job_flag = true;
							continue;
                        }
                        

                        
                        $isExist =   $db->queryOne("SELECT count(*) FROM TB_REQUEST WHERE REQ_STATUS!=3 AND REQ_NO!='$req_no' AND NPS_CONTENT_ID='$nps_content_id' ");
                        if( $isExist > 0){
                            throw new Exception('중복 요청된 목록입니다.');
                        }

						//아카이브 작업을 위한 복사본을 만든다. skc 2019.06.13
						//$task_id = $task_mgr->start_task_workflow($content_id, 'archive_copy', $user_id);
						$original = $db->queryRow("
							SELECT	A.*
									,B.PATH AS ROOT_PATH
							FROM	BC_MEDIA A
									LEFT OUTER JOIN BC_STORAGE B ON(B.STORAGE_ID = A.STORAGE_ID)
							WHERE	A.MEDIA_TYPE = 'original'
							AND		A.CONTENT_ID = $content_id
						");
						
						//	CPBC 동영상과 그외 유형의 아카이브 파일 경로 별개 처리
						/*if($content_info['bs_content_id'] == '506'){
							$storage_info = $db->queryRow("
								SELECT	*
								FROM	BC_STORAGE
								WHERE	STORAGE_ID = 104
							");
						}else{
							$storage_info = $db->queryRow("
								SELECT	*
								FROM	BC_STORAGE
								WHERE	STORAGE_ID = 121
							");
						}
						*/
						
						/*if(!empty($original)){
						if(!empty($original) && !empty($storage_info)){
							$v_target1 = $original['root_path'] . '/' . $original['path'];

							@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).'.log', '['.date("Y-m-d H:i:s").'] v_target1'.":".$v_target1."\n", FILE_APPEND);
							$v_target1 = iconv('utf-8', 'euc-kr', $v_target1);
							$v_file_size = filesize($v_target1);
							@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).'.log', '['.date("Y-m-d H:i:s").'] v_file_size'.":".$v_file_size."\n", FILE_APPEND);
							//파일명과 확장자 분리
							$filename_array = explode('.',$original['path']);
							
							if(count($filename_array) < 2) throw new Exception('Archive 대상 콘텐츠의 원본 파일을 확인해 주세요.');;

							$v_ext = $db->escape(strtoupper(array_pop($filename_array)));
							$v_target = $content_id . '.' . $v_ext;

							//아카이브 위치에 파일 복사
							$v_return = copy($v_target1, $storage_info['path'] . '/' . $v_target);

							$v_query = "
								INSERT INTO bc_media(
									content_id, storage_id, media_type, path, filesize, 
									created_date, reg_type, status, delete_date, flag, delete_status, 
									vr_start, vr_end, expired_date)
								VALUES (
									{$content_id}, {$storage_info['storage_id']}, 'archive', '$v_target', '$v_file_size', 
									TO_CHAR(NOW(), 'yyyymmddhh24miss'), 'archive', null, null, null, null, 
									null, null, '99991231000000');
							";

							@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).'.log', '['.date("Y-m-d H:i:s").'] v_query'.":".$v_query."\n", FILE_APPEND);
							$r_archive = $db->exec($v_query);
						}
						*/

						// //작업추가
						// $original = $db->queryRow("
						// 	SELECT	*
						// 	FROM	BC_MEDIA
						// 	WHERE	MEDIA_TYPE = 'original'
						// 	AND		CONTENT_ID = $content_id
						// ");

						// $ori_file = pathinfo($original['path'], PATHINFO_FILENAME);
						// $ori_ext = pathinfo($original['path'], PATHINFO_EXTENSION);
						
						// if(strtoupper($ori_ext) == 'MOV') {
						// 	$type = 'archive_mov';
						// }

						// @file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] content_id ===> '.$content_id."\r\n"."\r\n", FILE_APPEND);
						
						// $task_id = $task_mgr->start_task_workflow($content_id, $type, $user_id);
                        
                        
                        $archiveService = new \Api\Services\ArchiveService($app->getContainer());

                        $user = new \Api\Models\User();
                        $user->user_id = Session::getUser('user_id');
                        
                        $archiveInfo = $archiveService->archive($content_id, $user);
                        if(!$archiveInfo){
                            throw new Exception('아카이브 실행 실패하였습니다. 관리자에게 문의 바랍니다.');
                        }
                        $task_id = $archiveInfo->task_id ;
						//요청테이블에 승인 상태 및 정보 업데이트
						$r_archive_appr = $db->exec("
                            UPDATE	TB_REQUEST
                            SET		REQ_STATUS = ".ARCHIVE_APPROVE.",
                                    APPR_TIME = '$now',
                                    APPR_USER_ID = '$user_id',
                                    APPR_COMMENT = '$appr_comment',
                                    TASK_ID = $task_id
                            WHERE	REQ_NO = $req_no
                        ");
						//아카이브 테이블 추가
						// $archive_seq = getSequence('ARCHIVE_SEQ');
						// $r_archive = $db->exec("
						// 				INSERT INTO ARCHIVE
						// 					(ARCHIVE_SEQ, MEDIA_ID, TASK_ID, ARCHIVE_ID, DIVA_CATEGORY, DIVA_GROUP, QOS, DESTINATIONS)
						// 				VALUES
						// 					($archive_seq, ".$original['media_id'].", $task_id, '$ori_file', 'diva', 'spm_storage', '3', 'nas')
						// 			");
						// 아카이브 승인 상태에 대해서 NPS에 전송
						//$archive->updateRequestStatusToNPS($req_no);
					break;
					case 'restore' :
						// 리스토어는 DAS에서 콘텐츠 정보를 NPS에 등록후 NPS로부터 경로를 받아와야됨
						//$regist_content = $archive->registContentToNPS($req_no);
						$regist_return = json_decode($regist_content, true);
						@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] $regist_return ===> '.print_r($regist_return, true)."\r\n", FILE_APPEND);
						$filepath = $regist_return['filepath'];
						$nps_content_id = $regist_return['nps_content_id'];
                
                        $archiveService = new \Api\Services\ArchiveService($app->getContainer());

                        $user = new \Api\Models\User();
                        $user->user_id = Session::getUser('user_id');
                        $task_id = $archiveService->restore($content_id, $user); 
                        
                        if(!$task_id){
                            throw new Exception('요청작업 실패');
                        }
				    
                        
						//아카이브 테이블 추가
						// $archive_seq = getSequence('ARCHIVE_SEQ');
						// $r_archive = $db->exec("
						// 				INSERT INTO ARCHIVE
						// 					(ARCHIVE_SEQ, MEDIA_ID, TASK_ID, ARCHIVE_ID, DIVA_CATEGORY, DIVA_GROUP, QOS, DESTINATIONS)
						// 				VALUES
						// 					($archive_seq, ".$original['media_id'].", $task_id, '$ori_filename', 'diva', 'spm_storage', '3', 'nas')
						// 			");
						
						// 승인상태 업데이트
						$r_restore_appr = $db->exec("
												UPDATE	TB_REQUEST
												SET		REQ_STATUS = ".ARCHIVE_APPROVE.",
														APPR_TIME = '$now',
														APPR_USER_ID = '$user_id',
														APPR_COMMENT = '$appr_comment',
														TASK_ID = $task_id
												WHERE	REQ_NO = $req_no
										");
						// 리스토어 승인 상태에 대해서 NPS에 전송
						//$archive->updateRequestStatusToNPS($req_no);
					break;
					case 'delete' :
						if(!empty($req['task_id'])) {
							@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] archive duplicate ===> '.$req_no."\r\n"."\r\n", FILE_APPEND);
							$dup_job_flag = true;
							continue;
						}
						
						$isExist =   $db->queryOne("SELECT count(*) FROM TB_REQUEST WHERE REQ_STATUS=1 AND REQ_NO!='$req_no' and req_type='$type' AND NPS_CONTENT_ID='$nps_content_id' ");
                        if( $isExist > 0){
                            throw new Exception('중복 요청된 목록입니다.');
						}
						
						$original = $db->queryRow("
						SELECT	A.*
								,B.PATH AS ROOT_PATH
						FROM	BC_MEDIA A
								LEFT OUTER JOIN BC_STORAGE B ON(B.STORAGE_ID = A.STORAGE_ID)
						WHERE	A.MEDIA_TYPE = 'original'
						AND		A.CONTENT_ID = $content_id
						");

                        $archiveService = new \Api\Services\ArchiveService($app->getContainer());
                            //서비스 생성
                        $contentService = new \Api\Services\ContentService(app()->getContainer());
                        $mediaService = new \Api\Services\MediaService(app()->getContainer());
                        $tbRequestService = new \Api\Services\TbRequestService(app()->getContainer());

                        $taskService = new \Api\Services\TaskService( app()->getContainer());


                        $user = new \Api\Models\User();
                        $user->user_id = Session::getUser('user_id');
                        
                      
                        $task_id = 0 ;
						//요청테이블에 승인 상태 및 정보 업데이트
						$r_archive_appr = $db->exec("
                            UPDATE	TB_REQUEST
                            SET		REQ_STATUS = ".ARCHIVE_APPROVE.",
                                    APPR_TIME = '$now',
                                    APPR_USER_ID = '$user_id',
                                    APPR_COMMENT = '$appr_comment',
                                    TASK_ID = $task_id
                            WHERE	REQ_NO = $req_no
                        ");

                        //아카이브 삭제 요청
                        //파일 삭제
                        //DTL 삭제
                        $archiveService->delete($content_id , $user);

                        //BC_DELETE_CONTENT 입력 삭제요청
                        $contentDelete = $contentService->deleteRequest($content_id, '아카이브 삭제', $user);
                        $task = $taskService->getTaskManager();                
                        $task->set_priority(400);
                        $task->setStatus('scheduled');

                        $medias = $mediaService->getMediaByContentId($content_id);
                        foreach($medias as $media)
                        {
                            $mediaService->deleteReady($media->media_id);

                            if( $media->status == 0 && empty($media->flag) ){
                                //삭제 대상이 아닌 목록만
                                //삭제 워크플로우 수행
                                $mediaType = $media->media_type;                       
     
                                //삭제 워크플로우                    
                                if ($mediaType == 'original') {
                                    $channel ='delete_media_'.$mediaType;
                                    $originalTaskId = $task->start_task_workflow($content_id, $channel, $user->user_id );

                                }else if($mediaType == 'proxy' || $mediaType == 'proxy360' || $mediaType == 'proxy2m1080' || $mediaType == 'proxy15m1080' ){
                                    $channel ='delete_media_'.$mediaType;
                                    $taskId = $task->start_task_workflow($content_id, $channel, $user->user_id );

                                }else if($mediaType == 'archive'){
                                    $channel ='delete_media_'.$mediaType;
                                    $taskId = $task->start_task_workflow($content_id, $channel, $user->user_id );
                                }else if($mediaType == 'publish'){

                                }else if($mediaType == 'audio'){

                                }else if($mediaType == 'yt_thumb'){

                                }else if($mediaType == 'thumb'){
                                    $channel ='delete_media_'.$mediaType;
                                    $taskId = $task->start_task_workflow($content_id, $channel, $user->user_id );
                                }
                            }
                        }

                        $contentService->delete($content_id, $user);                        
                        //삭제 승인
                        $contentService->deleteAccept($contentDelete->id, $originalTaskId, $user);

					break;
				}
			}
			
		break;
        case 'reject' :
            
            $contentService = new \Api\Services\ContentService($app->getContainer());
			$req_lists = $db->queryAll("
					SELECT	*
					FROM	TB_REQUEST
					WHERE	REQ_NO IN ($req_str)
					");
			
			$zodiac = new Zodiac();
			
			foreach($req_lists as $req) {
				$req_no = $req['req_no'];
				$content_id = $req['das_content_id'];
				$type = $req['req_type'];
				
				@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] reject req ===> '.$req_no."\r\n", FILE_APPEND);
				
				// 승인상태 업데이트
				$r_restore_appr = $db->exec("
									UPDATE	TB_REQUEST
									SET		REQ_STATUS = ".ARCHIVE_REJECT.",
                                    APPR_TIME = '$now',
                                    APPR_USER_ID = '$user_id',
                                    APPR_COMMENT = '$appr_comment'
                                    WHERE	REQ_NO = $req_no
                        ");
                        
                if( $type == ArchiveRequestType::DELETE){
                    //삭제 반려일때는 원복 
                    $contentService->restore($content_id);
                }
				// 리스토어 승인 상태에 대해서 NPS에 전송
				// $archive->updateRequestStatusToNPS($req_no);

				// if(INTERWORK_SMS){
				// 	// 반려될 경우 요청자에게 SMS 발송
				// 	$user_info = $db->queryRow("
				// 					SELECT	*
				// 					FROM	BC_MEMBER
				// 					WHERE	USER_ID =
				// 								(
				// 									SELECT	REQ_USER_ID
				// 									FROM	TB_REQUEST
				// 									WHERE	REQ_NO = $req_no
				// 								)
				// 				");
				// 	$content_title = $db->queryOne("
				// 						SELECT	TITLE
				// 						FROM	BC_CONTENT
				// 						WHERE	CONTENT_ID = $content_id
				// 					");
				// 	$sub_title = mb_substr($content_title, 0, 35, "UTF-8");
				// 	$type_msg = '아카이브 반려-';
				// 	if($type == 'restore') {
				// 		$type_msg = '리스토어 반려-';
				// 	}
				// 	$sms_msg = $type_msg.$sub_title;
						
				// 	// if(!empty($user_info['phone']) && !empty($sms_msg)) {
				// 	// 	$phone = trim(str_replace('-', '', $user_info['phone']));
				// 	// 	$sms_param = array(
				// 	// 					'rcv_phn_id'		=>	$phone,
				// 	// 					'snd_phn_id'		=>	'023115572',
				// 	// 					'mesg'				=>	$sms_msg
				// 	// 				);
							
				// 	// 	$zodiac->putRequestSms($sms_param);
				// 	// }
				// }
			}
		break;
		default :
			throw new Exception($action.'은(는) 잘못된 요청입니다.');
		
	}
	

	switch($action){
		case 'approve':
			if($dup_job_flag) {
				$msg = '승인 목록 중에 이미 아카이브 된 소재가 있습니다.\n확인해 주십시오.';
			} else {
				$msg = '요청작업이 승인되었습니다.';
			}			
		break;
		case 'reject':
			$msg = '요청작업이 반려되었습니다.';
		break;
		default :
			$msg = '작업 완료';
		break;
	}

	echo json_encode(
		array(
		'success' => true,
		'msg' => $msg
	));	
} catch(Exception $e) {
	echo json_encode(
		array(
		'success'	=> false,
		'msg' => $e->getMessage(),
		'query' => $db->last_query
	));	
}
?>