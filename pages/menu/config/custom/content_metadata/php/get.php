<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/util.php';
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

switch($_POST['action']) {

    case 'content_type_list':
		get_content_type_list();
	break;
    
	case 'content_field_list':
		get_content_field_list($_POST['content_type_id']);
	break;

	case 'content_type_list_workflow':
		get_content_type_list_workflow();
	break;
    
    default:
        echo json_encode(array(
            'success' => false,
            'msg' => 'no more action 액션이 정의 되어있지 않습니다.'
        ));
    break;
}


function get_content_field_list($content_type_id) {
    global $db;
    
    $all = $db->queryAll('select * from bc_sys_meta_field where bs_content_id=' . $content_type_id . ' order by show_order');
    
    echo json_encode(array(
        'success' => true,
        'total' => count($all),
        'data' => $all
    ));
}

function get_content_type_list() {
    global $db;
    
    $all = $db->queryAll('select * from bc_bs_content order by show_order');
   
    echo json_encode(array(
        'success' => true,
        'total' => count($all),
        'data' => $all
    ));
}

function get_content_type_list_workflow(){
	global $db;
    
    $all = $db->queryAll('select * from bc_bs_content order by show_order');
   
	$all[] = array(
		'bs_content_id' => '0',
		'bs_content_title' => _text('MN00008')
	);
    echo json_encode(array(
        'success' => true,
        'total' => count($all),
        'data' => $all
    ));
}

?>