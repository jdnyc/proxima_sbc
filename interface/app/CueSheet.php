<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/pages/bis/bis.class.php');

function CueSheet($request)
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
			$broad_date	= $render_data[brod_date];
			$request_type	= $render_data[request_type];
			$cuesheet_id	= $render_data[cuesheet_id];
                        $cuesheet_title	= $render_data[cuesheet_title];
                        $media_type	= $render_data[media_type];
                        $prog_id	= $render_data[prog_id];
			$subcontrol_room= $render_data[subcontrol_room];

		}else if( $type == 'XML' ){

			$pagenum	= $render_data->pagenum;
			$pageitemcount	= $render_data->pageitemcount;
			$broad_date      = $render_data->brod_date;
			$request_type	= $render_data->request_type;
			$cuesheet_id	= $render_data->cuesheet_id;
                        $cuesheet_title	= $render_data->cuesheet_title;
                        $media_type	= $render_data->media_type;
                        $prog_id        = $render_data->prog_id;
			$subcontrol_room= $render_data->subcontrol_room;

		}else{
			throw new Exception ('invalid request', 101 );
		}

		if( is_null($pagenum) || empty($pageitemcount) || empty($request_type) ){
			throw new Exception ('invalid request', 101 );
		}

		$start = 0;
		$limit = $pageitemcount;
		if($pagenum) $start = ( $pagenum - 1 ) * $pageitemcount;

		if( $request_type == 'list' ){
			array_push($_select , " c.* ");
			array_push($_from , " BC_CUESHEET c ");
			array_push($_order , " c.CUESHEET_ID desc ");
                        array_push($_where , " c.type=upper('$media_type') ");
			if( !empty($broad_date) ){
				array_push($_where , " c.BROAD_DATE='$broad_date' ");
			}
                        if( !empty($cuesheet_title) ) {
                                array_push($_where, " c.CUESHEET_TITLE like '%$cuesheet_title%'");
                        }
                        if( !empty($prog_id) ) {
                                array_push($_where, " c.PROG_ID = '$prog_id'");
                        }
			if( !empty($subcontrol_room) ) {
                                array_push($_where, " c.SUBCONTROL_ROOM = '$subcontrol_room'");
                        }
		}else if( $request_type == 'item' ){
			array_push($_select , " c.CUESHEET_ID,c.SHOW_ORDER,c.TITLE,c.CONTENT_ID,c.CUESHEET_CONTENT_ID,m.media_type,m.path,m.filesize,COALESCE(bt.status, 'empty') status ");
                        if(strtoupper($media_type) == 'A') {
                            array_push($_select , " COALESCE(s.SYS_DURATION, '00:00:00:00') duration");
                            array_push($_from , " bc_sysmeta_sound s ");
                            array_push($_where, " s.sys_content_id = c.content_id");
                        } else if(strtoupper($media_type) == 'M') {
                            array_push($_select, " COALESCE(s.SYS_VIDEO_RT, '00:00:00:00') duration");
                            array_push($_from, " bc_sysmeta_movie s ");
                            array_push($_where, " s.sys_content_id = c.content_id");
                        }
			array_push($_from , " bc_cuesheet_content c,bc_media m, bc_task bt  ");
			array_push($_order , " c.SHOW_ORDER asc ");
			if( !empty($cuesheet_id) ){
                                array_push($_where , " bt.task_id (+)= c.task_id ");
				array_push($_where , " c.content_id=m.content_id and m.media_type='original' and c.cuesheet_id='$cuesheet_id' ");
			}
		}

		$query = " select ".join(' , ',$_select)." from ".join(' , ',$_from);

		if(!empty($_where)){
			$query .= " where ".join(' and ',$_where);
		}
		$order	= " order by ".join(' , ',$_order);

		$total = $db->queryOne("select count(*) from ( $query ) cnt ");

		$db->setLimit($limit, $start);
		$contentInfo = $db->queryAll($query.$order);

                if( $request_type == 'item' ){
                    foreach($contentInfo as $key => $content) {
                        $src_path = $content['path'];
                        $arr_src_path = explode('/', $src_path);
                        $val = array_pop($arr_src_path);
                        $contentInfo[$key]['path'] = $val;
                    }
                }

		// 미디어 큐시트 목록 조회일 경우 편성길이를 조회하여 추가
		if( $request_type == 'list' && $media_type == 'M') {
		    foreach($contentInfo as $key=>$val){
			$trff_no = $val['trff_no'];
			$trff_seq = $val['trff_seq'];
			$trff_ymd = $val['trff_ymd'];

			$bis = new BIS();
			$result =  $bis->GetDuration(array(
					    'trff_no'	=> $trff_no,
					    'trff_seq'	=> $trff_seq,
					    'trff_ymd'	=> $trff_ymd
				    ) );
			$datas = json_decode($result, true);
			$duration = $datas[0]['data']['duration'];
			$duration = substr($duration, 0, 2).':'.substr($duration, 2, 2).':'.substr($duration, 4, 2).':'.substr($duration, 6, 2);

			$contentInfo[$key]['duration'] = $duration;
		    }
		}
                // status 값을 0으로 전송하기 위해 처리

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