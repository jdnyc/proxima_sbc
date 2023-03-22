<?php 
$input = ((int)$_REQUEST['timeset']) * 1000;
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");
require_once('ui_functions.php');
$db->exec("UPDATE bc_html_config SET value=$input WHERE type='monitor_interval'");
echo $db->queryOne("SELECT value FROM bc_html_config WHERE type='monitor_interval'");
?>