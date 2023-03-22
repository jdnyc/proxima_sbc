<?php

function getIngestSchedule($request)
{
	global $db;

	try{
		$Interface = new InterfaceClass();
		InterfaceClass::_LogFile($filename,'request',$request);


		//쿼리 배열
		$_select = array();
		$_from = array();
		$_where = array();
		$_order = array();
		$_param = array();

		//변환
		$ReqRender		= InterfaceClass::checkSyntax($request);
		$type			= $ReqRender['type'];
		$render_data            = $ReqRender['data'];

		//리턴
		$response = $Interface->DefualtResponse($type);

		if( $type == 'JSON' ){

			$pagenum	= $render_data[pagenum];
			$pageitemcount	= $render_data[pageitemcount];
			$system_ip      = $render_data[ingest_system_ip];
                        $channel        = $render_data[channel];


		}else if( $type == 'XML' ){

			$pagenum	= $render_data->pagenum;
			$pageitemcount	= $render_data->pageitemcount;
			$system_ip	= $render_data->ingest_system_ip;
			$channel	= $render_data->channel;

		}else{
			throw new Exception ('invalid request-type', 101 );
		}

		if( is_null($pagenum) || is_null($pageitemcount) || is_null($system_ip) || is_null($channel) ){
			throw new Exception ('invalid request-empty-empty', 101 );
		}

		$start = 0;
		$limit = $pageitemcount;
		if($pagenum) $start = ( $pagenum - 1 ) * $pageitemcount;

                $query = "select * from ingestmanager_schedule where ingest_system_ip = '$system_ip' and channel = '$channel' order by start_time";

		$total = $db->queryOne("select count(*) from ( $query ) cnt ");

		$db->setLimit($limit, $start);
		$schedules = $db->queryAll($query);
                // status 값을 0으로 전송하기 위해 처리
                
		if($type == 'JSON'){

			$response['totalcount'] = $total;
			$response['totalpage'] = ceil( $total / $pageitemcount );
			$response['pagenum'] = $pagenum;

			$response['items'] = $schedules;

		}else{

			$response->addChild('totalcount', $total);
			$response->addChild('totalpage', ceil( $total / $pageitemcount ));
			$response->addChild('pagenum', $pagenum);
			$items_xml = $response->addChild('items');
			foreach($schedules as $item)
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