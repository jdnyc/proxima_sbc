<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/functions.php';

$receive_xml = file_get_contents('php://input');
//file_put_contents('log/harris_refresh_agency'.date('Ymd').'.html', date("Y-m-d H:i:s\t").$receive_xml."\n\n", FILE_APPEND);
$response = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?> \n<Response />");

libxml_use_internal_errors(true);
$xml = simplexml_load_string($receive_xml);
if(!$xml)
{
	foreach(libxml_get_errors() as $error)
	{
		$err_msg .= $error->message . "\t";
	}
	$result = $response->addChild('Result');
	$result->addAttribute('success', 'false');
	$result->addChild('msg', 'xml 파싱에러: ' .$err_msg);

	echo $response->asXML();
	exit;
}

$action = $xml->Action;
$server = $xml->Server;
$server_num = get_harris_server_num($server);
try{
	switch($action)
	{
		case 'Refresh_agency': //한번에 하나씩 변경하게 해놈.
			$query = "select refresh_set_time from harris_setting where server_name = '".$server."'";
			$set_time = $db->queryOne($query);
			$now = date('His');
			if(!empty($set_time))
			{
				if ( ($now <= ($set_time+5)) && ($now >= ($set_time-5)) )// 시분초 단위까지 디비에 입력할것.
				{
					$del_query = $db->queryOne("update harris set refresh_status = 'deleted', refresh_order = 'queue' where server_uid = $server_num");

					$regist = $response->addChild('Result');
					$regist->addAttribute('success', 'true');
					$agency = $regist->addChild('Agency', 'refresh_all_agency');
					$server_name = $regist->addChild('Server', $server);

					die($response->asXML());
				}
			}
			else
			{			
				//$query = "select agency, server_uid from harris where refresh_order = 'queue' and server_uid = $server_num group by agency limit 0,1";
				$query = "select id, target_agency, server_name from harris_refresh_task where status = 'queue' and server_name = '".$server."' order by regist_time asc";
				//file_put_contents('log/error_harris_refresh'.date('Ymd').'.html', date("Y-m-d H:i:s\t").$db->last_query."\n", FILE_APPEND);
				$result = $db->queryRow($query);

				if( $result['target_agency'] == 'Agency미지정' )
				{	
					$regist = $response->addChild('Result');
					$is_null_check = $regist->addChild('Is_null', 'true');
					$regist->addAttribute('success', 'true');
					$agency = $regist->addChild('Agency');
					$server_name = $regist->addChild('Server', $server);

					$do_update = doupdate_refresh($result['id']);
				}
				else if( $result['target_agency'] && $result['target_agency'] != 'Agency미지정' )
				{
					$regist = $response->addChild('Result');
					$regist->addAttribute('success', 'true');
					$is_null_check = $regist->addChild('Is_null', 'false');
					$agency = $regist->addChild('Agency', $result['target_agency']);
					$server_name = $regist->addChild('Server', $result['server_name']);

					$do_update = doupdate_refresh($result['id']);
				}
				else
				{
					$regist = $response->addChild('Result');
					$regist->addAttribute('success', "false");
					die($response->asXML());
				}
			}

			die($response->asXML());
		break;
	}
}
catch(Exception $e)
{
	$msg = $e->getMessage();
	switch($e->getCode())
	{
		case ERROR_QUERY:
			$msg += '( '.$db->last_query.' )';
		break;
	}
	
	$error = $response->addChild('Result');		
		$error->addAttribute('success', 'false');
		$error->addAttribute('msg', $msg);
	file_put_contents('log/error_harris_refresh'.date('Ymd').'.html', date("Y-m-d H:i:s\t").$db->last_query."\n", FILE_APPEND);
	die($response->asXML());
}
/*
refresh 작업 요청
<Request><Action>Refresh_agency</Action><Server>채널 운영실</Server></Request>
<Request><Action>Refresh_agency</Action><Server>부조</Server></Request>

Refresh 작업 답변
<Response>
	<Result success="true">
	<Is_null>true</Is_null>
	   <Agency>안동간고등어</Agency>
	   <Server>채널 운영실</Server>
	</Result>
</Response>

실패시 
<Response>
	<Result success="false" msg="">
	</Result>
</Response>

작업없을시

<Response>
	<Result success=false"></Result>
</Response>


테이블 추가
CREATE TABLE `db_ariel`.`harris_refresh_task` (
`id` INT UNSIGNED not NULL auto_increment COMMENT '카운팅',
`target_agency` VARCHAR( 250 ) NOT NULL COMMENT '갱신할agency',
`status` VARCHAR( 10 ) NOT NULL COMMENT '상태',
`regist_time` VARCHAR( 20 ) NOT NULL COMMENT '등록시간',
PRIMARY KEY ( `id` )
) ENGINE = MYISAM COMMENT = 'Agency정보갱신시 등록테이블';
ALTER TABLE `harris_refresh_task` ADD `server_name` VARCHAR( 100 ) NOT NULL COMMENT '서버이름'
*/
function doupdate_refresh($id){
	global $db;
	$do_update = $db->exec("update harris_refresh_task set status = 'complete' where id = $id ");
	return $do_update;
}

?>