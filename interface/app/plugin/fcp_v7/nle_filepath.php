<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

	//id는 받고

	// 파일명"/Users/bob/Movies/MAM/" +test_시간14자리.mxf
	//사용자 admin bob test ok! 나머지는 ERROR 처리

	$id       = $_REQUEST['user_id'];
	
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/nle_filepath_'.date('Ym').'.log',"\r\n[".date('Y-m-d H:i:s')."] nle_filepath_ START ======================DATETIME : ".$datetime." IP ADDRESS : ".$_SERVER['REMOTE_ADDR']."\r\n
	REQUEST :".print_r($_REQUEST,true)."\r\n", FILE_APPEND);

//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/logs/apc_related/ftp_complete_send.'.date('Ymd-H').'.log', date('Y-m-d H:i:s').'['.$_SERVER['REMOTE_ADDR']."] SEND CALL XML".$xml."\n\r\n\n", FILE_APPEND);

	if(!empty($id))
	{
		//$content_id = getSequence('SEQ_CONTENT_ID');
		//$file_path = '/Volumes/RENDER/'.date('YmdHis').'_'.$content_id.'.mxf';
		
		preg_match_all('/\\(([^{}]+)\\)/', $id, $matches);
        $inId = implode('', $matches[1]);		
        if( !empty($inId) ){
            $userId = $inId;
        }else{
            $userId =$id;
        }
        $uploadPath = config('plugin')['upload_path'];
		$fileKey = date('YmdHis').'_'.$userId.'_'.rand().'.mxf';


        $file = new \Api\Models\MapFile();
        $file->file_key = $fileKey;
        $file->user_id = $userId;
        $file->remote_ip = $_SERVER['REMOTE_ADDR'];
        $file->save();

        $file_path = $uploadPath.'/'.$fileKey;

		$rtn_msg = array("id"=>$userId,"errorcode"=>0,"errormsg"=>"","filepath"=>$file_path);
	}
	else
	{
		$rtn_msg = array("id"=>$id,"errorcode"=>1,"errormsg"=>"unknown user id!","filepath"=>$file_path);
	}
	
	$datetime = date('YmdHis');

	echo json_encode($rtn_msg);
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/nle_filepath_'.date('Ym').'.log',"\r\n\r\n[".date('Y-m-d H:i:s')."]nle_filepath_ END ======================DATETIME : ".$datetime." IP ADDRESS : ".$ip."\r\n
	REQUEST :".print_r($_REQUEST,true)."\r\n", FILE_APPEND);

?>
