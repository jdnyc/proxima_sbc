<?php

function SaveRundown($request)
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

			$rundownname		= $render_data[rundownname];
			$registdate	= $render_data[registdate];

			$subtitle		= $render_data[subtitle];
			$programid		= $render_data[programid];
			$programname		= $render_data[programname];
			$programsequence		= $render_data[programsequence];
			$pdusername		= $render_data[pdusername];

			$items		= $render_data[items];

		}else if( $type == 'XML' ){

			$rundownname		= $render_data->rundownname;
			$registdate	= $render_data->registdate;

			$subtitle		= $render_data->subtitle;
			$programid		= $render_data->programid;
			$programname		= $render_data->programname;
			$programsequence		= $render_data->programsequence;
			$pdusername		= $render_data->pdusername;
			$brodymd		= $render_data->brodymd;

			$items		= $render_data->items;

		}else{
			throw new Exception ('invalid request', 101 );
		}

		$rundown_id;
		$rundown_title =$rundownname;
		$regist_date =$registdate;
		$status = '';
		$progid = $programid;
		$prognm =$programname;
		$progseq =$programsequence;
		$subprogid ;
		$subprognm;
		$pdnm = $pdusername;
		$brodymd = $brodymd;

		$query = " insert into RUNDOWN (RUNDOWN_ID,RUNDOWN_TITLE,REGIST_DATE,STATUS,PROGID,PROGNM,PROGSEQ,SUBPROGID,SUBPROGNM,PDNM,BRODYMD) values ('$rundown_id','$rundown_title','$regist_date','$status','$progid','$prognm','$progseq','$subprogid' ,'$subprognm','$pdnm','$brodymd')";
		$r = $db->exec($query);

		foreach($items as $item)
		{
			$rundown_index	= $item[rundown_index];
			$duration		= $item[duration];
			$status			= $item[status];
			$play_code		= $item[play_code];
			$content_id		= $item[content_id];

			$query = " insert into RUNDOWN_CLIP (RUNDOWN_ID,RUNDOWN_INDEX,DURATION,STATUS,PLAY_CODE,CONTENT_ID ) values ('$rundown_id','$rundown_index','$duration','$status','$play_code','$content_id' )";
			$r = $db->exec($query);
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