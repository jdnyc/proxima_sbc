<?php
set_time_limit(0);
//var_dump($_POST);
include_once('functions.php');
if(isset($_POST)){
    $v_result = _fn_check_db_connection($_POST);
    
    //connected
    if($v_result){
        
        // write config for database.php
        //$v_write_config_result = fn_write_config($_POST);
        //if($v_write_config_result){
            // Install database schema and data
			
			// 0: Postgres || 1: Oracle
			if($_POST['db_driver'] == '0'){
				$v_result_install_user_schema = fn_install_schema_and_data($_POST);
			}else{
				$v_result_install_user_schema = fn_install_schema_and_data_oracle($_POST);
			}
            if($v_result_install_user_schema){
                echo json_encode(array("success" => true, "result" => "Install successfully!"));  
            }else{
                echo json_encode(array("success" => false, "result" => "Failed !!! Please check again your information!"));    
            }
        //}   
		
    }else{
        echo json_encode(array("success" => false, "result" => "Failed !!! Please check again your information!"));
    }
 
}