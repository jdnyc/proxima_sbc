<?php

use Proxima\core\Path;

session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/lang.php');



/**
 * 업로드 
 * TB_ORD_FILE 추가
 */

try {
    // 받은 데이터
    $ordId = $_POST['ord_id'];
    $index = $_POST['index'];
        if(is_null($index)){
            $index = 0;
        }
    $extension = pathinfo($_FILES['FileAttach']['name'], PATHINFO_EXTENSION);
    $ori_filename = pathinfo($_FILES['FileAttach']['name'], PATHINFO_BASENAME);
  
    $fileid = "ATTACH_" . $ordId . '_' . date("ymdhis").'_'.$index;

    $filename = $fileid . '.' . $extension;
    $tmp_filename = $_FILES['FileAttach']['tmp_name'];

    // $storage_info = $db->queryRow(" SELECT * FROM BC_STORAGE WHERE STORAGE_ID = 105 ");
    $storage_info = $db->queryRow("
    SELECT S.*
    FROM BC_STORAGE S, BC_UD_CONTENT_STORAGE US
    WHERE S.STORAGE_ID = US.STORAGE_ID
    AND US.US_TYPE = 'lowres'
    AND US.UD_CONTENT_ID = 1
    ");

    if (SERVER_TYPE == 'linux') {
        $storage_path = $storage_info['path_for_unix'];
    } else if (strtoupper($storage_info['type']) === 'NAS') {
        $storage_path = $storage_info['path_for_win'];
        // $storage_path = $storage_info['path'];
    } else {
        $storage_path = $storage_info['path'];
    }
    $dateDir = date('Y/m/d');

    // $storage_path = LOCAL_LOWRES_ROOT;

    $attach_path  = '/upload' . '/' . $dateDir . '/';

    $filepath_arr = explode('/', $attach_path);

    $dirs = $storage_path;

    foreach ($filepath_arr as $dir) {

        $dirs = Path::join($dirs, $dir);

        if (!is_dir($dirs) && !file_exists($dirs)) {
            mkdir($dirs, 0777);
        }
    }
    $new_file_name = Path::join($dirs, $filename);

    if (move_uploaded_file($tmp_filename, $new_file_name)) {
        //BC_MEDIA에 EDL 추가

        if (strpos($new_file_name, $storage_path . '/') !== false) {
            $media_path = str_replace($storage_path . '/', '', $new_file_name); //$content_id."/Attach/".$filename;
        } else {
            $storage_path = str_replace('/', '\\', $storage_path);
            $media_path = str_replace($storage_path, '', $new_file_name); //$content_id."/Attach/".$filename;
            $media_path = str_replace('\\', '/', $media_path);
        }
        $storageId = $storage_info['storage_id'];
        insertMedia($ordId, $media_path, $ori_filename, $storageId);
    } else {
        throw new Exception('파일 등록 실패');
    }
    
    echo json_encode(array(
        'success' => true,
        'result' => 'success'
    ));
} catch (Exception $e) {
    die(json_encode(array(
        'success' => false,
        'result' => 'failure',
        'msg' => $e->getMessage()
    )));
}

function insertMedia($ordId, $mediaPath, $fileName, $storageId)
{
    global $db;
    $oriFileName = $fileName;
    $fileName = \Proxima\core\Unit::normalizeUtf8String($fileName);
    if(empty($fileName)){
        $fileName = $oriFileName;
    }
    
    $query = "INSERT INTO TB_ORD_FILE (ord_id, file_path, file_name, storage_id) VALUES('{$ordId}', '{$mediaPath}', '{$fileName}', {$storageId})";

    $db->exec($query);
}
