<?php
include_once('functions.php');
if(isset($_POST)){
    $v_result = _fn_check_db_connection($_POST);
    if($v_result){
        echo json_encode(array("success" => true, "result" => "Connection successfully!"));
    }else{
        echo json_encode(array("success" => false, "result" => "Failed !!! Please check again your information!"));
    }
    
    
}

