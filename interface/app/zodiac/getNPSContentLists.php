<?php

$server->register('getNPSContentLists',
	array(
		'assetType' => 'xsd:string',
		's_type' => 'xsd:string',
		'contentType' => 'xsd:string',
		'sdate' => 'xsd:string',
		'edate' => 'xsd:string',
		'keyword' => 'xsd:string',
		'curPage' => 'xsd:string',
		'rowcount' => 'xsd:string',
		'usr_id' => 'xsd:string'
	),
	array(
		'success' => 'xsd:string',
		'msg' => 'xsd:string',
		'code' => 'xsd:string',
		'xml' => 'xsd:string'
	),
	$namespace,
	$namespace.'#getNPSContentLists',
	'rpc',
	'encoded',
	'getNPSContentLists'
);

function getNPSContentLists($assetType, $s_type, $contentType,$sdate, $edate, $keyword, $curPage, $rowcount, $usr_id) {
	global $db, $server;
@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/getNPSContentLists_'.date('Ymd').'.log', date('Y-m-d H:i:s').' assetType '.$assetType. "\r\n", FILE_APPEND);
	try{
		$response = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'.chr(10).'<response />');
		
		$server_pre = "http://".SERVER_IP;
		//$server_pre = "http://geminisoft.iptime.org";
		
		switch($assetType) {
			case 'video' :
				$assetTypeCode = MEDIA_BIT;
			break;
			case 'audio' :
				$assetTypeCode = AUDIO_BIT;
			break;
			case 'graphic' :
				$assetTypeCode = CG_BIT;
			break;
			case 'cm' :
				$assetTypeCode = 'cm';
			break;
		}
		
		// 검색엔진 적용 여부에 따라 DB검색을 할지 검색엔진 검색을 할지 분기
		$use_search_enging = false;
		if($use_search_enging) {
			// 검색엔진
		} else {
			// DB검색
			$search_data = getNPSContentListsFromDB($assetTypeCode, $s_type, $contentType, $sdate, $edate, $keyword, $curPage, $rowcount, $user_id);
		}
		
		$success = 'true';
		$msg = 'OK';
		
		// 광고일 경우 video와 타입이 동일 하기 때문에 video로 태그 변경
		if($assetType == 'cm') {
			$assetType = 'video';
		}
		$result = $response->addChild("result");
		$result->addAttribute("success", $success);
		$result->addAttribute("msg", $msg);
		
		$data = $response->addChild("data");
		$data->addAttribute("totalcount", $search_data['total']);
		$data->addAttribute("curpage", $curPage);
		$data->addAttribute("rowcount", $rowcount);
		
		$record = $data->addChild("record");
		$items = $record->addChild($assetType.'s');
		$items->addAttribute("totalcount", $search_data['total']);

		foreach($search_data['content_list'] as $content) {
@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/getNPSContentLists_'.date('Ymd').'.log', date('Y-m-d H:i:s').' assetType '.$assetType. "\r\n", FILE_APPEND);
@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/getNPSContentLists_'.date('Ymd').'.log', date('Y-m-d H:i:s').' title1 '.$content['title']. "\r\n", FILE_APPEND);
@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/getNPSContentLists_'.date('Ymd').'.log', date('Y-m-d H:i:s').' title2 '.convertamp($content['title']). "\r\n", FILE_APPEND);
			$item = $items->addChild($assetType);
			$item->addChild("title", convertamp($content['title']));
			$item->addChild("objectid", $content['objectid']);
			$item->addChild("playoutid", $content['playoutid']);
			
			//$filename = pathinfo($content['ori_path'], PATHINFO_BASENAME);
			//pathinfo 사용시 파일명이 한글이면 깨지는 증상 발생하여 변경
			$filename = preg_replace( '/^.+[\\\\\\/]/', '', $content['ori_path'] );
			
			if($assetType == 'graphic') {
				$frame_count = 1;
				switch($content['bs_content_id']) {
					case '506' :
						$graphictypecode = 'video';
						$graphictype = '영상';
					break;
					case '518' :
						$graphictypecode = 'image';
						$graphictype = '이미지';
					break;
					case '57078' :
						$graphictypecode = 'seq';
						$graphictype = '시퀀스';
						$frame_count = $content['group_count'];
					break;
				}
				
				$item->addChild("graphictype", $graphictype);
				$item->addChild("graphictypecode", $graphictypecode);
				$item->addChild("frame_count", $frame_count);
			}

			$item->addChild("filename", $filename);
			$item->addChild("durationtimecode", $content['duration']);
			
			if(!empty($content['thumb_path'])) {
                $midPath = $content['thumb_mid_path'] ?? '/data';
				$thumburl = $server_pre.$midPath.'/'.$content['thumb_path'];
			} else {
				$thumburl = $server_pre.'/img/incoming.jpg';
			}
			
			if(!empty($content['proxy_path'])) {
                $midPath = $content['proxy_mid_path'] ?? '/data';
				$proxyurl =  $server_pre.$midPath.'/'.$content['proxy_path'];
			} else {
				$proxyurl = '';
			}

			$item->addChild("thumbnailurl", $thumburl);
			$item->addChild("previewurl", $proxyurl);
		}

		
        @file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/getNPSContentLists_'.date('Ymd').'.log', date('Y-m-d H:i:s').' return '.getReturnXML($response). "\r\n", FILE_APPEND);
		return array(
				'success' => $success,
				'msg' => $msg,
				'code' => 200,
				'xml' => getReturnXML($response)
		);
	} catch(Exception $e) {
		$msg = $e->getMessage();
		$code = $e->getCode();
		$success = 'false';
		
		return array(
				'success' => $success,
				'msg' => $msg,
				'code' => $code
		);
	}
}