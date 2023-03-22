<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/libs/functions.php');

//받을 인자값 : set_in, set_out, content_id
// task 테이블에 pfr 작업 추가.
/*
PFR 타입코드 : 30
지원 포맷 : MXF, WMV, MP4, MOV
소스경로 및 타겟경로 : 영상의 전체 경로. (파일명과 포맷포함.)
파라메터 : 다음 5개의 파라메터를 사용하며, 실제 변경하는 것은 1,2번 변경. 3~5번은 고정.
1. 시작프레임
2. 종료프레임
3. PFR모드 : "C"
4. 포트 : "15555"
5. 자동종료 : "X'
 
예제. 30 프레임 == 1초
10초에서 50초를 PFR 작업시
 
"300" "1500" "C" "15555" "X"
*/

try
{
	$set_in		= $_POST['in'];
	$set_out	= $_POST['out'];
	$type		= $_POST['type'];

	$content_id = $_POST['content_id'];
	$user_id	= $_SESSION['user']['user_id'];

	$query = "select path from media where content_id = $content_id";
	switch($type)
	{
		case 'pfr_high':
			$query .= " and type = 'original'";
			$level = -1;
			$extension = '.mxf';
		break;

		case 'pfr_low':
			$query .= " and type = 'proxy'";
			$level = -2;
			$extension = '.mp4';
		break;

		case 'cj':
			$query .= " and type = 'original'";
			$level = -1;
			$extension = '.wmv';
		break;
	}

	$cur_time = date('YmdHis');
	$file = $mdb->queryOne($query);

	$storage = $mdb->queryRow("select * from storage where name = 'manager3'");
	$storage_id = $storage['storage_id'];
	$login_id = $storage['login_id'];
	$login_pw = $storage['login_pw'];

	$pfr_list = json_decode($_POST['pfr_list']);
	foreach ( $pfr_list as $pfr ) 
	{
		$media_id = getNextSequence();

		// 초를 프레임으로 변경
		$set_in = $pfr->in * FRAMERATE;
		$set_out = $pfr->out * FRAMERATE;

		$new_filename = stripExtensionOfFilename(basename($file)) . '_' . $set_in . '_' . $set_out . $extension;
		$target_path = join('/', explode('/', str_replace('\\', '/', $file), $level)).'/pfr/';
		$pfr_target_path = $target_path . $new_filename;

		$pfr_parameter = '"'.$set_in.'" "'.$set_out.'" "C" "15555" "X"';

		if ( $type == 'pfr_low' || $type == 'pfr_high' )
		{
			$r = $mdb->exec("insert into media (content_id, media_id, storage_id, type, path, filesize, created_time, register) ".
									"value ".
								"('$content_id', '$media_id', '$storage_id', '$type', '$pfr_target_path', '', '$cur_time', '$user_id')");

			$r = $mdb->exec("insert into task (media_id, type, status, priority, source, target, target_id, target_pw, parameter, creation_datetime) ".
									"values ".
								"('$media_id', '30', 'queue', '300', '$file', '$pfr_target_path', '$login_id', '$login_pw', '$pfr_parameter', '$cur_time')");
		}			
		else if ( $type == 'cj')
		{
			$r = $mdb->exec("insert into task (media_id, type, status, priority, source, target, target_id, target_pw, parameter, creation_datetime, destination) ".
									"values ".
								"('$media_id', '30', 'queue', '300', '$file', '$pfr_target_path', '$login_id', '$login_pw', '$pfr_parameter', '$cur_time', 'cj')");
		}
		else
		{
			throw new Exception('존재하지 않는 타입입니다.');
		}
	}

	echo json_encode(array(
		'success' => true
	));
}
catch (Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}


?>