<?php
/*
<Request><pagenum>1</pagenum><pageitemcount>20</pageitemcount><requestxml><search><contentid>1234567</contentid></search></requestxml></Request>
*/
function getContentList($request)
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
			$requestxml		= $render_data[requestxml];

			$searchField = $render_data[search];
//			usr_materialid
//			title
//			usr_subprog
//			usr_progid
//			usr_program
//			usr_turn
//			usr_brod_date
//			usr_content
//			usr_producer
//			usr_keyword
//			usr_mednm

			$contentid			= $searchField[content_id];
			$ud_content_id		= $searchField[ud_content_id];

			$usr_materialid		= $searchField[usr_materialid];
			$title				= $searchField[title];
			$usr_subprog		= $searchField[usr_subprog];
			$usr_progid			= $searchField[usr_progid];
			$usr_program		= $searchField[usr_program];
			$usr_turn			= $searchField[usr_turn];
			$usr_brod_date		= $searchField[usr_brod_date];
			$usr_content		= $searchField[usr_content];
			$usr_producer			= $searchField[usr_producer];
			$usr_grade				= $searchField[usr_grade];
			$usr_not_significant	= $searchField[usr_not_significant];
			$usr_keyword			= $searchField[usr_keyword];


		}else if( $type == 'XML' ){

			$pagenum = trim($render_data->pagenum);
			$pageitemcount = trim($render_data->pageitemcount);
			$requestxml = $render_data->requestxml;

			$searchField = $requestxml->search;
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

		$status = '2'; //콘텐츠 상태
		$is_deleted = 'N'; //삭제여부 - 존재
		array_push($_select , " c.* " );
		array_push($_from , " view_bc_content c " );
		array_push($_where , " c.status = $status " );
		array_push($_where , " c.is_deleted = '$is_deleted' " );

		//로우 메타데이터에서 컬럼 메타데이터로 변경 2014-06-19 이성용
		$renQuery = MetaDataClass::createMetaQuery('usr' , $ud_content_id , array(
			'select' => $_select,
			'from' => $_from,
			'where' => $_where,
			'order' => $_order
		) );
		$_select = $renQuery[select];
		$_from = $renQuery[from];
		$_where = $renQuery[where];
		$_order = $renQuery[order];

                $bs_content_id = $db->queryOne("select bs_content_id from bc_ud_content where ud_content_id ='$ud_content_id'");
                
                $renQuery = MetaDataClass::createMetaQuery('sys' , $bs_content_id , array(
			'select' => $_select,
			'from' => $_from,
			'where' => $_where,
			'order' => $_order
		) ); 
                
                $_select = $renQuery[select];
		$_from = $renQuery[from];
		$_where = $renQuery[where];
		$_order = $renQuery[order];
                
		if( !empty($contentid) ){
			array_push($_where , " c.content_id='$contentid' ");
		}else{
			if(!empty($searchField)){
				foreach($searchField as $key => $val)
				{
					if(strstr($key, 'usr')){
						$val = $db->escape($val);
						array_push($_where , " um.$key like '%$val%' ");
					}
				}
			}
			if( !empty($title) ){
				array_push($_where , " c.title like '%$title%' ");
			}

			if( !empty($ud_content_id) ){
				array_push($_where , " c.ud_content_id='$ud_content_id' ");
			}
		}

		$query = " select ".join(' , ',$_select)." from ".join(' , ',$_from);
		if( !empty($_where) ){
			$query .= " where ".join(' and ',$_where);
		}

		if( !empty($_order) ){
			$query .= " order by ".join(' , ',$_order);
		}else{
			$query .= " order by c.content_id desc";
		}

		$total = $db->queryOne("select count(*) from ( $query ) cnt ");

		$db->setLimit($limit, $start);
		$contentInfo = $db->queryAll( $query );

		$items = array();

		foreach($contentInfo as $conent)
		{
			$content_id = $conent['content_id'];


			$item = array();
//			$item['contentid'] = $content_id;


			array_push($items, $conent);
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