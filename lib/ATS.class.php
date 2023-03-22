<?php
class ATS
{
	const SOAP_URL = LOUDNESS_SERVER_IP;
	
	public $soap;

	function __construct() 
	{
		$this->soap = new nusoap_client(self::SOAP_URL,false);
	}
	
	static function _LogFile($name,$contents){
		if(is_array($contents)) {
			@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ats_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] '.$name.' ===> '.print_r($contents, true)."\r\n", FILE_APPEND);
		} else {
			@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ats_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] '.$name.' ===> '.$contents."\r\n", FILE_APPEND);
		}
	}
	
	/**
	 * changeJobPriority
	 * 현재 진행중인 작업의 갯수 얻어오는 함수
	 * @param $param : jobUuid, newJobPriority
	 * @return XML
	 */
	function changeJobPriority($param) {
		$return = $this->soap->call('changeJobPriority', '','http://tempuri.org/','http://tempuri.org/changeJobPriority');
		$this->_LogFile('test', $return);
		return $return;
	}
	
	/**
	 * getConcurrentJobs
	 * 현재 진행중인 작업의 갯수 얻어오는 함수
	 * @param $param : null
	 * @return XML
	 */
	function getConcurrentJobs($param) {
		$return = $this->soap->call('getConcurrentJobs', '','http://tempuri.org/','http://tempuri.org/getConcurrentJobs');
		$this->_LogFile('getConcurrentJobs', $return);
		return $return;
	}
	
	/**
	 * getJobList
	 * Job에 대한 XML을 가져오는 함수
	 * @param $param : null
	 * @return XML
	 */
	function getJobList($param) {
		$return = $this->soap->call('getJobList', '','http://tempuri.org/','http://tempuri.org/getJobList');
		$this->_LogFile('getJobList', $return);
		return $return;
	}

	/**
	* getJobXML
	* Job에 대한 XML을 가져오는 함수
	* @param $param : jobUuid
	* @return XML
	*/
	function getJobXML($param) {
		$return = $this->soap->call('getJobXML', array('jobUuid'=>$param['jobUuid']),'http://tempuri.org/','http://tempuri.org/getJobXML');
 		$this->_LogFile('getJobXML', $return['jobXML']);
		return $return;
	}
	
	/**
	 * submitJob
	 * 신규작업 추가 함수 함수
	 * @param $param : null
	 * @return XML
	 */
	function submitJob($param) {
		$return = $this->soap->call('submitJob', array('jobXML'=>$param['jobXML'], 'jobPriority'=>$param['jobPriority']),'http://tempuri.org/','http://tempuri.org/submitJob');
		$this->_LogFile('submitJob', $return);
		return $return;
	}
}
