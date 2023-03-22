<?php 


    use Proxima\core\Path;

    session_start();
    require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/lang.php');


try{
    $id = $_GET['id'];
    // die();
    $query = "select path from downloads where id = ".$id;
    $data = $db->queryOne($query);
 
    $path = $data;
    $filename = explode('/',$data);
    $name = $filename[2];
    $storage_info = $db->queryRow("
    SELECT	*
                FROM	BC_STORAGE
                WHERE	STORAGE_ID	= 105
    ");

    if(SERVER_TYPE =='linux'){
        $storage_path = $storage_info['path_for_unix'];
    } else if(strtoupper($storage_info['type']) === 'NAS'){
        $storage_path = $storage_info['path_for_win'];
    } else {
        $storage_path = $storage_info['path'];
    }

    $server_filename = Path::join($storage_path, $path);

    send_attachment($name, $server_filename);
} catch(Exception $e) {
    echo json_encode(array(
        'success' => false,
        'msg'=> $e->getMessage()
    ));
}


    function downloadCurl($filename, $url, $tempDir){
        //$url = 'https://contribute.geeksforgeeks.org/wp-content/uploads/gfg-40.png'; 
        $urlInfo = parse_url($url);
        $baseUrl = 'http' .'://'.'10.10.50.128:8080';
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