<?php

/**
 * 채널A에서 사용하던 코드
 */
// class BISUtill
// {

// 	const SOAP_URL = 'http://192.168.10.206:8080/webservice/CommonUtil?wsdl';

// 	function __construct()
// 	{

// 	}

// 	function _log($log){
// 		@file_put_contents(LOG_PATH.'/BISUTILL_'.date('Ymd').'.log', '['.date('Y-m-d H:i:s').'] '.print_r($log,true)."\n", FILE_APPEND);
// 	}

// 	function SendSMS($param){
// 		$client = new SoapClient( self::SOAP_URL );
// 		/*
// 		 'rcv_phn_id' => '01052499318', // 받는 사람 휴대폰 번호 (숫자만 가능)
// 		  'snd_msg' => '동아', //메시지 내용
// 		  'callback' => '010-3185-2012', // 보내는사람 휴대폰번호 (숫자만 가능)
// 		  'cmp_usr_id' = > 'CHA03', // 서비스 아이디 NULL일경우 CHA03 DEFAULT
// 		*/
// 		$return = $client->SendSMS(array(
// 			'rcv_phn_id'=> $param[rcv_phn_id],
// 			'snd_msg'=> mb_substr($param[snd_msg], 0, 79, 'UTF-8'),
// 			'callback'=> $param[callback],
// 			'cmp_usr_id'=> $param[cmp_usr_id]
// 		));
// 		if( !empty($return->return) ){

// 			$reencode = json_encode($return->return);
// 			$result =  json_decode($reencode , true);
// 		}
// 		return $result;
// 	}

// 	function SendEmail($param){
// 		$client = new SoapClient( self::SOAP_URL  );
// 		/*
// 		 'receive_email' => 'aaa@naver.com', // 받는사람 이메일
// 		  'send_email' => 'bbb@naver.com', //보내는 사람 이메일
// 		  'email_title' => '테스트입니다.', // 이메일 제목
// 		  'email_conent' = > '테스트입니다.', // 이메일 내용
// 		*/
// 		$return = $client->SendEmail(array(
// 			'receive_email'=> $param[receive_email],
// 			'send_email'=> $param[send_email],
// 			'email_title'=> $param[email_title],
// 			'email_conent'=> $param[email_conent]
// 		) );
// 		if( !empty($return->return) ){

// 			$reencode = json_encode($return->return);
// 			$result =  json_decode($reencode , true);
// 		}
// 		return $result;
// 	}

// }