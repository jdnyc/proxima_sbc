<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');


//$id = $_POST['permission_id'];

$code = $_REQUEST['code'];
$parent_id = $_REQUEST['parent_id'];
$code_path = $_REQUEST['code_path'];
$description = $_REQUEST['description'];

$p_depth = $_REQUEST['p_depth'];
$use = $_REQUEST['use'];
$member_group_id = $_REQUEST['groups'];
$groups = json_decode($member_group_id, true);



try{

    $check= $db->queryOne("SELECT CODE FROM BC_PERMISSION WHERE (CODE = '$code' OR CODE_PATH = '$code_path')");
    if(!$check){
        $use = ($use == 'true') ? 1 : 0;
        $max_permission_id = $db->queryOne("SELECT MAX(id) FROM BC_PERMISSION");
        $permission_id = $max_permission_id+1;
        $description = $db->escape($description);
        $add_permission = $db->exec("
                    INSERT INTO BC_PERMISSION
                    (ID, CREATED_AT, UPDATED_AT, DELETED_AT, CODE, CODE_PATH, PARENT_ID, DESCRIPTION, P_DEPTH, USE, SHOW_ORDER)
                    VALUES('".$permission_id."', TO_CHAR(SYSDATE, 'yyyymmddhh24MISS'), TO_CHAR(SYSDATE, 'yyyymmddhh24MISS'), '', '".$code."', '".$code_path."', '".$parent_id."', '".$description."', '".$p_depth."', '".$use."', 1)
            ");


        if(!empty($groups)){
        foreach($groups as $group){
            $max_group_id = $db->queryOne("SELECT MAX(id) FROM BC_PERMISSION_GROUP");
            $group_id = $max_group_id+1;
            $add_permission_group = $db->exec("
                        INSERT INTO BC_PERMISSION_GROUP
                        (ID, PERMISSION_ID, CREATED_AT, UPDATED_AT, DELETED_AT, MEMBER_GROUP_ID)
                        VALUES('".$group_id."', $permission_id, TO_CHAR(SYSDATE, 'yyyymmddhh24miss'), TO_CHAR(SYSDATE, 'yyyymmddhh24miss'), '', '".$group."')
            ");
            }
        }
        echo json_encode(array(
            'success'=> true,
            'msg'=>'등록완료'
        ));
    
    }
    else 
    {
        echo json_encode(array(
            'success'=>false,
            'msg'=> '이미 등록이 되어 있습니다.'
        ));
    }

  
}
catch(Exception $e){
    echo json_encode(array(
		'success' => false,
        'msg' => _text('MN00022')." : ".$e
    ));
}
?>
