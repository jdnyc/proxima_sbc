<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

$bs_content_info = array();


$bs_info = $db->queryAll("
							SELECT 	* 
							FROM 	BC_BS_CONTENT 
							ORDER  BY bs_content_id, show_order");

foreach($bs_info as $bsInfo ){
	$bs_content_info[$bsInfo[bs_content_id]] = $bsInfo[bs_content_code] ;
}

?>