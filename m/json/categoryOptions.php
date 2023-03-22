<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

$optValueArray = array();
$tabs = $db->queryAll("select * from bc_ud_content uc order by uc.show_order");
foreach ($tabs as $tab) {

	// 권한이 없으면 건너 뛰기
	if ( ! checkAllowUdContentGrant($_SESSION['user']['user_id'], $tab['ud_content_id'], GRANT_READ)) {
		continue;
	}

	$optValueArray[$tab['ud_content_id']] = $tab['ud_content_title'];
	
}

?>