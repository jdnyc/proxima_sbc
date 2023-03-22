<?php
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");

$query = "select category_id, path from path_mapping";

$path = $db -> queryAll($query);
 file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/edius_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] path_array ===> '.print_r($path, true)."\r\n", FILE_APPEND);
$path_array = array();

foreach($path as $info) {
    
    $index = $info['category_id'];
    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/edius_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] path_array ===> '.$index."\r\n", FILE_APPEND);
    $path_array[$index] = $info['path'];
}

file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/edius_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] category_array ===> '.print_r($path_array, true)."\r\n", FILE_APPEND);

echo '{"success":"true", "data": '.json_encode($path_array)."}";

?>

