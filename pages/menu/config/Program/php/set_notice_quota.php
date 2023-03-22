<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

$quota = $_REQUEST['quota'];

try
{
    
    if(is_null($quota)) throw new Exception("invalid value");
    
    $db->exec("update bc_code set code = '$quota' where code_type_id = 233");	    
    
    echo json_encode(array(
		'success' => true,
		'msg' => '쿼터값이 설정되었습니다'
    ));
} catch( Exception $e) {
   $msg = $e->getMessage();

    switch($e->getCode())
    {
	    case ERROR_QUERY:
		    $msg = $msg.'( '.$db->last_query.' )';
	    break;
    }

    die(json_encode(array(
	    'success' => false,
	    'msg' => $msg
    )));
}
?>
