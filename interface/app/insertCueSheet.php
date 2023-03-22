<?php

function insertCueSheet($request)
{
	global $db;

	try{
		$Interface = new InterfaceClass();
		InterfaceClass::_LogFile($filename,'insertCueSheet request',$request);
		$return;
		$response_json = array();
		$response_xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<Response />");

		$ReqRender		= InterfaceClass::checkSyntax($request);
                $type			= $ReqRender['type'];
		$render_data            = $ReqRender['data'];

		//리턴
		$response = $Interface->DefualtResponse($type);

		if( $type == 'JSON' ){
			$action 		= $render_data[action];
                        $edit_list 		= $render_data[edit_list];
                        $edit_content 		= $render_data[edit_content];
			$cuesheet_id            = $render_data[cuesheet_id];
			$cuesheet_title		= $render_data[cuesheet_title];
			$broad_date		= $render_data[broad_date];
			$cuesheet_type 		= $render_data[cuesheet_type];
			$user_id		= $render_data[user_id];
			$subcontrol_room	= $render_data[subcontrol_room];
                        $create_system   	= $render_data[create_system];
                        $prog_id        	= $render_data[prog_id];
			$cuesheet_items 	= $render_data[cuesheet_items];

                } else if ( $type == 'XML' ) {
                        $action 		= $render_data->action;
                        $edit_list 		= $render_data->edit_list;
                        $edit_content 		= $render_data->edit_content;
                        $cuesheet_id		= $render_data->cuesheet_id;
			$cuesheet_title		= $render_data->cuesheet_title;
			$broad_date		= $render_data->broad_date;
			$cuesheet_type 		= $render_data->cuesheet_type;
			$user_id		= $render_data->user_id;
			$subcontrol_room	= $render_data->subcontrol_room;
                        $create_system  	= $render_data->create_system;
                        $prog_id          	= $render_data->prog_id;
			$cuesheet_items 	= json_decode($render_data->cuesheet_items, true);

		} else {
			throw new Exception ('invalid request', 101 );
		}
                
                if( empty($action) ) throw new Exception("invaild request", 101);
                
		if( $action == 'add' && empty($cuesheet_id) ){
			$cuesheet_id = getSequence('SEQ_BC_CUESHEET_ID');

			InterfaceClass::insertCueSheet($cuesheet_id, $cuesheet_title, $broad_date, $cuesheet_type, $user_id, $subcontrol_room, $create_system, $prog_id);
                        if($cuesheet_type == 'M') {
                            InterfaceClass::insertCueSheetItems($cuesheet_items, $cuesheet_id, $user_id);
                        } else {
                            InterfaceClass::insertAudioCueSheetItems($cuesheet_items, $cuesheet_id, $user_id, 'add');
                        }
                        
                        $success = 'true';
                        $msg = '큐시트가 성공적으로 등록되었습니다';
			
		} else if( $action == 'edit' ) {
			
			if( empty($cuesheet_id) ) throw new Exception("invaild request", 101);
                        // Cuesheet List 수정 flag 값이 Y이면 cuesheet 수정 함수 호출
                        if($edit_list == 'Y') {
                            InterfaceClass::editCueSheet($cuesheet_id, $cuesheet_title, $broad_date, $user_id, $subcontrol_room);
                        }
                        // Cuesheet Content 수정 flag 값이 Y이면 cuesheet content 수정 함수 호출
                        if($edit_content == 'Y' && $cuesheet_type == 'A') {
                            InterfaceClass::insertAudioCueSheetItems($cuesheet_items, $cuesheet_id, $user_id, 'edit');
                        }
                        
                        $success = 'true';
                        $msg = '큐시트가 성공적으로 수정되었습니다';

		} else if($action== 'del') {
                        if( empty($cuesheet_id) ) throw new Exception("invaild request", 101);
                        
                        InterfaceClass::deleteCueSheet($cuesheet_id, $user_id);
                        
                        $success = 'true';
                        $msg = '큐시트가 성공적으로 삭제되었습니다';
		}


		if($type == 'JSON'){
			$response['success'] = $success;
			$response['message'] = $msg;
		}else{
			$response->addChild('success', $success);
			$response->addChild('message', $msg);
		}

		$return = $Interface->ReturnResponse($type,$response);
		InterfaceClass::_LogFile($filename,'insertMetadata return',$return);
		return $return ;

	}
	catch(Exception $e){

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