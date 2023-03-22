<?php
set_time_limit(0);
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$path = $_GET['path'];

if( !empty($path) )
{
	$download_file = LOCAL_LOWRES_ROOT.'/'.$path;
}
else
{
	echo 'Not Path';
	exit;
}

$filename = array_pop(explode('/', $download_file));
//$fname = iconv('utf-8', 'euc-kr', $filename);
$fname = str_replace(' ','_',$filename);
//echo $download_file;
//echo $fname;
//exit;
download_file($download_file, $fname);

//default_download($download_file, $fname);

function download_file($file_name , $fname) {

    if (!file_exists($file_name)) { die("<b>404 File not found!</b>"); }

    $file_extension = strtolower(substr(strrchr($file_name,"."),1));
    $file_size = filesize($file_name);
//    $md5_sum = md5_file($file_name);

   //This will set the Content-Type to the appropriate setting for the file
    switch($file_extension) {
        case "exe": $ctype="application/octet-stream"; break;
        case "zip": $ctype="application/zip"; break;
        case "mp3": $ctype="audio/mpeg"; break;
        case "mpg":$ctype="video/mpeg"; break;
        case "avi": $ctype="video/x-msvideo"; break;

        //The following are for extensions that shouldn't be downloaded (sensitive stuff, like php files)
        case "php":
        case "htm":
        case "html":
        //case "txt":
		die("<b>Cannot be used for ". $file_extension ." files!</b>"); break;

        default: $ctype="application/force-download";
    }

    if (isset($_SERVER['HTTP_RANGE'])) {
        $partial_content = true;
        $range = explode("-", $_SERVER['HTTP_RANGE']);
        $offset = intval($range[0]);
        $length = intval($range[1]) - $offset;
    }
    else {
        $partial_content = false;
        $offset = 0;
        $length = $file_size;
    }

    //read the data from the file
    $handle = fopen($file_name, 'r');
    fseek($handle, $offset);
//    $md5_sum = md5($buffer);
    if ($partial_content) $data_size = intval($range[1]) - intval($range[0]);
    else $data_size = $file_size;

    // send the headers and data
	header('Content-Description: File Transfer');
	header('Content-Transfer-Encoding: binary');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
    header("Content-Length: " . $data_size);
//    header("Content-md5: " . $md5_sum);
    header("Accept-Ranges: bytes");
    if ($partial_content) header('Content-Range: bytes ' . $offset . '-' . ($offset + $length) . '/' . $file_size);
    header("Connection: close");
    header("Content-type: " . $ctype);
	$fname_kr = iconv('utf-8', 'euc-kr', $fname);
    header('Content-Disposition: attachment; filename=' . $fname_kr);

    $buffer = '';
	while (!feof($handle)) {
	    $buffer = fread($handle, 1024*4);
		echo $buffer;
		flush();
	}
    fclose($handle);
}


function default_download($download_file, $filename){
	header('Content-Description: File Transfer');
	header('Content-Type: video/quicktime');
	header('Content-Disposition: attachment; filename='.$filename);
	header('Content-Transfer-Encoding: binary');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');

	header('Content-Length: ' . filesize($download_file));
	header("Connection: close");
	ob_clean();
	flush();
	readfile($download_file);

}
?>