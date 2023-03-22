<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');


/*
	http://mam.gemiso.com/result.php?filepath=/Volumes/xsan/test/12345.mxf&errorcode=0&errormsg=success
*/

/*
	$id = "";
	$file_path  = $_REQUEST['filepath'];
	$errorcode  = $_REQUEST['errorcode'];
	$errormsg   = $_REQUEST['errormsg'];

	if( !empty($file_path) && ($errorcode == 0 || !empty($errorcode)) && !empty($errormsg) )
	{
		$rtn_msg = array("id"=>$id,"errorcode"=>0,"errormsg"=>"errormsg value: $errormsg ","filepath"=>$file_path);	
	}
	else 
	{
		$rtn_msg = array("id"=>$id,"errorcode"=>1,"errormsg"=>"get params Error! filepath : $file_path,  errorcode : $progress  , errormsg : $errormsg ","filepath"=>$file_path);	
	}

	$datetime = date('YmdHis');
	
	echo json_encode($rtn_msg);
	@file_put_contents('nle_result_'.date('Ymd').'.log',"\r\n\r\nnle_filepath_ START ======================DATETIME : ".$datetime." IP ADDRESS : ".$ip."\r\n
	REQUEST :".print_r($_REQUEST,true)."\r\n", FILE_APPEND);

*/

	$datetime = date('YmdHis');

	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/nle_result_'.date('Ym').'.log',"\r\n\r\n[".date('Y-m-d H:i:s')."]nle_filepath_ START ======================DATETIME : ".$datetime." IP ADDRESS : ".$ip."\r\n
	REQUEST :".print_r($_REQUEST,true)."\r\n", FILE_APPEND);

	$id = "";
	$file_path  = $_REQUEST['filepath'];
	$errorcode  = $_REQUEST['errorcode'];
	$errormsg   = $_REQUEST['errormsg'];

	$filepath_array = explode('/', $file_path);
	$ori_filename = array_pop($filepath_array);
	$path = explode('_', $file_path);
	$file_name = array_pop($path);
    $content_id = str_replace('.mxf', '', $file_name);
    
    $fileInfo = \Api\Models\MapFile::where('file_key' , $ori_filename)->first();

    if( empty($fileInfo) ){
        //정상적이지 않은 에러상황
        $rtn_msg = array("id"=>$id,"errorcode"=>$errorcode,"errormsg"=>"not found fileinfo : $file_path,  errorcode : $errorcode  , errormsg : $errormsg ","filepath"=>$file_path);	        
	    @file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/nle_result_'.date('Ym').'.log',"\r\n\r\n[".date('Y-m-d H:i:s')."]nle_filepath_ rtn_msg ======================rtn_msg :".print_r($rtn_msg,true)."\r\n", FILE_APPEND);
        die(json_encode($rtn_msg));
    }
	$job_priority = 1;
	$channel = 'fcp';
	// $explode_file_path = explode('/', $file_path);
	// $source_path = array_pop($explode_file_path);
    // @file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/nle_result_'.date('Ym').'.log',"\r\n\r\n[".date('Y-m-d H:i:s')."]nle_filepath_ content_id ======================".$content_id."\r\n", FILE_APPEND);
	// $get_info = $db->queryRow("select * from bc_content where content_id = $content_id");
	// $ud_content_id = $get_info['ud_content_id'];
    // $user_id = $get_info['reg_user_id'];
    
    $user_id = $fileInfo->user_id;
    $content_id = $fileInfo->content_id;

	$task_mgr = new TaskManager($db);
	//$task_mgr->set_task_params($ud_content_id, 'online', '');
    $get_info = $db->queryRow("select * from bc_content where content_id = '$content_id'");
    if( !empty($get_info['reg_user_id']) ){
        $user_id = $get_info['reg_user_id'];
    }
 

	if( !empty($file_path) && ($errorcode == 0 || !empty($errorcode)) && !empty($errormsg) )
	{
		if($errormsg == 'Ok')
		{
			// 정상일때 작업시작

			$rtn_msg = array("id"=>$id,"errorcode"=>0,"errormsg"=>"errormsg value: $errormsg ","filepath"=>$file_path);
            @file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/nle_result_'.date('Ym').'.log',"\r\n\r\n[".date('Y-m-d H:i:s')."]nle_filepath_ insert_task_query_outside_data ======================"."($content_id, $channel, $job_priority, $user_id, $ori_filename)"."\r\n", FILE_APPEND);
			$task_id = $task_mgr->insert_task_query_outside_data($content_id, $channel, $job_priority, $user_id, $ori_filename);
            @file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/nle_result_'.date('Ym').'.log',"\r\n\r\n[".date('Y-m-d H:i:s')."]nle_filepath_ task_id ======================".$task_id."\r\n", FILE_APPEND);
			
		}
		else
		{
			//실패일 경우 에러처리.
			$rtn_msg = array("id"=>$id,"errorcode"=>$errorcode,"errormsg"=>"get params Error! filepath : $file_path,  errorcode : $errorcode  , errormsg : $errormsg ","filepath"=>$file_path);				
		}
	}
	else 
	{
		//정상적이지 않은 에러상황
		$rtn_msg = array("id"=>$id,"errorcode"=>$errorcode,"errormsg"=>"get params Error! filepath : $file_path,  errorcode : $errorcode  , errormsg : $errormsg ","filepath"=>$file_path);	
	}



	echo json_encode($rtn_msg);
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/nle_result_'.date('Ym').'.log',"\r\n\r\n[".date('Y-m-d H:i:s')."]nle_filepath_ rtn_msg ======================rtn_msg :".print_r($rtn_msg,true)."\r\n", FILE_APPEND);

?>