<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/config.php');
require_once('common/common.php');
require_once('common/functions.php');

try
{
	$r = $db->exec("update content set is_deleted=".DIVA_RESTORED." where is_deleted!=".DIVA_ARCHIVED);

	$r = $db->exec("update content set is_deleted=".DIVA_ARCHIVED." where status=".DIVA_ARCHIVED);

	die(json_encode(array(
		'success' => true
	)));
}
catch(Exception $e)
{
	die(json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	)));
}?>