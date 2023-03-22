<?php
session_start();
require_once ($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
require_once ($_SERVER['DOCUMENT_ROOT'] . '/lib/SGL.class.php');
require_once ($_SERVER['DOCUMENT_ROOT'] . '/workflow/lib/task_manager.php');

// SGL연동 방식이 SOAP으로 변경되면서 BC_TASK테이블에 작업 추가 후 상태값에 대한 처리 (2015.08.07 임찬모)

$job_priority = 1;
$user_id = $_SESSION['user']['user_id'];
$ask_admin = false;
$insert_task = new TaskManager($db);
$ip = $_SERVER['REMOTE_ADDR'];
try {
	if(count($data) > 10) {
		echo json_encode(array(
			'success' => false,
			//'msg' => '1회허용치(10개)를 초과하였습니다' MSG01010
			'msg' => _text('MSG01010').'(max:10)'
		));
	} else {
		foreach ($data as $content_id) {
			//진행중인 작업에 대해 중복 요청을 막기 위해 추가
			$query = "select count(content_id) from bc_restore_ing where content_id = $content_id";
			$restore_ing = $db->queryOne($query);

			if($restore_ing > 0) {
				$is_restore = true;
			} else {
				//원본 존재하는경우 막음
				$ori_check = $db->queryRow("select * from bc_media
					where content_id=".$content_id." and media_type='original'
					order by media_id desc");
				if($ori_check['delete_date'] == '') {
					//MSG01021 원본이 지워진 경우 리스토어가 가능합니다.
					throw new Exception(_text('MSG01021'));
				}

				$restore_run_check = $db->queryRow("
					SELECT	* 
					FROM	BC_TASK
					WHERE	MEDIA_ID IN (
							SELECT	MEDIA_ID
							FROM	BC_MEDIA
							WHERE	CONTENT_ID=".$content_id."
							)
					AND		TYPE='160'
					ORDER BY TASK_ID DESC");
				if( !empty($restore_run_check['task_id']) && $restore_run_check['status'] != 'complete') {
					//MSG02069 Restore job is in progress.
					throw new Exception(_text('MSG02069'));
				}

				//원본이 존재하는 경우 리스토어 작업 진행을 막고 그 이외의 경우에는 작업 진행토록 수정(2014.02.05 임찬모)
				$query = "select * from bc_content where content_id =$content_id";
				$infos = $db->queryRow($query);
				$del_yn = $infos['del_yn'];
				$del_status = $infos['del_status'];
				$displayname = $infos['title'];
				$bs_content_id = $infos['bs_content_id'];
				$sgl_uan = $infos['uan'];
				$is_group = $infos['is_group'];

				$query = "select ud_content_id, category_id, archive_date from bc_content where content_id = $content_id";
				$content_info = $db->queryRow($query);

				$check_ud_content = $content_info['ud_content_id'];
				$category_id = $content_info['category_id'];
				$archive_date = $content_info['archive_date'];

				$full_path =$db->queryOne("select path from bc_media where media_id = (select max(media_id) from bc_media where content_id = $content_id and media_type = 'archive')");
				$arr_full_path = explode('/', $full_path);
				$filename = array_pop($arr_full_path);
				$filename_arr = explode('.', $filename);
				$archive_type = array_pop($filename_arr);
				if($content_id < 43709 && $check_ud_content == '358' && $user_id != 'alex2207' && $user_id != 'bkjeong' && $user_id != 'reidar') {
					$ask_admin = true;
				} else {
					if($bs_content_id == SEQUENCE) {
						$channel = 'sgl_restore_seq';
					} else {
						if($is_group == 'G') {
							$channel = 'sgl_restore_group';
						} else {
							$channel = 'sgl_restore';
						}						
					}

					$insert_task->set_priority(200);
					$insert_task->start_task_workflow($content_id, $channel, $user_id);
					$task_id = $insert_task->get_task_id();

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
			}

			if($is_restore) {
print_r('111');
				//'msg' => '이미 진행중인 작업이 있습니다 작업흐름을 확인해주세요' MSG02069
				throw new exception(_text('MSG02069'));
//				echo json_encode(array(
//					'success' => false,
//					//'msg' => '이미 진행중인 작업이 있습니다 작업흐름을 확인해주세요' MSG02069
//					'msg' => _text('MSG02069')
//				));
			} else if($ask_admin) {
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
		}

		echo json_encode(array(
			'success' => true,
			//'msg' => '리스토어 요청이 완료되었습니다.' MN01021 MSG01009
			'msg' => _text('MN01021').' '._text('MSG01009')
		));
	}
} catch (Exception $e) {
	$msg = $e->getMessage();
	echo json_encode(array(
		'success' => false,
		'msg' => $msg
	));
}
?>