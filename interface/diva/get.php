<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

/*
	$qos			= '3'; //quality of service 넣어줄 때.
	아래의 매뉴얼 참고.

DIVArchive.7.1.Cpp API Reference Manual.v1.0.pdf

0 : DIVA_QOS_DEFAULT: Archiving is performed according to the
	default Quality Of Service (currently: direct and cache for archive
	operations).
1 : DIVA_QOS_CACHE_ONLY: Use cache archive only.
2 : DIVA_QOS_DIRECT_ONLY: Use direct archive only. No Disk
	Instance is created.
3 : DIVA_QOS_CACHE_AND_DIRECT: Use cache archive if available or
	direct archive if cache archive is not available.
4 : DIVA_QOS_DIRECT_AND_CACHE: Use direct archive if available or
	cache archive if direct archive is not available.

Additional and optional services are available. To request those
services, use a logical OR between the previously documented
Quality Of Service parameter and the following constants:

DIVA_ARCHIVE_SERVICE_DELETE_ON_SOURCE: Delete source files
when the tape migration is done. Available for local sources, disk
sources, and standard ftp sources. This feature is not available
for Complex Objects.
*/
try {
	//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).'_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] file_get_contents ===> '.file_get_contents('php://input')."\r\n", FILE_APPEND);
	//기본 값
	$default_destinations = 'san';		//소스
	$default_cate = 'cms';		//기본 카테고리값
	$default_group = 'spm_storage';	//기본 DIVA그룹
	//$root_path = '\\\\192.168.56.9\\nrlmg\\archive\\';	// Root Path
	//$root_path = '\\\nrlmg.tbsnps.com\\nrlmg\\archive\\';
	
	usleep(rand(100000,500000)); //0.1초부터 0.5초까지 랜덤 딜레이.
	$db->setLimit(10, 0);
	//t.task_id, t.type, t.status, t.source, t.target, t.parameter
	$task_list = $db->queryAll( "
		SELECT	A.ARCHIVE_SEQ, A.ARCHIVE_ID, A.REQNUM, A.DIVA_CATEGORY, A.DIVA_GROUP, A.DESTINATIONS AS DIVA_DESTINATIONS, T.*
		FROM	BC_TASK T, ARCHIVE A
		WHERE	TYPE IN ('110', '140', '150', '160', '170')
		AND		STATUS IN ('queue', 'processing' ,'process')
		AND		T.ROOT_TASK = A.TASK_ID
		AND		(T.ASSIGN_IP IS NULL OR T.ASSIGN_IP = '".$_SERVER['REMOTE_ADDR']."')
		ORDER BY T.PRIORITY, T.TASK_ID
	");
	//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).'_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] task_list ===> '.print_r($task_list, true)."\r\n", FILE_APPEND);
	if( empty($task_list) ) {
		throw new Exception('작업이 없습니다.');
	}
	
	//먼저 ASSIGN_IP부터 채운다.(중복 방지)
	$arr_sub_task = array();
	
	foreach($task_list as $task) {
		$arr_sub_task[] = $task['task_id'];
	}
	
	$db->exec("
			UPDATE	BC_TASK
			SET		ASSIGN_IP = '".$_SERVER['REMOTE_ADDR']."'
			WHERE	TASK_ID IN (".implode($arr_sub_task, ',').")
			AND		ASSIGN_IP IS NULL
		");

	//업데이트 제대로 된 자신의IP로 할당된 항목에 대해서만 다시 진행.
	$task_list = $db->queryAll( "
		SELECT	A.ARCHIVE_SEQ, A.ARCHIVE_ID, A.REQNUM, A.DIVA_CATEGORY, A.DIVA_GROUP, A.DESTINATIONS AS DIVA_DESTINATIONS, T.*
		FROM	BC_TASK T, ARCHIVE A
		WHERE	TYPE IN ('110', '140', '150', '160', '170')
		AND		STATUS IN ('queue', 'processing' ,'process')
		AND		T.ROOT_TASK = A.TASK_ID
		AND		T.TASK_ID IN (".implode($arr_sub_task, ',').")
		AND		T.ASSIGN_IP = '".$_SERVER['REMOTE_ADDR']."'
		ORDER BY T.PRIORITY, T.TASK_ID
	");

	$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><response><archive_list /></response>');
	
	foreach ($task_list as $task) {
		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).'_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] task ===> '.print_r($task, true)."\r\n", FILE_APPEND);

		//다시 한번 체크해서 자신의 작업이 아니면 건너뜀.
		$check_again = $db->queryRow("
			SELECT	TASK_ID, ASSIGN_IP
			FROM	BC_TASK
			WHERE	TASK_ID = ".$task['task_id']."
		");
		
		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).'_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] check_again ===> '.print_r($check_again, true)."\r\n", FILE_APPEND);
		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).'_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] REMOTE_ADDR ===> '.$_SERVER['REMOTE_ADDR']."\r\n", FILE_APPEND);

		if($check_again['assign_ip'] != $_SERVER['REMOTE_ADDR']) continue;

		$task_type = strtoupper(trim($task['type']));
		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).'_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] task_type ===> '.$task_type."\r\n", FILE_APPEND);
		$media_id = $task['media_id'];
		$archive_seq = $task['archive_seq'];

		$media_info = $db->queryRow("
			SELECT	*
			FROM	BC_MEDIA
			WHERE	MEDIA_ID = ".$media_id."
		");
		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).'_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] media_info ===> '.print_r($media_info, true)."\r\n", FILE_APPEND);
		$content_id = $media_info['content_id'];

		$xml_add_task = $xml->archive_list->addChild('archive');

		$xml_add_task->addAttribute('archive_id',	$task['archive_id']);
		$xml_add_task->addAttribute('task_id',		$task['task_id']);		
		$xml_add_task->addAttribute('status',		$task['status']);
		
		if ( $task_type == 'INFO' || $task_type == '170' ) {
			$xml_add_task->addAttribute('reqnum',		'');
		} else {
			$xml_add_task->addAttribute('reqnum',		$task['reqnum']);
		}

		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).'_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] task_type ===> '.$task_type."\r\n", FILE_APPEND);

		if ( $task_type == 'ARCHIVE' || $task_type == '110' ) {
			//기본값
			$priority		= diva_priority($task['priority'], $task_type);//'50'; //우선순위
			$category		= empty($task['diva_category']) ? $default_cate : $task['diva_category'] ; //카테고리
			$group			= empty($task['diva_group']) ? $default_group : $task['diva_group'] ; //그룹
			$destinations	= empty($task['diva_destinations']) ? $default_group : $task['diva_destinations'] ; //스토리지
			$qos			= '3'; //quality of service

			$xml_add_task->addAttribute('type',			'archive' );
			$xml_add_task->addAttribute('category',		$category);
			$xml_add_task->addAttribute('priority',		$priority);
			$xml_add_task->addAttribute('source',		$destinations);
			$xml_add_task->addAttribute('group',		$group);
			$xml_add_task->addAttribute('qos',			$qos);

			$task['source'] = str_replace("\\\\", "\\", $task['source'] );
			
			$root_path = $db->queryOne("
							SELECT	PATH
							FROM	BC_STORAGE
							WHERE	STORAGE_ID = ".$task['src_storage_id']."
						");

			//한글인 경우 basename으로는 깨지는 경우 발생..
			$conv_file_name = array_pop( explode('/', $task['source']) );
			$conv_file_path = str_replace($conv_file_name, '', $task['source']);
			$full_file_path = str_replace('//', '/', $root_path.'/'.$conv_file_path);
			//$full_file_path = "/".$full_file_path;
		
			$xml_add_task->addAttribute('filepath',		$full_file_path );
			$xml_add_task->addAttribute('path',			$conv_file_name );

			$sub_count = $db->queryAll("
				SELECT	DISTINCT PATH
				FROM	ARCHIVE_GROUP_INFO
				WHERE	ARCHIVE_SEQ = ".$archive_seq."
			");
			@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).'_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] archive_seq ===> '.$archive_seq."\r\n", FILE_APPEND);
			@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).'_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] sub_count ===> '.print_r($sub_count, true)."\r\n", FILE_APPEND);
			if( !empty($sub_count) ) {
				//그룹 인 경우. 전체 넣어주기
				foreach($sub_count as $sub) {
					//그룹일 시 파일패스 정보는 각각에 다 넣지 않는다. 대표 path정보로 하고, 파일명만 넣어준다.
					$conv_file_path = $root_path.'/'.$sub['path'];
					@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).'_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] conv_file_path ===> '.$conv_file_path."\r\n", FILE_APPEND);
					//한글인 경우 basename으로는 깨지는 경우 발생..
					$conv_file_name = array_pop( explode('/', $conv_file_path) );
					@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).'_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] conv_file_name ===> '.$conv_file_name."\r\n", FILE_APPEND);
					$xml_add_sub_task = $xml_add_task->addChild('g_archive');
					//그룹 영상을 하나하나 넣어준다.
					$xml_add_sub_task->addAttribute('filename',		$conv_file_name );
				}
			} else {
				//그룹이 아닌 경우라도 한 건은 넣어주기.
				$xml_add_sub_task = $xml_add_task->addChild('g_archive');
				//그룹 영상을 하나하나 넣어준다.
				$xml_add_sub_task->addAttribute('filename',		$conv_file_name );
			}
		} else if ( $task_type == 'RESTORE' || $task_type == '160' ) {
			//기본값
			$priority		= 100;//diva_priority($task['priority'], $task_type);//'100'; //우선순위
			$category		= empty($task['diva_category']) ? $default_cate : $task['diva_category'] ; //카테고리
			$group			= empty($task['diva_group']) ? $default_group : $task['diva_group'] ; //그룹
			$destinations	= empty($task['diva_destinations']) ? $default_group : $task['diva_destinations'] ; //스토리지
			$qos			= '4'; //quality of service

			$xml_add_task->addAttribute('type',			'restore');

			$xml_add_task->addAttribute('category',		$category);
			$xml_add_task->addAttribute('priority',		$priority);
			$xml_add_task->addAttribute('qos',			$qos);//캐시&다이렉트인지 다이렉트&캐시인지 여부
			$xml_add_task->addAttribute('destinations',	$destinations );

			$task['target'] = str_replace("\\\\", "\\", $task['target'] );

			// $sub_count = $db->queryAll("
			// 				SELECT	DISTINCT PATH
			// 				FROM	ARCHIVE_GROUP_INFO
			// 				WHERE	CONTENT_ID = ".$content_id."
			// 			");
			// if( !empty($sub_count) ) {//그룹소재인 경우
			// 	if(strstr($task['target'], '.')) {
			// 		$task['target'] = dirname($task['target']);
			// 	}

			// 	$filepath = $task['target'];
			// } else {
			// 	$filepath = dirname($task['target']);
			// }
			//root path
			$root_path = $db->queryOne("
							SELECT	PATH
							FROM	BC_STORAGE
							WHERE	STORAGE_ID = ".$task['trg_storage_id']."
						");
			//한글인 경우 basename으로는 깨지는 경우 발생..
			$conv_file_name = array_pop( explode('/', $task['target']) );
			$conv_file_path = str_replace($conv_file_name, '', $task['target']);
			$full_file_path = str_replace('//', '/', $root_path.'/'.$conv_file_path);
			//$full_file_path = "/".$full_file_path;

			//$xml_add_task->addAttribute('filepath', $full_file_path );
			//$xml_add_task->addAttribute('filename', $conv_file_name );
			$xml_add_task->addAttribute('filepath', '' );
			$xml_add_task->addAttribute('path', $full_file_path );

			@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).'_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] xml_add_task ===> '.print_r($xml_add_task, true)."\r\n", FILE_APPEND);

		} else if ( $task_type == 'PFR_RESTORE' || $task_type == '140') {
			//기본값
			$priority		= diva_priority($task['priority'], $task_type);//'100'; //우선순위
			$category		= empty($task['diva_category']) ? $default_cate : $task['diva_category'] ; //카테고리
			$group			= empty($task['diva_group']) ? $default_group : $task['diva_group'] ; //그룹
			$destinations	= empty($task['diva_destinations']) ? $default_group : $task['diva_destinations'] ; //스토리지
			$qos			= '4'; //quality of service

			$xml_add_task->addAttribute('type',			'pfr_restore');

			$xml_add_task->addAttribute('category',		$category);
			$xml_add_task->addAttribute('priority',		$priority);
			$xml_add_task->addAttribute('qos',				$qos );
			$xml_add_task->addAttribute('destinations',		$destinations );

			list($start, $end, $target_path) = explode(' ', $task['parameter']);
			
			//root path
			$root_path = $db->queryOne("
							SELECT	PATH
							FROM	BC_STORAGE
							WHERE	STORAGE_ID = ".$task['trg_storage_id']."
						");

			$task['target'] = str_replace("\\\\", "\\", $task['target'] );
			$conv_file_name = array_pop( explode('/', $task['target']) );
			$conv_file_path = str_replace($conv_file_name, '', $task['target']);
			$full_file_path = str_replace('//', '/', $root_path.'/'.$conv_file_path);
			//$full_file_path = "/".$full_file_path;

			//$xml_add_task->addAttribute('filename', 		$target_path );
			$xml_add_task->addAttribute('path', 			$target_path );
// 			$xml_add_task->addAttribute('pfr_path',			dirname($task['target']));
			$xml_add_task->addAttribute('pfr_path',			$full_file_path );
			$xml_add_task->addAttribute('pfr_name',			$conv_file_name );

			$xml_add_task->addAttribute('start',			$start);
			$xml_add_task->addAttribute('end', 				$end);

		} else if ( $task_type == 'DELETE' || $task_type == '150') {
			
			$priority		= diva_priority($task['priority'], $task_type);//'20'; //우선순위
			$category		= empty($task['diva_category']) ? $default_cate : $task['diva_category'] ; //카테고리

			$xml_add_task->addAttribute('type',			'delete');
			$xml_add_task->addAttribute('category',		$category);
			$xml_add_task->addAttribute('priority',		$priority);
			// delete시는 추가되는 속성값이 없슴.
			
		} else if ( $task_type == 'INFO' || $task_type == '170' ) {
			
			$priority		= diva_priority($task['priority'], $task_type);//'20'; //우선순위
			$category		= empty($task['diva_category']) ? $default_cate : $task['diva_category'] ; //카테고리

			$xml_add_task->addAttribute('type',			'info');
			$xml_add_task->addAttribute('category',		$category);
			$xml_add_task->addAttribute('priority',		$priority);
			
		} else {
			//알 수 없는 작업
			throw new Exception('알 수 없는 작업 타입('.$task['type'].')', 1);
		}

		if(  $task['status'] == "queue") {
			$result = $db->exec("
						UPDATE	BC_TASK
						SET		STATUS = 'processing',
								START_DATETIME = '".date('YmdHis')."',
								ASSIGN_IP = '".$_SERVER['REMOTE_ADDR']."'
						WHERE	TASK_ID = ".$task['task_id']
					);
		} else {
			$result = $db->exec("
						UPDATE	BC_TASK
						SET		ASSIGN_IP = '".$_SERVER['REMOTE_ADDR']."'
						WHERE	TASK_ID = ".$task['task_id']
					);
		}
	}

	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).'_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] asXML ===> '.$xml->asXML()."\r\n", FILE_APPEND);

	echo $xml->asXML();
} catch (Exception $e) {
	
	$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><response><archive_list /></response>');
	
	$xml->archive_list->addAttribute('success', 'false');
	$xml->archive_list->addAttribute('message', $e->getMessage());
	$xml->archive_list->addAttribute('query', $db->last_query);
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).'_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] error ===> '.$xml->asXML()."\r\n", FILE_APPEND);
	echo $xml->asXML();
}
?>
