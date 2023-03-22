<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/interface.class.php');
$server->register('RequestTransmissionContentAll',
		array(
				'request' => 'xsd:string'
		),
		array(
				'response'	=> 'xsd:string'
		),
		$namespace,
		$namespace.'#RequestTransmissionContentAll',
		'rpc',
		'encoded',
		'RequestTransmissionContentAll'
);

function RequestTransmissionContentAll($request)
{
	global $db;

	try{
		$Interface = new InterfaceClass();
		InterfaceClass::_LogFile($filename,'RequestTransmissionContent request',$request);
		$return;
		$response_json = array();
		$response_xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<Response />");

		$ReqRender			= InterfaceClass::checkSyntax($request);
		$type				= $ReqRender['type'];
		$render_data		= $ReqRender['data'];

		//리턴
		$response = $Interface->DefualtResponse($type);

		if( $type == 'JSON' ){
			$contents = $render_data['content_id'];
			$user_id = $render_data['user_id'];
		} else if ( $type == 'XML' ) {
			$contents = $render_data->content_id;
			$user_id = $render_data->user_id;
			//  $action 		= $render_data->action;
		} else {
			throw new Exception ('invalid request', 101 );
		}

		$channel	= 'transmission_zodiac';
		
		$content_ids = explode(',',$contents);
		$playout_ids = array();
		
		foreach($content_ids as $content_id){
			//미디어구분값 초기화
			$media_cd = '';
			//송출 아이디 생성
			$check_id_query = "
				SELECT	M.CONTENT_ID, M.PATH, T.PLAYOUT_ID, C.BS_CONTENT_ID
				FROM	BC_MEDIA M
						LEFT JOIN TB_ORD_TRANSMISSION_ID T ON  M.CONTENT_ID = T.CONTENT_ID,
						BC_CONTENT C
				WHERE	M.CONTENT_ID = ".$content_id."
				AND		M.CONTENT_ID = C.CONTENT_ID
				AND		M.MEDIA_TYPE = 'original'
			";
			$check_id = $db->queryRow($check_id_query);
			
			if( empty($check_id['playout_id']) ){
				if( $check_id['bs_content_id'] == SEQUENCE ){
					// 				$path = explode('/',  dirname($check_id['path']));
					// 				$playout_id = array_pop($path);
					// 파일러 등록일 경우 seq는 폴더명이 content_id 이기 때문에 신규로 파일 ID 작성
			
					$playout_id = buildFileID();
				}else{
					$playout_id = basename($check_id['path'], strrchr($check_id['path'], '.'));
				}
					
				$seq_id = getSequence('SEQ_TB_ORD_TRANSMISSION_ID');//SEQ_TB_ORD_TRANSMISSION_ID
				$insert_data = array(
						'id'			=>	$seq_id,
						'content_id'	=>	$content_id,
						'playout_id'	=>	$playout_id
				);
				$db->insert('TB_ORD_TRANSMISSION_ID', $insert_data);
			}else {
				$playout_id = $check_id['playout_id'];
			}
			
			//송출 목록 생성 TB_ORD_TRANSMISSION
			$seq_id = getSequence('SEQ_TB_ORD_TRANSMISSION');//SEQ_TB_ORD_TRANSMISSION
			$isnert_data = array(
					'ord_tr_id'		=>	 $seq_id,
					'content_id'	=>	 $content_id,
					'create_time'	=>	 date('YmdHis'),
					'create_user'	=>	 $user_id,
					'playout_id'	=>	$playout_id
			);
			
			$query_insert = $db->insert('TB_ORD_TRANSMISSION', $isnert_data);
			
			if( $query_insert ) {
				$media_info = $db->queryRow("
					SELECT	*
					FROM		BC_MEDIA
					WHERE	CONTENT_ID = ".$content_id."
						AND	MEDIA_TYPE = 'original'
						AND	DELETE_DATE IS NULL
						AND	FILESIZE > 0
				");
				
				if( empty($media_info['media_id'])  ) {
					// 미디어가 없을 경우 false return
					$success = 'false';
					$msg = '요청하신 콘텐츠의 원본파일이 없습니다.';
				} else {
					// 미디어가 있을 경우에는 전송요청
					$content_info = $db->queryRow("
						SELECT	C.BS_CONTENT_ID, C.IS_GROUP, G.UD_GROUP_CODE, C.GROUP_COUNT
						FROM	BC_CONTENT C, BC_UD_GROUP G
						WHERE	C.UD_CONTENT_ID = G.UD_CONTENT_ID
						AND		CONTENT_ID = ".$content_id."
					");
					$frame_count = 1;
					
					if( $content_info['ud_group_code'] == 4 ){//CG
						if( $content_info['bs_content_id'] == SEQUENCE || $content_info['is_group'] == 'G' ){
							$channel = 'transmission_graphic_seq_zodiac';
						} else {
							$channel = 'transmission_graphic_zodiac';
						}
					
						if( $content_info['bs_content_id'] == SEQUENCE ){
							$frame_count = $content_info['group_count'];
						}
						
						$media_cd = '002';
					} else {
						if( $content_info['is_group'] == 'G' ){
							$channel = 'transmission_group_zodiac';
						} else {
							$channel = 'transmission_zodiac';
							$check_ext = pathinfo($media_info['path'], PATHINFO_EXTENSION );
					
							if(strtoupper($check_ext) == 'MXF') {
								$channel = 'transmission_zodiac';
							} else if (strtoupper($check_ext) == 'MOV') {
								$channel = 'transmission_zodiac_rewrap';
							}
						}
						$media_cd = '001';
					}
					
					$ext = substr(strrchr($media_info['path'],"."),1);
					$task = new TaskManager($db);
					$task_id = $task->start_task_workflow($content_id, $channel, $user_id);
					
					if($task_id){
						//송출테이블에 TASK_ID 업데이트
						$query_update = "
							UPDATE	TB_ORD_TRANSMISSION	SET
							TASK_ID = ".$task_id."
							WHERE	ORD_TR_ID = '".$ord_tr_id."'
						";
					}
				}
			}

			$tmp_array = array(
				'content_id' => $content_id,
				'playout_id' => $playout_id,
				'media_cd'   => $media_cd
			);
			
			
			array_push($playout_ids, $tmp_array);
		}
		
		$success = 'true';
		$msg = '전송 요청 되었습니다.';
		
		if($type == 'JSON'){
			$response['success'] = $success;
			$response['data'] = $playout_ids;
			$response['message'] = $msg;
		}else{
			$response->addChild('success', $success);
			$response->addChild('data', $playout_id);
			$response->addChild('message', $msg);
		}

		$return = $Interface->ReturnResponse($type,$response);
		//InterfaceClass::_LogFile($filename,'RequestTransmissionContent return',$return);
		return $return ;

	} catch(Exception $e) {

		$msg = $e->getMessage();
		$code = $e->getCode();
		$success = 'false';

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

		InterfaceClass::_LogFile($filename,'return',$return);
		return $return;
	}
}
?>