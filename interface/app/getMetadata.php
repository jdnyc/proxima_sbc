<?php

function getMetadata($request)
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

			$ud_content_id		 = $render_data[ud_content_id];

		}else if( $type == 'XML' ){

			$ud_content_id = $render_data->ud_content_id;

		}else{
			throw new Exception ('invalid request', 101 );
		}

		//쿼리 배열
		$_select = array();
		$_from = array();
		$_where = array();
		$_order = array();
		$_param = array();

		array_push($_select , " c.* ");
		array_push($_from , " BC_USR_META_FIELD c ");
		array_push($_order , " c.ud_content_id asc,show_order asc ");

		if( !empty($ud_content_id) ){
			array_push($_where , " c.ud_content_id='$ud_content_id' ");
		}

		$query = " select ".join(' , ',$_select)." from ".join(' , ',$_from);

		if( !empty($_where) ){
			$query .= " where ".join(' and ',$_where);
		}

		$query .= " order by ".join(' , ',$_order);

		$items = $db->queryAll($query);


		if($type == 'JSON'){
			$response['items'] = $items;
		}else{
			$items_xml = $response->addChild('items');
			foreach($items as $item){
				$item_xml = $items_xml->addChild('item');
				foreach($item as $key => $val ){
					$item_xml->addChild($key, $val );
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