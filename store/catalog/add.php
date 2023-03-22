<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/timecode.class.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/FcpXML.class.php');
$start_time_code_frame = 0;
try {
	$user_id = $_SESSION['user']['user_id'];
	$media_id = $_POST['media_id'];
	$title = $_POST['title'];
	$content = $_POST['content'];
	$peoples = $_POST['peoples'];
	$start_frame = $_POST['start_frame'];
	$end_frame = $_POST['end_frame'];
	$action = $_POST['action'];
	$content_id = $_POST['content_id'];
	$frame_rate = getFrameRate($content_id);
	//$comments = $db->escape($_POST['comments']);

	switch ($action) {
		case 'add_sub_story_board':
			$story_board_id = getSequence('SEQ_STORY_BOARD');
			$cur_time = date('YmdHis');


			$time_code_start_sec = frameToTimeCode($start_frame, $content_id);
			$time_code_end_sec = frameToTimeCode($end_frame, $content_id);
			$content_info = $db->queryRow("
				SELECT	M.STORAGE_ID, M.STATUS, C.CONTENT_ID,C.UD_CONTENT_ID, C.TITLE,M.PATH, S.*
				FROM		BC_MEDIA M,
							VIEW_BC_CONTENT C
								LEFT JOIN	BC_SYSMETA_MOVIE S
								ON				S.SYS_CONTENT_ID = C.CONTENT_ID
				WHERE	C.CONTENT_ID = " . $content_id . "
				AND		M.CONTENT_ID = C.CONTENT_ID
				AND		M.MEDIA_TYPE = 'proxy'
				AND		COALESCE(M.STATUS , '0') = '0'
			");
			$ud_content_id = $content_info['ud_content_id'];

			$us_type = 'lowres';


			$storage_info = $db->queryRow("
				SELECT	* 
				FROM	VIEW_UD_STORAGE
				WHERE	ud_content_id = " . $ud_content_id . " 
				and		us_type='" . $us_type . "'
			");
			//$start_timecode =  $db->queryOne("SELECT SYS_START_TIMECODE FROM BC_SYSMETA_MOVIE WHERE sys_content_id = '$content_id'");

			if ($type == 'preview') {
				$xml_title = $content_info['title'] . '_' . $type;
			} else {
				$xml_title = $content_info['title'];
			}

			// $fcp = new FcpXML();
			// $fcp->setTitle($xml_title);
			// $fcp->setAudioChannel(8);
			// $fcp->setResolution(1920, 1080);
			if (empty($start_timecode)) {
				$start_timecode = '00:00:00;00';
			}

			//for window path
			//$file_path = $storage_info[path].'/'.$content_info[path];
			//for linux path
			$file_path = $storage_info['path_for_unix'] . '/' . $content_info['path_for_unix'];
			$duration = $content_info['sys_video_rt'];

			$starttc = $time_code_start_sec . ';00';
			$endtc = $time_code_end_sec . ';00';
			$intc = $time_code_start_sec . ';00';
			$outtc = $time_code_end_sec . ';00';
			//($filepath, $start_tc, $duration , $in , $out, $start, $end )
			$frame_rate = getFrameRate($content_id);
			// $fcp->addTLInfo($file_path, $frame_rate, $start_timecode, $duration, $intc, $outtc, $starttc, $endtc);

			// $xml = $fcp->createFcpXML();

			// $path_array = explode('/', $content_info['path']);
			// array_pop($path_array);

			// //확장자 뺀 파일명
			// $target_path = join('/', $path_array);

			// $target_path = $target_path . '/' . $content_info['content_id'] . '_' . $story_board_id . '.' . 'xml';


			// //for window path
			// //$down_path = $storage_info[path].'/'.$target_path;
			// //for linux path
			// $down_path = $storage_info['path_for_unix'] . '/' . $target_path;
			// //$down_path_array = explode('/', $down_path );
			// //if( $down_path_array[0] == 'Z:'){
			// //$down_path_array[0] = 'D:/Storage';
			// //}
			// //$down_path = join('/', $down_path_array);

			// //echo $xml->asXML();
			// $fcp->_LogFile('', $down_path, $xml->asXML());
			// $result = $fcp->_PrintFile($down_path, $xml->asXML());

			if (true) {
				// $filesize_xml = filesize($down_path);

				$query = "	INSERT INTO BC_STORY_BOARD (
													STORY_BOARD_ID,
													MEDIA_ID,
													START_FRAME,
													END_FRAME,
													TITLE,
													CONTENT,
													PEOPLES,
													IS_DELETED,
													CREATED_DATE,
													CREATED_USER_ID,
													XML_PATH
													 ) 
							VALUES ('$story_board_id','$media_id','$start_frame','$end_frame','$title','$content', '$peoples','N','$cur_time','$user_id', '$target_path')";
				//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sql_postgresql_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")." ::QUERY  :::"."\r\n".$query."\r\n\n", FILE_APPEND);
				$r = queryExec($query);
				searchUpdate($content_id);
				$msg = 'success';
			} else {
				$msg = _text('MSG00167');
			}

			break;
	}

	echo json_encode(array(
		'success' => true,
		'msg' => $msg,
		'query' => $query
	));
} catch (Exception $e) {
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}
