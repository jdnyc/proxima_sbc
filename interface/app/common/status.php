<?php
/*
<request>{"request_id": "11111,22222"}</request>

<result>
	<status>0</status>
	<message>OK</message>
	<total>2</total>
	<data_list>
		<data>
			<request_id>11111</request_id>
			<from_user_id>U0001</from_user_id>
			<from_system>NPS</from_system>
			<target_system>ARCHIVE</target_system>
			<target_user_id>U0002</target_user_id>
			<request_type>TM</request_type>
			<status>complete</status>
			<log></log>
		</data>
		<data>
			<request_id>22222</request_id>
			<from_user_id>U0001</from_user_id>
			<from_system>NPS</from_system>
			<target_system>ARCHIVE</target_system>
			<target_user_id>U0002</target_user_id>
			<request_type>TM</request_type>
			<status>complete</status>
			<log></log>
		</data>
	</data_list>
</result>
*/
function status($request)
{
	global $db;

	try{
		$Interface = new InterfaceClass();
		InterfaceClass::_LogFile($filename,'request',$request);

		//변환
		$ReqRender		=  InterfaceClass::checkSyntax($request);
		$type			= $ReqRender['type'];
		$render_data	= $ReqRender['data'];

		//리턴
		$response = $Interface->DefualtResponse($type);

		if( $type == 'JSON' ){
			$request_id = trim($render_data['request_id']);
		}else if( $type == 'XML' ){
			$request_id = trim($render_data->request_id);
		}else{
			throw new Exception ('invalid request', 101 );
		}

		if( empty($request_id) ){
			throw new Exception ('invalid request', 101 );
		}

		$task_all = $db->queryAll("select * from bc_task
			where task_id in (".$request_id.")
			order by task_id desc");
		foreach($task_all as $task)
		{
			
		}

		$info = $task_all;

		if($type == 'JSON'){
			$response['data'] = json_encode($info);
		}else{
			$items_xml = $response->addChild('data_list');
			foreach($info as $sub_info){
				$items_xml_sub = $items_xml->addChild('data');
				foreach($sub_info as $key => $val ){
					$items_xml_sub->addChild($key, $val );
				}
			}
		}

		$return = $Interface->ReturnResponse($type,$response);
		InterfaceClass::_LogFile($filename,'return',$return);
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