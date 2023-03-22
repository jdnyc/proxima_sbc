<?php

use Proxima\core\Path;

session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/MetaData.class.php');

try {
	$content_id = $_POST['content_id'];
	$ud_content_id = $_POST['ud_content_id'];
	$attachFileType = $_POST['attach_file_type'];
	
	//throw new Exception ("Extension invalid");

	$attach_count = $db->queryOne("
					SELECT COUNT(MEDIA_ID)
					FROM BC_MEDIA
					WHERE MEDIA_TYPE = 'Attach'
					AND CONTENT_ID = '$content_id'
				");
	$attach_count = $attach_count + 1;
	//파일 정보
	$media_id = getSequence('seq_media_id');
	//$fileid = "ATTACH_".$content_id."_".$attach_count;
	$fileid = "attach_" . $content_id . "_" . $media_id; //media_id로 변경
	$extension = pathinfo($_FILES['FileAttach']['name'], PATHINFO_EXTENSION);
	//$ori_filename = pathinfo($_FILES['FileAttach']['name'], PATHINFO_BASENAME);
	$ori_filename = $_FILES['FileAttach']['name'];
	$filename = $fileid . '.' . $extension;

	$tmp_filename = $_FILES['FileAttach']['tmp_name'];
	$storage_info = $db->queryRow("
						SELECT S.*
						FROM BC_STORAGE S, BC_UD_CONTENT_STORAGE US
						WHERE S.STORAGE_ID = US.STORAGE_ID
						AND US.US_TYPE = 'lowres'
						AND US.UD_CONTENT_ID = '$ud_content_id'
					");
    
	if (SERVER_TYPE == 'linux') {
		$storage_path = $storage_info['path_for_unix'];
	} else if (strtoupper($storage_info['type']) === 'NAS') {
		$storage_path = $storage_info['path_for_win'];
		// $storage_path = $storage_info['path'];
	} else {
		$storage_path = $storage_info['path'];
	}
	
	$media_path = $db->queryRow("select * from bc_media where content_id=" . $content_id . " and media_type='proxy'");
	$attach_path = $media_path['path'];
	$attach_path = explode('/', $attach_path);
	//파일명 제거
	array_pop($attach_path);
	//Proxy라는 고정폴더 제거
	array_pop($attach_path);
	$file_path = stripslashes(implode('/', $attach_path) . "/Attach");

	// 루트 디렉터리가 있는지 확인
	if (!isValidRootDir($storage_path)) {
		throw new \Exception('Invalid root path. path : ' . $storage_path);
	}

	if (!file_exists($storage_path)) {
		throw new \Exception('Storage dir does not exists. path : ' . $storage_path);
	}

	$filepath_arr = explode('/', $file_path);

	$dirs = $storage_path;
	foreach ($filepath_arr as $dir) {
		$dirs = Path::join($dirs, $dir);
		if (!is_dir($dirs) && !file_exists($dirs)) {
			mkdir($dirs, 0777);
		}
	}
	//2018/12/24/144331/144331/Proxy/proxy_144331.mp4
	// 첨부파일 새로운 파일 명 생성

	$new_file_name = Path::join($dirs, $filename);
	if (move_uploaded_file($tmp_filename, $new_file_name)) {
		//BC_MEDIA에 EDL 추가

		if (strpos($new_file_name, $storage_path . '/') !== false) {
			$media_path = str_replace($storage_path . '/', '', $new_file_name); //$content_id."/Attach/".$filename;
		} else {
			$storage_path = str_replace('/', '\\', $storage_path);
			$media_path = str_replace($storage_path . '\\', '', $new_file_name); //$content_id."/Attach/".$filename;
			$media_path = str_replace('\\', '/', $media_path);
		}
        
		insertMedia($media_id, $content_id, 'Attach',$storage_info['storage_id'], $ud_content_id, $media_path, $_FILES['FileAttach']['size'], $ori_filename,$attachFileType);
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

function isValidRootDir($rootDir)
{
	if (!is_dir($rootDir)) {
		return false;
	}

	return true;
}


function insertMedia($media_id, $content_id, $channel, $storage_id,$ud_content_id, $fullpath, $filesize, $memo = '', $attachType = '')
{
	global $db;

	//$fullpath = getFullPath($content_id, $ud_content_id);
	$created_datetime = date('YmdHis');
	$expired_date = check_media_expire_date($ud_content_id, 'original', $created_datetime);

	if (!empty($memo)) {
		$ori_memo = $memo;
		$memo = \Proxima\core\Unit::normalizeUtf8String($memo);
		if(empty($memo)){
			$memo = $ori_memo;
		}		
		//HUIMAI, 첨부파일에 원본파일명도 넣음.
		$memo = $db->escape($memo);
		if(!empty($attachType)) {
			$query = "insert into bc_media (media_id, content_id, media_type, storage_id, path, filesize, reg_type, created_date, expired_date, status, memo,attach_type)
					values ({$media_id}, {$content_id}, '{$channel}',{$storage_id}, '{$fullpath}', '$filesize', '{$channel}', '{$created_datetime}', '{$expired_date}', '0', '{$memo}','{$attachType}')";
		} else {
			$query = "insert into bc_media (media_id, content_id, media_type, storage_id, path, filesize, reg_type, created_date, expired_date, status, memo)
					values ({$media_id}, {$content_id}, '{$channel}',{$storage_id}, '{$fullpath}', '$filesize', '{$channel}', '{$created_datetime}', '{$expired_date}', '0', '{$memo}')";
		}
	} else {
		$query = "insert into bc_media (media_id, content_id, media_type, storage_id, path, filesize, reg_type, created_date, expired_date, status, attach_type)
                values ({$media_id}, {$content_id}, '{$channel}', {$storage_id}, '{$fullpath}', '$filesize', '{$channel}', '{$created_datetime}', '{$expired_date}', '0','{$attachType}')";
	}
	$db->exec($query);
}

function getStoragePath($ud_content_id)
{
	global $db;

	$storage_id = $db->queryOne("select storage_id from bc_ud_content_storage where ud_content_id = '$ud_content_id' and us_type = 'highres'");
	$storage_path = $db->queryOne("select path from bc_storage where storage_id = '$storage_id'");

	return $storage_path;
}
