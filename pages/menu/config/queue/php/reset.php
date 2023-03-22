<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$db->exec("update queue set status='queue'");
?>