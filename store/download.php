<?php
//2015-11-09 upload_other download.php 수정
session_start();
set_time_limit(600);
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');
$user_id = $_SESSION['user']['user_id'];//2015-11-09 upload_other
// $user_id = $_REQUEST['user_id'];
$content_id		= $_GET['content_id'];
$media_id		= $_GET['media_id'];
$media_type		= $_GET['media_type'];
$ud_content_id  = $_GET['ud_content_id'];
try {

	if (empty($content_id)) {
		$content_id = $db->queryOne("select content_id from bc_media where media_id=$media_id");
	}
	$content_info = getContentInfo($content_id);

	if ($_GET['page_loc'] == 'catalog') {
		$content_id = $_GET['content_id'];
		$storage_info = $db->queryRow("
			SELECT	PATH
					,VIRTUAL_PATH
					,path_for_unix
			FROM	VIEW_UD_STORAGE
			WHERE	UD_CONTENT_ID = (SELECT UD_CONTENT_ID FROM BC_CONTENT WHERE CONTENT_ID = '" . $content_id . "')
			AND		US_TYPE = 'lowres'
		");

		$storage_path = $storage_info['path'];
		if (DB_TYPE == 'oracle') {
			$media_info =  $db->queryRow("
				SELECT	PATH
						,CASE
							WHEN LENGTH(SUBSTR(PATH, INSTR(PATH, '.', -1) + 1)) > 4 THEN
								''
							ELSE
								SUBSTR(PATH, INSTR(PATH, '.', -1) + 1)
						END AS EXT
				FROM	BC_SCENE
				WHERE	SCENE_ID = '" . $_GET['scene_id'] . "'
			");
		} else {
			$media_info =  $db->queryRow("
				SELECT	PATH
						,CASE
							WHEN LENGTH(SUBSTR(PATH, STRPOS(PATH, '.') + 1)) > 4 THEN
								''
							ELSE
								SUBSTR(PATH, STRPOS(PATH, '.') + 1)
						END AS EXT
				FROM	BC_SCENE
				WHERE	SCENE_ID = '" . $_GET['scene_id'] . "'
			");
		}
	} else {
		$search_array = array();

		if (!empty($content_id)) {
			array_push($search_array, " CONTENT_ID = '" . $content_id . "' ");
		}

		if (!empty($media_id)) {
			array_push($search_array, " media_id = '" . $media_id . "' ");
		}

		if (empty($search_array)) throw new Exception("not found id");

		$search_text = join(' and ', $search_array);

		if (DB_TYPE == 'oracle') {
			$media_info = $db->queryRow("
				SELECT	CONTENT_ID
						,PATH
						,CASE
							WHEN LENGTH(SUBSTR(PATH, INSTR(PATH, '.', -1) + 1)) > 4 THEN
								''
							ELSE
								SUBSTR(PATH, INSTR(PATH, '.', -1) + 1)
                        END AS EXT,
                        MEMO,
                        storage_id
				FROM	BC_MEDIA
				WHERE	MEDIA_TYPE = '$media_type'
				AND		" . $search_text . "
				ORDER BY  MEDIA_ID DESC
			");
		} else {
			$media_info = $db->queryRow("
				SELECT	CONTENT_ID
						,PATH
						,CASE
							WHEN LENGTH(SUBSTR(PATH, STRPOS(PATH, '.') + 1)) > 4 THEN
								''
							ELSE
								SUBSTR(PATH, STRPOS(PATH, '.') + 1)
                        END AS EXT,
                        MEMO,
                        storage_id
				FROM	BC_MEDIA
				WHERE	MEDIA_TYPE = '$media_type'
				AND		" . $search_text . "
				ORDER BY  MEDIA_ID DESC
			");
		}

		//미디어 아이디로 조회 된 경우에 콘텐츠 아이디 셋팅
		$content_id = $media_info['content_id'];
		
		// 미디어 테이블의 storage_id로 스토리지 정보 조회
        $storage_info = $db->queryRow("select * from bc_storage where storage_id={$media_info['storage_id']}");
	}

	if (SERVER_TYPE == 'linux') {
		$storage_path = $storage_info['path_for_unix'];
	} else {
        if (strtoupper($storage_info['type']) === 'NAS') {
            $storage_path = $storage_info['path_for_win'];
        }else{
            $storage_path = $storage_info['path'];
        }
	}

	$storage_path = $storage_path;

	$server_filename = $storage_path . '/' . $media_info['path'];
	// $server_filename = $media_info['path'];
	//echo $server_filename;


	//산업인력공단과 같이 파일의 확장자가 없는 케이스 예외처리
	if (empty($media_info['ext'])) {
		$content_subtitle = str_replace('"', '', str_replace(' ', '_', $content_info['usr_wt_spgmnm']));
		$metadatas = MetaDataClass::getValueInfo('usr', $content_info['ud_content_id'], $content_id);
		$content_subtitle = str_replace('"', '', str_replace(' ', '_', $metadatas['usr_wt_spgmnm']));
		$date_info = explode(' ', $metadatas['usr_wt_bdate']);
		$filename = $content_title . '_' . $content_subtitle . '_' . $date_info[0] . '.mpg';
	} else {
		//$filename = $content_title.'_'.$content_subtitle.'_'.$date_info[0].'.'.$media_info['ext'];
		$filename = $content_title . '_' . date("YmdHis") . '.' . $media_info['ext'];
	}

	$filename = str_replace('__', '_', $filename);

	//memo에 첨부파일 원본파일명 넣었고, 그대로 다운로드.
	if ($_GET['page_loc'] != 'catalog' && !empty($media_info['memo'])) {
		$filename = $media_info['memo'];
	}

	//file_put_contents(LOG_PATH.'/AAAA_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")."filename ::: \r\n".$filename."\r\n", FILE_APPEND);

	//다운로드 기록 로그 남기기
	insertLog('download', $user_id, $content_id, $media_type);

    $serverHost = get_server_param('HTTP_HOST');
    $servers = explode(",", config('sms_auth')['domain']);  
    
    //내부 외부 구분
    if ( in_array($serverHost, $servers)) {
        $v_return = send_attachment($filename, $server_filename);
    }else{        
        //파일다운로드 함수 호출
        //if($user_id=='admin'){
            $downPath = $storage_info['path'].'/'. urlencode($media_info['path']);
            $downUrl = "http://10.10.50.132:8080/download?full_path=true&path=".$downPath."&name=".date("YmdHis");
            header( 'Location: '.$downUrl );
        //}else{
        //    $v_return = send_attachment($filename, $server_filename);
        //}
    }
} catch (Exception $e) {
	echo $e->getMessage();
}
function getbasename($path)
{
	$pattern = (strncasecmp(PHP_OS, 'WIN', 3) ? '/([^\/]+)[\/]*$/' : '/([^\/\\\\]+)[\/\\\\]*$/');
	if (preg_match($pattern, $path, $matches))
		return $matches[0];
	return '';
}
function getContentInfo($content_id)
{
	global $db;

	$query = "
		SELECT	CONTENT_ID, TITLE, UD_CONTENT_ID
		FROM	BC_CONTENT
		WHERE	CONTENT_ID = " . $content_id . "
	";

	$result = $db->queryRow($query);

	return $result;
}
