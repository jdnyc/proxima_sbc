<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/archive.class.php');

try
{
    $node = $_REQUEST['node'];
    
    if(!is_numeric($node) || empty($node)) {
        $node = 0;
    }
    
    $archive = new Archive();
    $data = $archive->getCategory(array(
        'node' => $node
    ));

    $datas = json_decode($data, true);

    echo json_encode($datas['data']);
}
catch(Exception $e)
{
    echo json_encode(array(
        'success'   =>  false,
        'msg'   =>  $e->getMessage()
    ));
}
?>