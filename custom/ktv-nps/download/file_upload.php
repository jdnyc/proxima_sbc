<?php

use Proxima\core\Path;

session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/lang.php');

try{
 
    $tmp_name = $_FILES['FileAttach']['tmp_name'];
    $fileType = pathinfo($_FILES['FileAttach']['name'], PATHINFO_EXTENSION);
    $ori_filename = pathinfo($_FILES['FileAttach']['name'], PATHINFO_BASENAME);
    $file = explode('.', $ori_filename);
    // $fileid = "ATTACH_".$file[0].'_'.date('ymdhis');
    $filename = $file[0].'.'.$fileType;
    $title = $_POST['title'];
    $icon = $_POST['icon'];
    // $published = $_POST['published'];
    $published= 1;

    $description = $title;
    $storage_info = $db->queryRow("
    SELECT S.*
    FROM BC_STORAGE S, BC_UD_CONTENT_STORAGE US
    WHERE S.STORAGE_ID = US.STORAGE_ID
    AND US.US_TYPE = 'lowres'
    AND US.UD_CONTENT_ID = 1
    ");

    if(SERVER_TYPE == 'linux'){
        $storage_path = $storage_info['path_for_unix'];
    } else if (strtoupper($storage_info['type']) === 'NAS'){
        $storage_path = $storage_info['path_for_win'];
    } else {
        $storage_path = $storage_info['path'];
    }
    $attach_path = '/install'. '/';
    // $filepath_arr = explode('/', $attach_path);
    $dirs = $storage_path;

    // $tmp_dirs = explode('/', $dirs);
    // $dirs = ('//'.$tmp_dirs[2].'/'.$tmp_dirs[3]);
    // var_dump($dirs);
    // die();
    switch($icon)
    {
        case '도움말 아이콘':
            $icon= 'fa-question-circle';
        break;
        case '윈도우 아이콘':
            $icon= 'fa-windows';
        break;
        case '애플 아이콘':
            $icon='fa-apple';
        break;
        case '프로그램 아이콘':
            $icon='fa-microphone';
        break;
    }
    // foreach ($filepath_arr as $dir) {

        $dirs = Path::join($dirs, $attach_path);

        if (!is_dir($dirs) && !file_exists($dirs)) {
            mkdir($dirs, 0777);
        }
    // }
    $new_file_name = Path::join($dirs, $filename);
    $return = move_uploaded_file($tmp_name, $new_file_name);
        if($return){
            if(strpos($new_file_name, $storage_path. '/') !== false){
                $media_path = str_replace($storage_path . '/' , '', $new_file_name);
            } else {
                $storage_path = str_replace('/', '\\', $storage_path);
                $media_path = str_replace($storage_path, '', $new_file_name);
                $media_path = str_replace('\\', '/', $media_path);
            }
            
            if(SERVER_TYPE == 'linux'){
                $media_path = '/'.$media_path;
            } 
            insertDownLoad($title, $icon, $media_path, $description,$published );

        } else {
            throw new Exception('파일 등록 실패');
        }
        echo json_encode( array (
            'success' => true,
            // 'data'=> $data,
            'result' => 'success'
        ));
} catch(Exception $e) {
    die(json_encode(array(
        'success'=> false,
        'result'=> 'failure',
        'msg'=> $e->getMessage()
    )));
}
    function insertDownLoad($title, $icon, $media_path, $description, $published){

        global $db;

        $query = "select max(id) from downloads"; // show_order 를 아이디 값에 맞게 추출
        $data = $db->queryOne($query);
        $ori_media_path = $media_path;
        $media_path = \Proxima\core\Unit::normalizeUtf8String($media_path);
        if(empty($media_path)){
            $media_path = $ori_media_path;
        }
        $query = "INSERT INTO 
                downloads (title, icon, path, description, published, show_order, created_at, updated_at) 
            VALUES ('{$title}', '{$icon}', '{$media_path}', '{$description}', '{$published}', {$data}+1, sysdate, sysdate)"; 
        $db->exec($query);
    }



// try{
    
// }


?>