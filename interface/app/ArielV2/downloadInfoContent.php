<?php

function downloadInfoContent($request)
{
	global $db;

	try{
		$Interface = new InterfaceClass();
		InterfaceClass::_LogFile($filename,'request',$request);

		$ReqRender		=  InterfaceClass::checkSyntax($request);
		$type			= $ReqRender['type'];
		$render_data	= $ReqRender['data'];

		//리턴
		$response = $Interface->DefualtResponse($type);

		if( $type == 'JSON' ){

			$contentid		 = $render_data[contentid];

		}else if( $type == 'XML' ){

			$contentid = $render_data->contentid;

		}else{
			throw new Exception ('invalid request', 101 );
		}

		if( empty($contentid) ){
			throw new Exception ('invalid request', 101 );
		}

		//쿼리 배열
		$_select = array();
		$_from = array();
		$_where = array();
		$_order = array();
		$_param = array();

		array_push($_select , " m.* ");
                array_push($_select , " c.ud_content_id ");
		array_push($_from , " bc_media m ");
                array_push($_from , " bc_content c ");
		array_push($_order , " m.media_id desc ");
		array_push($_where , " m.media_type='original' ");
		array_push($_where , " m.content_id='$contentid' ");


		$query = " select ".join(' , ',$_select)." from ".join(' , ',$_from)." where ".join(' and ',$_where)." order by ".join(' , ',$_order);

		$mediaInfo = $db->queryRow($query);

		$filepath_array = explode("/",$mediaInfo['path']);

		$filename = array_pop($filepath_array);
		$filepath = join("/",$filepath_array);
                $ud_content_id = $mediaInfo['ud_content_id'];
                
                $query = "select s.* from bc_storage s, bc_ud_content_storage us where s.storage_id = us.storage_id and us.us_type = 'download' and us.ud_content_id = '$ud_content_id'";
		$storageInfo = $db->queryRow($query);

		$spath_array = explode(':',$storageInfo[path]);
		$server_ip = $spath_array[0];
		$server_port = $spath_array[1];
		$ftppassive = 'Y';

		$info = array(
			'ftpserverip' => $server_ip,
			'ftpserverport' => $server_port,
			'ftppassive' => $ftppassive,
			'ftpuserid' => $storageInfo[login_id],
			'ftpuserpassword' => $storageInfo[login_pw],
			'filepath' => $filepath,
			'filename' => $filename
		);

		if($type == 'JSON'){

			$response['info'] = $info;
		}else{
			$info = $response->addChild('info');

			foreach($info as $key => $val )
			{
				$info->addChild($key, $val );
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