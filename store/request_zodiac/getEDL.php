<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/FcpXML.class.php');

try
{
	$ord_id = $_REQUEST['ord_id'];
	$fcp = new FcpXML();
	$xml = $fcp->TB_ORD_EDL_parser( $ord_id );
	$fcp->_PrintFile( ATTACH_ROOT.'/EDL/'.$ord_id.'.xml' ,$xml );
	$size = strlen($xml);
	header('Pragma: public');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Cache-Control: private', false);	//특정 브라우저에서 필요함
	header('Content-Type: application/force-download');
	header('Content-Disposition: attachment; filename='.$ord_id.'.xml');
	header('Content-Description: File Transfer');
	header('Content-Transfer-Encoding: binary');
	header('Content-Length: ' . $size );
	ob_clean();	//출력 버퍼를 지웁니다.
	flush();	//출력 버퍼를 비웁니다.
	echo $xml;

}catch(Exception $e)
{
	echo $e->getMessage();
}
?>