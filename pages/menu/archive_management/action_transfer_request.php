<?php
/// 아카이브 관련 작업
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/Search.class.php';

try
{
	$action = $_POST['action'];

	switch($action){
		case 'accept':
			//요청 리스트의 상태도 승인으로 바꿔줌.
		$data_ids = json_decode($_POST[ids]); // 일반승인에 대한 처리
		$user_id = $_SESSION['user']['user_id'];
//	if(!empty($data_ids))
//	{
//		put_log("cha_transfer_request_accpet => $data_ids");
//		cha_transfer_request_accpet($data_ids);
//	}



			$arr_req_info = $db->queryAll("select car.* 
				from cha_transfer_request car
				where car.req_no in (".implode($data_ids, ',').")");

			foreach($arr_req_info as $req)
			{
				$req_no = $req['req_no'];
				$auth_query ="
						UPDATE	CHA_TRANSFER_REQUEST
						SET		REQ_STATUS = '3'
							   ,AUTH_USER_ID = '".$user_id."'
							   ,AUTH_TIME = '".date('YmdHis')."'
						WHERE	REQ_NO ='".$req_no."'
				";
				
				$db->exec($auth_query);
			}
		
			transfer_request_accpet();		
		break;

		case 'accept_cancel':
			transfer_accpet_cancel();		
		break;

		default :
			$msg = '조건이 맞지 않습니다';
			throw new Exception($msg);
		break;
	}
}
catch(Exception $e)
{
	echo json_encode(
		array(
		'success'	=> false,
		'msg' => $e->getMessage(),
		'query' => $db->last_query
	));	
}




function transfer_request_accpet(){
	global $db;

	$user_id = $_SESSION['user']['user_id'];

	$data_ids = json_decode($_POST[ids]); // 일반승인에 대한 처리
	put_log("cha_transfer_request_accpet => $data_ids");
//	if(!empty($data_ids))
//	{
//		put_log("cha_transfer_request_accpet => $data_ids");
//		cha_transfer_request_accpet($data_ids);
//	}



	$arr_req_info = $db->queryAll("select car.* 
		from cha_transfer_request car
		where car.req_no in (".implode($data_ids, ',').")");

	foreach($arr_req_info as $req)
	{
		$params         = $req['params'];
		$nps_content_id = $req['nps_content_id'];
		$mtrl_id = $req['mtrl_id'];
		$req_no = $req['req_no'];
		$src_device_id = $req['src_device_id'];
		$tgt_device_id = $req['tgt_device_id'];
		$path = $req['path'];
		$nps_ud_content_id = $req['nps_ud_content_id'];
		if($nps_ud_content_id == UD_NPS_INGEST) {
			$ud_folder = 'NPS_INGEST';
		} else if($nps_ud_content_id == UD_NPS_EMASTER) {
			$ud_folder = 'NPS_EDIT';
		} else if($nps_ud_content_id == UD_NPS_BMASTER) {
			$ud_folder = 'NPS_MASTER';
		}
		$pgm_id = $req['pgm_id'];
		if($src_device_id == 'PVIDEO') {
			$target_ud_system = UD_SYS_NPS_SA;
		} else {
			$target_ud_system = UD_SYS_NPS_SA;
		}
		$root_path = mapUDSystemDIVARoot($target_ud_system);

		if( empty($nps_content_id) ) {
			//nps_content_id가 없다면 광화문 -> 상암동
			if($src_device_id == 'NVIDEO') {
				$channel = 'FTP_F_iCMSNDS_to_SADAS';
			} else if($src_device_id == 'PVIDEO') {
				$channel = 'FTP_F_iCMSPDS_to_SADAS';
			}

			$target_path = $pgm_id.'/'.$ud_folder.'/clip/'.basename($path);

			$options = array(
				'source' => $path,
				'target' => $target_path,
				'cha_req_no' => $req_no
			);

			put_log("cha_transfer_request_accpet TASK REGISTER =>".print_r($options,true));
			$task = new TaskManager($db);
			$task_id = $task->insert_task_query('', $channel, 1, $user_id, null, null, $options);	

					

//			//mode와 data로 SOAP통해 요청.
//			//메타 불러와서
//			$mode = 'GetMtrlInfo';
//			$data = $mtrl_id;
//			require($_SERVER['DOCUMENT_ROOT'].'/interface/app/client/common.php');
//			$datas = $include_return;
//			$datas = json_decode($datas['result']['return'], true);
//			$datas = $datas[0][data];
//			
//			//광화문 to 상암동 등록
//			$mode = 'CMStoNPS_T';
//			$data = $datas;
//			require($_SERVER['DOCUMENT_ROOT'].'/interface/app/client/common.php');
//			$datas = $include_return;
		} else {
			//nps_content_id가 있다면 상암동 -> 광화문
			//NPS에 작업요청
			
			if($params)
			{

				$mode = 'registiCMS';
				$data = $params;
				require($_SERVER['DOCUMENT_ROOT'].'/interface/app/client/common.php');
				$datas = $include_return;

				if($datas['success'] == true || $datas['success'] == "true")
				{
					$task_id = $datas['task_id'];

					if($task_id)
					{
						$update_query = "UPDATE cha_transfer_request 
										SET TRANS_TASK_ID = $task_id 
										WHERE req_no = $req_no";
						
						$db->exec($update_query);
					}
				}
			}
			else 
			{
				$update_query = "UPDATE cha_transfer_request 
										SET req_status='5'
										WHERE req_no = $req_no";
						
						$db->exec($update_query);

				echo json_encode(
					array(
						'success' => false,
						'msg' => '전송정보가 부족하여 실패하였습니다.',
						'data' => $data_ids
					)
				);	 

				return;
			}
		}

		//요청 리스트의 상태도 승인으로 바꿔줌.
		$auth_query ="
				UPDATE	CHA_TRANSFER_REQUEST
				SET		REQ_STATUS = '3'
					   ,AUTH_USER_ID = '".$user_id."'
					   ,AUTH_TIME = '".date('YmdHis')."'
				WHERE	REQ_NO ='".$req_no."'
		";
		
		$db->exec($auth_query);
	}

	echo json_encode(
		array(
			'success' => true,
			'msg' => '성공적으로 적용되었습니다',
			'data' => $data_ids
		)
	);	 	
}

function transfer_accpet_cancel(){
	global $db;
	$data_ids = json_decode($_POST[ids]); 
	if(!empty($data_ids))
	{	
		$user_id = $_SESSION['user']['user_id'];
		foreach($data_ids as $req_no){
			$auth_query ="
					UPDATE	CHA_TRANSFER_REQUEST
					SET		REQ_STATUS = '".CHA_REJT."'
						   ,AUTH_USER_ID = '".$user_id."'
						   ,AUTH_TIME = '".date('YmdHis')."'
					WHERE	REQ_NO ='".$req_no."'
			";
			
			$db->exec($auth_query);			
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


function put_log($text)
{
	file_put_contents(LOG_PATH . '/archive_management_action' . date('Ymd') . '.html',
		date("Y-m-d H:i:s\t") . $text . "\r\n\r\n", FILE_APPEND);
}

?>