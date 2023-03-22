<?php 
    
    session_start();
    require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/lang.php');
    $action =($_POST['action'] !='') ? $_POST['action'] : 'delete';
    $delete = $action;
    // var_dump($delete);
    // var_dump($_POST['id']);
    // $title 


try{
    if($action == 'delete'){
            $query = "DELETE FROM DOWNLOADS where id = ".$_POST['id'];
    }
    echo $query;
    $data = $db-> exec($query);

    echo json_encode(array(
        'success'	=> true,
        'data'		=> $data
    ));
}catch(Exception $e){
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