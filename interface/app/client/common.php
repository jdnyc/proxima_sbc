<?php
set_time_limit(600);
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/interface.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/MetaData.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/soap/nusoap.php');

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

try
{
	//SOAP Client 함수들은 작업 끝난 후 결과를 $result에 배열로 넣어줌
	switch($mode)
	{
//		case 'CMStoNPS':
//			//보도CMS자료를 NPS로 신규등록
//			require_once('CMStoNPS.php');
//			$result = CMStoNPS($data);
//		break;		
		case 'GetDasPgmCategory':
			//DAS에서 프로그램 카테고리 정보 불러오기
			require_once('GetDasPgmCategory.php');
			$result = GetDasPgmCategory($data);
		break;
		case 'GetDasGenreCategory':
			//DAS에서 장르 카테고리 정보 불러오기
			require_once('GetDasGenreCategory.php');
			$result = GetDasGenreCategory($data);
		break;
		case 'SetDasArchiveRequest':
			//DAS로 아카이브 요청
			require_once('SetDasArchiveRequest.php');
			$result = SetDasArchiveRequest($data);
		break;
		default: 
			throw new Exception("잘못된 mode($mode)");
		break;
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
		$include_return = $result;
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
		$include_return = $e->getMessage();
	}
}

?>