<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/config.php');


$db->exec("update task set status='queue', destination='' where type in ('archive', 'restore', 'pfr_restore')");

?>