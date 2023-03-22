<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/config.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/SGL.class.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php';


try
{
	$mode = $_POST['mode'];
	$in = $_POST['tc_in'];
	$out = $_POST['tc_out'];
	$content_id = $_POST['content_id'];
	$list_no = $_POST['list_no'];
	$tc_no = $_POST['tc_no'];
	$user_id = $_SESSION['user']['user_id'];
	$pfr_list = $_POST['pfr_list'];

	if($mode == 'tc_add') {
		$seq_tc_list = getSequence('SEQ_TC_LIST_NO');
		$insert_q = "insert into tc_list
			(TC_NO,CONTENT_ID,TC_IN,TC_OUT,CREATED_DATE)
			values
			(".$seq_tc_list.",'".$content_id."','".$in."','".$out."','".date('YmdHis')."')";
		$db->exec($insert_q);
	} else if($mode == 'tc_edit') {
		$update_q = "update tc_list set
				tc_in='".$in."',
				tc_out='".$out."'
			where tc_no='".$tc_no."'";
		$db->exec($update_q);
	} else if($mode == 'tc_del') {
		$delete_q = "delete from tc_list
			where tc_no='".$tc_no."'";
		$db->exec($delete_q);
	} else if($mode == 'pfr_request') {
		//PFR작업 시작. content_id 넘어옴.
		$pfr_list = json_decode($pfr_list, true);

		foreach($pfr_list as $pi) {
			$in = ceil($pi['in']*29.97);
			$out = ceil($pi['out']*29.97);
			$tc_in = str_replace(':','',$pi['tc_in']);
			$tc_out = str_replace(':','',$pi['tc_out']);

			$insert_task = new TaskManager($db);
			$sgl = new SGL();

			//PFR 작업할 경로 받기
			$ori_info = $db->queryRow("
							SELECT C.TITLE, M.*
							FROM BC_MEDIA M, BC_CONTENT C
							WHERE M.CONTENT_ID=C.CONTENT_ID
							AND C.CONTENT_ID = '$content_id'
							AND M.MEDIA_TYPE='original'
						");
			$target_file_name = array_pop(explode('/', $ori_info['path']));
			$target_ext = array_pop(explode('.', $ori_info['path']));
			$target = str_replace('.'.$target_ext,'', $target_file_name)
						.'_'.$tc_in.'_'.$tc_out.'.'.$target_ext;
			$sgl_filepath = SGL_PFR_ROOT.'/'.$target;

			$restore_run_check = $db->queryRow("
				SELECT	* 
				FROM	BC_TASK
				WHERE	MEDIA_ID IN (
						SELECT	MEDIA_ID
						FROM	BC_MEDIA
						WHERE	CONTENT_ID=".$content_id."
						)
				AND		TYPE='140'
				AND		TARGET='".$target."'
				ORDER BY TASK_ID DESC");
			if( !empty($restore_run_check['task_id']) && $restore_run_check['status'] != 'complete') {
				//MSG02069 Restore job is in progress.
				throw new Exception(_text('MSG02069'));
			}

			$channel = 'sgl_pfr_restore';
			$insert_task->set_priority(200);
			$arr_source_path = array('source_path' => '', 'target_path' => $target);
			$arr_param_info = array($arr_source_path);
			$insert_task->start_task_workflow($content_id, $channel, $user_id, $arr_param_info);
			$task_id = $insert_task->get_task_id();
			$displayname = $ori_info['title'];

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

				//priority 정보는 설정된 값에서 가져옴 없으면 default
				$priority = $db->queryOne("
							SELECT E.RESTORE_PRIORITY
							FROM BC_CONTENT C, BC_CATEGORY_ENV E
							WHERE C.CONTENT_ID = '$content_id'
							AND C.CATEGORY_ID = E.CATEGORY_ID
						");
				if(!empty($priority)) {
					$priority = 4;
				}

				$pfr_start = $in;
				$pfr_end = $out;

				$return = $sgl->FlashNetRestore($content_id, $sgl_filepath, $displayname, $priority, $pfr_start, $pfr_end);

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
	}

	echo json_encode(array(
		'success' => true,
		'msg' => 'Success'
	));
}
catch ( Exception $e )
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage(),
		'last_query' => $db->last_query
	));
}

?>