<?php
class SGL
{
	private $arr_sys_code = null;
	public $soap_url = '';//'http://192.168.1.200:8080/wsdl/IWebService?wsdl';

	public function __construct() {
		global $arr_sys_code;
		$this->arr_sys_code = $arr_sys_code;
		$this->soap_url = $arr_sys_code['interwork_flashnet']['ref4'];
	}

	function _log($log){
		@file_put_contents(LOG_PATH.'/SGL_'.date('Ymd').'.log', '['.date('Y-m-d H:i:s').'] '.print_r($log,true)."\r\n", FILE_APPEND);
	}

	// Archive request
	function FlashNetArchive($strGuid, $strFilePath, $strGroupName, $strDisplayName, $strArchivePriority){
		$client = new SoapClient( $this->soap_url );

		$strDisplayName = mb_strcut($strDisplayName, 0, 128, "UTF-8");
		$this->_log("FlashNetArchive($strGuid, $strFilePath, $strGroupName, $strDisplayName, $strArchivePriority)");
		$return = $client->FlashNetArchive($strGuid, $strFilePath, $strGroupName, $strDisplayName, $strArchivePriority);

		$this->_log($return);

		if( !empty($return) ){

			libxml_use_internal_errors(true);
			$rtn = simplexml_load_string($return);

			if (!$rtn) {
				foreach(libxml_get_errors() as $error){
					$err_msg .= $error->message . "\n";
				}

				$this->_log($err_msg);
				$result = array(
					'success' => false,
					'msg' => $err_msg
				);
			} else {
				if($rtn[Status] == 'Passed') {
					$result = array(
						'success' => true,
						'request_id' => $rtn[RequestId],
						'uan' => $rtn[UAN]
					);

				} else {
					$msg = $rtn[Error];
					$result = array(
						'success' => false,
						'msg' => $msg
					);
				}
			}
		}
		return $result;
	}

	// Group Archive request
	function FlashNetGroupArchive($strGuid, $strArrayFilePath, $strGroupName, $strDisplayName, $strArchivePriority){
		$client = new SoapClient( $this->soap_url );

		$strDisplayName = mb_strcut($strDisplayName, 0, 128, "UTF-8");
		$this->_log("FlashNetGroupArchive($strGuid, ".print_r($strArrayFilePath,true).", $strGroupName, $strDisplayName, $strArchivePriority)");
		$return = $client->FlashNetGroupArchive($strGuid, $strArrayFilePath, $strGroupName, $strDisplayName, $strArchivePriority);

		$this->_log($return);

		if( !empty($return) ){

			libxml_use_internal_errors(true);
			$rtn = simplexml_load_string($return);

			if (!$rtn) {
				foreach(libxml_get_errors() as $error){
					$err_msg .= $error->message . "\n";
				}

				$this->_log($err_msg);
				$result = array(
					'success' => false,
					'msg' => $err_msg
				);
			} else {
				if($rtn[Status] == 'Passed') {
					$result = array(
						'success' => true,
						'request_id' => $rtn[RequestId],
						'uan' => $rtn[UAN]
					);

				} else {
					$msg = $rtn[Error];
					$result = array(
						'success' => false,
						'msg' => $msg
					);
				}
			}
		}
		return $result;
	}

	// Sequence archive request
	function FlashNetSequnceArchive($strGuid, $strFirstFilePath, $strGroupName, $strDisplayName, $strArchivePriority, $strFileCount){
		$client = new SoapClient( $this->soap_url );

		$strDisplayName = mb_strcut($strDisplayName, 0, 128, "UTF-8");
		$this->_log("FlashNetSequnceArchive($strGuid, $strFirstFilePath, $strGroupName, $strDisplayName, $strArchivePriority,$strFileCount)");
		$return = $client->FlashNetSequnceArchive($strGuid, $strFirstFilePath, $strGroupName, $strDisplayName, $strArchivePriority,$strFileCount);
		$this->_log($return);

		if( !empty($return) ){

			libxml_use_internal_errors(true);
			$rtn = simplexml_load_string($return);

			if (!$rtn) {
				foreach(libxml_get_errors() as $error){
					$err_msg .= $error->message . "\n";
				}

				$this->_log($err_msg);
				$result = array(
					'success' => false,
					'msg' => $err_msg
				);
			} else {
				if($rtn[Status] == 'Passed') {
					$result = array(
						'success' => true,
						'request_id' => $rtn[RequestId],
						'uan' => $rtn[UAN]
					);

				} else {
					$msg = $rtn[Error];
					$result = array(
						'success' => false,
						'msg' => $msg
					);
				}
			}
		}
		return $result;
	}

	// restore request
	function FlashNetRestore($strGuid, $strFilePath, $strDisplayName, $strRestorePriority, $strPartialStart, $strPartialEnd){
		$client = new SoapClient( $this->soap_url );

		$strDisplayName = mb_strcut($strDisplayName, 0, 128, "UTF-8");
		$this->_log("FlashNetRestore($strGuid, $strFilePath, $strDisplayName, $strRestorePriority, $strPartialStart, $strPartialEnd)");
		$return = $client->FlashNetRestore($strGuid, $strFilePath, $strDisplayName, $strRestorePriority, $strPartialStart, $strPartialEnd);

		$this->_log($return);

		if( !empty($return) ){

			libxml_use_internal_errors(true);
			$rtn = simplexml_load_string($return);

			if (!$rtn) {
				foreach(libxml_get_errors() as $error){
					$err_msg .= $error->message . "\n";
				}
				$this->_log($err_msg);
				$result = array(
						'success' => false,
						'msg' => $err_msg
					);
			} else {
				if($rtn[Status] == 'Passed') {
					$result = array(
						'success' => true,
						'request_id' => $rtn[RequestId]
					);

				} else {
					$msg = $rtn[Error];
					$result = array(
						'success' => false,
						'msg' => $msg
					);
				}
			}
		}
		return $result;
	}


	// restore request by UAN
	function FlashNetRestoreUAN($strUAN, $strFilePath, $strDisplayName, $strRestorePriority){
		$client = new SoapClient( $this->soap_url );

		$strDisplayName = mb_strcut($strDisplayName, 0, 128, "UTF-8");
		$this->_log("FlashNetRestoreUAN($strUAN, $strFilePath, $strDisplayName, $strRestorePriority)");
		$return = $client->FlashNetRestoreUAN($strUAN, $strFilePath, $strDisplayName, $strRestorePriority);

		$this->_log($return);

		if( !empty($return) ){

			libxml_use_internal_errors(true);
			$rtn = simplexml_load_string($return);

			if (!$rtn) {
				foreach(libxml_get_errors() as $error){
					$err_msg .= $error->message . "\n";
				}
				$this->_log($err_msg);
				$result = array(
						'success' => false,
						'msg' => $err_msg
					);
			} else {
				if($rtn[Status] == 'Passed') {
					$result = array(
						'success' => true,
						'request_id' => $rtn[RequestId]
					);

				} else {
					$msg = $rtn[Error];
					$result = array(
						'success' => false,
						'msg' => $msg
					);
				}
			}
		}
		return $result;
	}


	// Archive delete
	function FlashNetDelete($strGuid){
		$client = new SoapClient( $this->soap_url );

		$this->_log("FlashNetDelete($strGuid)");
		$return = $client->FlashNetDelete($strGuid);

		$this->_log($return);

		if( !empty($return) ){

			libxml_use_internal_errors(true);
			$rtn = simplexml_load_string($return);

			if (!$rtn) {
				foreach(libxml_get_errors() as $error){
					$err_msg .= $error->message . "\n";
				}

				$this->_log($err_msg);
				$result = array(
						'success' => false,
						'msg' => $err_msg
					);
			} else {
				if($rtn[Status] == 'Passed') {
					$result = array(
						'success' => true,
						'status' => $rtn[Status]
					);

				} else {
					$msg = $rtn[Error];
					$result = array(
						'success' => false,
						'msg' => $msg
					);
				}
			}
		}
		return $result;
	}

	// Stop task
	function FlashNetStopJob($strGuid){
		$client = new SoapClient( $this->soap_url );

		$this->_log("FlashNetStopJob($strGuid)");
		$return = $client->FlashNetStopJob($strGuid);

		$this->_log($return);

		if( !empty($return) ){

			libxml_use_internal_errors(true);
			$rtn = simplexml_load_string($return);

			if (!$rtn) {
				foreach(libxml_get_errors() as $error){
					$err_msg .= $error->message . "\n";
				}

				$this->_log($err_msg);
				$result = array(
						'success' => false,
						'msg' => $err_msg
					);
			} else {
				if($rtn[Status] == 'Passed') {
					$result = array(
						'success' => true,
						'status' => $rtn[Status]
					);

				} else {
					$msg = $rtn[Error];
					$result = array(
						'success' => false,
						'msg' => $msg
					);
				}
			}
		}
		return $result;
	}


	// Get group info
	function FlashNetListGroup($strGuid){
		$client = new SoapClient( $this->soap_url );

		$this->_log("FlashNetListGroup($strGuid)");
		$return = $client->FlashNetListGroup($strGuid);

		$this->_log($return);

		if( !empty($return) ){

			libxml_use_internal_errors(true);
			$rtn = simplexml_load_string($return);

			if (!$rtn) {
				foreach(libxml_get_errors() as $error){
					$err_msg .= $error->message . "\n";
				}

				$this->_log($err_msg);
			} else {
				if($rtn[Status] == 'Passed') {
					$result = array(
						'success' => true,
						'groups' => $rtn->GroupDetails
					);

				} else {
					$msg = $rtn[Error];
					$result = array(
						'success' => false,
						'msg' => $msg
					);
				}
			}
		}
		return $result;
	}

	// Get server list
	function FlashNetListServer($strGuid){
		$client = new SoapClient( $this->soap_url );

		$this->_log("FlashNetListServer($strGuid)");
		$return = $client->FlashNetListServer($strGuid);

		$this->_log($return);

		if( !empty($return) ){

			libxml_use_internal_errors(true);
			$rtn = simplexml_load_string($return);

			if (!$rtn) {
				foreach(libxml_get_errors() as $error){
					$err_msg .= $error->message . "\n";
				}

				$this->_log($err_msg);
			} else {
				if($rtn[Status] == 'Passed') {
					$result = array(
						'success' => true,
						'servers' => $rtn->ServerDetails
					);

				} else {
					$msg = $rtn[Error];
					$result = array(
						'success' => false,
						'msg' => $msg
					);
				}
			}
		}
		return $result;
	}

	//Get guid info. Show in Flashnet log top grid
	function FlashNetListGuid($strGuid){
		$client = new SoapClient( $this->soap_url );
		
		$this->_log("FlashNetListGuid($strGuid)");
		$return = $client->FlashNetListGuid($strGuid);

		$this->_log($return);

		if( !empty($return) ){

			libxml_use_internal_errors(true);
			$rtn = simplexml_load_string($return);

			if (!$rtn) {
				foreach(libxml_get_errors() as $error){
					$err_msg .= $error->message . "\n";
				}

				$this->_log($err_msg);
			} else {
				if($rtn[Status] == 'Passed') {
					$result = array(
						'success' => true,
						'groups' => $rtn->FileDetails 
					);

				} else {
					$msg = $rtn[Error];
					$result = array(
						'success' => false,
						'msg' => $msg 
					);
				}
			}
		}
		return $result;
	}

	// SGL LOG. Show in Flashnet log bottom grid.
	function FlashNetReadLog($strGuid){
		$client = new SoapClient( $this->soap_url );

		$this->_log("FlashNetReadLog($strGuid)");
		$return = $client->FlashNetReadLog($strGuid);

		$this->_log($return);

		$return = str_replace("\n", "&lt;br /&gt;", $return);
		if( !empty($return) ){

			libxml_use_internal_errors(true);
			$rtn = simplexml_load_string($return);

			if (!$rtn) {
				foreach(libxml_get_errors() as $error){
					$err_msg .= $error->message . "\n";
				}

				$this->_log($err_msg);
			} else {
				if($rtn[Status] == 'Passed') {
					$str_num = strpos($rtn[LogText], 'Volume');
					$volume = substr($rtn[LogText], $str_num);
					$str_num = strpos($volume, "'");
					$str_num2 = strpos($volume, "'", 8);
					$volume = substr($volume, $str_num, $str_num2 - $str_num);
					$volume = str_replace("'","",$volume);
					$result = array(
							'success' => true,
							'logs' => $rtn[LogText],
							'volume' => $volume
					);

				} else {
					$msg = $rtn[Error];
					$result = array(
							'success' => false,
							'msg' => $msg
					);
				}
			}
		}
		return $result;
	}

	// Get SGL status per Guid
	function getFlashNetStatus($strGuid){
		$client = new SoapClient( $this->soap_url );

		$this->_log("FlashNetStatus($strGuid)");
		$return = $client->FlashNetStatus($strGuid);

		$this->_log($return);

		$return = str_replace("\n", "&lt;br /&gt;", $return);
		if( !empty($return) ){

			libxml_use_internal_errors(true);
			$rtn = simplexml_load_string($return);

			if (!$rtn) {
				foreach(libxml_get_errors() as $error){
					$err_msg .= $error->message . "\n";
				}

				$this->_log($err_msg);
			} else {
				if($rtn[Status] == 'Passed') {
					$result = array(
							'success' => true,
							'status' => $rtn->StatusInfo
					);

				} else {
					$msg = $rtn[Error];
					$result = array(
							'success' => false,
							'msg' => $msg
					);
				}
			}
		}
		return $result;
	}

}