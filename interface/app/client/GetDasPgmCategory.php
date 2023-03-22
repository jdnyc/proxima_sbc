<?php

function GetDasPgmCategory($data)
{
	$json = array(
		'request_id' => $data
	);
	$json = json_encode($json);
	$param = '<request>'.$json.'</request>';
	$function = 'GetPgmCategory';

	//NPS로 param전달
	$result = InterfaceClass::client(CHA_SOAP_DAS, $function, $param);
	$result = json_decode($result, true);

	return $result;
}
?>