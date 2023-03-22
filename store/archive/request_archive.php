<?php

use Proxima\core\Session;
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/archive.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');
Session::init();
try {
	$req_comment = $_REQUEST['req_comment'];
	$das_ud_content_id = $_REQUEST['ud_content_id'];
	$user_id = $_SESSION['user']['user_id'];
	$genre = $_REQUEST['genre'];
	$contents = json_decode($_REQUEST['contents']); 

	$items = array();
	
	foreach($contents as $content) {
		//그룹 콘텐츠 일 경우 포함
		$query = "
			SELECT	*
			FROM	BC_CONTENT
			WHERE	(CONTENT_ID = $content->content_id )
			AND		IS_DELETED = 'N'
			AND		STATUS >= '0'
		";
		$v_group_contents = $db->queryAll($query);
		
		foreach($v_group_contents as $v_group_content) {
			$content_id = $v_group_content['content_id'];
			$ud_content_id = $v_group_content['ud_content_id'];
			$bs_content_id = $v_group_content['bs_content_id'];


			//아카이브 요청 시 proxy_6m, proxy_15m, proxy_wav, proxy_240p, proxy_360p, proxy_720p, thumb_home 삭제 워크플로우 추가. skc 2019.08.16
			// $query = "
			// 	SELECT	DISTINCT CONTENT_ID
			// 	FROM	BC_MEDIA
			// 	WHERE	CONTENT_ID IN (SELECT CONTENT_ID FROM BC_CONTENT WHERE CONTENT_ID = $content_id )
			// 	AND		MEDIA_TYPE NOT IN ('original', 'archive', 'raw', 'thumb', 'proxy', 'album')
			// ";

			// $v_contents = $db->queryAll($query);

			// if(count($v_contents) > 0){
			// 	$channel = 'delete_others_mp4';
				
			// 	$task = new TaskManager($db);

			// 	foreach ($v_contents as $v_content) {
			// 		$task->insert_task_query_outside_data($v_content, $channel, 1, $user_id, null, null, null);
			// 	}
			// }
			
			$usrmetatable = MetaDataClass::getTableName('usr', $ud_content_id);
			$sysmetatable = MetaDataClass::getTableName('sys', $bs_content_id);
			
			$query = "
				SELECT	A.*, UD.UD_SYSTEM_CODE, Q.ERROR_COUNT, Q.IS_CHECKED
				FROM	(SELECT C.*, UM.*, SYS.*
						FROM BC_CONTENT C, ".$usrmetatable." UM, ".$sysmetatable." SYS
						WHERE (C.CONTENT_ID = '$content_id' OR C.PARENT_CONTENT_ID = '$content_id')
						AND C.CONTENT_ID = UM.USR_CONTENT_ID
						AND C.CONTENT_ID = SYS.SYS_CONTENT_ID
						) A
						LEFT OUTER JOIN BC_MEDIA_QUALITY_INFO Q ON Q.CONTENT_ID = A.CONTENT_ID
						LEFT OUTER JOIN BC_UD_SYSTEM UD ON A.CONTENT_ID = UD.CONTENT_ID
			";
			$metas = $db->queryRow($query);

			//아카이브 요청 시 입력된 장르로 메타데이터 업데이트
			// if($metas['usr_genre'] !== $genre) {
			// 	$db->exec("
			// 		UPDATE	".$usrmetatable."
			// 		SET		USR_GENRE = '$genre'
			// 		WHERE	USR_CONTENT_ID = $content_id
			// 	");
			// }

			if(is_null($metas['error_count'])) {
				$qc_status = '';
			} else if($metas['error_count'] == 0 && $metas['is_checked'] == 'Y') {
				$qc_status = 'complete';
			} else if($metas['error_count'] == 0 && $metas['is_checked'] == 'N') {
				$qc_status = 'error';
			} else if($metas['error_count'] > 0 && $metas['is_checked'] == 'N') {
				$qc_status = 'error';
			} else {
				$qc_status = '';
			}
			
			// NPS의 ARCHIVE_REQUEST 테이블에 항목 추가
			$req_no = getSequence('SEQ_REQUEST_ARCHIVE');
			$cur_datetime = date('YmdHis');
			$r = $db->exec("
					INSERT INTO TB_ARCHIVE_REQUEST
						(REQ_NO, NPS_CONTENT_ID,  DAS_CONTENT_ID, REQ_TYPE, REQ_COMMENT, STATUS, REQ_USER_ID, REQ_TIME)
					VALUES
						($req_no, $content_id,   $content_id, 'archive', '$req_comment', '1', '$user_id', '$cur_datetime')
				");

			// NPS의 아카이브 상태 테이블에 값 추가 (2017.10.22 추가)
			// $hasStatus = $db->queryOne("
			// 				SELECT	COUNT(CONTENT_ID)
			// 				FROM	TB_CONT_STATUS
			// 				WHERE	CONTENT_ID = $content_id
			// 			");
			// if($hasStatus > 0) {
				// $r = $db->exec("
				// 		UPDATE	BC_CONTENT_STATUS
				// 		SET		ARCHIVE_STATUS = 1
				// 		WHERE	CONTENT_ID = $content_id
				// 	");
			// } else {
			// 	$r = $db->exec("
			// 			INSERT INTO TB_CONT_STATUS
			// 				(CONTENT_ID, ARCHIVE_STATUS, ARCHIVE_REQ_NO)
			// 			VALUES
			// 				($content_id, 1, $req_no)
			// 		");
			// }

			//DAS에 있는 TB_REQUEST 테이블을 NPS에 생성해서 그대로 사용하도록함
			//아카이브/리스토어 요청 관리 데이터 추가. skc 2019.09.21
			// $genre_info = $db->queryRow("
			// 				SELECT	*
			// 				FROM	TB_GENRE_COMBO
			// 				WHERE	GENRE_ID = $genre
			// 			");
			
			// $trg_category_id =$genre_info['category_id'];
			// $trg_category_title = $genre_info['genre_title'];
			$r = $db->exec("
				INSERT INTO TB_REQUEST
					(REQ_NO, REQ_TIME, REQ_TYPE, REQ_USER_ID, REQ_STATUS,  DAS_CONTENT_ID, NPS_CONTENT_ID, REQ_COMMENT, QUALITYCHECK)
				VALUES
					($req_no, '$cur_datetime', 'archive', '$user_id', '1', $content_id, $content_id, '$req_comment', '$qc_status')
            ");

            //콘텐츠 상태 업데이트
            $contentService = new \Api\Services\ContentService($app->getContainer());
                
            $contentStatusData = [
                'archive_status' => 0,
                'archv_requst_at' => date('YmdHis'),
                'archv_rqester' => $user_id
            ];
            $contentStatusDto = new \Api\Services\DTOs\ContentStatusDto($contentStatusData);
            $keys       = array_keys($contentStatusData);
            $contentStatusDto = $contentStatusDto->only(...$keys);

            $user = new \Api\Models\User();
            $user->user_id = Session::getUser('user_id');
            $contentService->update($content_id,null, $contentStatusDto, null,null, $user);
		}
	}

	echo json_encode(array(
		'success' => true,
		'msg' => '요청이 완료되었습니다.'
	));
} catch (Exception $e) {
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}

function checkArchived($content_list) {
	$result = array();

	if (is_string($content_list)) {
		$_content_list = json_decode($content_list, true);
	}

	if (is_array($_content_list)) {
		foreach ($_content_list as $content) {
			if (Archive::is_archived($content['content_id']) !== false) {
				array_push($result, $content['title']);
			}
		}
	} else {
		throw new Exception('아카이브 요청하시 콘텐츠 목록 값에 오류가 있습니다('.$content_list.')');
	}

	return $result;
}

?>