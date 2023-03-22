<?php
if(isset($_POST)){
	$arr_info = $_POST;
	_fn_change_xml_info_in_file($arr_info);
	echo json_encode(array('success'=> true));
}

function _fn_change_xml_info_in_file($arr_info){
	
	$doc = simplexml_load_file($_SERVER['DOCUMENT_ROOT'].'/lib/config.SYSTEM.xml');
	$doc->items->SERVER_IP = $arr_info['server_ip'];
	$doc->items->STREAM_SERVER_IP = $arr_info['stream_server_ip'];
//	$doc->items->SERVER_PORT = $arr_info['server_port'];
//	$doc->items->STREAM_SERVER_PORT = $arr_info['stream_server_port'];
//	if($arr_info['server_ip'] != '') {
//		$doc->items->SERVER_IP = $arr_info['server_ip'];
//	}
//	if($arr_info['stream_server_ip'] != '') {
//		$doc->items->STREAM_SERVER_IP = $arr_info['stream_server_ip'];
//	}
	
	file_put_contents($_SERVER['DOCUMENT_ROOT'].'/lib/config.SYSTEM.xml', $doc->asXML());
	
}
