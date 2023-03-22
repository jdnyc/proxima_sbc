<?php

namespace Proxima\models\loudness;

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/soap/nusoapATS.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

use \Proxima\core\ModelBase;

/**
 * Minnetonka AudioToolsServer를 연계하기 위한 클래스
 * ATS 인터페이스 버전은 ATS3를 사용하여 연계 - 2018.03.05 
 */
class AudioToolsServer extends ModelBase
{
	/**
	 * End Point는 동일하기때문에 추후 장비의 확장성등을 고려해서
	 * IP만 파라미터로 받아서 처리
	 */
	private $connection;
	
	public static function createConnection($serverIp) {
		$soap_url = 'http://'.$serverIp.'/Axis2/services/ATS3_Workflow_Service';
		$connection = new \nusoapATS_client($soap_url, false);
		
		return $connection;
	}

	public function callServerFunction($functionName, $param){
		if(empty($this->connection)) {
			$this->connection = self::createConnection($this->get('serverIp'));
		}

		$return = $this->connection->call($functionName, $param, 'http://tempuri.org/','http://tempuri.org/'.$functionName);

		return $return;
	}

	public static function writeLog($log, $description) {
		if(is_array($description)) {
			@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ats_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] '.$log.' ===> '.print_r($description, true)."\r\n", FILE_APPEND);
		} else {
			@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ats_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] '.$log.' ===> '.$description."\r\n", FILE_APPEND);
		}
    }
    
    /**
     * 고정된 XML형식에 대하여 파일을 읽어들여서 source / output등 필요한 데이터만 변경하여 리턴
     * Error / Result 폴더 위치는 로컬에 고정
     * @param [string] $source
     * @param [string] $output
     * @return void
     */
    public static function makeWorkflowXML($source, $output) {
        $xmlTxtFile = "D:/Dev_project/CJO/src/utils/ATSXML.txt";
        $fp = fopen($xmlTxtFile,"r");
        $xmlStr = fread($fp, filesize($xmlTxtFile));
        /*원본, 저장 경로 변경*/
        $xmlStr = str_replace('%SOURCE%', $source, $xmlStr);
        $xmlStr = str_replace('%OUTPUT%', $output, $xmlStr);
        /* 임시경로, 오류경로, XML 결과값 저장 경로는 고정 */
        $xmlStr = str_replace('%TEMP_DIRECTORY%', 'E:\\Temp', $xmlStr);
        $xmlStr = str_replace('%ERROR_DIRECTORY%', 'E:\\Error', $xmlStr);
        $xmlStr = str_replace('%RESULT_XML_DIRECTORY%', 'E:\\Result', $xmlStr);
        
        return $xmlStr;
    }

	public function getWorkflowList($param) {
        self::writeLog('getWorkflowList Param', $param);
        self::makeWorkflowXML('a', 'b');
		$workflowState = (empty($param['workflowState'])) ? 0 : $param['workflowState'];
		$return = self::callServerFunction('getWorkflowList', array('workflowState'=>$workflowState));
		self::writeLog('getWorkflowList Return', $return);
	}

	/**
	 * 워크플로우 진행상황을 얻어오는 함수
	 * Minnetonka쪽 함수로 얻어온 결과를 파싱해서 필요한 정보만 입력 - 2018.03.26 Alex
	 *
	 * @param [type] $param
	 * @return void
	 */
	public function getWorkflowProgres($param) {
		global $db;
		$task_id = $param['task_id'];
		$workflowUID = $param['workflowUID'];
		$ip = $param['server_ip'];

		$workflowArr = $this->getWorkflowStatus($workflowUID);
		libxml_use_internal_errors(true);
		$workflowXML = simplexml_load_string($workflowArr['workflowStatus']);

		$state = $workflowXML->Workflow->State;
		$percent = (int)$workflowXML->Workflow->PercentCompleted;

		switch($state) {
			case '1' : // error
				$status = 'error';
				break;
			case '2' : // completed
				$status = 'complete';
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

		// update_task_status.php 로도 업데이트
		$task = new \TaskManager($db);
		$request = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Request />');
		$request->addChild("TaskID", $task_id );
		$request->addChild("TypeCode", '55' );
		$request->addChild("Progress", $percent );
		$request->addChild("Status", $status );
		$request->addChild("Ip", $ip);
		$request->addChild("Log", $log);
		$sendxml =  $request->asXML();
		$result = $task->Post_XML_Soket($_SERVER['HTTP_HOST'], '/workflow/update_task_status.php', $sendxml );
		
		return $status;
	}

	/**
	 * 워크플로우 결과를 얻어오는 함수
	 * Minnetonka쪽 함수로 얻어온 결과를 파싱해서 필요한 정보만 입력 - 2018.03.29 Alex
	 *
	 * @param [type] $param
	 * @return void
	 */
	public function getWorkflowProcessingResult($param) {
		global $db;
		$task_id = $param['task_id'];
		$workflowUID = $param['workflowUID'];
		$content_id = $param['content_id'];

		$workflowArr = $this->getWorkflowProcessingResults($workflowUID);
		libxml_use_internal_errors(true);
		$workflowXML = simplexml_load_string($workflowArr['workflowProcessingResults']);
		
		foreach($workflowXML->Step as $step) {
			if($step->StepName == '[1-2]_LOUDNESS_PROCESSING') {
				$processResult = $step->ProcessingResults->ProcessResults[3];
				
				if(!empty($processResult)) {
					$measurement = $processResult->Summary->Program->Measurement;
					$truePeak = $measurement->TruePeak_dBTP;
					$momentary = $measurement->MaximumMomentaryLoudness_LUFS;
					$shortterm = $measurement->MaximumShortTermLoudness_LUFS;
					$integrate = $measurement->ProgrammeLoudness_LUFS;
					$range = $measurement->LoudnessRange_LU;

					/* 결과값 업데이트 */
					$hasResult = $db->queryOne("
									SELECT	COUNT(LOUDNESS_ID)
									FROM	TB_LOUDNESS
									WHERE	TASK_ID = $task_id
								");
					if($hasResult > 0) {
						$db->exec("
							UPDATE	TB_LOUDNESS
							SET		TRUEPEAK = '$truePeak',
									INTEGRATE = '$integrate',
									MOMENTARY = '$momentary',
									SHORTTERM = '$shortterm',
									LOUDNESSRANGE = '$range'
							WHERE	TASK_ID = $task_id
						");
					} else {
						$loudnessId = getSequence('loudness_seq');
						$db->exec("
							INSERT INTO TB_LOUDNESS
								(LOUDNESS_ID, CONTENT_ID, TASK_ID, TRUEPEAK, INTEGRATE, MOMENTARY, SHORTTERM, LOUDNESSRANGE)
							VALUES
								($loudnessId, $content_id, $task_id, '$truePeak', '$integrate', '$momentary', '$shortterm', '$range')
						");
					}
				}
			}
		}
	}
	
	/**
	 * workflowStatus 얻어오는 함수. Minnetonka API 이용 - 2018.03.26 Alex
	 *
	 * @param [string] $workflowUID
	 * @return [string] workflowXML
	 */
	public function getWorkflowStatus($workflowUID) {
		//self::writeLog('getWorkflowStatus Param', $workflowUID);
		$returnXML = $this->callServerFunction('getWorkflowStatus', array('workflowUuid'=>$workflowUID));
		self::writeLog('getWorkflowStatus Return', $returnXML);

		return $returnXML;
	}

	/**
	 * workflowResult 얻어오는 함수. Minnetonka API 이용 - 2018.03.29 Alex
	 *
	 * @param [string] $workflowUID
	 * @return [string] workflowXML
	 */
	public function getWorkflowProcessingResults($workflowUID) {
		self::writeLog('getWorkflowProcessingResults Param', $workflowUID);
		$returnXML = $this->callServerFunction('getWorkflowProcessingResults', array('workflowUuid'=>$workflowUID));
		self::writeLog('getWorkflowProcessingResults Return', $returnXML);

		return $returnXML;
	}

	/**
	 * 완료된 workflow를 Minnetonka 목록에서 지우는 함수. Minnetonka API 이용 - 2018.03.29 Alex
	 *
	 * @param [string] $workflowUID
	 * @return [string] workflowXML
	 */
	public function removeCompletedWorkflow($workflowUID) {
		self::writeLog('removeCompletedWorkflow Param', $workflowUID);
		$returnXML = $this->callServerFunction('removeCompletedWorkflow', array('workflowUuid'=>$workflowUID));
		self::writeLog('removeCompletedWorkflow Return', $returnXML);

		return $returnXML;
	}

	/**
	 * Minnetonka에 워크플로우 진행하도록 XML 전달 - 2018.03.26 Alex
	 * @param [type] $param
	 * @return void
	 */
	public function submitWorkflow($param) {
		global $db;
		self::writeLog('submitWorkflow Param', $param);
		$workflowXML = self::makeWorkflowXML($param['source'], $param['output']);
		//self::writeLog('submitWorkflow XML', $workflowXML);
		$return = $this->callServerFunction('submitWorkflow', array('workflowXML'=>$workflowXML, 'workflowPriority'=>1));
		self::writeLog('submitWorkflow Return', $return);

		if($return['submitWorkflowResult'] == 0) {
			$workflowUID = $return['workflowUID'];
			$task_id = $param['task_id'];
			$agentIp = $param['serverIp'];
			$hasMapData = $db->queryOne("
				SELECT	COUNT(TASK_ID)
				FROM	TB_LOUDNESS
				WHERE	TASK_ID = ".$task_id
			);
			if($hasMapData > 0) {
				/*기존데이터 존재하기때문에 Update*/
				$db->exec("
					UPDATE	TB_LOUDNESS_MAP
					SET		WORKFLOWUID = '".$workflowUID."',
							AGENTIP = '".$agentIp."'
					WHERE	TASK_ID = ".$task_id
				);
			} else {
				/*신규 Insert*/
				$db->exec("
					INSERT INTO TB_LOUDNESS_MAP
						(TASK_ID, WORKFLOWUID, AGENTIP)
					VALUES
						(".$task_id.", '".$workflowUID."', '".$agentIp."')
				");
			}
			
			self::writeLog('submitWorkflow Task_id', $task_id);
			self::writeLog('submitWorkflow Return UID', $workflowUID);
		}
	}
}