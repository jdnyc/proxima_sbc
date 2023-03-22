<?php

use Proxima\core\Path;

session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/lang.php');

try {
    $type = $_POST['type'];
    if (empty($type)) {
        $type = $_GET['type'];
    };
    
    switch ($type) {
        case 'attach':

            $path = $_POST['path'];
            if (empty($path)) {
                $path = $_GET['path'];
            };
            $name = $_POST['name'];
            if (empty($name)) {
                $name = $_GET['name'];
            };
            $ordId = $_POST['ord_id'];
            if (empty($ordId)) {
                $ordId = $_GET['ord_id'];
            };

			 $fileid = $_POST['fileid'];
            if (empty($fileid)) {
                $fileid = $_GET['fileid'];
            };


            // $storage_id = $db->queryOne("select * from tb_ord_file where file_path=$path");
            // $storageId = $db->queryOne("select storage_id from tb_ord_file where ord_id = " . $ordId . " AND file_name = '" . $name . "' AND file_path= '" . $path . "'");
            $fileInfo = $db->queryRow("select * from tb_ord_file where  id= '{$fileid}'");
			$storageId = $fileInfo['storage_id'];
			$name = $fileInfo['file_name'];
			$path = $fileInfo['file_path'];
            if(empty($storageId)){
                //send_attachment2($name, $path);
                downloadCurl($name, $path, '/mnt/main/CMS/upload/Temp/');
            }else{
                $storage_info = $db->queryRow("
                SELECT	*
                FROM	BC_STORAGE
                WHERE	STORAGE_ID	= $storageId
                ");
                
                if (SERVER_TYPE == 'linux') {
                    $storage_path = $storage_info['path_for_unix'];
                } else if (strtoupper($storage_info['type']) === 'NAS') {
                    $storage_path = $storage_info['path_for_win'];
                } else {
                    $storage_path = $storage_info['path'];
                }
                // 슬래시
                // $server_filename = $storage_path . '/' . $path;
                // 역슬래시
                $server_filename  = Path::join($storage_path, $path);
                
                // $download = send_attachment2($name, $server_filename);
                send_attachment($name, $server_filename);
            }
          

            // echo json_encode(array(
            //     'success' => true,
			// 	'storage_path' => $storageId,
			// 	'path' => $path,
			// 	'fileid' => $fileid

            // ));
            break;
    }
} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'msg' => $e->getMessage()
    ));
}

function downloadCurl($filename, $url, $tempDir){
	//$url = 'https://contribute.geeksforgeeks.org/wp-content/uploads/gfg-40.png'; 
    $urlInfo = parse_url($url);
    $baseUrl = 'http' .'://'.'10.10.50.180:9200';
    $url = $urlInfo['path'];
    $params ='';
    if( $urlInfo['query'] ){
        $params = '?'.$urlInfo['query'];
    }
    $url = $baseUrl.$url.$params;
    
	// Initialize the cURL session 
	$ch = curl_init($url); 
	  
	// Inintialize directory name where 
	// file will be save 
	$dir = $tempDir; 
	  
	// Use basename() function to return 
	// the base name of file  
	$file_name = basename($url); 
	  
	// Save file into file location 
	$save_file_loc = $dir . $file_name; 
	  
	// Open file  
	$fp = fopen($save_file_loc, 'wb'); 
	  
	// It set an option for a cURL transfer 
	curl_setopt($ch, CURLOPT_FILE, $fp); 
	curl_setopt($ch, CURLOPT_HEADER, 0); 
	  
	// Perform a cURL session 
	curl_exec($ch); 
	  
	// Closes a cURL session and frees all resources 
	curl_close($ch); 
	  
	// Close file 
    fclose($fp); 
    
    send_attachment($filename, $save_file_loc);
    
    //저장한 파일 지우기
    //unlink($save_file_loc);
    
    return $save_file_loc;
}
?>