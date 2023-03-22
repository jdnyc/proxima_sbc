<?php

function getRundownlist($request)
{
	global $db;

	try{
		InterfaceClass::_LogFile($filename,'request',$request);

		//변환
		$ReqRender		=  InterfaceClass::checkSyntax($request);
		$type			= $ReqRender['type'];
		$render_data	= $ReqRender['data'];

		//리턴
		$response = $Interface->DefualtResponse($type);

		if( $type == 'JSON' ){

			$pagenum		= $render_data[pagenum];
			$pageitemcount	= $render_data[pageitemcount];
			$registdatetime	= $render_data[registdatetime];


		}else if( $type == 'XML' ){

			$pagenum		= $render_data->pagenum;
			$pageitemcount	= $render_data->pageitemcount;
			$registdatetime = $render_data->registdatetime;

		}else{
			throw new Exception ('invalid request', 101 );
		}

		if( is_null($pagenum) || empty($pageitemcount) ){
			throw new Exception ('invalid request', 101 );
		}

		$start = 0;
		$limit = $pageitemcount;
		if($pagenum) $start = ( $pagenum - 1 ) * $pageitemcount;

		//쿼리 배열
		$_select = array();
		$_from = array();
		$_where = array();
		$_order = array();
		$_param = array();

		array_push($_select , " c.* ");
		array_push($_from , " rundown c ");
		array_push($_order , " c.rundown_id desc ");

		if( !empty($registdatetime) ){
			array_push($_where , " c.regist_date='$registdatetime' ");
		}

		$query = " select ".join(' , ',$_select)." from ".join(' , ',$_from)." where ".join(' and ',$_where)." order by ".join(' , ',$_order);

		$total = $db->queryOne("select count(*) from ( $query ) cnt ");

		$db->setLimit($limit, $start);
		$contentInfo = $db->queryAll($query);

		if($type == 'JSON'){

			$response['totalcount'] = $total;
			$response['totalpage'] = ceil( $total / $pageitemcount );
			$response['pagenum'] = $pagenum;

			$response['items'] = $contentInfo;

		}else{

			$response->addChild('totalcount', $total);
			$response->addChild('totalpage', ceil( $total / $pageitemcount ));
			$response->addChild('pagenum', $pagenum);
			$items_xml = $response->addChild('items');
			foreach($contentInfo as $item)
			{
				$item_xml = $items_xml->addChild('item');
				foreach($item as $key => $val )
				{
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