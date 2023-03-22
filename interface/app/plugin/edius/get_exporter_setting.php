<?php 
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

try
{
	$receive_xml = file_get_contents('php://input');
	if(empty($receive_xml)) throw new Exception('요청 값이 없습니다.');

	$xml = new SimpleXMLElement($receive_xml);
	
	$response = new SimpleXMLElement("<Response />");

	$request = $xml->ExportSetting;
	$row = $db->queryRow("select * from exporter_setting where id = ".$request['id']);
	$result = $response->addChild('Result');
	$result->addAttribute('success', 'true');
	$result->addAttribute('msg', 'ok');

	$exportSetting = $result->addChild('ExportSetting');
	$exportSetting->addAttribute('vbitrate', $row['vbitrate']);
	$exportSetting->addAttribute('render_engine_sd', $row['render_engine_sd']);
	$exportSetting->addAttribute('render_engine_hd', $row['render_engine_hd']);
	$exportSetting->addAttribute('output_path', $row['output_path']);

	die($response->asXML());	
}
catch (Exception $e)
{
	$error = $response->addChild('Result');
	$error->addAttribute('success', 'false');
	$error->addAttribute('msg', $e->getMessage());

	die($response->asXML());
}

?>