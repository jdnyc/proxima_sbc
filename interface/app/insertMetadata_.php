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
						$channel.='_'.$server_ip;
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
			InterfaceClass::insertContent($metaValues, $content_id, $category_id,$bs_content_id, $ud_content_id, $title , $user_id, $status);
			//InterfaceClass::insertBaseContentValue($content_id, $bs_content_id );
			InterfaceClass::insertMetaValues($metaValues, $content_id, $ud_content_id);

			if ( ! empty($ud_content_id)) {
				$Search = new Search();
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
						$channel.='_'.$server_ip;
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

		if ($type == 'JSON') {
			$response['task_id'] = $task_id;
			$response['content_id'] = $content_id;
			$response['task_list_info'] = $task_list_info;
		}else{
			$response->addChild('task_id', $task_id);
			$response->addChild('content_id',$content_id);
		}

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