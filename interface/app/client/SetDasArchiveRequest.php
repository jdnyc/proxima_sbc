<?php
function SetDasArchiveRequest($data)
{	
	$data = json_encode($data);
	$param = '<request>'.$data.'</request>';
	$function = 'GetGenreCategory';

	//NPS로 param전달
	$result = InterfaceClass::client(CHA_SOAP_DAS, $function, $param);
	$result = json_decode($result, true);

	return $result;
}
?>