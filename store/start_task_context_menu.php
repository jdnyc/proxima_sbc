<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/SNS.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_option_parser.php');

$user_id = $_SESSION['user']['user_id'];
$content_ids = $_REQUEST['content_ids'];
$channel = $_REQUEST['channel'];
$arr_content_ids = json_decode($content_ids, true);
try {
	if(substr($channel,0, 3) == 'sns'){
		$sns_objects = json_decode($_REQUEST['sns'], true);
		$count_exsits_content = 0;
		foreach($sns_objects as $sns_object) {
			
			$content_id = $sns_object['id'];
			$sns_title = $sns_object['title'];
			$sns_content = $sns_object['content'];
			$task_user_id = $user_id;
			
			$task_mgr = new TaskManager($db);
			$arr_param_info = null;
			$options = null;
			
			$query = "SELECT * FROM BC_TASK_WORKFLOW WHERE REGISTER = '$channel' AND TYPE != 'p'";
			$task_workflow_info = $db->queryRow($query);

			$task_workflow_id = $task_workflow_info['task_workflow_id'];
			$wf_bs_content_id = $task_workflow_info['bs_content_id'];
			
			$bc_content_info = $db->queryRow("select * from bc_content where content_id=".$content_id);

			$ct_bs_content_id = $bc_content_info['bs_content_id'];
			
			//throw new Exception('task_workflow에 등록되지 않은 인제스트 채널입니다.(채널:'.$channel.')');
			if(empty($task_workflow_id)) {
				throw new Exception('('._text('MN01071').':'.$channel.')'._text('MSG02083'));//Workflow does not exist
			}
			
			if($wf_bs_content_id > 0) {
				$query = "SELECT * FROM BC_TASK_WORKFLOW
				WHERE REGISTER = '$channel' AND TYPE != 'p' AND BS_CONTENT_ID = ".$ct_bs_content_id;
				$task_workflow_info = $db->queryRow($query);
				$task_workflow_id = $task_workflow_info['task_workflow_id'];
				$wf_bs_content_id = $task_workflow_info['bs_content_id'];
				if(empty($task_workflow_info) || $wf_bs_content_id != $ct_bs_content_id) {
					throw new Exception('('._text('MN01071').':'.$channel.')'._text('MSG02084'));//Content type is wrong between workflow and content.
				}
			}
			
			$query = "
			select (SELECT PATH FROM BC_STORAGE WHERE STORAGE_ID = r.SOURCE_PATH_ID) SRC_RULE_PATH,
			(SELECT PATH FROM BC_STORAGE WHERE STORAGE_ID = r.TARGET_PATH_ID) TRG_RULE_PATH,
			r.*
			from 	bc_task_workflow_rule r
			where 	task_workflow_id = $task_workflow_id
			";
			$get_jobs = $db->queryRow($query);

			//AD OD 워크플로우 구분 2013-01-18 이성용
			//if( $this->checkStorageGroup($content_id, $get_jobs) ) return false;
			//$getStorageInfo = $this->getStorageInfo($content_id, $get_jobs);
			$query = "
			select tr.*, tt.type
			from 	bc_task_rule tr, bc_task_type tt
			where 	tr.task_type_id = tt.task_type_id
			and 	tr.task_rule_id = {$get_jobs['task_rule_id']}";
			$task_rule = $db->queryRow($query);
			
			if(empty($get_jobs['source_path_id'])) {
				$source_path_id = 'null';
			} else {
				$source_path_id = $get_jobs['source_path_id'];
			}
			$query = "select s.type , s.path , s.login_id, s.login_pw from bc_storage s where s.storage_id={$source_path_id}";
			$source_login_info = $db->queryRow($query);
		
			if(empty($get_jobs['target_path_id'])) {
				$target_path_id = 'null';
			} else {
				$target_path_id = $get_jobs['target_path_id'];
			}
			$query = "select s.type , s.path , s.login_id, s.login_pw from bc_storage s where s.storage_id={$target_path_id}";
			$target_login_info = $db->queryRow($query);

			$src_storage_id = $get_jobs['source_path_id'];
			$trg_storage_id = $get_jobs['target_path_id'];
			
			//		if( !empty($getStorageInfo) ){
			//			$src_storage_id = $getStorageInfo['source_path'];
			//			$trg_storage_id = $getStorageInfo['target_path'];
			//			$source_login_info = $this->db->queryRow("select * from bc_storage where storage_id='$src_storage_id' ");
			//			$target_login_info = $this->db->queryRow("select * from bc_storage where storage_id='$trg_storage_id' ");
			//		}
			
			//소스 옵션 분석
			$source_opt_parser = new TaskOptionParser($task_mgr, 'source', $channel, $arr_param_info, $task_rule, $get_jobs );
			
			if ($content_id != '') {
				$source_opt_parser->parseTaskOption($content_id, $task_rule['source_opt']);
			}
			
			$source_opt_arr = $source_opt_parser->getTaskOption();
			if($source_opt_arr['media_type'] == 'xml') {
				$storage_info = $this->db->queryRow("
					SELECT	*
					FROM	VIEW_UD_STORAGE
					WHERE	UD_CONTENT_ID = ".$bc_content_info['ud_content_id']."
					AND		US_TYPE='lowres'
				");
			
				$fullpath = $db->escape($source_opt_arr['full_path']);
				$meta_xml = $task_mgr->make_xml($content_id, $task_user_id, $channel, basename($fullpath));
				$filename = $storage_info['path'].'/'.$fullpath;
				$filename = iconv('utf-8','cp949',$filename);
				file_put_contents($filename, $meta_xml);
				$filesize = @filesize($filename);
				if(empty($filesize)) $filesize = 0;
			
				$db->exec("
					UPDATE	BC_MEDIA
					SET		FILESIZE=".$filesize."
							,CREATED_DATE='".date('YmdHis')."'
							,REG_TYPE='".$channel."'
					WHERE	MEDIA_ID=".$source_opt_arr['media_id']."
				");
			}
			
			
			//타겟 옵션 분석
			$target_opt_parser = new TaskOptionParser($$task_mgr, 'target', $channel, $arr_param_info, $task_rule, $get_jobs);
			if ($arr_param_info != null && !empty($arr_param_info[0]['target_path'])) {
				$target_opt_parser->setFullPath($arr_param_info[0]['target_path']);
			}
			
			if ($content_id != '') {
				$target_opt_parser->parseTaskOption($content_id, $task_rule['target_opt'], $source_opt_parser->getMediaType() , $source_opt_parser->getMediaId() );
			}
			
			//소스 타겟 스토리지 루트패스 저장
			if( !empty($getStorageInfo) ){
				$source_opt_parser->setSourceRoot($source_login_info['path']);
			}else if(empty($get_jobs['src_rule_path'])){
				$source_opt_parser->setSourceRoot($source_login_info['path']);
			}else{
				$source_opt_parser->setSourceRoot($get_jobs['src_rule_path']);
			}
			
			if( !empty($getStorageInfo) ){
				$target_opt_parser->setTargetRoot($target_login_info['path']);
			}else if( empty($get_jobs['trg_rule_path']) ){
				$target_opt_parser->setTargetRoot($target_login_info['path']);
			}else{
				$target_opt_parser->setTargetRoot($get_jobs['trg_rule_path']);
			}
			
			//echo 'content_id:'.$content_id.'<br/>';
			//echo 'task_rule:[\'target_opt\']'.$task_rule['target_opt'].'<br/>';
			
			$task_type = $task_rule['type'];
			$parameter = $task_rule['parameter'];
			
			//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/task_manager_2_'.date("Ymd").'.log', date('YmdHis').'task_rule :'. print_r($task_rule, true)."\n", FILE_APPEND);
			
			if ($arr_param_info != null) {
				if(!empty($arr_param_info[0]['root_task'])){
					$root_task = $arr_param_info[0]['root_task'];
				}
			
				if( $target_opt_parser->getMediaType() == 'pfr' ){// pfr 작업시 미디어정보에 인아웃 값 업데이트
					$vr_start = $target_opt_parser->vr_start;
					$vr_end = $target_opt_parser->vr_end;
			
					$pfr_media_id = $target_opt_parser->getMediaId();
			
					if( !is_null($vr_start) && !is_null($vr_end) && !is_null($pfr_media_id) ){
						$db->exec("update bc_media set vr_start=$vr_start, vr_end=$vr_end where media_id=$pfr_media_id");
					}
				}
			}
			
			$cur_time = date('YmdHis');
			$source = $source_opt_parser->getFullPath();
			$target = $target_opt_parser->getFullPath();
			if (is_array($options) && ! empty($options['change_target_path']))  {
				$target = $options['change_target_path'];
			}
			
			$source_media_id = $source_opt_parser->getMediaId();
			$target_media_id = $target_opt_parser->getMediaId();
			
			if( empty($target_media_id) ){
				$target_media_id = $source_opt_parser->getMediaId();
			}
			$task_id = getSequence('TASK_SEQ');
			
			//BC_SOCIAL_TRANSFER STATUS(REQUEST, SUCCESS, FAIL)
			$is_sns_exists = $db->queryRow("
						SELECT	B.TYPE
								,A.*
						FROM	(
								SELECT	*
								FROM		BC_SOCIAL_TRANSFER
								WHERE	CONTENT_ID=".$content_id."
								AND		SOCIAL_TYPE='".$parameter."'
								) A
								LEFT OUTER JOIN
								BC_TASK B
								ON(A.TASK_ID=B.TASK_ID)
					");
			$sns_upload;
			if($is_sns_exists['sns_seq_no'] == '') {
					$sns = new SNS($db);
					//$sns_title = $sns->getTitle($content_id);
					$sns_seq_no = getSequence('SEQ_BC_SOCIAL_TRANSFER_NO');
					$insert_data_sns = array(
							'sns_seq_no' => $sns_seq_no,
							'task_id' => $task_id,
							'content_id' => $content_id,
							'social_type' => $parameter,
							'title' => $sns_title,
							'content' => $sns_content,
							'status' => 'REQUEST',
							'reg_user_id' => $task_user_id,
							'created_date' => $cur_time,
							'deleted_date' => '',
							'web_url1' => '',
							'web_url2' => ''
					);
				
					$db->insert('BC_SOCIAL_TRANSFER', $insert_data_sns);
					//After insert task, sns upload
					$sns_upload = 'Y';
					
					$task_mgr->set_task_id($task_id);
						
					if(empty($root_task) || $root_task == ''){
						$root_task = $task_id;
					}
						
					if( $target_opt_parser->getMediaType() == 'out' ){
						$target_media_id = 0;
					}
						
					//2015-12-10 추가
					if( empty($target_media_id) ){
						$target_media_id =0;
					}
					if( empty($src_storage_id) ){
						$src_storage_id =0;
					}
					if( empty($trg_storage_id) ){
						$trg_storage_id =0;
					}
					//2016-02-24 INSERT QUERY 수정
					$insert_data = array(
							'task_id' => $task_id,
							'media_id' => $target_media_id,
							'type' => $task_type,
							'status' => 'queue',
							'priority' => 1,
							'source' => $source,
							'source_id' => $source_login_info['login_id'],
							'source_pw' => $source_login_info['login_pw'],
							'target' => $target,
							'target_id' => $target_login_info['login_id'],
							'target_pw' => $target_login_info['login_pw'],
							'parameter' => $parameter,
							'creation_datetime' => $cur_time,
							'destination' => $channel,
							'task_workflow_id' => $get_jobs['task_workflow_id'],
							'job_priority' => $get_jobs['job_priority'],
							'task_rule_id' => $get_jobs['task_rule_id'],
							'task_user_id' => $task_user_id,
							'root_task' => $root_task,
							'workflow_rule_id' => $get_jobs['workflow_rule_id'],
							'src_content_id' => $content_id,
							'src_media_id' => $source_media_id,
							'trg_media_id' => $target_media_id,
							'src_storage_id' => $src_storage_id,
							'trg_storage_id' => $trg_storage_id
					);
						
					$db->insert('BC_TASK', $insert_data);
				}else{
					if($is_sns_exists['type'] == SNS_SHARE){

						if($is_sns_exists['status'] != 'FAIL'){

							$count_exsits_content +=1;
							$sns_upload = 'N';
						}else{

							$sns = new SNS($db);
							$update_data_sns = array(
								'task_id' => $task_id,
								'title' => $sns_title,
								'content' => $sns_content,
								'status' => 'REQUEST',
								'reg_user_id' => $task_user_id,
								'created_date' => $cur_time,
								'deleted_date' => '',
								'web_url1' => '',
								'web_url2' => ''
							);
							
							$update_data_sns_where = " sns_seq_no = ".$is_sns_exists['sns_seq_no']." ";
							$db->update('BC_SOCIAL_TRANSFER', $update_data_sns, $update_data_sns_where);

							$sns_upload = 'Y';
							
							$task_mgr->set_task_id($task_id);
								
							if(empty($root_task) || $root_task == ''){
								$root_task = $task_id;
							}
								
							if( $target_opt_parser->getMediaType() == 'out' ){
								$target_media_id = 0;
							}

							if( empty($target_media_id) ){
								$target_media_id =0;
							}

							$insert_data = array(
								'task_id' => $task_id,
								'media_id' => $target_media_id,
								'type' => $task_type,
								'status' => 'queue',
								'priority' => 1,
								'source' => $source,
								'source_id' => $source_login_info['login_id'],
								'source_pw' => $source_login_info['login_pw'],
								'target' => $target,
								'target_id' => $target_login_info['login_id'],
								'target_pw' => $target_login_info['login_pw'],
								'parameter' => $parameter,
								'creation_datetime' => $cur_time,
								'destination' => $channel,
								'task_workflow_id' => $get_jobs['task_workflow_id'],
								'job_priority' => $get_jobs['job_priority'],
								'task_rule_id' => $get_jobs['task_rule_id'],
								'task_user_id' => $task_user_id,
								'root_task' => $root_task,
								'workflow_rule_id' => $get_jobs['workflow_rule_id'],
								'src_content_id' => $content_id,
								'src_media_id' => $source_media_id,
								'trg_media_id' => $target_media_id,
								'src_storage_id' => $src_storage_id,
								'trg_storage_id' => $trg_storage_id
							);
							$db->insert('BC_TASK', $insert_data);

						} // end if-else $is_sns_exists['status'] != 'FAIL'

				//end if $is_sns_exists['type'] == SNS_SHARE
				}else if($is_sns_exists['type'] == SNS_DELETE){
					
					if($is_sns_exists['status'] != 'DELETED'){
						return;
					}else{
						
						$sns = new SNS($db);
						$update_data_sns = array(
							'task_id' => $task_id,
							'title' => $sns_title,
							'content' => $sns_content,
							'status' => 'REQUEST',
							'reg_user_id' => $task_user_id,
							'created_date' => $cur_time,
							'deleted_date' => '',
							'web_url1' => '',
							'web_url2' => ''
						);
						
						$update_data_sns_where = " sns_seq_no = ".$is_sns_exists['sns_seq_no']." ";
						$db->update('BC_SOCIAL_TRANSFER', $update_data_sns, $update_data_sns_where);
						$sns_upload = 'Y';
						
						$task_mgr->set_task_id($task_id);
							
						if(empty($root_task) || $root_task == ''){
							$root_task = $task_id;
						}
							
						if( $target_opt_parser->getMediaType() == 'out' ){
							$target_media_id = 0;
						}

						if( empty($target_media_id) ){
							$target_media_id =0;
						}

						$insert_data = array(
							'task_id' => $task_id,
							'media_id' => $target_media_id,
							'type' => $task_type,
							'status' => 'queue',
							'priority' => 1,
							'source' => $source,
							'source_id' => $source_login_info['login_id'],
							'source_pw' => $source_login_info['login_pw'],
							'target' => $target,
							'target_id' => $target_login_info['login_id'],
							'target_pw' => $target_login_info['login_pw'],
							'parameter' => $parameter,
							'creation_datetime' => $cur_time,
							'destination' => $channel,
							'task_workflow_id' => $get_jobs['task_workflow_id'],
							'job_priority' => $get_jobs['job_priority'],
							'task_rule_id' => $get_jobs['task_rule_id'],
							'task_user_id' => $task_user_id,
							'root_task' => $root_task,
							'workflow_rule_id' => $get_jobs['workflow_rule_id'],
							'src_content_id' => $content_id,
							'src_media_id' => $source_media_id,
							'trg_media_id' => $target_media_id,
							'src_storage_id' => $src_storage_id,
							'trg_storage_id' => $trg_storage_id
						);
						$db->insert('BC_TASK', $insert_data);

					} // end if-else $is_sns_exists['status'] != 'DELETED'
				}//end if $is_sns_exists['type'] == SNS_DELETE
			}// end if-else is_sns_exists['sns_seq_no'] == ''

			//After insert task, sns upload
			if($sns_upload == 'Y') {
				$sns->upload($task_id);
			}
		} // end foreach($sns_objects as $sns_object)
			
		$message_return;
		if($count_exsits_content == 0){
			$message_return = _text('MSG00094');
			
		}else if(count($sns_objects) == $count_exsits_content){
			
			$social_name;
			if($channel == 'sns_facebook_GWR_A'){
				$social_name = 'Facebook';
			}else if($channel == 'sns_youtube_GWR_A'){
				$social_name = 'Youtube';
			}else if($channel == 'sns_twitter_GWR_A'){
				$social_name = 'Twitter';
			}
			$message_return = _text('MSG02126').' '.$social_name.' '._text('MSG02131');
			
		}else if(count($sns_objects) > $count_exsits_content){
				
			$message_return = _text('MSG02127').' '.count($sns_objects).' '._text('MSG02128').' '.$count_exsits_content.' '._text('MSG02129').' '.(count($sns_objects) -$count_exsits_content).' '._text('MSG02130');
		}
		
		echo json_encode(array(
				'success' => true,
				'msg' =>$message_return
		));
	
	}else{
		foreach($arr_content_ids as $content_id) {
			$job_priority = 1;
			$task_mgr = new TaskManager($db);
			$task_mgr->start_task_workflow($content_id, $channel, $user_id);
		}
		echo json_encode(array(
				'success' => true,
				'msg' => _text('MSG00094')
		));
	}
}
catch(Exception $e) {
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}


?>