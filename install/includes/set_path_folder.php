<?php
if(isset($_POST)){
    if($_POST['mode'] == "check"){
        $v_result = _fn_check_path_folder($_POST['path_folder']);
        echo json_encode($v_result);    
    }else if($_POST['mode'] == "set"){
        $v_result = _fn_check_path_folder($_POST['path_folder']);
        
        if($v_result["success"]){
            _fn_change_storage_path_in_file($_POST['path_folder']);
            
			echo json_encode(array('success'=> true));        
        }else{
            echo json_encode(array('success'=> false));
        }    
    }
    
    
    
    
}
function _fn_check_path_folder($av_folder_path){
    if(is_dir($av_folder_path)){
        return array('success'=> true,'result' =>"$av_folder_path is a directory.");
    }else{
		$return_make_folder = @mkdir ($av_folder_path, 0777, true );
		if($return_make_folder){
			return array('success'=> true,'result' =>"$av_folder_path is created.");
		}else{
			return array('success'=> false,'result' =>"$av_folder_path is cannot created.");
		}
        
    }       
}


function _fn_change_storage_path_in_file($av_path){
	
	$doc = simplexml_load_file($_SERVER['DOCUMENT_ROOT'].'/lib/config.SYSTEM.xml');
	$before_web_path_local_value = (string)$doc->items->WEB_PATH_LOCAL_VALUE;
	
    $v_file_path = '../../config/config.php';
    $oldMessage =  $before_web_path_local_value;
    
    $deletedFormat = $av_path;
    
    //read the entire string
    $str=file_get_contents($v_file_path);
    
    //replace something in the file string - this is a VERY simple example
    $str=str_replace("$oldMessage", "$deletedFormat",$str);
    
    //write the entire string
    file_put_contents($v_file_path, $str);
    
    // file http.conf    
    $v_file_path2 = '../../../Apps/apache/conf/httpd.conf';
    
    //read the entire string
    $str2=file_get_contents($v_file_path2);
    
    //replace something in the file string - this is a VERY simple example
    $str2=str_replace("$oldMessage", "$deletedFormat",$str2);
    
    //write the entire string
    file_put_contents($v_file_path2, $str2);

	// file http.conf    
    $v_file_path3 = '../../../Apps/apache/conf/extra/httpd-proxima-resources.conf';
    
    //read the entire string
    $str3=file_get_contents($v_file_path3);
    
    //replace something in the file string - this is a VERY simple example
    $str3=str_replace("$oldMessage", "$deletedFormat",$str3);
    
    //write the entire string
    file_put_contents($v_file_path3, $str3);

	// update xml
	
	$doc->items->WEB_PATH_LOCAL_VALUE = str_replace("\\", "/", $av_path);
	file_put_contents($_SERVER['DOCUMENT_ROOT'].'/lib/config.SYSTEM.xml', $doc->asXML());
	
}
