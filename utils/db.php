<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$all = $dbconn->query("select * from harris");

print_r($all->getColumnNames());
?>