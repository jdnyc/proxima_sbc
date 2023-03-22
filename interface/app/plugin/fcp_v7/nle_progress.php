<?php
/*

http://mam.gemiso.com/progress.php?filepath=/Volumes/xsan/test/12345.mxf&progress=25


*/
	//file_path get 으로 받고 
	//progress   get 으로 받고 


	//$id        = "12345";
    $file_path = $_REQUEST['filepath'];
	$progress  = $_REQUEST['progress'];

	if( $file_path && ($progress==0 || !empty($progress)))
	{
		$rtn_msg = array("id"=>$id,"errorcode"=>0,"errormsg"=>"progress value: $progress ","filepath"=>$file_path);	
	}
	else 
	{
		$rtn_msg = array("id"=>$id,"errorcode"=>1,"errormsg"=>"get params Error! filepath : $file_path,  progress : $progress ","filepath"=>$file_path);	
	}
	

	//DB 저장 따러

	echo json_encode($rtn_msg);
	@file_put_contents('nle_progress_'.date('Ymd').'.log',"\r\n\r\nnle_filepath_ START ======================DATETIME : ".$datetime." IP ADDRESS : ".$ip."\r\n
	REQUEST :".print_r($_REQUEST,true)."\r\n", FILE_APPEND);

?>