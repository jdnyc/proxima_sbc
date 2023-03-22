<?php

/**
 * @param $request
 * @return string
 */
function insertMetadata($request) {
	global $db;

	try {
		$Interface = new InterfaceClass();
		InterfaceClass::_LogFile('','insertMetadata request',$request);

		$response_json = array();
		$response_xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<Response />");

		$ReqRender		=  InterfaceClass::checkSyntax($request);
		$type			= $ReqRender['type'];
		$render_data	= $ReqRender['data'];
		//리턴
		$response = $Interface->DefualtResponse($type);

		if ($type == 'JSON') {
			$inserttype = $render_data['inserttype'];
			$requestxml = $render_data['requestmeta'];
		} else if ($type == 'XML') {
			$inserttype  = $render_data->inserttype;
			$requestxml  = $render_data->requestmeta;
			$requestxml = json_decode($requestxml	, true);
		}else{
			throw new Exception ('invalid request', 101 );
		}
		$status = -3;
		$is_group = $requestxml['is_group'];

		if($is_group == 'Y') {
			$category_id = $requestxml['metadata'][0]['c_category_id'];
			$ud_content_id = $requestxml['metadata'][0]['k_ud_content_id'];
			$bs_content_id = $db->queryOne("select bs_content_id from bc_ud_content where ud_content_id='$ud_content_id'");
			$title		= $requestxml['metadata'][0]['k_title'];
			$user_id	= $requestxml['user_id'];
			$channel	= $requestxml['channel'];
			$filename	= $db->escape($requestxml['filename']);
			$server_ip	= $requestxml['server_ip'];
			$parent_id	= $requestxml['parent_id'];
			$index		= $requestxml['index'];
			$group_count	= $requestxml['group_count'];
			$interface_id	= $requestxml['interface_id'];

			$metaValues = array();
			$metaValues		= InterfaceClass::getMetaValues( $requestxml['metadata'] );
			$expire_date    = $requestxml['expire_date'] ? $requestxml['expire_date'] : '99991231';

			if(empty($channel)){
				$channel = $requestxml['flag'];
			}

			$task = new TaskManager($db);

			if($inserttype == 'each') {
				if (empty($regist_type) || $regist_type == 'meta') {
					$content_id = getSequence('SEQ_CONTENT_ID');
					$group_type = 'I';
					if ($is_group == 'Y') {
						$group_type = 'C';
						$group_count = $index;
						$parent_content_id = $parent_id;
						if ( ! empty($parent_content_id)) {
							$parent_id = $parent_content_id;
						} else {
							$parent_id = $content_id;
						}
						if ($group_count == '1') {
							$group_type = 'G';
							$parent_content_id = null;
						}
					}

					insertContent($metaValues, $content_id, $category_id, $bs_content_id, $ud_content_id, $title , $user_id, $topic_id, $group_type, $group_count, $parent_content_id);

					insertMetaValues($metaValues, $content_id, $ud_content_id);

					$workflowInfo = $task->getWorkflowInfo($channel, $content_id);
				}

				if (empty($regist_type) || $regist_type == 'task') {
					if ( ! empty($target_content_id) && ( $regist_type == 'task')) {
						$content_id = $target_content_id;
					}

					// todo 세션 확인 필요
					if (empty($user_id)) {
						$user_id = 'system';
					}

					if (empty($task_id)) {
						$task_id = $task->insert_task_query_outside_data($content_id, $channel, 1, $user_id, $filename);
						$task_list_info = $task->get_task_list(null);

						if ( ! empty($task_list_info)) {
							$workflow = $db->queryRow("select USER_TASK_NAME,TASK_WORKFLOW_ID from bc_task_workflow where register = '$channel'");
							$interface_id = $task->InsertInterface($workflow['user_task_name'], 'USER', $user_id, 'USER', $user_id, $content_id , 'regist', $workflow['task_workflow_id']);
							foreach ($task_list_info as $list_info) {
								$task->InsertInterfaceCH($interface_id, 'NPS', 'TASK', $list_info['task_id'], $content_id);
							}
						}
					}
				}
			}

			if($type == 'JSON'){
				$response['task_id'] = $task_id;
				$response['content_id'] = $content_id;
				$response['parent_id'] = $parent_id;
				$response['interface_id'] = $interface_id;
				$response['task_list_info'] = $task_list_info;
			}else{
				$response->addChild('task_id', $task_id);
				$response->addChild('content_id',$content_id);
				$response->addChild('parent_id', $parent_id);
				$response->addChild('interface_id', $interface_id);
			}
		} else {
			//메타데이터 등록
			if ($inserttype	== 0 ||  $inserttype == 1) {
				$category_id = $requestxml['category_id'];
				$bs_content_id = $requestxml['bs_content_id'];
				$ud_content_id = $requestxml['ud_content_id'];
				$title = $requestxml['title'];
				$user_id = $requestxml['user_id'];
				$channel = $requestxml['channel'];
				$filename = $requestxml['filename'];
				$server_ip = $requestxml['server_ip'];

				$metaValues = array();
				if ($requestxml['metadata_type'] == 'id') {
					if (empty($channel)) {
						$channel = $requestxml['flag'];
						if ( ! empty($server_ip)) {
							//$channel.='_'.$server_ip;
						}
					}

					if (empty($filename)) throw new Exception ('empty filename', 105);
					if (empty($channel)) throw new Exception ('empty channel', 106);

					$metaValues = InterfaceClass::getMetaValues( $requestxml['metadata'] );
					$category_id = $requestxml['metadata'][0]['c_category_id'];
					$ud_content_id = $requestxml['metadata'][0]['k_ud_content_id'];
					$title = $requestxml['metadata'][0]['k_title'];
					$bs_content_id = $db->queryOne("select bs_content_id from bc_ud_content where ud_content_id='$ud_content_id'");
				}else{
					$metaValues_tmp		= InterfaceClass::getMetaNameValues( $requestxml['metadata'] );
					$nametoidmap = MetaDataClass::getFieldNametoIdMap('usr' , $ud_content_id );
					foreach ($metaValues_tmp as $name => $val) {
						$metaValues[$nametoidmap[$name]] = $val;
					}
				}

				if (strstr($requestxml['flag'], 'ingest')) {
					$status = 2;
				}

				$content_id = getSequence('SEQ_CONTENT_ID');

				
				//KTV 미디어ID 발급 등록시 발급한다            
				$contentService = new \Api\Services\ContentService(app()->getContainer());
				$metaValues['media_id'] = $contentService->getMediaId($bs_content_id);
	

				InterfaceClass::insertContent($metaValues, $content_id, $category_id,$bs_content_id, $ud_content_id, $title , $user_id, $status);
				MetaDataClass::insertSysMeta(array(), $bs_content_id , $content_id );
				//InterfaceClass::_LogFile('','insertSysMeta ',$bs_content_id.':'.$content_id);
				InterfaceClass::insertMetaValues($metaValues, $content_id, $ud_content_id);

				//콘텐츠 상태 추가
				$contentStatus = new \Api\Models\ContentStatus();
				$contentStatus->content_id = $content_id;
				
				//주조 전송
				if( !empty($requestxml['metadata'][0]['k_send_to_main']) ){         
					$contentStatus->mcr_trnsmis_sttus = 'request';           
				}
				// 부조 전송
				if(!empty($requestxml['metadata'][0]['k_send_to_sub']) && !empty($requestxml['metadata'][0]['k_send_to_sub_news'])) {
					// 둘다 전송
					$contentStatus->scr_trnsmis_sttus = 'request';
					$contentStatus->scr_news_trnsmis_sttus = 'request';
					$contentStatus->scr_trnsmis_ty = 'all';
				}
                else if( !empty($requestxml['metadata'][0]['k_send_to_sub']) && empty($requestxml['metadata'][0]['k_send_to_sub_news']) ){         
					// AB 부조 전송
                    $contentStatus->scr_trnsmis_sttus = 'request';   
                    $contentStatus->scr_trnsmis_ty = 'ab';          
                }
                else if( empty($requestxml['metadata'][0]['k_send_to_sub']) && !empty($requestxml['metadata'][0]['k_send_to_sub_news']) ){         
					// 뉴스 부조 전송
                    $contentStatus->scr_news_trnsmis_sttus = 'request';   
                    $contentStatus->scr_trnsmis_ty = 'news';           
                }
				//확인
				if( !empty($requestxml['metadata'][0]['k_qc_confirm']) ){         
					$contentStatus->qc_cnfrmr = $user_id; 
					$contentStatus->qc_cnfirm_at = 1;
				}
				$contentStatus->save();

				if ( ! empty($ud_content_id)) {
					//$Search = new Search();
				}
			}

			// 작업만 등록
			if ($inserttype	== 0 || $inserttype	== 2) {
				if ($inserttype	== 2) {
					$content_id = $requestxml['content_id'];
					$user_id = $requestxml['user_id'];
					$channel = $requestxml['channel'];
					$filename = $requestxml['filename'];
					$server_ip = $requestxml['server_ip'];
					if (empty($channel)) {
						$channel = $requestxml['flag'];
						if ( ! empty($server_ip)) {
							//$channel.='_'.$server_ip;
						}
					}
				}

				$task = new TaskManager($db);
				$task_id = $task->insert_task_query_outside_data($content_id, $channel, 1, $user_id, $filename);

				$task_list_info = $task->get_task_list(null);

				if ( ! empty($task_list_info)) {
					$workflow = $db->queryRow("select USER_TASK_NAME,TASK_WORKFLOW_ID from bc_task_workflow where register = '$channel'");
					$interface_id = $task->InsertInterface($workflow['user_task_name'], 'USER', $user_id, 'USER', $user_id, $content_id , 'regist', $workflow['task_workflow_id'] );
					foreach ($task_list_info as $list_info){
						$task->InsertInterfaceCH($interface_id, 'NPS', 'TASK', $list_info['task_id'], $content_id);
					}
				}
			}

			if($type == 'JSON'){
				$response['task_id'] = $task_id;
				$response['content_id'] = $content_id;
				$response['task_list_info'] = $task_list_info;

			}else{
				$response->addChild('task_id', $task_id);
				$response->addChild('content_id',$content_id);
			}
		}


        searchUpdate($content_id);

		$return = $Interface->ReturnResponse($type,$response);
		InterfaceClass::_LogFile('','insertMetadata return',$return);
		return $return ;

	}
	catch(Exception $e){

		$msg = $e->getMessage();
		$code = $e->getCode();
		$success = 'false';

		if(empty($code)){
			$code = '200';
		}

		if($type == 'JSON'){
			$response['success'] = $success;
			$response['message'] = $msg;
			$response['status'] = $code;
		}else{
			$response->success = $success;
			$response->message = $msg;
			$response->status = $code;
		}
		$return = $Interface->ReturnResponse($type,$response);

		InterfaceClass::_LogFile('','insertMetadata return error',$return);
		return $return;
	}
}
?>