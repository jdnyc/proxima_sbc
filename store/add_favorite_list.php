<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

try {
    $user_id = $_SESSION['user']['user_id'];
    $action = $_REQUEST['action'];
    $contents = json_decode($_REQUEST['contents']);
    $content_type = $_REQUEST['content_type'];
    
    switch ($action) {
        case 'add' :
            $favorite_category_id = $_REQUEST['favorite_category_id'];
            
            foreach ($contents as $content) {
                 $show_order = date('YmdHis');
                 
                 $is_list = $db->queryOne("select content_id from bc_favorite where user_id ='$user_id' and content_id='$content'");
                 
                 if($is_list) {
                     $db->exec("update bc_favorite set show_order = '$show_order', favorite_category_id = '$favorite_category_id' where user_id='$user_id' and content_id ='$content'");
                 } else {
                     $db->exec("insert into bc_favorite (user_id, show_order, content_id, favorite_category_id, content_type)  values ('$user_id', '$show_order' , '$content', '$favorite_category_id', '$content_type')");
                 }
            }
            
            break;
        case 'remove' : 
            
            foreach ($contents as $content) {
                $db->exec("delete from bc_favorite where user_id='$user_id' and content_id = '$content'");
            }
            
            break;
    } 
    
    echo json_encode(array(
        'success' => true,
        'action' => $action
    ));
        
} catch(Exception $e) {
	die(json_encode( array(
		'success' => false,
		'msg' => $e->getMessage()
	)));
}

?>
