<?php
set_time_limit(600);
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/interface.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/MetaData.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/soap/nusoap.php');
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/Search.class.php';
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/archive.class.php');

//Ajax로 이 페이지를 부르거나
//include_once로 호출할 수도 있음
$mode_g = 'include';
if(empty($mode))
{
	$mode = $_REQUEST['mode'];
	$mode_g = 'ajax';
}

if(empty($data))
{
	$data = $_REQUEST['data'];
}

if(empty($xml_mode))
{
	$xml_mode = $_REQUEST['xml_mode'];
}


if(empty($data_type))
{
	$data_type = $_REQUEST['data_type'];
}


try
{
	file_put_contents(LOG_PATH.'/SOAP_client_common'.date('Ymd').'.log', date('H:i:s')."\n\n"."mode11:".$mode."\n\n", FILE_APPEND);
	//SOAP Client 함수들은 작업 끝난 후 결과를 $result에 배열로 넣어줌
	switch($mode)
	{
/* 상암동 <-> 광화문 영상전송 */
		case 'ODA_Restore_Accept_ExecuteTask':
			//리스토어 승인 ODA
			include_once('ExecuteTaskODA.php');
			$result = ExecuteTaskODA($data);
		break;
/*       */
		case '':
		break;
		default: 
			throw new Exception("ODA ODS-D Soap client : error mode($mode)");
		break;
	}
	
	if($result['message'] != '' || $result['status'] != '0') {
		throw new Exception($result['message']);
	}

	//리턴로그 안남길 인터페이스
	if(!in_array($mode, array('MtrlStatus'))) {
		file_put_contents(LOG_PATH.'/SOAP_client_common'.date('Ymd').'.log', date('H:i:s')."\n\n".print_r($result, true)."\n\n", FILE_APPEND);
	}

	if($mode_g == 'ajax')
	{
		echo json_encode(array(
			'success' => true,
			'result' => $result,
			'msg' => '성공'
		));
	}
	else
	{
		$success_return = true;
		$include_return = array(
			'success' => true,
			'result' => $result,
			'msg' => '성공'
		);
	}
}
catch(Exception $e)
{
	if($mode_g == 'ajax')
	{
		echo json_encode(array(
			'success' => false,
			'msg' => $e->getMessage()
		));
	}
	else
	{
		$success_return = false;
		$include_return = array(
			'success' => false,
			'msg' => $e->getMessage()
		);
	}
}

?>