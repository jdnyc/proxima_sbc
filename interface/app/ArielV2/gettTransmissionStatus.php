<?php

function gettTransmissionStatus($request)
{
	global $db;

	try{
		$Interface = new InterfaceClass();
		InterfaceClass::_LogFile($filename,'gettTransmissionStatus request',$request);
		$return;
		$response_json = array();
		$response_xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<Response />");

		$ReqRender			= InterfaceClass::checkSyntax($request);
		$type				= $ReqRender['type'];
		$render_data		= $ReqRender['data'];

		//리턴
		$response = $Interface->DefualtResponse($type);

		if( $type == 'JSON' ){
			$ord_tr_id = $render_data['ord_tr_id'];
			$user_id = $render_data['user_id'];
        } else if ( $type == 'XML' ) {
			$ord_tr_id = $render_data->ord_tr_id;
			$user_id = $render_data->user_id;
			//  $action 		= $render_data->action;
		} else {
			throw new Exception ('invalid request', 101 );
		}

		$query = "
			SELECT	O.ORD_TR_ID, O.CONTENT_ID, O.CREATE_TIME,
						T.CREATION_DATETIME, T.COMPLETE_DATETIME, T.STATUS , T.PROGRESS
			FROM		TB_ORD_TRANSMISSION O, BC_TASK T
			WHERE	O.TASK_ID = T.TASK_ID
				AND	O.ORD_TR_ID IN('".join('\',\'', $ord_tr_id)."')
		";

		$order = " ORDER BY	O.CREATE_TIME DESC ";

		$total = $db->queryOne("SELECT COUNT(*) FROM (".$query.")CNT");
		$data = $db->queryAll($query.$order);

		$success= 'true';

		if($type == 'JSON'){
			$response['success'] = $success;
			$response['total'] = $total;
			$response['data'] = $data;
		}else{
			$response->addChild('success', $success);
			$response->addChild('total', $total);
			$response->addChild('data', $data);
		}

		$return = $Interface->ReturnResponse($type,$response);
		InterfaceClass::_LogFile($filename,'gettTransmissionStatus return',$return);
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