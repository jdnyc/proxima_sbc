<?php

$server->register('getNPSPublishContentLists',
	array(
		'assetType' => 'xsd:string',
		'category_id' => 'xsd:string',
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
	$namespace.'#getNPSPublishContentLists',
	'rpc',
	'encoded',
	'getNPSPublishContentLists'
);

function getNPSPublishContentLists($assetType, $category_id, $sdate, $edate, $keyword, $curPage, $rowcount, $usr_id) {
	global $db, $server;

	try{
		$response = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'.chr(10).'<response />');
		$server_pre = "http://".SERVER_IP;
				
		// 검색엔진 적용 여부에 따라 DB검색을 할지 검색엔진 검색을 할지 분기
		$use_search_enging = false;
		if($use_search_enging) {
			// 검색엔진
		} else {
			// DB검색
			$search_data = getNPSPublishContentListsFromDB($assetType, $category_id, $sdate, $edate, $keyword, $curPage, $rowcount, $user_id);
		}
		
		$success = 'true';
		$msg = 'OK';
		
		// 광고일 경우 video와 타입이 동일 하기 때문에 video로 태그 변경
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
			$item = $items->addChild($assetType);
			$item->addChild("title", convertamp($content['title']));
			$item->addChild("objectid", $content['objectid']);
			$item->addChild("playoutid", $content['playoutid']);
			$item->addChild("reg_user_nm", $content['reg_user_nm']);
			$item->addChild("created_date", $content['created_date']);
			$item->addChild("pgm_nm", $content['pgm_nm']);
			
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
				$thumburl = $server_pre.'/data/'.$content['thumb_path'];
			} else {
				$thumburl = $server_pre.'/img/incoming.jpg';
			}
			
			if(!empty($content['proxy_path'])) {
				$proxyurl =  $server_pre.'/data/'.$content['proxy_path'];
			} else {
				$proxyurl = '';
			}

			$item->addChild("thumbnailurl", $thumburl);
			$item->addChild("previewurl", $proxyurl);
		}

		
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