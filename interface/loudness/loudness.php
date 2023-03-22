<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/ATS.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

try{
	// 요청작업 중에 상태값이 0(요청만 된상태) 인 것이 있는지 체크
	$loudness_tasks = $db->queryAll("
							SELECT	L.*, T.TYPE, T.SOURCE, T.TARGET,
									(SELECT PATH FROM BC_STORAGE WHERE STORAGE_ID = T.SRC_STORAGE_ID) AS SOURCE_ROOT,
									(SELECT PATH FROM BC_STORAGE WHERE STORAGE_ID = T.TRG_STORAGE_ID) AS TARGET_ROOT
							FROM	TB_LOUDNESS L
									LEFT OUTER JOIN BC_TASK T ON T.TASK_ID = L.TASK_ID
							WHERE	L.STATE = 0
							AND		JOBUID IS NULL
							ORDER BY L.LOUDNESS_ID ASC
					");
	
	$ats = new ATS();
	
	foreach($loudness_tasks as $task) {
		$loudness_id = $task['loudness_id'];
		$user_id = $task['req_user_id'];
		$source = $task['source_root'].'/'.$task['source'];
		$target = $task['target_root'].'/'.$task['target'];
		
		//TEST용
		// 네트워크 드라이브(드라이브 문자)는 안됨
		// UNC Path는 local일 경우는 가능
		// UNC Path는 타IP 일 경우 불가능
		// 2016.04.01 임찬모 신기철 확인
// 		$source= 'c:/temp/ATS_InputFiles/Sample Video Files/XDCAM-HD422.mxf';
		$new_path = str_replace('/', '\\', $task['source']);
		//$source = '\\\\192.168.1.202\\Storage\\Storage\\highres\\'.$new_path;
		//$source = '\\\\192.168.1.202\\Storage\\305.MXF';
		//$source = str_replace('/', '\\', $source);
		$source = '\\\\192.168.1.202\\Storage\\Storage\\highres\\'.$new_path;
		switch($task['req_type']) {
			case 'M' : 
				$xml = createLoudnessMeasurementXML($user_id, $source);
				$front_position = strpos($xml, '<AudioToolsServer Version="0.1">');
				$end_position = strpos($xml, '</Response>');

				$xml = substr($xml, $front_position);
				$xml = substr($xml, 0, '-'.(strlen('</Response>')+1));

				$param = array(
					'jobXML' => $xml,
					'jobPriority' => '1'
				);
				
			break;
			case 'C' :
			break;
		}
		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ats_test_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] param ===> '.print_r($param, true)."\r\n", FILE_APPEND);
		$result = $ats->submitJob($param);
		// $result = $ats-> submitJob($param);
		if($result['submitJobResult'] == 0) {
			$jobUID = $result['jobUID'];
			//update
			$db->exec("
				UPDATE	TB_LOUDNESS
				SET		JOBUID = '$jobUID'
				WHERE	LOUDNESS_ID = $loudness_id
				AND		JOBUID IS NULL
			");
		}
	}
	
	
} catch (Exception $e) {
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ats_interface_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] submit error ===> '.$e->getMessage()."\r\n", FILE_APPEND);	
}