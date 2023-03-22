<?php

function getIngestProgramList($request)
{
	global $db;

	try{
		$Interface = new InterfaceClass();
		InterfaceClass::_LogFile($filename,'getIngestProgramList request',$request);


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

			$user_id	= $render_data[user_id];
			$action 	= $render_data[action];

		}else if( $type == 'XML' ){

			$user_id	= $render_data->user_id;
			$action 	= $render_data->action;

		}else{
			throw new Exception ('invalid request', 101 );
		}

		if($action == 'all') {
		    $query = "select c.category_title as prog_nm, p.path as prog_id 
				from bc_category c, path_mapping p
				where c.category_id = p.category_id ";
		} else {
		    $query = "select c.category_title as prog_nm, p.path as prog_id 
				from bc_category c, path_mapping p, user_mapping u
				where c.category_id = p.category_id and p.category_id = u.category_id and u.user_id = '$user_id'";
		}
    
                $programs = $db->queryAll($query);
                $total = $db->queryOne("select count(*) from ( $query ) cnt ");
                
		$result = array();
    
                foreach ($programs as $program) {
                    $result[] = array(
                            'prog_nm'	=> $program['prog_nm'],
                            'prog_id'	=> $program['prog_id']
                    );
                }
                
		if($type == 'JSON'){

			$response['totalcount'] = $total;

			$response['items'] = $result;

		}else{

			$response->addChild('totalcount', $total);
			$items_xml = $response->addChild('items');
			foreach($result as $item)
			{
				$item_xml = $items_xml->addChild('item');
				foreach($item as $key => $val )
				{
					$item_xml->addChild($key, $val );
				}
			}
		}

		$return = $Interface->ReturnResponse($type,$response);
		InterfaceClass::_LogFile($filename,'getIngestProgramList return',$return);
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

		InterfaceClass::_LogFile($filename,'getIngestProgramList return',$return);
		return $return;
	}
}
?>