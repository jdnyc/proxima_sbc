<?php

use Proxima\core\Session;

require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/archive.class.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/Zodiac.class.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/workflow/lib/task_manager.php');

Session::init();
$user_id = $_SESSION['user']['user_id'];

$content_ids = json_decode($_POST['content_ids']);
$new_ud_content_id = $_POST['target_ud_content_id'];
$restore_pgm_id = $_POST['restore_pgm_id'];
$restore_pgm_nm = $db->escape($_POST['restore_pgm_nm']);
$restore_epsd_id = $_POST['restore_epsd_id'];
$restore_epsd_nm = $db->escape($_POST['restore_epsd_nm']);
$restore_expire_date = $_POST['restore_expire_date'];
$restore_comnt = $db->escape($_POST['restore_request_comnt']);
$now = date('YmdHis');
$now_c = date('H:i');
$req_type = 'restore';

$archive = new Archive();
$task_mgr = new TaskManager($db);

try {
	//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] content_ids ===> '.print_r($content_ids, true)."\r\n", FILE_APPEND);
	foreach($content_ids as $v_content_id) { 
		//그룹 콘텐츠 일 경우 포함
		$query = "
			SELECT	*
			FROM	BC_CONTENT
			WHERE	CONTENT_ID = $v_content_id
			AND		IS_DELETED = 'N'
			AND		STATUS = '2'
			ORDER BY CONTENT_ID
		";
		$v_group_contents = $db->queryAll($query);
		
		$new_parent_content_id = 'null';
		foreach($v_group_contents as $v_group_content) {
			$src_content_id = $v_group_content['content_id'];
			//$src_parent_content_id = $v_group_content['parent_content_id'];
			$src_ud_content_id = $v_group_content['ud_content_id'];
			$src_bs_content_id = $v_group_content['bs_content_id'];
			$src_is_group = $v_group_content['is_group'];

			// if(is_null($src_parent_content_id)){
			// 	$src_parent_content_id = 'null';
			// }

			// RESTORE UD_CONTENT INFO
			if(!empty($new_ud_content_id)){
				$ud_content_info = $db->queryRow("
					SELECT	*
					FROM	BC_UD_CONTENT
					WHERE	UD_CONTENT_ID = $new_ud_content_id
				");
			}else{
				$ud_content_info = $db->queryRow("
					SELECT	*
					FROM	BC_UD_CONTENT
					WHERE	UD_CONTENT_CODE = 'RESTORE'
				");
			}

			$new_ud_content_id = $ud_content_info['ud_content_id'];
			$new_bs_content_id = $ud_content_info['bs_content_id'];

			// $content_info = $db->queryRow("
			// 	SELECT	C.*, COALESCE(R.RESTORE_METHOD, 'M') AS RESTORE_METHOD, R.RESTORE_S_TIME, R.RESTORE_E_TIME
			// 	FROM	BC_CONTENT C
			// 			LEFT OUTER JOIN BC_CATEGORY_ENV_RESTORE R ON R.CATEGORY_ID = C.CATEGORY_ID
			// 	WHERE CONTENT_ID = $src_content_id
			// ");
			
			// 요청 SEQ
			 $req_no = getSequence('SEQ_REQUEST_ARCHIVE'); 
			// // 아카이브(리스토어) 요청 테이블에 추가
			// $r = $db->exec("
			// 	INSERT INTO TB_REQUEST
			// 		(REQ_NO, REQ_TIME, REQ_TYPE, REQ_USER_ID, PGM_ID, PGM_NM, REQ_STATUS, REQ_STATUS_GROUP, REQ_EXPIRE_TIME, REQ_COMMENT, NPS_CONTENT_ID, PARENT_CONTENT_ID, DAS_CONTENT_ID, TRG_CATEGORY_ID, TRG_CATEGORY_TITLE, TRG_UD_CONTENT_ID)
			// 	VALUES
			// 		($req_no, '$now', '$req_type', '$user_id', '$restore_pgm_id', '$restore_pgm_nm', '".ARCHIVE_REQUEST."', '".ARCHIVE_REQUEST."', '$restore_expire_date', '$restore_comnt', $src_content_id, $src_parent_content_id, $src_content_id, $restore_epsd_id, '$restore_epsd_nm', $new_ud_content_id)
            // ");
            
            $r = $db->exec("
                INSERT INTO TB_REQUEST
                    (REQ_NO, REQ_TIME, REQ_TYPE, REQ_USER_ID, REQ_STATUS,  DAS_CONTENT_ID, NPS_CONTENT_ID, REQ_COMMENT)
                VALUES
                    ($req_no, '$now', 'restore', '$user_id', '1', $src_content_id, $src_content_id, '$restore_comnt')
            ");

            //콘텐츠 상태 업데이트
            // $contentService = new \Api\Services\ContentService($app->getContainer());
                
            // $contentStatusData = [
            //     //'archive_status' => 1,
            //     //'archv_requst_at' => date('YmdHis'),
            //     //'archv_rqester' => $user_id
            // ];
            // $contentStatusDto = new \Api\Services\DTOs\ContentStatusDto($contentStatusData);
            // $keys       = array_keys($contentStatusData);
            // $contentStatusDto = $contentStatusDto->only(...$keys);

            // $user = new \Api\Models\User();
            // $user->user_id = Session::getUser('user_id');
            // $contentService->update($content_id,null, $contentStatusDto, null,null, $user);
			
			// 요청 항목에 대해서 NPS에 등록
			//$archive->registRestoreRequestToNPS($req_seq);
			
			// $r = $db->exec("
			// 	INSERT INTO TB_ARCHIVE_REQUEST
			// 		(REQ_NO, NPS_CONTENT_ID, PARENT_CONTENT_ID, DAS_CONTENT_ID, REQ_TYPE, STATUS, REQ_USER_ID, REQ_TIME, REQ_COMMENT, DAS_REQ_NO)
			// 	VALUES
			// 		($req_no, $src_content_id, $src_parent_content_id, $src_content_id, '$req_type', '".ARCHIVE_REQUEST."', '$user_id', '$now', '$restore_comnt', $req_no)
			// ");
			
			//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] req_no ===> '.$req_no."\r\n", FILE_APPEND);

			// 요청된 항목에 대해서 자동 승인 여부 확인
			//$auto_auth_flag = false;
			//$auto_auth_flag = check_auto_auth($content_info['restore_method'] , $content_info['restore_s_time'] , $content_info['restore_e_time'] , $now_c);

			// if($auto_auth_flag) {
			// 	//신규 콘텐츠 등록
			// 	$registnps = new registNPS();
			// 	$new_content_id = getSequence('SEQ_CONTENT_ID');
			// 	$contentinfo = $registnps->insertContent($new_content_id, $restore_epsd_id, $new_bs_content_id, $new_ud_content_id, $content_info['title'], $user_id, $restore_expire_date, 'I', '', '');

			// 	//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] src_content_id ===> '.$src_content_id."\r\n", FILE_APPEND);
			// 	//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] new_content_id ===> '.$new_content_id."\r\n", FILE_APPEND);
			// 	//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] src_ud_content_id ===> '.$src_ud_content_id."\r\n", FILE_APPEND);
			// 	//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] trg_ud_content_id ===> '.$new_ud_content_id."\r\n", FILE_APPEND);

			// 	//메타데이터 복사 to new content
			// 	$task_mgr->changeUsrMeta($src_content_id, $new_content_id, $src_ud_content_id, $new_ud_content_id);
				
			// 	//프로그램 메타데이터 변경
			// 	$usrmetatable = MetaDataClass::getTableName('usr', $new_ud_content_id);
				
			// 	//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] usrmetatable ===> '.$usrmetatable."\r\n", FILE_APPEND);

			// 	$epsd_info = $db->queryRow("
			// 		SELECT	*
			// 		FROM	TB_USR_PGM_EPSD
			// 		WHERE	CATEGORY_ID = $restore_epsd_id
			// 	");

			// 	if(!is_null($epsd_info)){
			// 		if($src_is_group == 'G'){
			// 			$new_parent_content_id = $new_content_id;
			// 			$parent_content_id = 'null';
			// 			$group_count = $new_content_id;
			// 		}else{
			// 			$parent_content_id = $new_parent_content_id;
			// 			$group_count = 'null';
			// 		}

			// 		//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] restore_epsd_id ===> '.$restore_epsd_id."\r\n", FILE_APPEND);
			// 		//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] parent_content_id ===> '.$parent_content_id."\r\n", FILE_APPEND);
			// 		//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] group_count ===> '.$group_count."\r\n", FILE_APPEND);
			// 		//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] src_is_group ===> '.$src_is_group."\r\n", FILE_APPEND);

			// 		$category_full_path	= getCategoryFullPath($restore_epsd_id);
			// 		$v_sql = "
			// 			UPDATE	BC_CONTENT
			// 			SET		CATEGORY_ID = $restore_epsd_id,
			// 					CATEGORY_FULL_PATH = '$category_full_path',
			// 					UD_CONTENT_ID = $new_ud_content_id,
			// 					IS_GROUP = '$src_is_group',
			// 					PARENT_CONTENT_ID = $parent_content_id,
			// 					GROUP_COUNT = $group_count
			// 			WHERE	CONTENT_ID = $new_content_id
			// 		";
			// 		//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] v_sql1 ===> '.$v_sql."\r\n", FILE_APPEND);
			// 		$rtn = $db->exec($v_sql);
					
			// 		//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] restore_pgm_id ===> '.$restore_pgm_id."\r\n", FILE_APPEND);
			// 		//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] restore_pgm_nm ===> '.$restore_pgm_nm."\r\n", FILE_APPEND);
			// 		//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] epsd_no ===> '.$epsd_info['epsd_no']."\r\n", FILE_APPEND);
			// 		//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] epsd_nm ===> '.$epsd_info['epsd_nm']."\r\n", FILE_APPEND);
			// 		//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] src_is_group ===> '.$src_is_group."\r\n", FILE_APPEND);

			// 		$v_epsd_no = $epsd_info['epsd_no'];
			// 		$v_epsd_nm = $epsd_info['epsd_nm'];

			// 		$v_sql = "
			// 			UPDATE	".$usrmetatable."
			// 			SET		USR_PGM_ID = '$restore_pgm_id',
			// 					USR_PGM_NM = '$restore_pgm_nm',
			// 					USR_EPSD_NO = '$v_epsd_no',
			// 					USR_SUB_TITLE = '$v_epsd_nm'
			// 			WHERE	USR_CONTENT_ID = $new_content_id
			// 		";
			// 		//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] v_sql2 ===> '.$v_sql."\r\n", FILE_APPEND);
			// 		$r = $db->exec($v_sql);
			// 		//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] r ===> '.$r."\r\n", FILE_APPEND);
			// 	}
				
			// 	$v_sql = "
			// 		SELECT	*
			// 		FROM	BC_MEDIA
			// 		WHERE	MEDIA_TYPE ='original'
			// 		AND		CONTENT_ID = ".$src_content_id
			// 	;
			// 	//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] v_sql3 ===> '.$v_sql."\r\n", FILE_APPEND);
			// 	$target_media_info = $db->queryRow($v_sql);

			// 	//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] target_media_info ===> '.print_r($target_media_info, true)."\r\n", FILE_APPEND);

			// 	//CPBC 원본 파일명이 한글이 포함되어 콘텐츠 ID로 변경 후 DIVA 아카이브 되었기에 변경처리 필요. skc 2019.09.25
			// 	$arr_path = explode('.', $target_media_info['path']);
			// 	$filepath = $target_media_info['content_id'].'.'.array_pop($arr_path);

			// 	//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] filepath ===> '.$filepath."\r\n", FILE_APPEND);

			// 	// 전송작업 등록
			// 	if($content_info['bs_content_id'] == '506'){
			// 		$channel = 'restore_diva'; //동영상
			// 	}else if($content_info['bs_content_id'] == '515'){
			// 		$channel = 'restore_nas_audio'; //오디오
			// 	}else if($content_info['bs_content_id'] == '518'){
			// 		$channel = 'restore_nas_image'; //이미지
			// 	}else if($content_info['bs_content_id'] == '57057'){
			// 		$channel = 'restore_nas_document'; //문서
			// 	}else{
			// 		$channel = 'restore_diva'; //동영상
			// 	}

			// 	$arr_param_info = array(
			// 		array(
			// 			'target_path' => $filepath
			// 		)
			// 	);
				
			// 	$task_mgr->set_priority(200);
			// 	$task_id = $task_mgr->start_task_workflow($src_content_id, $channel, $user_id, $arr_param_info);
				
			// 	$media_id = $target_media_info['media_id'];
			// 	//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] media_id ===> '.$media_id."\r\n", FILE_APPEND);
			// 	//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] task_id ===> '.$task_id."\r\n", FILE_APPEND);

			// 	//아카이브 테이블 추가
			// 	$archive_seq = getSequence('ARCHIVE_SEQ');
			// 	$r_restore = $db->exec("
			// 		INSERT INTO ARCHIVE
			// 			(ARCHIVE_SEQ, MEDIA_ID, TASK_ID, ARCHIVE_ID, DIVA_CATEGORY, DIVA_GROUP, QOS, DESTINATIONS)
			// 		VALUES
			// 			($archive_seq, $media_id, $task_id, '$src_content_id', 'diva', 'spm_storage', '3', 'nas')
			// 	");

			// 	// 승인 상태 업데이트
			// 	$r = $db->exec("
			// 		UPDATE	TB_REQUEST
			// 		SET		REQ_STATUS = '".ARCHIVE_APPROVE."',
			// 				APPR_TIME = '$now',
			// 				APPR_USER_ID = 'autoAppr',
			// 				APPR_COMMENT = '자동승인',
			// 				NPS_CONTENT_ID = $new_content_id,
			// 				TASK_ID = $task_id
			// 		WHERE	REQ_NO = $req_no
			// 	");

			// 	// 리스토어 승인 상태에 업데이트
			// 	$r = $db->exec("
			// 		UPDATE	TB_ARCHIVE_REQUEST
			// 		SET		STATUS = ".ARCHIVE_APPROVE.",
			// 				APPR_USER_ID = 'autoAppr',
			// 				APPR_TIME = '$now',
			// 				APPR_COMMENT = '자동승인',
			// 				NPS_CONTENT_ID = $new_content_id
			// 		WHERE	DAS_REQ_NO = $req_no
			// 	");

			// 	//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/' .basename(__FILE__). '_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] ARCHIVE_APPROVE ===> '.ARCHIVE_APPROVE."\r\n", FILE_APPEND);
			// } else if(INTERWORK_SMS == 'Y'){
			// 	$zodiac = new Zodiac();
				
			// 	// 콘텐츠관리그룹 사용자 정보 조회
			// 	$trg_users = $db->queryAll("
			// 		SELECT	M.*
			// 		FROM	BC_MEMBER M, BC_MEMBER_GROUP_MEMBER MG
			// 		WHERE	M.MEMBER_ID = MG.MEMBER_ID
			// 		AND		MG.MEMBER_GROUP_ID = ".ARCHIVE_APPROVE_GROUP_ID."
			// 	");

			// 	$msg = '리스토어가 요청되었습니다.확인바랍니다.';
				
			// 	foreach($trg_users as $user){
					
			// 		$phone = trim(str_replace('-', '', $user['phone']));
			// 		$phone = '01073389637';
			// 		$sms_param = array(
			// 			'rcv_phn_id'		=>	$phone,
			// 			'snd_phn_id'		=>	'023115572',
			// 			'mesg'				=>	$msg
			// 		);
					
			// 		$zodiac->putRequestSms($sms_param);
			// 	}
			// }
		}
	}
	
	echo json_encode(array(
		'success' => true,
		'msg' => '요청이 완료되었습니다.'
	));
	
} catch (Exception $e) {
	echo json_encode(array(
			'success' => false,
			'msg' => $e->getMessage(),
			'query' => $db->last_query
	));
}

?>
