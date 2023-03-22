<?php 

    session_start();
    require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/lang.php');



try{


    $start= $_POST['start'] ?? 0;
    $limit = $_POST['limit'];
    if(empty($limit)){
        $limit = 30;
    }
    $query = 'select * from DOWNLOADS 
                where published = 1 
                order by show_order asc';

    $db->setLimit($limit,$start);
    $data = $db->queryAll($query);
    echo json_encode(array(
        'success'	=> true,
        'data'		=> $data
    ));
}
catch(Exception $e){
    $msg = $e->getMessage();

    switch($e->getCode()){
        case ERROR_QUERY:
            $msg = $msg.'('.$db->last_query.')';
        break;
    }
    die(json_encode(array(
        'success'=> false,
        'msg' => $msg
    )));
}
?>


