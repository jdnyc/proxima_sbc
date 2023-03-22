<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

	$content_ids = json_decode($_GET['content_id'], true);
	$download_mode = $_GET['download_mode'];
	$number_content = count($content_ids);
	if($number_content > 0){
		if($download_mode == 0){
			$v_result = fn_download_original($content_ids);
		}else{
			$v_result = fn_download_proxy($content_ids);
		}
	}else{

	}

	function fn_download_original($content_ids) {
		$number_content = count($content_ids);

		if($number_content >1){
			$v_result = fn_create_zip($content_ids, 0);
		}else{
			$v_result = fn_image_download($content_ids,0);
		}
		return $v_result;
	}

	function fn_download_proxy($content_ids) {
		$number_content = count($content_ids);
		if($number_content >1){
			$v_result = fn_create_zip($content_ids, 1);
		}else{
			$v_result = fn_image_download($content_ids, 1);
		}
		return $v_result;
	}

	function fn_create_zip($av_image_ids = array(), $av_type = 0) {
		global $db;

		print_r('fn_create_zip');
		if($av_type == 0){
			$v_zip_filename = 'download_original'.'_'.date('His').'.zip';
		}else{
			$v_zip_filename = 'download_proxy'.'_'.date('His').'.zip';
		}

		
		$v_zip_file = new ZipArchive();
		if($v_zip_file->open($v_zip_filename, false ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
			return false;
		}

		foreach($av_image_ids as $v_content_id) {
			if($av_type == 0){
				$query = "
						SELECT	CONTENT_ID,
								PATH
						FROM 	BC_MEDIA
						WHERE 	MEDIA_TYPE = 'original'
						AND 	CONTENT_ID = ".$v_content_id

					;
				$root_path = HIGHRES_ROOT;
			}else{
				$query = "
						SELECT	CONTENT_ID,
								PATH
						FROM 	BC_MEDIA
						WHERE 	MEDIA_TYPE = 'proxy'
						AND 	CONTENT_ID = ".$v_content_id

					;
				$root_path = LOWRES_ROOT;
			}

			$v_result = $db->queryRow($query);

			$v_image_path = $root_path.'/'.$v_result['path'];
			$v_image_ext = array_pop(explode('.', $v_image_path));
			if($av_type == 0){
				$v_file_name = $v_result['content_id'].'_original.'.$v_image_ext;	
			}else{
				$v_file_name = $v_result['content_id'].'_proxy.'.$v_image_ext;
			}
			if(file_exists($v_image_path)) {
				$v_zip_file->addFile($v_image_path, iconv("utf-8", "euc-kr", $v_file_name));
			}

		}

		$v_zip_file->close();
		
		header("Pragma: public"); // required
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private",false); // required for certain browsers
		header("Content-Type: application/zip");
		header("Content-Disposition: attachment; filename=".$v_zip_filename.";" );
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: ".filesize($v_zip_filename));
		header('Content-Description: File Transfer');
		
		//header('Content-Length: ' . filesize($v_zip_filename));
		ob_clean();
		flush();
		readfile($v_zip_filename);
		if(file_exists($v_zip_filename)){
			unlink($v_zip_filename);
		}
		return file_exists($v_zip_filename);
	}

	function fn_image_download($av_image_ids = array(), $av_type = 0){
		global $db;
		if($av_type == 0){
			$query = "
					SELECT	CONTENT_ID,
							PATH
					FROM 	BC_MEDIA
					WHERE 	MEDIA_TYPE = 'original'
					AND 	CONTENT_ID = ".$av_image_ids[0]

				;
			$root_path = HIGHRES_ROOT;
		}else{
			$query = "
					SELECT	CONTENT_ID,
							PATH
					FROM 	BC_MEDIA
					WHERE 	MEDIA_TYPE = 'proxy'
					AND 	CONTENT_ID = ".$av_image_ids[0]

				;
			$root_path = LOWRES_ROOT;
		}

		$v_result = $db->queryRow($query);

		$fullPath = $root_path.'/'.$v_result['path'];
		$v_image_ext = array_pop(explode('.', $fullPath));

		if($av_type == 0){
			$v_file_name = $v_result['content_id'].'_original.'.$v_image_ext;	
		}else{
			$v_file_name = $v_result['content_id'].'_proxy.'.$v_image_ext;
		}
		
		if( file_exists($fullPath) ){
			$fsize = filesize($fullPath);
			$path_parts = pathinfo($fullPath);
			$ext = strtolower($path_parts["extension"]);
			switch ($ext) {
				case "gif": $ctype="image/gif"; break;
				case "png": $ctype="image/png"; break;
				case "jpeg": $ctype="image/jpeg"; break;
				case "jpg": $ctype="image/jpg"; break;
				default: $ctype="application/force-download";
			}
		
			header("Pragma: public"); // required
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Cache-Control: private",false); // required for certain browsers
			header("Content-Type: application/octet-stream");
			header("Content-Disposition: attachment; filename=".$v_file_name.";" );
			header("Content-Transfer-Encoding: binary");
			header("Content-Length: ".$fsize);
			ob_clean();
			flush();
			readfile( $fullPath );
		} else
			//echo $fullPath."<br/>";
			die('File Not Found');

	}
?>