<?php

include_once('functions.php');
if(isset($_POST)){
    $v_result = _fn_check_db_connection($_POST);
    if($v_result){
		
		// 0: Postgres || 1: Oracle
		if($_POST['db_driver'] == '0'){
			$v_result_create_user_schema = fn_create_user_and_schema($_POST);
		}else{
			$v_result_create_user_schema = fn_create_user_and_set_tablespace($_POST);
		}
        if($v_result_create_user_schema){
            echo json_encode(array("success" => true, "result" => "Create new database user successfully!"));  
        }else{
            echo json_encode(array("success" => false, "result" => "Failed !!! Please check again your information!"));    
        }
    }else{
        echo json_encode(array("success" => false, "result" => "Failed !!! Please check again your information!"));
    }
    
    
}
