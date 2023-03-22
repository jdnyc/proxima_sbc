<?php
session_start();
if(empty($_SERVER['DOCUMENT_ROOT'])) {
	$_SERVER['DOCUMENT_ROOT'] = 'C:/Proxima-Apps-Zodiac/nps';
}
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/soap/nusoap.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/ATS.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

try{
	// LOUDNESS 테이블에 진행중이거나 대기중인 작업이 있는지 확인
	$loudness_tasks = $db->queryAll("
							SELECT	L.*, T.TYPE AS TASK_TYPE, T.STATUS AS TASK_STATUS
							FROM	TB_LOUDNESS L
									LEFT OUTER JOIN BC_TASK T ON T.TASK_ID = L.TASK_ID
							WHERE	L.STATE NOT IN ( '1', '2', '3' )
							AND		L.JOBUID IS NOT NULL
							ORDER BY L.LOUDNESS_ID ASC
					");
	
	if(!empty($loudness_tasks)) {
		$ats = new ATS();
		// 있으면 작업 XML 요청하고 리턴값 업데이트
		foreach($loudness_tasks as $task) {
			$loudness_id = $task['loudness_id'];
			$jobUid = $task['jobuid'];
			$task_id = $task['task_id'];
			$task_type = $task['task_type'];
			
			$param = array(
					'jobUuid' => $task['jobuid']
			);
			
			$result = $ats->getJobXML($param);
			
			$xml_string = $result['jobXML'];
			$contents = $db->escape($xml_string);
			$xml = simplexml_load_string($xml_string);
			
			//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ats_test_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] xml ===> '.$xml->AudioToolsServerJob->State."\r\n", FILE_APPEND);
			if($result['getJobXMLResult'] == 0) {
				$state = $xml->AudioToolsServerJob->State;
				$progress = (int)$xml->AudioToolsServerJob->PercentJobCompleted;
				switch($state) {
					case '1' : // error
						$status = 'error';
					break;
					case '2' : // completed
						$status = 'complete';
						if(empty($task['MEASUREMENT_STATE'])) {
							//get the result xml file from local and do the log
							$log_file = $xml->AudioToolsServerJob->ProcessingResults->ProcessResults->LogFilePath;
							if(!empty($log_file)) {
								//$log_file = str_replace('\\', '/', $log_file);
								//$log_file = str_replace('/192.168.1.202/Storage', 'Z:/', $log_file);
								recordMeasurementLog($log_file, $loudness_id);
							}
						}
							
					break;
					case '3' : // stopped(canceled but remains on the server)
						$status = 'cancel';
					break;
					case '6' : // Queued
						$status = 'queue';
					break;
					case '8' : // Starting
						$status = 'processing';
					break;
					case '13' : // Running
						$status = 'processing';
					break;
					default:
						$status = 'queue';
					break;
				}
				// Loudness update
				$db->exec("
					UPDATE	TB_LOUDNESS
					SET		STATE = '$state'
					WHERE	LOUDNESS_ID = $loudness_id
					AND		JOBUID = '$jobUid'
				");
				
				//insert log			
				if(!in_array($status, array(1, 2, 3))) {
					// error, completed, stooped 가 아니면 log insert
					$loudness_log_id = getSequence('LOUDNESS_LOG_SEQ');
					$insert_data = array(
						'LOUDNESS_LOG_ID'	=> $loudness_log_id,
						'LOUDNESS_ID'	=>	$loudness_id,
						'LOG'			=>	':log_content_c',
						'CREATION_DATE'	=>	date('YmdHis')
					);
	
					$query_insert_log = $db->insert('TB_LOUDNESS_LOG', $insert_data, 'not exec');
					$query_insert_log_clob = str_replace("':log_content_c'", ":log_content_c", $query_insert_log);
					
					$db->clob_exec($query_insert_log_clob,':log_content_c',$contents, -1);
				}
				
	
				// update_task_status.php 로도 업데이트
				$task = new TaskManager($db);
				$request = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Request />');
				$request->addChild("TaskID", $task_id );
				$request->addChild("TypeCode", $task_type );
				$request->addChild("Progress", $progress );
				$request->addChild("Status", $status );
				$request->addChild("Ip", $ip);
				$request->addChild("Log", $log);
				$sendxml =  $request->asXML();
				$result = $task->Post_XML_Soket($_SERVER['HTTP_HOST'], '/workflow/update_task_status.php', $sendxml );
			}
		}
	}
	
} catch (Exception $e) {
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ats_interface_err_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] getJobXML error ===> '.$e->getMessage()."\r\n", FILE_APPEND);
}


//record Meauserement result
function recordMeasurementLog($path, $loudness_id) {
	global $db;

	// standard value
	$standard_value = $db->queryOne("
						SELECT	REF3
						FROM	BC_SYS_CODE
						WHERE	CODE = 'INTERWORK_LOUDNESS'
					");
	// target : TimeStamp / TruePeak_dBTP / AverageITU_LKFS / MaximumMomentaryLoudness_LUFS / MaximumShortTermLoudness_LUFS / LoudnessRange_LU
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/test_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] path ===> '.$path."\r\n", FILE_APPEND);

	$xml = simplexml_load_file($path);

	$entries = $xml->Details->Entry;

	if(count($entries) > 0 ) {
		foreach ($entries as $entry) {
			$timestamp = $entry->TimeStamp;
			$measurement = $entry->Program->Measurement;

			$truepeak = $measurement->TruePeak_dBTP;
			$integrate = $measurement->AverageITU_LKFS;
			$momentary = $measurement->MaximumMomentaryLoudness_LUFS;
			$shortterm = $measurement->MaximumShortTermLoudness_LUFS;
			$loudnessrange = $measurement->LoudnessRange_LU;

			$loudness_diff = ($integrate - $standard_value);
			$status = 'P';
			if($loudness_diff > 0 ) {
				$status = 'D';
			}
			$loudness_measurement_log_id = getSequence('LOUDNESS_MEASUREMENT_LOG_SEQ');
			//DB insert
			$r = $db->exec("
					INSERT INTO TB_LOUDNESS_MEASUREMENT_LOG
						(LOUDNESS_ID, LOUDNESS_MEASUREMENT_LOG_ID, TIMESTAMP, TRUEPEAK, INTEGRATE, MOMENTARY, SHORTTERM, LOUDNESSRANGE, TARGETLEVEL, STATUS)
					VALUES
						($loudness_id, $loudness_measurement_log_id ,'$timestamp', '$truepeak', '$integrate', '$momentary', '$shortterm', '$loudnessrange', '', '$status')
				");
		}
	}

	$summary = $xml->Summary;

	$s_measurement = $summary->Program->Measurement;

	$s_truepeak = $s_measurement->TruePeak_dBTP;
	$s_integrate = $s_measurement->AverageITU_LKFS;
	$s_momentary = $s_measurement->MaximumMomentaryLoudness_LUFS;
	$s_shortterm = $s_measurement->MaximumShortTermLoudness_LUFS;
	$s_loudnessrange = $s_measurement->LoudnessRange_LU;

	$loudness_status = 'P';
	if(($s_integrate - $standard_value) > 0 ) {
		$loudness_status = 'D';
	}
	// MEASUREMENT_STATE in TB_LOUDNESS update
	$r2 = $db->exec("
			UPDATE	TB_LOUDNESS
			SET		MEASUREMENT_STATE = '$loudness_status',
			TRUEPEAK = '$s_truepeak',
			INTEGRATE = '$s_integrate',
			MOMENTARY = '$s_momentary',
			SHORTTERM = '$s_shortterm',
			LOUDNESSRANGE = '$s_loudnessrange',
			TARGETLEVEL = ''
			WHERE	LOUDNESS_ID = $loudness_id
			AND		MEASUREMENT_STATE IS NULL
		");

	return true;
}

?>