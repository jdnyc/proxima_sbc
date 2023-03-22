<?php

function Active($request)
{
	global $db;
	
	try{
		//리턴
		$response = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<Response />");
		$response->addChild('message', $message);
		$response->addChild('status', $status);
		$response->addChild('success', $success);

		if($request) {
		// active 일 경우 active 구분값을 active로 변경하고 기존에 밀린 작업이 있을 경우 진행시킴
			$msg = 'Active';
			$db->exec("
				UPDATE BC_CODE
				SET CODE = 'Y'
				WHERE CODE_TYPE_ID = 
					(SELECT ID
					FROM BC_CODE_TYPE
					WHERE CODE = 'sgl_active_check')
				AND NAME = 'Active Code'
			");

		} else if(!$request) {
		// deactive 일 경우 active 구분값을 deactive로 변경
			$msg = 'Deactive';
			$db->exec("
				UPDATE BC_CODE
				SET CODE = 'N'
				WHERE CODE_TYPE_ID = 
					(SELECT ID
					FROM BC_CODE_TYPE
					WHERE CODE = 'sgl_active_check')
				AND NAME = 'Active Code'
			");
		} else {
			throw new Exception ('invalid request', 101 );
		}

		$response->success = 'true';
		$response->message = $msg;
		$response->status = '0';

		return $response->asXML() ;

	}
	catch(Exception $e){

		$msg = $e->getMessage();
		$code = $e->getCode();
		$success = 'false';

		$response->success = $success;
		$response->message = $msg;
		$response->status = $code;
		
		return $response->asXML();
	}
}
?>