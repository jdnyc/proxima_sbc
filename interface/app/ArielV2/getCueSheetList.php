<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/pages/bis/bis.class.php');

function getCueSheetList($request)
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
			$broad_date	= $render_data[broad_date];
                        $cuesheet_title	= $render_data[cuesheet_title];
                        $media_type	= $render_data[media_type];
                        $prog_id	= $render_data[prog_id];
			$subcontrol_room= $render_data[subcontrol_room];

		}else if( $type == 'XML' ){

			$pagenum	= $render_data->pagenum;
			$pageitemcount	= $render_data->pageitemcount;
			$broad_date     = $render_data->broad_date;
                        $cuesheet_title	= $render_data->cuesheet_title;
                        $media_type	= $render_data->media_type;
                        $prog_id	= $render_data->prog_id;
			$subcontrol_room= $render_data->subcontrol_room;

		}else{
			throw new Exception ('invalid request', 101 );
		}

		if( is_null($pagenum) || empty($pageitemcount) || empty($media_type) ){
			throw new Exception ('invalid request', 101 );
		}

		$start = 0;
		$limit = $pageitemcount;
		if($pagenum) $start = ( $pagenum - 1 ) * $pageitemcount;

                array_push($_select , " c.* ");
                array_push($_from , " BC_CUESHEET c ");
                array_push($_order , " c.CUESHEET_ID desc ");
                array_push($_where , " c.TYPE = '$media_type' ");
                
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


		$query = " select ".join(' , ',$_select)." from ".join(' , ',$_from);
                
		if(!empty($_where)){
			$query .= " where ".join(' and ',$_where);
		}
		$order	= " order by ".join(' , ',$_order);

		$total = $db->queryOne("select count(*) from ( $query ) cnt ");

		$db->setLimit($limit, $start);
		$cuesheets = $db->queryAll($query.$order);
		
		if($media_type == 'M') {
		    // 편성길이의 싱크를 맞추기 위해서 DUNET 인터페이스를 통해서 편성길이 정보를 받아옴
		    foreach($cuesheets as $key=>$val){
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

			$cuesheets[$key]['duration'] = $duration;
		    }
		}
		
		if($type == 'JSON'){

			$response['totalcount'] = $total;
			$response['totalpage'] = ceil( $total / $pageitemcount );
			$response['pagenum'] = $pagenum;

			$response['items'] = $cuesheets;

		}else{

			$response->addChild('totalcount', $total);
			$response->addChild('totalpage', ceil( $total / $pageitemcount ));
			$response->addChild('pagenum', $pagenum);
			$items_xml = $response->addChild('items');
			foreach($cuesheets as $item)
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