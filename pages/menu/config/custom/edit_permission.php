<?php 
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');


$id = $_REQUEST['permission_id'];
$code = $_REQUEST['code'];
$code_path = $_REQUEST['code_path'];
$description = $_REQUEST['description'];
$p_depth = $_REQUEST['p_depth'];
$parent_id = $_REQUEST['parent_id'];
$use = $_REQUEST['use'];
$member_group_id = $_REQUEST['groups'];
$groups = json_decode($member_group_id, true);


try{

    $use = ($use == 'true') ? 1 : 0;
    $edit_permission = $db->exec("
            UPDATE BC_PERMISSION
            SET CODE = '$code',
                CODE_PATH = '$code_path',
                DESCRIPTION = '$description',
                P_DEPTH = '$p_depth',
                PARENT_ID = '$parent_id',
                USE = '$use',
                UPDATED_AT = TO_CHAR(SYSDATE, 'yyyymmddhh24miss')
            WHERE ID = '$id'
        ");

    $delete_group= $db->exec("DELETE FROM BC_PERMISSION_GROUP WHERE PERMISSION_ID = $id");
    if(!empty($groups)){
    foreach($groups as $group){
        $max_group_id = $db->queryOne("SELECT MAX(id) FROM BC_PERMISSION_GROUP");
        $group_id = $max_group_id+1;
        $edit_permission_group = $db->exec("
                        INSERT INTO BC_PERMISSION_GROUP
                        (ID, PERMISSION_ID, CREATED_AT, UPDATED_AT, DELETED_AT, MEMBER_GROUP_ID)
                        VALUES('".$group_id."', $id, TO_CHAR(SYSDATE, 'yyyymmddhh24miss'), TO_CHAR(SYSDATE, 'yyyymmddhh24miss'), '', '".$group."')
    ");

    }
 
    }
    echo json_encode(array(
        'success'=>true,
        'msg'=> '수정 완료'
    ));
}
catch(Exception $e){
    echo json_encode(array(
        'success'=> false,
        'msg' => _text('MN00022')." : ".$e
    ));
}

?>
