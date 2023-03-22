<?php 

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$id = $_REQUEST['id'];

try{
    if($id)
    {   $delete_permission = $db->exec("UPDATE BC_PERMISSION 
                                        SET DELETED_AT = TO_CHAR(SYSDATE, 'yyyymmddhh24miss'),
                                            USE = 0,
                                            CODE_PATH = ''
                                        WHERE ID = $id");
        //$delete_permission = $db->exec("DELETE FROM BC_PERMISSION WHERE ID =$id");
        
        $delete_group= $db->exec("UPDATE BC_PERMISSION_GROUP
                                  SET DELETED_AT = TO_CHAR(SYSDATE, 'yyyymmddhh24miss')
                                  WHERE PERMISSION_ID = $id");
                                  
        //$delete_group= $db->exec("DELETE FROM BC_PERMISSION_GROUP WHERE PERMISSION_ID = $id");
    }

    echo json_encode(array(
        'success'=> true,
        'permission'=>$delete_permission,
        'group'=>$delete_group
    ));
}
catch(Exception $e)
{
	echo json_encode(array(
		'success' => false,
        'msg' => _text('MN00022')." : ".$e
    ));
}

?>