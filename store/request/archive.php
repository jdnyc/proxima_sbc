<?php
session_start();
set_time_limit(0);
require_once ($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
require_once ($_SERVER['DOCUMENT_ROOT'] . '/lib/SGL.class.php');
require_once ($_SERVER['DOCUMENT_ROOT'] . '/workflow/lib/task_manager.php');

// SGL연동 방식이 SOAP으로 변경되면서 BC_TASK테이블에 작업 추가 후 상태값에 대한 처리 (2015.08.07 임찬모)
//sleep(40);
//echo 'archive da';
//exit;
$job_priority = 1;
$user_id = $_SESSION['user']['user_id'];
$insert_task = new TaskManager($db);
@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sgl_archive_request_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] request ===> '.print_r($data, true)."\r\n", FILE_APPEND);
try {

	foreach ($data as $info) {

		$content_id = $info->content_id;
		$archive_group = $info->archive_group;

		$ori_check = $db->queryRow("select * from bc_media
			where content_id='".$content_id."' and media_type='original'
			order by media_id desc");
		if($ori_check['delete_date'] != '') {
			//MSG01020 이미 원본이 삭제된 콘텐츠입니다.
			throw new Exception(_text('MSG01020'));
		}

		$archive_check = $db->queryRow("select * from bc_media
			where content_id='".$content_id."' and media_type='archive'
			order by media_id desc");
		if( !empty($archive_check['media_id']) && $archive_check['delete_date'] == '') {
			//MSG02068 This content already archived
			$is_archive_task_working = $db->queryRow("
				SELECT	TASK_ID, STATUS, PROGRESS
				FROM	BC_TASK
				WHERE	MEDIA_ID=".$archive_check['media_id']."
				AND		TYPE='".ARCHIVE."'
				ORDER BY TASK_ID");
			if($is_archive_task_working['status'] != 'error') {
				throw new Exception(_text('MSG02068'));
			}
		}

		$content_info = $db->queryRow("
			SELECT A.TITLE
				  ,A.BS_CONTENT_ID
				  ,A.GROUP_COUNT
				  ,A.IS_GROUP
				  ,E.ARCHIVE_PRIORITY
			FROM (
				  SELECT CATEGORY_ID, TITLE, CONTENT_ID, BS_CONTENT_ID, GROUP_COUNT, IS_GROUP
				  FROM BC_CONTENT A
				  WHERE CONTENT_ID='".$content_id."'
				 ) A
				 LEFT OUTER JOIN BC_CATEGORY_ENV E
				 ON (A.CATEGORY_ID=E.CATEGORY_ID)
						");
		$bs_content_id = $content_info['bs_content_id'];
		$is_group = $content_info['is_group'];
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
			$task_id = $insert_task->get_task_id();
			@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sgl_archive_request_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] task_id ===> '.$task_id."\r\n", FILE_APPEND);

			$task_info = $db->queryRow("
							SELECT *
							FROM BC_TASK
							WHERE TASK_ID = '$task_id'
						");

			$sgl = new SGL();
			$sgl_filepath = SGL_ROOT.'/'.$task_info['source'];
			//$display_name = pathinfo($sgl_filepath, PATHINFO_BASENAME);
			//priority 정보는 설정된 값에서 가져옴 없으면 default

			$display_name = $content_info['title'];
			$priority = $content_info['archive_priority'];
			$strFileCount = $content_info['group_count'];

			if(empty($priority)) {
				$priority = 5;
			}

			@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sgl_archive_request_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] content_id ===> '.$content_id."\r\n", FILE_APPEND);
			@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sgl_archive_request_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] sgl_filepath ===> '.$sgl_filepath."\r\n", FILE_APPEND);
			@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sgl_archive_request_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] archive_group ===> '.$archive_group."\r\n", FILE_APPEND);
			@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sgl_archive_request_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] display_name ===> '.$display_name."\r\n", FILE_APPEND);
			@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sgl_archive_request_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] priority ===> '.$priority."\r\n", FILE_APPEND);

			if($bs_content_id == SEQUENCE) {
				$return = $sgl->FlashNetSequnceArchive($content_id, $sgl_filepath, $archive_group, $display_name, $priority, $strFileCount);
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
					$return = $sgl->FlashNetGroupArchive($content_id, $sgl_arr_filepath, $archive_group, $display_name, $priority);
				} else {
					$return = $sgl->FlashNetArchive($content_id, $sgl_filepath, $archive_group, $display_name, $priority);
				}
			}

			if(!$return['success']) {
				if($return['msg'] == '') $return['msg'] = 'error';
				// BC_TASK는 error로 업데이트
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
						('$task_id', '".$return['msg']."', '$now', 'error')
				");

				throw new Exception ($return['msg']);
			} else {
				$db->exec("
					UPDATE SGL_ARCHIVE
					SET	SESSION_ID = '".$return['request_id']."'
					WHERE TASK_ID = '$task_id'
				");
				$db->exec("
					UPDATE BC_CONTENT
					SET	UAN = '".$return['uan']."'
					WHERE CONTENT_ID = '$content_id'
				");
			}
		}
	}


	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sgl_archive_request_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] Success ===> true'."\r\n", FILE_APPEND);
	echo json_encode(array(
		'success' => true,
		//'msg' => '아카이브 요청이 완료되었습니다.' MN00056 MSG01009
		'msg' => _text('MN00056').' '._text('MSG01009')
	));
} catch (Exception $e) {
	$msg = $e->getMessage();
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sgl_archive_request_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] ERROR ===> '.$msg."\r\n", FILE_APPEND);
	echo json_encode(array(
		'success' => false,
		'msg' => $msg
	));
}