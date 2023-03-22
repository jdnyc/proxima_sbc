<?php

function getContentInfo($request)
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

			$pagenum		 = $render_data[pagenum];
			$pageitemcount	 = $render_data[pageitemcount];
			$searchField		= $render_data[search];

			$contentid			= $searchField[contentid];
			$materialid			= $searchField[materialid];
			$title				= $searchField[title];
			$subtitle			= $searchField[subtitle];
			$programid			= $searchField[programid];
			$programname		= $searchField[programname];
			$programsequence	= $searchField[programsequence];
			$startonairdate		= $searchField[startonairdate];
			$endonairdate		= $searchField[endonairdate];
			$pdusername			= $searchField[pdusername];
			$grade				= $searchField[grade];


		}else if( $type == 'XML' ){

			$pagenum = trim($render_data->pagenum);
			$pageitemcount = trim($render_data->pageitemcount);
			//$requestxml = $render_data->search;

			$searchField = $render_data->search;
			//throw new Exception (print_r($requestxml,true), 101 );

			$contentid			= $searchField->contentid;
			$materialid			= $searchField->materialid;
			$title				= $searchField->title;
			$subtitle			= $searchField->subtitle;
			$programid			= $searchField->programid;
			$programname		= $searchField->programname;
			$programsequence	= $searchField->programsequence;
			$startonairdate		= $searchField->startonairdate;
			$endonairdate		= $searchField->endonairdate;
			$pdusername			= $searchField->pdusername;
			$grade				= $searchField->grade;

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
		array_push($_from , " bc_content c ");
		array_push($_order , " c.content_id desc ");

		if( !empty($contentid) ){
			array_push($_where , " c.content_id='$contentid' ");
		}else{
			if( !empty($materialid)		){
				$_param[1] = $materialid ;
			}
			if( !empty($title)			){
				$_param[2] = $title ;
			}
			if( !empty($subtitle)		){
				$_param[3] = $subtitle ;
			}
			if( !empty($programid)		){
				$_param[4] = $programid ;
			}
			if( !empty($programname)	){
				$_param[5] = $programname ;
			}
			if( !empty($programsequence)){
				$_param[6] = $programsequence ;
			}
			if( !empty($startonairdate) ){
				$_param[7] = $startonairdate ;
			}
			if( !empty($endonairdate)	){
				$_param[8] = $endonairdate ;
			}
			if( !empty($pdusername)		){
				$_param[9] = $pdusername ;
			}
			if( !empty($grade)			){
				$_param[10] = $grade ;
			}

			foreach($_param as $usr_meta_field_id => $usr_meta_value )
			{
				array_push($_from , " VIEW_USR_META m$usr_meta_field_id ");
				array_push($_where , " c.content_id =m$usr_meta_field_id.content_id ");
				array_push($_where , " m$usr_meta_field_id.usr_meta_field_id = '$usr_meta_field_id'  ");
				array_push($_where , " m$usr_meta_field_id.usr_meta_value = '$usr_meta_value' ");
			}
		}

		$query = " select ".join(' , ',$_select)." from ".join(' , ',$_from)." where ".join(' and ',$_where)." order by ".join(' , ',$_order);

		$total = $db->queryOne("select count(*) from ( $query ) cnt ");

		$db->setLimit($limit, $start);
		$contentInfo = $db->queryAll($query);

		$items = array();

		foreach($contentInfo as $conent)
		{
			$content_id = $conent['content_id'];

			$sub_query = "select usr_meta_field_title,usr_meta_field_type,usr_meta_field_id,usr_meta_value from VIEW_USR_META where content_id=$content_id";
			$metadatas = $db->queryAll($sub_query);

			$item = array();
			$item['contentid'] = $content_id;

			foreach($metadatas as $metadata)
			{
				$item[$metadata['usr_meta_field_id']] = $metadata['usr_meta_value'];
			}

			array_push($items, $item);
		}


		if($type == 'JSON'){
			$response['totalcount'] = $total;
			$response['totalpage'] = ceil( $total / $pageitemcount );
			$response['pagenum'] = $pagenum;
			$response['items'] = $items;
		}else{
			$response->addChild('totalcount', $total);
			$response->addChild('totalpage', ceil( $total / $pageitemcount ));
			$response->addChild('pagenum', $pagenum);
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