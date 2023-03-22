<?
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$media_id = $_REQUEST['media_id'];

function goBack($msg='', $url='') {
   echo "<script>";
   if($msg) echo 'alert("'.$msg.'");';
   if($url) echo 'location.replace("'.$url.'");';
   else echo 'history.go(-1);';
   echo "</script>";
}
 
//▶ 외부에서 접근시에 에러 출력하는 부분으로 테스트 하실 때에는 주석처리 하시는 것이 좋습니다..^^
/*
if(!$_SERVER["HTTP_REFERER"] || !ereg(str_replace(".","\\.",$_SERVER["HTTP_HOST"]), $_SERVER["HTTP_REFERER"])) {
   goBack("정상적인 방법으로 다운로드해 주세요.");
   exit;
}
*/
 
$media_info_all = $db->queryAll("select * from bc_media where media_id in (".$media_id.")");
foreach($media_info_all as $media_info) {
	switch($media_info['media_type']) {
		case 'original':
			$dir = '\\\\onlmg.tbs.local\nrlmg\highres/';
		break;
		case 'proxy':
			$dir = '\\\\nrlmg.tbs.local\onlmg\lowres/';
		break;
		case 'attach':
			$dir = '\\\\nrlmg.tbs.local\onlmg\lowres/';
		break;
		default:
			$dir = '\\\\nrlmg.tbs.local\onlmg\lowres/';
		break;
	}

	$path = iconv("UTF-8", "euc-kr", $media_info['path']);

	$row[0] = array_pop(explode('/', $path));
	$row[1] = $path;

	$real_name = stripslashes($row[0]);
	$real_name = str_replace(' ', '_', $real_name);
	$save_name = $dir.stripslashes($row[1]);

	// 만약 파일이 없을 경우 에러출력
	if ( ! file_exists($save_name)) {
	   goBack("Cannot find file to download(".$save_name.").");
	   exit;
	} else {
		$action = 'download';
		insertLog($action, $_SESSION['user']['user_id'], $media_info['content_id'], $media_info['media_type'].' download');

		// Must be fresh start 
		if (headers_sent()) {
			die('Headers Already Sent'); 
		}

		// Required for some browsers 
		if (ini_get('zlib.output_compression')) {
			ini_set('zlib.output_compression', 'Off'); 
		}

		// Parse Info / Get Extension 
		$fsize = filesize($filepath); 
		$path_parts = pathinfo($filepath); 
		$ext = strtolower($path_parts["extension"]); 

		// Determine Content Type 
		switch ($ext) { 
			case "pdf": $ctype="application/pdf"; break; 
			case "exe": $ctype="application/octet-stream"; break; 
			case "zip": $ctype="application/zip"; break; 
			case "doc": $ctype="application/msword"; break; 
			case "xls": $ctype="application/vnd.ms-excel"; break; 
			case "ppt": $ctype="application/vnd.ms-powerpoint"; break; 
			case "gif": $ctype="image/gif"; break; 
			case "png": $ctype="image/png"; break; 
			case "jpeg": 
			case "jpg": $ctype="image/jpg"; break; 
			default: $ctype="application/force-download"; 
		} 

		header("Pragma: public"); // required 
		header("Expires: 0"); 
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
		header("Cache-Control: private",false); // required for certain browsers 
		header("Content-Type: $ctype"); 
		header("Content-Disposition: attachment; filename=".$real_name);
		header("Content-Transfer-Encoding: binary"); 
		header("Content-Length: ".filesize($save_name)); 
		ob_clean(); 
		flush();    

		$fp = fopen($save_name, "rb"); 
		if(!fpassthru($fp)) fclose($fp);
	   
	   
	//   if(strstr("(MSIE 5.0|MSIE 5.1|MSIE 5.5|MSIE 6.0)", $_SERVER["HTTP_USER_AGENT"]) && !strstr("(Opera|Netscape)", $_SERVER["HTTP_USER_AGENT"])) {
	//      Header("Content-type: application/octet-stream");
	//      Header("Content-Length: ".filesize($save_name));
	//      Header("Content-Disposition: attachment; filename=".$real_name);
	//      Header("Content-Transfer-Encoding: binary");
	//      Header("Pragma: no-cache");
	//      Header("Expires: 0");
	//   } else {
	//      Header("Content-type: file/unknown");
	//      Header("Content-Length: ".filesize($save_name));
	//      Header("Content-Disposition: attachment; filename=".$real_name);
	//      Header("Content-Description: PHP3 Generated Data");
	//      Header("Pragma: no-cache");
	//      Header("Expires: 0");
	//   }
	//   $fp = fopen($save_name, "rb"); 
	//   if(!fpassthru($fp)) fclose($fp);
	}
}
?> 
