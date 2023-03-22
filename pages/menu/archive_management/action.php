<?php
/// 아카이브 관련 작업
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/archive.class.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/workflow/lib/task_manager.php');
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/Search.class.php';

try {
	$action = $_POST['action'];
	switch ($action) {
		case 'accept':
			archive_accpet();
			break;

		case 'accept_cancel':
			archive_accpet_cancel();
			break;

		case 'delete':
			archive_delete();
			break;

			// 사용자 tape 삭제요청
		case 'delete_request':
			archive_delete_request();
			break;

			// 사용자 archive_storage_delete_request 삭제요청
		case 'archive_storage_delete_request':
			archive_storage_delete_request();
			break;

		case 'archive_storage_delete':
			archive_storage_delete();
			break;

		case 'archive_storage_delete_retry':
			archive_storage_delete_retry();
			break;

		case 'change_udsystem':
			change_to_ud_contents();
			break;



		default:
			$msg = '조건이 맞지 않습니다';
			throw new Exception($msg);
			break;
	}
} catch (Exception $e) {
	echo json_encode(
		array(
			'success'	=> false,
			'msg' => $e->getMessage(),
			'query' => $db->last_query
		)
	);
}

/*TAPE 삭제 */
function archive_delete_request()
{
	global $db;

	$data_ids = json_decode($_POST[ids], true); // 일반승인에 대한 처리
	$comment  = $_POST['comment'];

	foreach ($data_ids as $data) {
		$req_check_query = "
			SELECT count(*)
			FROM   CHA_ARCHIVE_REQUEST 
			WHERE  ARC_TYPE = 'restore'
				   AND STATUS in (1,2,3,5,14,15,16) 
				   AND CONTENT_ID =" . $data;
		$req_count = $db->queryOne($req_check_query);

		if ($req_count > 0) {
			echo json_encode(
				array(
					'success' => false,
					'msg' => '요청한 콘텐츠에 대한 리스토어 요청/작업이 있습니다.',
					'data' => $data
				)
			);
		} else {
			$query = "
				SELECT ARCHIVE_STATUS 
				FROM   BC_CONTENT 
				WHERE  CONTENT_ID  = " . $data;

			$archive_status = $db->queryOne($query);

			if ($archive_status == ARCHIVE_REQUEST_DELETE) {
				echo json_encode(
					array(
						'success' => false,
						'msg' => '이미 TAPE 삭제 요청한 콘텐츠입니다.',
						'data' => $data
					)
				);
			} else if ($archive_status == ARCHIVE_COMPLETE) {
				//이미 요청된 
				// 파일에 대한 정보를 변경해준다.
				$query = "UPDATE BC_CONTENT 
								 SET ARCHIVE_STATUS = '" . ARCHIVE_REQUEST_DELETE . "'
							   WHERE CONTENT_ID  = " . $data;
				$r = $db->exec($query);
				$to_date = date('YmdHis');
				$request_id = $db->queryOne("select nextval('SEQ_DELETE_REQUEST_ID')");
				$request_comnt = $db->escape($comment);
				$type = 'T'; //R storage  : R  / TAPE : T
				$user_id = $_SESSION['user']['user_id'];
				$query = "
								INSERT 
								INTO		CHA_DELETE_REQUEST
											   (REQUEST_DATE
												,REQUEST_USER_ID
												,REQUEST_COMNT
												,MEDIA_ID
												,CONTENT_ID
												,REQUEST_TYPE
												,REQUEST_ID)
								VALUES
								(
									 '$to_date'
									,'$user_id'
									,'$request_comnt'
									,(select media_id from bc_media where content_id = $data and media_type = 'archive')
									,{$data}
									,'$type'
									,{$request_id}
								)";
				$r = $db->exec($query);

				if ($r) {
					echo json_encode(
						array(
							'success' => true,
							'msg' => '삭제 요청이 완료되었습니다.',
							'data' => $data
						)
					);
				} else {
					echo json_encode(
						array(
							'success' => false,
							'msg' => '삭제 요청이 실패하였습니다.',
							'data' => $data
						)
					);
				}
			} else {
				echo json_encode(
					array(
						'success' => false,
						'msg' => '요청한 콘텐츠 상태가 아카이브 할 수 없는 상태입니다.',
						'data' => $data
					)
				);
			}
		}
	}
}

/* ARCHIVE- R 삭제*/
//아카이브, 리스토어 요청-승인/반려-작업완료 상태. CIS / NPS 로 구분
//CHA_ARCHIVE_REQUEST 테이블 상태값
//1:요청,2:승인,3:니어라인까지 작업완료,4:작업완료
//      12:반려,13:니어라인 작업오류,14:작업오류

//iCMS값. 01 대기, 02 반려, 03 승인완료, 04 작업완료, 05 작업중, 06 작업실패
function archive_storage_delete_request()
{

	global $db;

	$data_ids = json_decode($_POST[ids], true); // 일반승인에 대한 처리

	$comment  = $_POST['comment'];

	foreach ($data_ids as $data) {
		//요청 1 , 승인 3 , 작업중 5 인 것들은 X 
		$req_check_query = "
			SELECT count(*)
			FROM   CHA_ARCHIVE_REQUEST 
			WHERE  ARC_TYPE = 'restore'
				   AND STATUS in (1,3,5) 
				   AND CONTENT_ID =" . $data;

		$req_count = $db->queryOne($req_check_query);

		if ($req_count > 0) {
			echo json_encode(
				array(
					'success' => false,
					'msg' => '요청한 콘텐츠에 대한 리스토어 요청/작업이 있습니다.',
					'data' => $data_ids
				)
			);
		} else {
			$media_status_check_query = "
				 SELECT STATUS,
						FLAG,
						MEDIA_ID
				 FROM   BC_MEDIA 
				 WHERE  CONTENT_ID = " . $data . "
				        AND MEDIA_TYPE = 'original'";

			$check_data  = $db->queryRow($media_status_check_query);
			//print_r($check_data 
			$status = $check_data['status'];
			$flag   = trim($check_data['flag']);
			$meida_id = $check_data['media_id'];

			if ($status == 1) {
				//이미 삭제된 데이터
				echo json_encode(
					array(
						'success' => false,
						'msg' => "이미 삭제된 콘텐츠입니다.",
						'data' => $data
					)
				);
			} else if ($status == 0) {
				if (empty($flag) || $flag == '0') {
					//이미 요청된 
					// 파일에 대한 정보를 변경해준다.
					$query = "UPDATE BC_MEDIA 
								 SET FLAG = '" . DEL_MEDIA_REQUEST_FLAG . "'
							   WHERE MEDIA_ID = (
													SELECT media_id 
													FROM BC_MEDIA 
													WHERE CONTENT_ID = $data
														  AND MEDIA_TYPE = 'original'
														  AND STATUS = 0
												 )";
					$r = $db->exec($query);
					$to_date = date('YmdHis');
					$request_id = getSequence('SEQ_DELETE_REQUEST_ID');
					$request_comnt = $db->escape($comment);
					$type = 'R'; //R storage  : R  / TAPE : T
					$user_id = $_SESSION['user']['user_id'];
					$query = "
								INSERT 
								INTO		CHA_DELETE_REQUEST
											   (REQUEST_DATE
												,REQUEST_USER_ID
												,REQUEST_COMNT
												,MEDIA_ID
												,CONTENT_ID
												,REQUEST_TYPE
												,REQUEST_ID)
								VALUES
								(
									 '$to_date'
									,'$user_id'
									,'$request_comnt'
									,{$meida_id}
									,{$data}
									,'$type'
									,{$request_id}
								)";
					$r = $db->exec($query);

					if ($r) {
						echo json_encode(
							array(
								'success' => true,
								'msg' => '삭제 요청이 완료되었습니다.',
								'data' => $data
							)
						);
					} else {
						echo json_encode(
							array(
								'success' => false,
								'msg' => '삭제 요청이 실패하였습니다.',
								'data' => $data
							)
						);
					}
				} else {
					if ($flag == DEL_MEDIA_REQUEST_FLAG) {
						$msg = "이미 삭제 요청된 콘텐츠입니다.";
					} else if ($flag == DEL_MEDIA_COMPLETE_FLAG) {
						$msg = "이미 삭제된 콘텐츠입니다.";
					}

					echo json_encode(
						array(
							'success' => false,
							'msg' => $msg,
							'data' => $data
						)
					);
				}
			}
		}
	}
}


function archive_accpet()
{
	global $db;
	$data_ids = json_decode($_POST[ids], true); // 일반승인에 대한 처리
	$user_id = $_SESSION['user']['user_id'];
	if (empty($user_id)) $user_id = 'admin';

	foreach ($data_ids as $data) {
		$content_id = $data['content_id'];
		$ud_system = $data['ud_system'];
		$mtrl_id = $data['mtrl_id'];

		$query = "
				  SELECT CCI.GROUP_YN,
						 (SELECT COUNT(*) FROM archive_group_info where content_id = c.content_id) as group_count
				  FROM   BC_CONTENT C,
				         CONTENT_CODE_INFO CCI
				  WHERE  c.CONTENT_ID =" . $content_id . "   
				         AND c.CONTENT_ID = cci.CONTENT_ID
						 AND c.ARCHIVE_STATUS is null
				 ";

		put_log("archive_accpet => " . $query);

		$req_info = $db->queryRow($query);
		/*
		$req_info = $db->queryRow("
			select cci.group_yn, car.req_no
			from bc_content c, content_code_info cci
			where c.content_id=cci.content_id
			  and c.content_id=car.content_id
			  and c.content_id='".$content_id."'
			  and c.is_deleted!='Y'
			  and lower(car.arc_type)='archive'
			 -- and car.status='".CHA_FIN_NL."'
			order by req_no desc");*/
		if (empty($req_info)) throw new Exception('승인 이거나 진행중인 콘텐츠 입니다.(content_id:' . $content_id . ')');

		put_log("archive_accpet req_info => " . print_r($req_info, true));

		$req_no = $req_info['req_no'];
		$group_count = $req_info['group_count'];

		$group_yn = $req_info['group_yn'];

		if ($group_yn == '') $group_yn = 'N';

		if ($group_count && $group_count > 0 && $group_yn == 'N') {
			$uquery = "
				UPDATE CONTENT_CODE_INFO
				SET GROUP_YN = 'Y'
				WHERE CONTENT_ID = $content_id
			";

			$db->exec($uquery);

			$group_yn = 'Y';
		}


		put_log("archive_accpet group_yn => " . $group_yn);

		$job_priority = 1;
		switch ($group_yn) {
			case 'Y':
				$channel = 'FTP_D_GHR_to_SADAS';
				if (!empty($mtrl_id)) {
					$mode = 'ArchiveMaterialInfo';
					$data = array("mtrl_id" => $mtrl_id);
					require($_SERVER['DOCUMENT_ROOT'] . '/interface/app/client/common.php');
				}
				break;

			case 'N':
				$channel = 'FTP_F_GHR_to_SADAS';
				break;
				/*
			case UD_SYS_PDS_GH.'N':
			case UD_SYS_NPS_GH.'N':
				$channel = 'FTP_F_GHR_to_SADAS';
			break;
			case UD_SYS_PDS_GH.'Y':
			case UD_SYS_NPS_GH.'Y':
				$channel = 'FTP_F_GHR_to_SADAS';
			break;
			case UD_SYS_NDS_GH.'N':
				$channel = 'FTP_F_GHR_to_SADAS';
			break;
			case UD_SYS_NDS_GH.'Y':
				$channel = 'FTP_D_GHR_to_SADAS';
			break;
			case UD_SYS_PDS_GH.'N':
				$channel = 'FTP_F_GHPDS_to_SADAS';
			break;
			case UD_SYS_PDS_GH.'Y':
				$channel = 'FTP_D_GHPDS_to_SADAS';
			break;
			
			case UD_SYS_R_GH.'N':
				$channel = 'FTP_F_GHR_to_SADAS';
			break;
			
			case UD_SYS_R_GH.'Y':
				$channel = 'FTP_D_GHR_to_SADAS';
			break;
			*/
		}


		put_log("archive_accpet => group_YN : " . $group_yn . " ,CHANNEL : " . $channel . ", CONTENT_ID :" . $content_id);

		if (empty($channel)) {
			echo json_encode(
				array(
					'success' => false,
					'msg' => '아카이브에 대한 작업 정보 부족으로 실패하였습니다.',
					'data' => $data_ids
				)
			);
			return;
		}

		$task = new TaskManager($db);
		$task_id = $task->insert_task_query($content_id, $channel, $job_priority, $user_id);

		//task테이블에 req_no 업데이트
		$task_update_q = "update bc_task set
				cha_req_no='" . $req_no . "'
			where task_id='" . $task_id . "'";
		$db->exec($task_update_q);

		//아카이브 상태값을 승인표시
		$stt_update_q = "update bc_content set archive_status='" . ARCHIVE_ACCEPT . "'
			where content_id='" . $content_id . "'";
		$db->exec($stt_update_q);

		$arc_user_id = $_SESSION['user']['user_id'] ? $_SESSION['user']['user_id'] : 'temp';
		$action = 'archive_accept';
		if ($arc_user_id == 'temp') {
			insertLog($action, $arc_user_id, $content_id, 'Session Error !! temp user!! TAPE Archive Accept!');
		} else {
			insertLog($action, $arc_user_id, $content_id, 'TAPE Archive Accept!');
		}
	}

	echo json_encode(
		array(
			'success' => true,
			'msg' => '성공적으로 적용되었습니다',
			'data' => $data_ids
		)
	);
}

function archive_accpet_cancel()
{
	global $db;
	$data_ids = json_decode($_POST[ids]);
	//승인취소라면, 우리 MAM에서 삭제한다는 의미.
	if (!empty($data_ids)) {
		foreach ($data_ids as $content_id) {
			$update_q = "update bc_content set
					is_deleted='Y'
				where content_id='" . $content_id . "'";
			put_log($update_q);
			//$db->exec($update_q);

			$cci_info = $db->queryRow("select * from content_code_info
				where content_id='" . $content_id . "'");

			//doDeleteArchiveStatus  (파라메타 : mtrl_id)
			//승인취소가 된 자료라고 DUNET에 알려준다.
			//$url = CHA_DUNET_ROOT.'/arc/doDeleteArchiveStatus.dunet';


			//광화문 to 상암동 등록
			$mode = 'DeleteArchiveStatus';
			$data = '<?xml version="1.0" encoding="UTF-8"?><UserMeta>
						<mtrl_id>' . $cci_info['mtrl_id'] . '</mtrl_id>
					</UserMeta>';
			require($_SERVER['DOCUMENT_ROOT'] . '/interface/app/client/common.php');
			$datas = $include_return;
		}
	}

	echo json_encode(
		array(
			'success' => true,
			'msg' => '성공적으로 적용되었습니다',
			'data' => $data_ids
		)
	);
}

function archive_delete()
{
	global $db;
	$data_ids = json_decode($_POST[ids]); // 일반승인에 대한 처리
	$data_expire_ids = json_decode($_POST[ids_expire]); // 만료것에 대한 처리 아이디

	$request_ids  = json_decode($_POST['request_ids']);
	$user_id      = $_SESSION['user']['user_id'];
	$auth_commnet = $_POST['auth_comment'];
	$now          = date('YmdHis');

	if (!empty($request_ids)) {
		$auth_commnet = $db->escape($auth_commnet);
		$query = "
						UPDATE CHA_DELETE_REQUEST
						SET    AUTH_USER_ID = '$user_id'
							  ,AUTH_DATE    = '$now'
							  ,AUTH_COMNT   = '$auth_commnet'
						WHERE  REQUEST_TYPE = 'T'
						       AND CONTENT_ID   in(" . implode(',', $request_ids) . ")";
		$db->exec($query);

		put_log("cha_diva_archive_del => " . implode(',', $request_ids));
		cha_diva_archive_del($request_ids);

		$query = "UPDATE BC_CONTENT 
					 SET ARCHIVE_STATUS = '" . ARCHIVE_REQUEST_DELETE_ACCEPT . "'
				   WHERE CONTENT_ID in (" . implode(',', $request_ids) . ")";
		$db->exec($query);
	}

	if (!empty($data_ids)) {
		put_log("cha_diva_archive_del => " . implode(',', $data_ids));
		cha_diva_archive_del($data_ids);

		$query = "UPDATE BC_CONTENT 
					 SET ARCHIVE_STATUS = '" . ARCHIVE_REQUEST_DELETE_ACCEPT . "'
				   WHERE CONTENT_ID in (" . implode(',', $data_ids) . ")";
		$db->exec($query);
	}



	echo json_encode(
		array(
			'success' => true,
			'msg' => '성공적으로 적용되었습니다',
			'data' => implode(',', $data_ids)
		)
	);
}

//Archive스토리지에서 삭제하는 함수.
//Tape화 된 자료만 해당한다.
function archive_storage_delete()
{
	global $db;
	$user_id      = $_SESSION['user']['user_id'];
	$data_ids     = json_decode($_POST[ids]);
	$request_ids  = json_decode($_POST['request_ids']);
	$auth_commnet = $_POST['auth_comment'];
	$now          = date('YmdHis');


	if (!empty($request_ids)) {
		$auth_commnet = $db->escape($auth_commnet);
		$query = "
						UPDATE CHA_DELETE_REQUEST
						SET    AUTH_USER_ID = '$user_id'
							  ,AUTH_DATE    = '$now'
							  ,AUTH_COMNT   = '$auth_commnet'
						WHERE  CONTENT_ID   in(" . implode(',', $request_ids) . ")";
		$db->exec($query);


		foreach ($request_ids as $content_id) {

			//미디어 정보에 삭제요청상태라고 업데이트.
			//DEL_MEDIA_REQUEST_FLAG  => DEL_MEDIA_ADMIN_APPROVE_FLAG 로변경 관리자가 승인한 항목
			chA_media_del_update($content_id, 'original', DEL_MEDIA_ADMIN_APPROVE_FLAG);
			put_log("chA_media_del_update => $content_id, 'original', DEL_MEDIA_ADMIN_APPROVE_FLAG");

			$content_info = $db->queryRow("select * from content_code_info where content_id='" . $content_id . "'");
			$group_yn = $content_info['group_yn'];
			if ($group_yn == 'Y') {
				$channel = 'FTP_D_GHR_Delete';
			} else {
				$channel = 'FTP_F_GHR_Delete';
			}

			$job_priority = 1;
			if (empty($user_id)) $user_id = 'admin';
			$task = new TaskManager($db);
			$task->insert_task_query($content_id, $channel, $job_priority, $user_id);
		}
	}

	if (!empty($data_ids)) {
		foreach ($data_ids as $content_id) {

			//미디어 정보에 삭제요청상태라고 업데이트.
			//DEL_MEDIA_REQUEST_FLAG  => DEL_MEDIA_ADMIN_APPROVE_FLAG 로변경 관리자가 승인한 항목
			chA_media_del_update($content_id, 'original', DEL_MEDIA_ADMIN_APPROVE_FLAG);
			put_log("chA_media_del_update => $content_id, 'original', DEL_MEDIA_ADMIN_APPROVE_FLAG");

			$content_info = $db->queryRow("select * from content_code_info where content_id='" . $content_id . "'");
			$group_yn = $content_info['group_yn'];
			if ($group_yn == 'Y') {
				$channel = 'FTP_D_GHR_Delete';
			} else {
				$channel = 'FTP_F_GHR_Delete';
			}

			$job_priority = 1;
			if (empty($user_id)) $user_id = 'admin';
			$task = new TaskManager($db);
			$task->insert_task_query($content_id, $channel, $job_priority, $user_id);
		}
	}

	echo json_encode(
		array(
			'success' => true,
			'msg' => '성공적으로 적용되었습니다',
			'data' => $data_ids
		)
	);
}


//Archive스토리지에서 삭제 재시도 함수.
function archive_storage_delete_retry()
{
	global $db;
	$data_ids = json_decode($_POST[ids]);

	$creation_date = date("YmdHis");
	$reason = '사용자에 의한 작업 del_retry';

	if (!empty($data_ids)) {
		foreach ($data_ids as $content_id) {
			$task_info = $db->queryRow("select * 
				from bc_task
				where media_id in (select media_id 
									from bc_media
									where content_id='" . $content_id . "'
									  and media_type='original')
				  and type='100'
				order by task_id desc");
			$task_id = $task_info['task_id'];
			if (!empty($task_info)) {
				$rtn = $db->exec("update bc_task set status='queue' where task_id=$task_id ");
				$rtn = $db->exec("insert into bc_task_log (TASK_ID,DESCRIPTION,CREATION_DATE) values('$task_id','$reason' ,'$creation_date' )");
			}
		}
	}

	echo json_encode(
		array(
			'success' => true,
			'msg' => '성공적으로 적용되었습니다',
			'data' => $data_ids
		)
	);
}



/*
	컨텐츠 메타 밸류 변경
*/


function change_to_ud_contents()
{
	global $db;
	$data_ids = json_decode($_POST[ids]);

	if (!empty($data_ids)) {
		foreach ($data_ids as $content_id) {
			file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/Change_to_ud_contents_' . date('Y-m-d') . '.log', $_SERVER['REMOTE_ADDR'] . "\t[" . date('Y-m-d H:i:s') . ']  콘텐츠 UD_CONTENT 변경하기 CONTENT_ID  : ' . $content_id . "\r\n", FILE_APPEND);


			$query = "
						SELECT *
						FROM   BC_USR_META_FIELD
						WHERE  UD_CONTENT_ID in(" . UD_NDS . "," . UD_PDS . ")
			";

			$r = $db->queryAll($query);

			$pds_metamap = array();
			$nds_metamap = array();

			foreach ($r as $d) {
				$ud_content_id       = $d['ud_content_id'];
				$usr_meta_field_id   = $d['usr_meta_field_id'];
				$usr_meta_field_code = $d['usr_meta_field_code'];

				if ($ud_content_id == UD_PDS) {
					$pds_metamap[$usr_meta_field_code] = $usr_meta_field_id;
				} else if ($ud_content_id == UD_NDS) {
					$nds_metamap[$usr_meta_field_code] = $usr_meta_field_id;
				}
			}

			$v_query = "
						SELECT b.*,
							   (
									select usr_meta_field_code 
									from bc_usr_meta_field 
									where usr_meta_field_id = b.usr_meta_field_id
								) as code 
						FROM   BC_USR_META_VALUE b
						WHERE b.CONTENT_ID = {$content_id}
			";

			$rs = $db->queryAll($v_query);

			file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/Change_to_ud_contents_' . date('Y-m-d') . '.log', $_SERVER['REMOTE_ADDR'] . "\t[" . date('Y-m-d H:i:s') . ']  콘텐츠 UD_CONTENT 메타 정보 변경하기 CONTENT_ID  : ' . $content_id . "\r\n", FILE_APPEND);

			foreach ($rs as $d) {
				$content_id    = $d['content_id'];
				$ud_content_id = $d['ud_content_id'];
				$filed_id      = $d['usr_meta_field_id'];
				$filed_cd      = $d['code'];

				if ($ud_content_id == UD_PDS) {
					$c_ud_content_id  =  UD_NDS;
					$c_field_id       =  $nds_metamap[$filed_cd];
				} else if ($ud_content_id == UD_NDS) {
					$c_ud_content_id  =  UD_PDS;
					$c_field_id       =  $pds_metamap[$filed_cd];
				}


				$q = "
					UPDATE BC_USR_META_VALUE
					SET    UD_CONTENT_ID = $c_ud_content_id
						  ,USR_META_FIELD_ID = $c_field_id
					WHERE  CONTENT_ID = $content_id
						   AND USR_META_FIELD_ID = $filed_id
				";

				file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/Change_to_ud_contents_' . date('Y-m-d') . '.log', $_SERVER['REMOTE_ADDR'] . "\t[" . date('Y-m-d H:i:s') . ']     QUERY : ' . $q . "\r\n", FILE_APPEND);

				$db->exec($q);

				file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/Change_to_ud_contents_' . date('Y-m-d') . '.log', $_SERVER['REMOTE_ADDR'] . "\t[" . date('Y-m-d H:i:s') . ']    입력완료! \r\n', FILE_APPEND);
			}


			if ($ud_content_id == UD_PDS) {
				$c_ud_content_id  =  UD_NDS;
				$c_category       =  1;
				$c_category_full_path = '/0/1';
			} else if ($ud_content_id == UD_NDS) {
				$c_ud_content_id  =  UD_PDS;
				$c_category       =  2;
				$c_category_full_path = '/0/2';
			}


			$conetnt_c_query = "
				UPDATE BC_CONTENT
				SET    UD_CONTENT_ID = $c_ud_content_id
					  ,CATEGORY_ID   = '" . $c_category . "'
					  ,CATEGORY_FULL_PATH = '" . $c_category_full_path . "'
				WHERE  CONTENT_ID = $content_id
			";

			file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/Change_to_ud_contents_' . date('Y-m-d') . '.log', $_SERVER['REMOTE_ADDR'] . "\t[" . date('Y-m-d H:i:s') . ']     CONTENT 변경 쿼리 : ' . $conetnt_c_query, FILE_APPEND);

			$db->exec($conetnt_c_query);
		}

		//검색엔진 삭제 하고 다시 등록 해줘야함.

		$s = new Search();
		$xml = $s->delete($ud_content_id, $content_id);
		$xml = $s->update('insert', $c_ud_content_id, $content_id);

		echo json_encode(
			array(
				'success' => true,
				'msg' => '정상적으로 처리되었습니다.'
			)
		);
	} else {

		echo json_encode(
			array(
				'success' => false,
				'msg' => '요청한 데이터가 없습니다.'
			)
		);
	}
}


function put_log($text)
{
	file_put_contents(
		LOG_PATH . '/archive_management_action' . date('Ymd') . '.html',
		date("Y-m-d H:i:s\t") . $text . "\r\n\r\n",
		FILE_APPEND
	);
}
