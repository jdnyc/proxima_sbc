<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
function ExecuteTaskODA($data)
{
	global $arr_sys_code;
	global $db;
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ExecuteTaskODA'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'interwork_oda_ods_d:::'.print_r($arr_sys_code['interwork_oda_ods_d']['ref2'], true)."\n", FILE_APPEND);
	
//	$function = 'SoapOdaRestore';
//
//	$param = array(
//			'value' => $data
//	);
//	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ExecuteTaskODA'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'function:::'.$function."\n", FILE_APPEND);
//	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ExecuteTaskODA'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'$param:::'.print_r($param, true)."\n", FILE_APPEND);
//
//	$client = new nusoap_client($arr_sys_code['interwork_oda_ods_d']['ref2'], true);
//	
//	
//	$client->xml_encoding = "UTF-8";
//	$client->soap_defencoding = "UTF-8";
//	$client->decode_utf8 = false;
//	//$client = new nusoap_client('http://192.168.1.109:8080/wsdl/IODA_IFService');
//	//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ExecuteTaskODA'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'SOAP:::'.print_r($client, true)."\n", FILE_APPEND);
//	if ( $err = $client->getError() ) {
//		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ExecuteTaskODA'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'First client->getError:::'.print_r($client->getError(), true)."\n", FILE_APPEND);
//	}
//	$result = $client->call($function, $param);
//	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ExecuteTaskODA'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'result:::'.print_r($result, true)."\n", FILE_APPEND);
//	
//	if ($client->fault) {
//		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ExecuteTaskODA'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'client->fault:::'.print_r($client->fault, true)."\n", FILE_APPEND);
//	}
//	if ( $err = $client->getError() ) {
//		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ExecuteTaskODA'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'client->getError:::'.print_r($client->getError(), true)."\n", FILE_APPEND);
//	}
//
//	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ExecuteTaskODA'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'getDebug:::'.print_r($client->getDebug(),true)."\n", FILE_APPEND);
//
//	//soap server check 
//	$err = $client->getError();
//	if ($err) {
//		$result['status'] = 1;
//		//$result['message'] = '<pre>ODA 연결 상태를 확인하세요. ODA Server error.</pre>';
//		$result['message'] = '<pre>ODA '._text('MSG02132').'</pre>';
//		return $result;
//	}
//	
//	//error check
//	if ($client->fault) {
//		$result['status'] = 1;
//		$result['message'] = '<h2>Fault (Expect - The request contains an invalid SOAP body)</h2><pre>'; print_r($result); echo '</pre>';
//	} else {
//		$err = $client->getError();
//		
//		if ($err) {
//			//soap module check
//			$result['status'] = 1;
//			$result['message'] = '<h2>Error</h2><pre>' . $err . '</pre>';
//		} else {
//			$v_status = '0';
//			$v_message = '0';
//			if($result['return']['ResultCode'] == '0'){
//				$v_status = '0';
//				//$v_message = '연결 상태 정상';
//				$result['message'] = _text('MSG02133');
//			}else if($result['return']['ResultCode'] == '1'){
//				$v_status = '1';
//				//$v_message = '작업중입니다. 잠시 후 다시 시도해주세요.';
//				$result['message'] = _text('MSG02134');
//			}else if($result['return']['ResultCode'] == '2'){
//				$v_status = '1';
//				//$v_message = $data['CartridgeID'] . ' 카트리지를 삽입해주세요.';
//				$result['message'] = _text('MSG02135');
//			}else if($result['return']['ResultCode'] == '3'){
//				$v_status = '1';
//				//$v_message = '콘텐츠ID 오류';
//				$result['message'] = _text('MSG02136');
//			}else if($result['return']['ResultCode'] == '4'){
//				$v_status = '1';
//				//$v_message = 'ODA 연결 실패';
//				$result['message'] = _text('MSG02137');
//			}
//
//			$result['status'] = $v_status;
//			$result['message'] = $v_message;
//		}
//	}


	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ExecuteTaskODA'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'SOAP function:::'.'SoapOdaRestore'."\n", FILE_APPEND);
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ExecuteTaskODA'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'SOAP data:::'.print_r($data, true)."\n", FILE_APPEND);
	
	$v_reslt_arr =	array();
	$client = new SoapClient( $arr_sys_code['interwork_oda_ods_d']['ref2'] );
	$v_return = $client->SoapOdaRestore($data);
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ExecuteTaskODA'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'SOAP return1:::'.print_r($v_return, true)."\n", FILE_APPEND);
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ExecuteTaskODA'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'SOAP return2:::'.$v_return->ResultCode."\n", FILE_APPEND);
	
	if( !empty($v_return) ){
		$v_status = '0';
		$v_message = '0';
		if($v_return->ResultCode == '0'){
			$v_status = '0';
			//$v_message = '연결 상태 정상';
			$v_message = _text('MSG02133');
		}else if($v_return->ResultCode == '1'){
			@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ExecuteTaskODA'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'SOAP return3:::'.$v_return->ResultCode."\n", FILE_APPEND);
			@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ExecuteTaskODA'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'SOAP MSG02134:::'._text('MSG02134')."\n", FILE_APPEND);
			$v_status = '1';
			//$v_message = '작업중입니다. 잠시 후 다시 시도해주세요.';
			$v_message = _text('MSG02134');
			@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ExecuteTaskODA'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'SOAP return3:::'.$v_return->ResultCode."\n", FILE_APPEND);
		}else if($v_return->ResultCode == '2'){
			$v_status = '1';
			//$v_message = $data['CartridgeID'] . ' 카트리지를 삽입해주세요.';
			$v_message = _text('MSG02135');
		}else if($v_return->ResultCode == '3'){
			$v_status = '1';
			//$v_message = '콘텐츠ID 오류';
			$v_message = _text('MSG02136');
		}else if($v_return->ResultCode == '4'){
			$v_status = '1';
			//$v_message = 'ODA 연결 실패';
			$v_message = _text('MSG02137');
		}
		
		if($v_return->ResultCode != '0'){
			$content_id = $data['ContentID'];
			$v_msg = 'error';
			$task_id = $data['TaskID'];
			$cartridge_id = $data['CartridgeID'];
			
			$v_query = "
					UPDATE	BC_TASK
					SET		STATUS				= '".$v_msg."'
							,COMPLETE_DATETIME	= '".date('YmdHis')."'
					WHERE	TASK_ID				= $task_id
					";
			
			@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ExecuteTaskODA'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'$v_query:::'.$v_query."\n", FILE_APPEND);
			
			$q = $db->exec($v_query);
			
			$v_query = "
					UPDATE	BC_ARCHIVE_REQUEST
					SET		TAPE_ID				= '".$cartridge_id."'
							,STATUS				= 'APPROVE'
							,FAILED_REASON		= '".$v_message."'
							,FAILED_DATETIME	= '".date('YmdHis')."'
							,COMPLETE_DATETIME	= '".date('YmdHis')."'
					WHERE	TASK_ID				= $task_id
					";
			$db->exec($v_query);
			
			@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ExecuteTaskODA'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'$v_query:::'.$v_query."\n", FILE_APPEND);
		}

		$v_reslt_arr['status'] = $v_status;
		$v_reslt_arr['message'] = $v_message;
	}

	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ExecuteTaskODA'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'$v_reslt_arr:::'.print_r($v_reslt_arr, true)."\n", FILE_APPEND);
	
	return $v_reslt_arr;
}
?>