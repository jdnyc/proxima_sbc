<?php
	
	//echo $_REQUEST['save_folder_url'];

	//$rtn_val = array("content_id"=>"1234","export_url"=>$_REQUEST['save_folder_url']);
	
	//echo json_encode($rtn_val);
		

try{
		$receive_xml = file_get_contents('php://input');
		$ip = $_SERVER['REMOTE_ADDR'];
		$json_decode = json_decode($receive_xml);

		//print_r($json_decode);

		if($json_decode)
		{		
			$resq = "";
			foreach ($json_decode as $key => $value) // Loop through the key value pairs
			{         
				//$resq .= "\n\r".$key."=".$value;                    // Add the key value pairs to the variable
			}
		}
		else 
		{
			$resq = "empty!";
		}


		$lenth = sizeof($_REQUEST);
		$datetime = date("Y-m-d H:i:s");

		file_put_contents('nle_explort'.date('Ymd').'.log',"\r\n\r\nSTART START ======================DATETIME : ".$datetime." IP ADDRESS : ".$ip."\r\n", FILE_APPEND);

		file_put_contents('nle_explort'.date('Ymd').'.log',"\r\n1:DATETIME : ".$datetime." IP ADDRESS : ".$ip."\r\n".$_REQUEST ." length : ".$lenth."\r\n", FILE_APPEND);
	//	file_put_contents('nle_explort'.date('Ymd').'.log',"\r\nDATETIME : ".$datetime." IP ADDRESS : ".$ip."\r\n".print_r($_REQUEST) ." length : ".$lenth, FILE_APPEND);
		file_put_contents('nle_explort'.date('Ymd').'.log',"\r\n2:DATETIME : ".$datetime." IP ADDRESS : ".$ip."\r\n".$receive_xml." length : ".$lenth."\r\n", FILE_APPEND);

		file_put_contents('nle_explort'.date('Ymd').'.log',"\r\n3:DATETIME : ".$datetime." IP ADDRESS : ".$ip."\r\n".$resq." length : ".$lenth."\r\n", FILE_APPEND);

		foreach ($_REQUEST as $key => $value) // Loop through the key value pairs
		{         
			$req .= "\n\r$key=$value";                    // Add the key value pairs to the variable
		}

		file_put_contents('nle_explort'.date('Ymd').'.log',"\r\nIP ADDRESS : ".$ip."\r\n".$req, FILE_APPEND);
		//sleep(2);
		//echo "true";
		$id = "12345";
		$rtn_msg = array("id"=>$id,"errorcode"=>0,"errormsg"=>"","file_path"=>$file_path);	
	//	$rtn_msg = array("id"=>$id,"errorcode"=>0,"errormsg"=>"");
		
		echo json_encode($rtn_msg);
	}
	catch (Exception $e)
	{
		$rtn_msg = array("id"=>$id,"errorcode"=>1,"errormsg"=>$e->getMessage(),"file_path"=>$file_path);		
		echo json_encode($rtn_msg);
	}
//
?>