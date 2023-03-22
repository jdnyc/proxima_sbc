<?php

function getCueSheetItemList($request)
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
                        $media_type	= $render_data[media_type];
                        $cuesheet_id	= $render_data[cuesheet_id];


		}else if( $type == 'XML' ){

			$pagenum	= $render_data->pagenum;
			$pageitemcount	= $render_data->pageitemcount;
                        $media_type	= $render_data->media_type;
                        $cuesheet_id	= $render_data->cuesheet_id;

		}else{
			throw new Exception ('invalid request', 101 );
		}

		if( is_null($media_type) || is_null($cuesheet_id) ){
			throw new Exception ('invalid request', 101 );
		}

                array_push($_select , " c.CUESHEET_ID,c.SHOW_ORDER,c.TITLE,c.CONTENT_ID,c.CUESHEET_CONTENT_ID, COALESCE(c.CONTROL, 'empty') CONTROL, m.media_type,m.path,m.filesize,COALESCE(bt.status, 'empty') status ");
                if(strtoupper($media_type) == 'A') {
                    array_push($_select , " COALESCE(s.SYS_DURATION, '00:00:00:00') duration");
                    array_push($_select , " us.*");
                    array_push($_from , " bc_sysmeta_sound s ");
                    array_push($_from , " bc_usrmeta_nps_soundsrc us ");
                    array_push($_where, " s.sys_content_id = c.content_id");
                    array_push($_where, " us.usr_content_id = c.content_id");
                } else if(strtoupper($media_type) == 'M') {
                    array_push($_select, " COALESCE(s.SYS_VIDEO_RT, '00:00:00:00') duration");
                    array_push($_select , " um.*");
                    array_push($_from, " bc_sysmeta_movie s ");
                    array_push($_from , " bc_usrmeta_nps_master um ");
                    array_push($_where, " s.sys_content_id = c.content_id");
                    array_push($_where, " um.usr_content_id = c.content_id");
                }
                array_push($_from , " bc_cuesheet_content c,bc_media m, bc_task bt  ");
                array_push($_order , " c.SHOW_ORDER asc ");
                if( !empty($cuesheet_id) ){
                        array_push($_where , " bt.task_id (+)= c.task_id ");
                        array_push($_where , " c.content_id=m.content_id and m.media_type='original' and c.cuesheet_id='$cuesheet_id' ");
                }


		$query = " select ".join(' , ',$_select)." from ".join(' , ',$_from);

		if(!empty($_where)){
			$query .= " where ".join(' and ',$_where);
		}
		$order	= " order by ".join(' , ',$_order);

		$total = $db->queryOne("select count(*) from ( $query ) cnt ");

		$contents = $db->queryAll($query.$order);
                // status 값을 0으로 전송하기 위해 처리

		if($type == 'JSON'){

			$response['totalcount'] = $total;
			$response['items'] = $contents;

		}else{

			$response->addChild('totalcount', $total);
			$items_xml = $response->addChild('items');
			foreach($contents as $item)
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